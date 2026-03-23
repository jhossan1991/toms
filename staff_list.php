<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Permissions Check
$user_roles  = $_SESSION['roles'] ?? []; // Get the array from session
$user_branch = $_SESSION['branch_id'] ?? 0;

// Determine if they are a Super Admin
$is_super_admin = in_array('Super Admin', $user_roles);

// Helper function for Expiry Status
function getExpiryBadge($date) {
    if (!$date || $date == '0000-00-00') {
        return '<span class="badge bg-secondary">N/A</span>';
    }

    $expiry_timestamp = strtotime($date);
    $today_timestamp = strtotime('today');
    
    // Calculate difference in days
    $diff = round(($expiry_timestamp - $today_timestamp) / (60 * 60 * 24));

    if ($diff < 0) {
        return '<span class="badge bg-danger">🔴 Expired</span>';
    } elseif ($diff <= 30) {
        return '<span class="badge bg-warning text-dark">🟡 Expiring (' . $diff . 'd)</span>';
    } else {
        return '<span class="badge bg-success">🟢 Valid</span>';
    }
}

// --- FILTER LOGIC ---
$where_clauses = [];
$params = [];

// 1. Branch Filter Logic
if (!$is_super_admin) {
    // Regular users (Managers, etc.) are restricted to their own branch
    $where_clauses[] = "s.branch_id = ?";
    $params[] = $user_branch;
} elseif (!empty($_GET['branch'])) {
    // Super Admins see all by default, but can filter by a specific branch if selected
    $where_clauses[] = "s.branch_id = ?";
    $params[] = $_GET['branch'];
}

// 2. Global Search
if (!empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $where_clauses[] = "(s.full_name LIKE ? OR s.staff_id_code LIKE ? OR s.mobile LIKE ? OR s.email LIKE ? OR s.qid_number LIKE ?)";
    // Push parameter 5 times for the 5 placeholders
    for($i=0; $i<5; $i++) { $params[] = $search; }
}

// 3. Status Filter
if (!empty($_GET['status'])) { 
    $where_clauses[] = "s.status = ?"; 
    $params[] = $_GET['status']; 
}

// 4. Role Filter (Matches 'roles' name from the HTML select)
if (!empty($_GET['roles'])) { 
    $where_clauses[] = "u.role LIKE ?"; 
    $params[] = "%" . $_GET['roles'] . "%"; 
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- FETCH DATA ---
$query = "SELECT s.*, b.name as branch_name, u.role, u.account_status 
          FROM staff_profiles s
          LEFT JOIN branches b ON s.branch_id = b.id
          LEFT JOIN users u ON s.id = u.staff_profile_id
          $where_sql
          ORDER BY s.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$staff_list = $stmt->fetchAll();

// Fetch branches for the filter dropdown
$branches = $pdo->query("SELECT id, name FROM branches")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff List | Al Hayiki</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .filter-card { border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .staff-table th { background-color: #f1f4f8; color: #333; font-size: 0.85rem; text-transform: uppercase; }
        .staff-name-link { font-weight: 600; color: #258d54; text-decoration: none; }
        .staff-name-link:hover { text-decoration: underline; }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold"><i class="fas fa-users-cog me-2"></i>Staff Management</h3>
                <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item">HR</li><li class="breadcrumb-item active">Staff List</li></ol></nav>
            </div>
            <div>
                <a href="add_staff.php" class="btn btn-success px-4 py-2 fw-bold shadow-sm">
                    <i class="fas fa-plus me-2"></i>ADD NEW STAFF
                </a>
            </div>
        </div>

        <div class="card filter-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="small fw-bold">Global Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, ID, Mobile, Email or QID..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Branch</label>
                        <select name="branch" class="form-select">
                            <option value="">All Branches</option>
                            <?php foreach($branches as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= (($_GET['branch'] ?? '') == $b['id']) ? 'selected' : '' ?>><?= $b['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Role</label>
                        <select name="roles" class="form-select">
                            <option value="">All Roles</option>
                            <option value="Translator" <?= (($_GET['roles'] ?? '') == 'Translator') ? 'selected' : '' ?>>Translator</option>
                            <option value="Manager" <?= (($_GET['roles'] ?? '') == 'Manager') ? 'selected' : '' ?>>Branch Manager</option>
                            <option value="PRO" <?= (($_GET['roles'] ?? '') == 'PRO') ? 'selected' : '' ?>>PRO</option>
                            <option value="Super Admin" <?= (($_GET['roles'] ?? '') == 'Super Admin') ? 'selected' : '' ?>>Super Admin</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Emp. Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Active" <?= (($_GET['status'] ?? '') == 'Active') ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= (($_GET['status'] ?? '') == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table id="staffTable" class="table table-hover align-middle mb-0 staff-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Staff Name</th>
                            <th>Contact</th>
                            <th>Branch</th>
                            <th>Roles</th>
                            <th>QID Details</th>
                            <th>Passport</th>
                            <th>Acc. Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($staff_list as $staff): ?>
                        <tr>
                            <td class="small fw-bold"><?= htmlspecialchars($staff['staff_id_code']) ?></td>
                            <td>
                                <a href="view_staff.php?id=<?= $staff['id'] ?>" class="staff-name-link"><?= htmlspecialchars($staff['full_name']) ?></a><br>
                                <span class="badge bg-light text-dark border small fw-normal">Vacation: <?= htmlspecialchars($staff['in_vacation']) ?></span>
                            </td>
                            <td class="small">
                                <i class="fas fa-phone me-1 text-muted"></i> <?= htmlspecialchars($staff['mobile']) ?><br>
                                <i class="fas fa-envelope me-1 text-muted"></i> <?= htmlspecialchars($staff['email']) ?>
                            </td>
                            <td><?= htmlspecialchars($staff['branch_name'] ?? 'N/A') ?></td>
                            <td class="small"><?= str_replace(',', '<br>•', htmlspecialchars($staff['role'] ?? '')) ?></td>
                            <td>
                                <span class="small fw-bold"><?= htmlspecialchars($staff['qid_number']) ?></span><br>
                                <?= getExpiryBadge($staff['qid_expiry']) ?>
                            </td>
                            <td>
                                <span class="small fw-bold"><?= htmlspecialchars($staff['passport_number']) ?></span><br>
                                <?= getExpiryBadge($staff['passport_expiry']) ?>
                            </td>
                            <td>
                                <?php if($staff['account_status'] == 'Active'): ?>
                                    <span class="text-success small fw-bold"><i class="fas fa-check-circle"></i> Active</span>
                                <?php else: ?>
                                    <span class="text-danger small fw-bold"><i class="fas fa-lock"></i> Locked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view_staff.php?id=<?= $staff['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit_staff.php?id=<?= $staff['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-sm btn-outline-danger" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    $('#staffTable').DataTable({
        dom: 'Brtip',
        buttons: [
            { extend: 'excel', className: 'btn btn-sm btn-light border', text: '<i class="fas fa-file-excel text-success"></i> Excel' },
            { extend: 'pdf', className: 'btn btn-sm btn-light border', text: '<i class="fas fa-file-pdf text-danger"></i> PDF' }
        ],
        pageLength: 20,
        language: {
            paginate: { next: '>', previous: '<' }
        }
    });
});
</script>
</body>
</html>