<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userBranch = $_SESSION['branch_id'];
$userRoles  = $_SESSION['roles'] ?? [];
$isMainAdmin = (in_array('Admin', $userRoles) && $userBranch == 1);

// Handle Branch Filtering for Admin
$filterBranch = ($isMainAdmin && isset($_GET['branch_filter']) && $_GET['branch_filter'] !== '') ? (int)$_GET['branch_filter'] : null;

// Build Query
$query = "SELECT j.*, 
                 COALESCE(NULLIF(c.company_name, ''), c.name) AS display_client, 
                 b.name AS branch_display_name 
          FROM jobs j 
          LEFT JOIN clients c ON j.client_id = c.id 
          LEFT JOIN branches b ON j.branch_id = b.id";

$params = [];
if (!$isMainAdmin) {
    $query .= " WHERE j.branch_id = ?";
    $params[] = $userBranch;
} elseif ($filterBranch) {
    $query .= " WHERE j.branch_id = ?";
    $params[] = $filterBranch;
}

$query .= " ORDER BY j.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALHAYIKI | Manage Jobs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --brand: #198754; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; font-size: 0.85rem; }
        
        /* Layout Wrapper */
        .flex-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex-grow: 1; padding: 25px; overflow-y: auto; max-height: 100vh; }
        
        /* Table Styling */
        .table-card { border-radius: 12px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); background: #fff; }
        .table thead th { background: #f8f9fa; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; padding: 12px; }
        
        /* Status Badges */
        .status-badge { width: 90px; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; display: inline-block; text-align: center; }
        .bg-draft { background-color: #ffc107; color: #000; } /* Warning/Yellow */
        .bg-inprogress { background-color: #0d6efd; color: #fff; } /* Primary/Blue */
        .bg-ready { background-color: #198754; color: #fff; } /* Success/Green */
        .bg-completed { background-color: #212529; color: #fff; } /* Dark */
    </style>
</head>
<body>

<div class="flex-wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0 text-dark">Job List</h3>
                    <p class="text-muted small mb-0">Manage and track all operational tasks</p>
                </div>
                <a href="add_job.php" class="btn btn-success">
                    <i class="fas fa-plus-circle me-1"></i> Create New Job
                </a>
            </div>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-center">
                        <?php if ($isMainAdmin): ?>
                        <div class="col-md-3">
                            <select name="branch_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Branches</option>
                                <?php
                                $allBranches = $pdo->query("SELECT id, name FROM branches")->fetchAll();
                                foreach ($allBranches as $br) {
                                    $sel = ($filterBranch == $br['id']) ? 'selected' : '';
                                    echo "<option value='{$br['id']}' $sel>{$br['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="jobSearch" class="form-control border-start-0" placeholder="Search Job No, Client, or Service...">
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Job Details</th>
                                <th>Client & Branch</th>
                                <th>Financials</th>
                                <th>Balance Due</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="jobTableBody">
                            <?php foreach ($jobs as $job): 
                                // Clean up status for CSS class (e.g., "In Progress" becomes "inprogress")
                                $statusKey = strtolower(str_replace(' ', '', $job['status'] ?? 'draft'));
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-primary"><?= $job['job_no'] ?></div>
                                    <div class="text-muted small"><i class="far fa-calendar-alt me-1"></i><?= date('d-M-Y', strtotime($job['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($job['display_client']) ?></div>
                                    <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.65rem;"><?= htmlspecialchars($job['branch_display_name'] ?? 'N/A') ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold">QR <?= number_format($job['grand_total'], 2) ?></div>
                                </td>
                                <td>
                                    <div class="<?= ($job['amount_due'] > 0) ? 'text-danger' : 'text-success' ?> fw-bold">
                                        QR <?= number_format($job['amount_due'], 2) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge status-badge bg-<?= $statusKey ?>">
                                        <?= $job['status'] ?: 'Draft' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm">
                                        <a href="assign_task.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-white border bg-white" title="Assign Translator"><i class="fas fa-user-tag text-warning"></i></a>
                                        <a href="print_job_slip.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-white border bg-white" title="Print Slip"><i class="fas fa-print text-dark"></i></a>
                                        <a href="edit_job.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-white border bg-white" title="Edit Job"><i class="fas fa-edit text-primary"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($jobs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No jobs found for this criteria.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function(){
        // Live filter for the table
        $("#jobSearch").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#jobTableBody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>

</body>
</html>