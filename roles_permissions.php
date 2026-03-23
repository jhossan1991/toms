<?php
include 'db.php';
session_start();

// Only Super Admin can access this page
if (($_SESSION['role'] ?? '') !== 'SuperAdmin' && ($_SESSION['role'] ?? '') !== 'Super Admin') {
    die("Unauthorized access.");
}

// Module List from your requirements
$modules = [
    'Dashboard', 'Clients', 'Quotations', 'Jobs', 'Assignments', 
    'Vendors', 'Invoices', 'Payments', 'Vendor Settlements', 
    'Expenses', 'Reports', 'Notifications', 'System Settings'
];

// Fetch Roles from DB
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $permissions = json_encode($_POST['perm']);
    // You should create a 'role_permissions' table or update the 'roles' table to store this JSON
    // For now, we will simulate the UI structure
    $success = "Permissions updated successfully (Logic to save to DB goes here).";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Roles & Permissions | ALHAYIKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="container-fluid p-4">
        <h3 class="fw-bold mb-4">Roles & Permissions Matrix</h3>
        
        <div class="card shadow-sm">
            <div class="table-responsive">
                <form method="POST">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Module</th>
                            <?php foreach($roles as $r): ?>
                                <th class="text-center"><?= $r['name'] ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($modules as $mod): ?>
                        <tr>
                            <td class="fw-bold"><?= $mod ?></td>
                            <?php foreach($roles as $r): ?>
                            <td>
                                <select name="perm[<?= $r['id'] ?>][<?= $mod ?>]" class="form-select form-select-sm">
                                    <option value="None">None</option>
                                    <option value="View">View</option>
                                    <option value="Own">Own Assigned</option>
                                    <option value="Branch">Own Branch</option>
                                    <option value="Full">Full Access</option>
                                </select>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">Save Permissions</button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>