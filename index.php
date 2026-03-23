<?php
session_start();
include 'db.php';

// --- 1. AUTHENTICATION & PERMISSIONS ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userBranch = $_SESSION['branch_id'];
$userRoles  = $_SESSION['roles'] ?? [];

// Standardized Roles
$isMainAdmin     = ((in_array('Admin', $userRoles) || in_array('Main Admin', $userRoles)) && $userBranch == 1); 
$isDocController = in_array('Document Controller', $userRoles);
$isAccountant    = in_array('Accountant', $userRoles);
$hasGlobalAccess = ($isMainAdmin || $isDocController || $isAccountant);

// --- 2. PAGINATION & FILTER SETUP ---
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Set current view to jobs (Since clients are moving to clients.php)
$view = 'jobs'; 
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Global Access Branch Filter
if ($hasGlobalAccess) {
    $currentBranch = (isset($_GET['branch']) && $_GET['branch'] !== '') ? (int)$_GET['branch'] : $userBranch;
} else {
    $currentBranch = $userBranch;
}

// --- 3. DATA FETCHING (JOBS ONLY) ---

// A. Stats Query (For the top dashboard cards)
$statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as progress_count,
                SUM(grand_total) as total_revenue
               FROM jobs WHERE 1=1";

$sParams = [];
if ($currentBranch !== '') { 
    $statsQuery .= " AND branch_id = ?"; 
    $sParams[] = $currentBranch; 
}

$stmtS = $pdo->prepare($statsQuery);
$stmtS->execute($sParams);
$stats = $stmtS->fetch(PDO::FETCH_ASSOC);

// B. Main List Query
$query = "SELECT j.*, c.name as client_name, b.name as branch_name  
          FROM jobs j 
          JOIN clients c ON j.client_id = c.id 
          LEFT JOIN branches b ON j.branch_id = b.id
          WHERE 1=1";
$params = [];

if ($currentBranch !== '') { $query .= " AND j.branch_id = ?"; $params[] = $currentBranch; }
if ($statusFilter !== '') { $query .= " AND j.status = ?"; $params[] = $statusFilter; }
if (!empty($search)) {
    $query .= " AND (j.job_no LIKE ? OR c.name LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%"]);
}

// C. Prepare Count for Pagination
$countQuery = "SELECT COUNT(*) FROM jobs j JOIN clients c ON j.client_id = c.id WHERE 1=1";
$cParams = [];
if ($currentBranch !== '') { $countQuery .= " AND j.branch_id = ?"; $cParams[] = $currentBranch; }
if ($statusFilter !== '') { $countQuery .= " AND j.status = ?"; $cParams[] = $statusFilter; }

$query .= " ORDER BY j.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$dataList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtC = $pdo->prepare($countQuery);
$stmtC->execute($cParams);
$totalRows = $stmtC->fetchColumn();
$totalPages = ceil($totalRows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALHAYIKI | Operations Dashboard</title>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --brand: #198754; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; font-size: 0.85rem; }
        .flex-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex-grow: 1; padding: 25px; overflow-y: auto; max-height: 100vh; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border-radius: 12px; }
        .stat-card { border-left: 4px solid var(--brand); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { font-size: 1.5rem; opacity: 0.3; }
        .table thead th { background: #f8f9fa; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; padding: 12px; }
        .status-badge { width: 85px; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; }
    </style>
</head>
<body class="bg-light">

<div class="flex-wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0 text-dark">Operations Dashboard</h3>
                    <p class="text-muted small mb-0">Branch: <strong><?= $currentBranch == 1 ? 'Main (HM)' : ($currentBranch == 2 ? 'Hilal (HH)' : 'PRO Office') ?></strong></p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php if ($hasGlobalAccess): ?>
                        <select class="form-select form-select-sm shadow-sm" id="branchSelect" style="width: 180px;" onchange="location.href='index.php?branch='+this.value">
                            <option value="1" <?= $currentBranch == 1 ? 'selected' : '' ?>>Main Branch (HM)</option>
                            <option value="2" <?= $currentBranch == 2 ? 'selected' : '' ?>>Hilal (HH)</option>
                            <option value="3" <?= $currentBranch == 3 ? 'selected' : '' ?>>PRO Office</option>
                        </select>
                    <?php endif; ?>
                    
                    <div class="btn-group shadow-sm">
                        <a href="export.php?view=jobs&branch=<?= $currentBranch ?>" class="btn btn-white border bg-white"><i class="fas fa-download"></i> Export</a>
                        <a href="add_job.php" class="btn btn-success">
                            <i class="fas fa-plus-circle me-1"></i> Create New Job
                        </a>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card p-3 border-primary">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">TOTAL JOBS</div>
                                <h4 class="mb-0 fw-bold"><?= number_format($stats['total']) ?></h4>
                            </div>
                            <i class="fas fa-briefcase stat-icon text-primary"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3 border-warning">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">DRAFTS</div>
                                <h4 class="mb-0 fw-bold"><?= number_format($stats['draft_count'] ?? 0) ?></h4>
                            </div>
                            <i class="fas fa-file-invoice stat-icon text-warning"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3 border-info">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">ACTIVE</div>
                                <h4 class="mb-0 fw-bold"><?= number_format($stats['progress_count'] ?? 0) ?></h4>
                            </div>
                            <i class="fas fa-spinner fa-spin stat-icon text-info"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3 border-success">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small fw-bold">REVENUE</div>
                                <h4 class="mb-0 fw-bold text-success">QR <?= number_format($stats['total_revenue'] ?? 0, 2) ?></h4>
                            </div>
                            <i class="fas fa-coins stat-icon text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-center" id="searchForm">
                        <input type="hidden" name="branch" value="<?= $currentBranch ?>">
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" id="liveSearch" class="form-control border-start-0" placeholder="Search Job # or Client..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select form-select-sm" id="statusFilter">
                                <option value="">All Statuses</option>
                                <?php foreach(['Draft','In Progress','Ready','Completed'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $statusFilter == $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <a href="index.php?branch=<?= $currentBranch ?>" class="btn btn-light btn-sm border">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Reference</th>
                                <th>Client & Branch</th>
                                <th>Financials</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="live-table-body">
                            <?php foreach($dataList as $row): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= $row['job_no'] ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><i class="far fa-calendar-alt me-1"></i><?= date('d-m-Y', strtotime($row['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($row['client_name']) ?></div>
                                    <span class="badge bg-light text-dark fw-normal border" style="font-size: 0.65rem;"><?= $row['branch_name'] ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-success">QR <?= number_format($row['grand_total'], 2) ?></div>
                                    <div class="text-muted small" style="font-size: 0.7rem;"><?= $row['payment_status'] ?? 'Unpaid' ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $badgeClass = match($row['status']) {
                                            'Draft' => 'bg-warning text-dark',
                                            'In Progress' => 'bg-primary',
                                            'Ready' => 'bg-success',
                                            'Completed' => 'bg-dark',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?> status-badge"><?= $row['status'] ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm">
                                        <a href="view_job.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border bg-white" title="View"><i class="fas fa-eye text-primary"></i></a>
                                        <a href="print_job.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-white border bg-white" title="Print"><i class="fas fa-print"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white py-3 border-0">
                    <div class="row align-items-center">
                        <div class="col small text-muted">
                            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?>
                        </div>
                        <div class="col-auto">
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&branch=<?= $currentBranch ?>&status=<?= $statusFilter ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'modals/job_edit_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
/**
 * LIVE SEARCH LOGIC
 * Updates the job table as you type without reloading the page.
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('liveSearch');
    const statusFilter = document.getElementById('statusFilter');
    const tableBody = document.getElementById('live-table-body');
    
    // We lock these to the PHP variables set at the top of index.php
    const view = "jobs"; 
    const branch = "<?= $currentBranch ?>";

    function doLiveSearch() {
        // Visual feedback that the table is updating
        tableBody.style.opacity = '0.5';
        
        const query = searchInput.value;
        const status = statusFilter ? statusFilter.value : '';
        
        // Fetching from fetch_results.php
        fetch(`fetch_results.php?view=${view}&search=${encodeURIComponent(query)}&status=${status}&branch=${branch}`)
            .then(res => res.text())
            .then(data => {
                tableBody.innerHTML = data;
                tableBody.style.opacity = '1';
            })
            .catch(err => {
                console.error("Search Error:", err);
                tableBody.style.opacity = '1';
            });
    }

    if(searchInput) searchInput.addEventListener('input', doLiveSearch);
    if(statusFilter) statusFilter.addEventListener('change', doLiveSearch);
});

/**
 * JOB SAVE LOGIC
 * Handles the Modal submission using SweetAlert2
 */
window.saveJob = function() {
    const form = document.getElementById('jobForm');
    const formData = new FormData(form);
    
    // Simple validation
    if(!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    fetch('save_job.php', { 
        method: 'POST', 
        body: formData 
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire({
                title: 'Updated!',
                text: data.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => { 
                window.location.reload(); 
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Connection Error', 'Could not reach the server.', 'error');
    });
}
</script>
</body>
</html>