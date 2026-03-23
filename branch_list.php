<?php
include 'db.php';
session_start();

// 1. Access Control Check (Matches roles in your SQL: SuperAdmin, Admin, etc.)
$allowed_roles = ['Super Admin', 'SuperAdmin', 'Admin', 'Branch Manager'];

if (!isset($_SESSION['roles']) || !array_intersect($allowed_roles, $_SESSION['roles'])) {
    die("Access Denied. Your role is: " . ($_SESSION['roles'][0] ?? 'None'));
}

$role = $_SESSION['roles'][0]; 

// 2. Fetch Branches with Real-time Stats from Staff and Clients tables
try {
    $query = "SELECT b.*, 
              (SELECT COUNT(*) FROM staff_profiles WHERE branch_id = b.id) as staff_count,
              (SELECT COUNT(*) FROM clients WHERE branch_id = b.id) as client_count
              FROM branches b 
              ORDER BY b.is_main_branch DESC, b.name ASC";
    $branches = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Management | ALHAYIKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .badge-hq { background-color: #0dcaf0; font-size: 0.7rem; vertical-align: middle; }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.02); }
    </style>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php
            if($_GET['msg'] == 'added') echo "Branch added successfully!";
            if($_GET['msg'] == 'deleted') echo "Branch permanently deleted.";
            if($_GET['msg'] == 'deactivated') echo "Branch has been deactivated.";
            if($_GET['msg'] == 'activated') echo "Branch is now active.";
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
                <h3 class="fw-bold mb-0">Branch Management</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Branches</li>
                    </ol>
                </nav>
            </div>
            <?php if(in_array($role, ['Super Admin', 'SuperAdmin', 'Admin'])): ?>
                <a href="add_branch.php" class="btn btn-success px-4">
                    <i class="fas fa-plus me-2"></i>Add New Branch
                </a>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="branchSearch" class="form-control border-start-0 ps-0" 
                                   placeholder="Search by branch name, code, mobile, or email...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="Active">Active Only</option>
                            <option value="Inactive">Inactive Only</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="branchTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Branch Details</th>
                            <th>Quick Stats</th>
                            <th>Contact Info</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($branches as $b): 
                            // Correctly decode JSON fields from DB
                            $mobiles = json_decode($b['mobile_numbers'], true) ?: [];
                            $emails = json_decode($b['emails'], true) ?: [];
                        ?>
                        <tr class="branch-row" data-status="<?= $b['status'] ?>">
                            <td class="ps-4">
                                <div class="fw-bold text-dark mb-0">
                                    <?= htmlspecialchars($b['name']) ?>
                                    <?php if($b['is_main_branch']): ?>
                                        <span class="badge badge-hq ms-1">HQ</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">Code: <?= htmlspecialchars($b['branch_code']) ?></small>
                            </td>
                            <td>
                                <div class="d-flex gap-3">
                                    <div class="text-center" title="Staff Members">
                                        <div class="small fw-bold text-primary"><?= $b['staff_count'] ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Staff</div>
                                    </div>
                                    <div class="text-center" title="Linked Clients">
                                        <div class="small fw-bold text-success"><?= $b['client_count'] ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Clients</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <?php if($b['landline']): ?>
                                        <div class="mb-1"><i class="fas fa-phone-alt me-2 text-muted"></i><?= htmlspecialchars($b['landline']) ?></div>
                                    <?php endif; ?>
                                    
                                    <?php foreach(array_slice($mobiles, 0, 2) as $m): ?>
                                        <div class="text-nowrap"><i class="fab fa-whatsapp me-2 text-success"></i><?= htmlspecialchars($m) ?></div>
                                    <?php endforeach; ?>

                                    <?php foreach(array_slice($emails, 0, 1) as $e): ?>
                                        <div class="text-muted text-nowrap mt-1 small"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($e) ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="small text-muted">
                                <div class="text-dark"><?= htmlspecialchars($b['area']) ?>, Zone <?= htmlspecialchars($b['zone_number']) ?></div>
                                <div>St. <?= htmlspecialchars($b['street_name']) ?>, Bldg. <?= htmlspecialchars($b['building_number']) ?></div>
                            </td>
                            <td>
                                <?php if($b['status'] == 'Active'): ?>
                                    <span class="badge rounded-pill bg-success-subtle text-success border border-success px-3">
                                        <i class="fas fa-check-circle me-1"></i>Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger px-3">
                                        <i class="fas fa-times-circle me-1"></i>Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group">
                                    <a href="view_branch.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if(in_array($role, ['Super Admin', 'SuperAdmin', 'Admin'])): ?>
                                        <a href="edit_branch.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if($b['status'] == 'Active'): ?>
                                            <button onclick="updateStatus(<?= $b['id'] ?>, 'Inactive')" class="btn btn-sm btn-outline-warning" title="Deactivate">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="updateStatus(<?= $b['id'] ?>, 'Active')" class="btn btn-sm btn-outline-success" title="Activate">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($branches)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No branches found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// 1. Live Search Logic
document.getElementById('branchSearch').addEventListener('keyup', function() {
    filterTable();
});

// 2. Status Filter Logic
document.getElementById('statusFilter').addEventListener('change', function() {
    filterTable();
});

function filterTable() {
    const searchTerm = document.getElementById('branchSearch').value.toLowerCase();
    const statusTerm = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.branch-row');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const status = row.getAttribute('data-status');
        
        const matchesSearch = text.includes(searchTerm);
        const matchesStatus = statusTerm === "" || status === statusTerm;

        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    });
}

// 3. Status Update (Deactivate/Activate)
function updateStatus(id, newStatus) {
    const action = newStatus === 'Inactive' ? 'deactivate' : 'activate';
    if(confirm(`Are you sure you want to ${action} this branch?`)) {
        window.location.href = `update_branch_status.php?id=${id}&status=${newStatus}`;
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>