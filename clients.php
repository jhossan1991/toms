<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 1. Setup Permissions & Branching
$userBranch = $_SESSION['branch_id'];
$userRoles  = $_SESSION['roles'] ?? [];
$isMainAdmin = (in_array('Admin', $userRoles) && $userBranch == 1);

// 2. Pagination & Filter Logic
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search    = $_GET['search'] ?? '';
$f_status  = $_GET['status'] ?? '';
$f_branch  = $_GET['branch'] ?? ($isMainAdmin ? '' : $userBranch);
$f_type    = $_GET['type'] ?? '';
$view_mode = $_GET['view'] ?? 'active'; 

// 3. Build Query
$params = [];
$whereClauses = [];

// Ensure Archive mode is strictly enforced
$whereClauses[] = ($view_mode === 'archived') ? "c.is_archived = 1" : "c.is_archived = 0";

if ($search) {
    $whereClauses[] = "(c.name LIKE ? OR c.mobile_primary LIKE ? OR c.email LIKE ? OR c.id LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($f_status) {
    $whereClauses[] = "c.status = ?";
    $params[] = $f_status;
}

if ($f_type) {
    $whereClauses[] = "c.client_type = ?";
    $params[] = $f_type;
}

if ($f_branch) {
    $whereClauses[] = "c.branch_id = ?";
    $params[] = $f_branch;
}

$whereSql = " WHERE " . implode(" AND ", $whereClauses);

// 4. Fetch Summary Totals
$summary = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_archived = 0 THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN is_archived = 1 THEN 1 ELSE 0 END) as archived_count,
    (SELECT SUM(amount_due) FROM jobs WHERE payment_status != 'Paid') as total_due
    FROM clients")->fetch();

// 5. Pagination Calculation
$totalRowsStmt = $pdo->prepare("SELECT COUNT(*) FROM clients c $whereSql");
$totalRowsStmt->execute($params);
$totalRows = $totalRowsStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// 6. Fetch Client Data - Updated with Core Columns Logic
$query = "SELECT c.*, 
    b.name as branch_name,
    (SELECT SUM(amount_due) FROM jobs WHERE client_id = c.id AND payment_status != 'Paid') as balance,
    (SELECT MAX(created_at) FROM jobs WHERE client_id = c.id) as last_job_date,
    (SELECT COUNT(*) FROM jobs WHERE client_id = c.id) as activity_count
    FROM clients c 
    LEFT JOIN branches b ON c.branch_id = b.id
    $whereSql 
    ORDER BY c.id ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll();

// Fetch branches for admin filter
$branches = [];
if ($isMainAdmin) {
    $branches = $pdo->query("SELECT id, name FROM branches")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ALHAYIKI | Client Directory</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <style>
        :root { --brand: #198754; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; font-size: 0.85rem; }
        .flex-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex-grow: 1; padding: 25px; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05); border-radius: 12px; }
        .table thead th { background: #f8f9fa; text-transform: uppercase; font-size: 0.7rem; padding: 12px; border-bottom: 2px solid #eee; }
        .badge-active { background: #d1e7dd; color: #0f5132; }
        .badge-hold { background: #fff3cd; color: #664d03; }
        .badge-inactive { background: #f8d7da; color: #842029; }
        .client-link { text-decoration: none; color: #212529; transition: color 0.2s; font-weight: 600; }
        .client-link:hover { color: var(--brand); text-decoration: underline; }
        
    </style>
</head>
<body>

<div class="flex-wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0 text-dark">Client Relationship Manager</h3>
                    <p class="text-muted small mb-0">Currently viewing: <strong><?= ucfirst($view_mode) ?></strong> list</p>
                </div>
                <a href="add_client.php" class="btn btn-success shadow-sm">
                    <i class="fas fa-plus-circle me-1"></i> New Client
                </a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card p-3 border-start border-4 border-primary">
                        <small class="text-muted fw-bold">TOTAL REGISTERED</small>
                        <h4 class="fw-bold mb-0"><?= $summary['total'] ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 border-start border-4 border-success">
                        <small class="text-muted fw-bold">ACTIVE LIST</small>
                        <h4 class="fw-bold mb-0 text-success"><?= $summary['active_count'] ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 border-start border-4 border-secondary">
                        <small class="text-muted fw-bold">ARCHIVED</small>
                        <h4 class="fw-bold mb-0 text-secondary"><?= $summary['archived_count'] ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 border-start border-4 border-danger">
                        <small class="text-muted fw-bold">TOTAL OUTSTANDING</small>
                        <h4 class="fw-bold mb-0 text-danger"><?= number_format($summary['total_due'] ?? 0, 2) ?> QAR</h4>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-center">
                        <input type="hidden" name="view" value="<?= $view_mode ?>">
                        <div class="col-md-3">
                            <input type="text" name="search" id="liveSearch" class="form-control form-control-sm" placeholder="Search ID or Name..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="Active" <?= $f_status=='Active'?'selected':'' ?>>Active</option>
                                <option value="On Hold" <?= $f_status=='On Hold'?'selected':'' ?>>On Hold</option>
                                <option value="Inactive" <?= $f_status=='Inactive'?'selected':'' ?>>Inactive</option>
                            </select>
                        </div>
                        <?php if($isMainAdmin): ?>
                        <div class="col-md-2">
                            <select name="branch" class="form-select form-select-sm">
                                <option value="">All Branches</option>
                                <?php foreach($branches as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= $f_branch == $b['id'] ? 'selected' : '' ?>><?= $b['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col text-end">
                            <button type="submit" class="btn btn-success btn-sm px-4">Filter</button>
                            <a href="clients.php?view=<?= $view_mode ?>" class="btn btn-light btn-sm border">Reset</a>
                            <a href="?view=<?= $view_mode=='active'?'archived':'active' ?>" class="btn <?= $view_mode=='active'?'btn-outline-secondary':'btn-primary' ?> btn-sm">
                                <i class="fas <?= $view_mode=='active'?'fa-archive':'fa-users' ?> me-1"></i>
                                <?= $view_mode=='active'?'Switch to Archive':'Switch to Active' ?>
                            </a>
                            <button onclick="exportToExcel()" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-file-excel me-1"></i> Excel
                        </button>
                        <button onclick="exportToPDF()" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-file-pdf me-1"></i> PDF
                        </button>
                        </div>
                        
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="clientTable">
                        <thead>
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Display Name & Type</th>
                                <th>Mobile & Email</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Balance (QAR)</th>
                                <th>Activity</th>
                                <th>Last Job</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="live-table-body">
                            <?php if(empty($clients)): ?>
                                <tr><td colspan="9" class="text-center py-4 text-muted">No clients found in <?= $view_mode ?> list.</td></tr>
                            <?php endif; ?>
                            
                            <?php foreach($clients as $row): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <a href="client_view.php?id=<?= $row['id'] ?>" class="client-link">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </a>
                                    <div class="small text-muted"><?= $row['client_type'] ?></div>
                                </td>
                                <td>
                                    <ul class="list-unstyled mb-0 small">
                                        <li><i class="fas fa-phone-alt fa-fw me-2 text-muted"></i><?= $row['mobile_primary'] ?></li>
                                        <li><i class="fas fa-envelope fa-fw me-2 text-muted"></i><?= $row['email'] ?: '<span class="text-muted">No Email</span>' ?></li>
                                    </ul>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['branch_name'] ?? 'Unassigned') ?></span>
                                </td>
                                <td>
                                    <?php 
                                        $s = $row['status'];
                                        $cls = ($s == 'Active') ? 'badge-active' : (($s == 'On Hold') ? 'badge-hold' : 'badge-inactive');
                                    ?>
                                    <span class="badge <?= $cls ?>"><?= $s ?></span>
                                </td>
                                <td>
                                    <?php $bal = $row['balance'] ?? 0; ?>
                                    <span class="fw-bold <?= ($bal > 0) ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format($bal, 2) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="fw-bold text-dark"><?= $row['activity_count'] ?></span> <span class="text-muted">Jobs</span>
                                    </div>
                                </td>
                                <td class="small">
                                    <?= $row['last_job_date'] ? date('d M Y', strtotime($row['last_job_date'])) : '<span class="text-muted">Never</span>' ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Actions</button>
                                        <ul class="dropdown-menu shadow">
                                            <li><a class="dropdown-item" href="client_view.php?id=<?= $row['id'] ?>"><i class="fas fa-eye me-2 text-info"></i> View Profile</a></li>
                                            <li><a class="dropdown-item" href="edit_client.php?id=<?= $row['id'] ?>"><i class="fas fa-edit me-2 text-primary"></i> Edit</a></li>
                                            
                                            <?php if($row['is_archived'] == 0): ?>
                                                <li><a class="dropdown-item text-warning" href="#" onclick="toggleArchive(<?= $row['id'] ?>, 'archive')">
                                                    <i class="fas fa-archive me-2"></i> Move to Archive
                                                </a></li>
                                            <?php else: ?>
                                                <li><a class="dropdown-item text-success" href="#" onclick="toggleArchive(<?= $row['id'] ?>, 'unarchive')">
                                                    <i class="fas fa-box-open me-2"></i> Restore to Active
                                                </a></li>
                                            <?php endif; ?>

                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>')"><i class="fas fa-trash-alt me-2"></i> Permanent Delete</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer bg-white border-0 py-3">
                    <div class="row align-items-center">
                        <div class="col small text-muted">Showing page <?= $page ?> of <?= $totalPages ?: 1 ?></div>
                        <div class="col-auto">
                            <nav><ul class="pagination pagination-sm mb-0">
                                <?php for($i=1; $i<=$totalPages; $i++): ?>
                                    <li class="page-item <?= $i==$page?'active':'' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>&status=<?= $f_status ?>&branch=<?= $f_branch ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul></nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Live Search
let debounceTimer;
const tableBody = document.getElementById('live-table-body');

document.getElementById('liveSearch').addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const query = this.value;
    
    debounceTimer = setTimeout(() => {
        tableBody.style.opacity = '0.5';
        // Fetch results from the server
        fetch(`fetch_results.php?view=clients&search=${encodeURIComponent(query)}&mode=<?= $view_mode ?>`)
            .then(res => res.text())
            .then(data => {
                tableBody.innerHTML = data;
                tableBody.style.opacity = '1';
                
                // If DataTables was initialized for an export, destroy it so it can re-scan the new rows
                if ($.fn.DataTable.isDataTable('#clientTable')) {
                    $('#clientTable').DataTable().destroy();
                }
            })
            .catch(err => console.error('Search error:', err));
    }, 400);
});

function toggleArchive(clientId, action) {
    const title = action === 'archive' ? 'Archive Client?' : 'Restore Client?';
    Swal.fire({
        title: title,
        text: "This changes the visibility of the client in the directory.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Yes, proceed'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData();
            formData.append('id', clientId);
            formData.append('action', action);
            fetch('archive_client.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Updated!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

function confirmDelete(clientId, clientName) {
    Swal.fire({
        title: `Delete ${clientName}?`,
        text: "Type DELETE to confirm. This only works if no jobs exist.",
        input: 'text',
        inputPlaceholder: 'DELETE',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33'
    }).then((result) => {
        if (result.value === 'DELETE') {
            let formData = new FormData();
            formData.append('id', clientId);
            fetch('delete_client.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Deleted!', '', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Blocked', data.message, 'error');
                }
            });
        }
    });
}

// Function to export the current table to Excel
function exportToExcel() {
    // 1. Initialize a temporary DataTable on the existing HTML table
    let table = $('#clientTable').DataTable({
        destroy: true, // Critical: resets the instance
        paging: false,
        searching: false,
        info: false
    });
    
    // 2. Trigger the Excel Action
    new $.fn.dataTable.Buttons(table, {
        buttons: [{
            extend: 'excelHtml5',
            title: 'AlHayiki_Clients_' + new Date().toISOString().slice(0,10),
            exportOptions: { 
                columns: [0, 1, 2, 3, 4, 5, 6, 7] // Only export data columns
            }
        }]
    }).container().find('button').click();
    
    // 3. Clean up: Destroy the table instance so it doesn't interfere with your UI
    table.destroy();
}

function exportToPDF() {
    let table = $('#clientTable').DataTable({
        destroy: true,
        paging: false,
        searching: false,
        info: false
    });

    new $.fn.dataTable.Buttons(table, {
        buttons: [{
            extend: 'pdfHtml5',
            title: 'AlHayiki Client Directory',
            orientation: 'landscape',
            pageSize: 'A4',
            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] },
            customize: function (doc) {
                doc.defaultStyle.fontSize = 8;
                doc.styles.tableHeader.fillColor = '#198754';
                doc.styles.tableHeader.color = 'white';
                doc.content[1].table.widths = ['10%', '20%', '20%', '10%', '10%', '10%', '10%', '10%'];
            }
        }]
    }).container().find('button').click();

    table.destroy();
}
</script>
</body>
</html>