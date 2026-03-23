<?php
include 'db.php';
session_start();

// 1. Session Check & Role Logic (Array-based from login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['roles'])) {
    header("Location: login.php");
    exit;
}

$staff_id = (int)$_GET['id'];
$user_roles = $_SESSION['roles']; // This is an array
$user_branch = $_SESSION['branch_id'] ?? null;
$is_super_admin = in_array('Super Admin', $user_roles);

// 2. Fetch Comprehensive Data
$stmt = $pdo->prepare("
    SELECT s.*, u.username, u.role as assigned_roles, u.account_status, u.permissions, u.last_login 
    FROM staff_profiles s 
    LEFT JOIN users u ON s.id = u.staff_profile_id 
    WHERE s.id = ?
");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

if (!$staff) die("Staff not found.");

// 3. Access Control Check
if (!$is_super_admin && $staff['branch_id'] != $user_branch) {
    die("Access Denied: You can only edit staff within your own branch.");
}

$branches = $pdo->query("SELECT id, name FROM branches")->fetchAll();
$available_roles = ['Super Admin', 'Branch Manager', 'Front Desk', 'Translator', 'Reviewer', 'Accountant', 'PRO', 'Support Staff'];
$current_roles = explode(',', $staff['assigned_roles'] ?? '');

// Decode permissions (Assuming stored as JSON in 'users' table)
$current_permissions = json_decode($staff['permissions'] ?? '[]', true) ?? [];

$available_permissions = [
    'View Quotations', 'Create Quotations', 'Edit Quotations',
    'View Jobs', 'Create Jobs', 'Assign Jobs',
    'View Invoices', 'Create Invoices',
    'Manage Vendors', 'Manage Clients',
    'Access Settings', 'HR Management'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Staff | <?= htmlspecialchars($staff['full_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .nav-tabs .nav-link.active { border-bottom: 3px solid #198754; color: #198754; font-weight: bold; }
        .permission-card { border: 1px solid #eee; padding: 10px; border-radius: 5px; background: #fff; transition: 0.3s; }
        .permission-card:hover { border-color: #198754; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <form action="update_staff_action.php" method="POST">
        <input type="hidden" name="staff_id" value="<?= $staff_id ?>">
        
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Edit Profile: <span class="text-success"><?= $staff['staff_id_code'] ?></span></h5>
                <div class="btn-group">
                    <a href="view_staff.php?id=<?= $staff_id ?>" class="btn btn-sm btn-outline-secondary">Back to Profile</a>
                    <button type="submit" class="btn btn-sm btn-success px-4">Save Changes</button>
                </div>
            </div>
            
            <ul class="nav nav-tabs px-3 mt-3" id="editTabs">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#sectionA">Basic & Employment</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sectionB">Legal Documents</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sectionC">Role & Permissions</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sectionD">System Access</a></li>
            </ul>

            <div class="tab-content p-4">
                <div class="tab-pane fade show active" id="sectionA">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Staff Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= $staff['full_name'] ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Mobile</label>
                            <input type="text" name="mobile" class="form-control" value="<?= $staff['mobile'] ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= $staff['email'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Sponsor Company</label>
                            <input type="text" name="sponsor_company" class="form-control" value="<?= $staff['sponsor_company'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Working Under Company</label>
                            <input type="text" name="working_under_company" class="form-control" value="<?= $staff['working_under_company'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date Joined</label>
                            <input type="date" name="date_joined" class="form-control" value="<?= $staff['date_joined'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Assigned Branch</label>
                            <select name="branch_id" class="form-select" <?= !$is_super_admin ? 'disabled' : '' ?>>
                                <?php foreach($branches as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= $staff['branch_id'] == $b['id'] ? 'selected' : '' ?>><?= $b['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(!$is_super_admin): ?>
                                <input type="hidden" name="branch_id" value="<?= $staff['branch_id'] ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">System Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" <?= $staff['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $staff['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">In Vacation</label>
                            <select name="in_vacation" class="form-select">
                                <option value="No" <?= $staff['in_vacation'] == 'No' ? 'selected' : '' ?>>No</option>
                                <option value="Yes" <?= $staff['in_vacation'] == 'Yes' ? 'selected' : '' ?>>Yes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="sectionB">
                    <div class="row g-4">
                        <div class="col-md-6 border-end">
                            <h6 class="text-primary fw-bold"><i class="fas fa-id-card me-2"></i>Qatar ID (QID)</h6>
                            <label class="form-label small">QID Number</label>
                            <input type="text" name="qid_number" class="form-control mb-2" value="<?= $staff['qid_number'] ?>">
                            <label class="form-label small">QID Expiry Date</label>
                            <input type="date" name="qid_expiry" class="form-control expiry-calc" value="<?= $staff['qid_expiry'] ?>" data-target="qid_badge">
                            <div id="qid_badge" class="mt-2"></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary fw-bold"><i class="fas fa-passport me-2"></i>Passport Information</h6>
                            <label class="form-label small">Passport Number</label>
                            <input type="text" name="passport_number" class="form-control mb-2" value="<?= $staff['passport_number'] ?>">
                            <label class="form-label small">Passport Expiry Date</label>
                            <input type="date" name="passport_expiry" class="form-control expiry-calc" value="<?= $staff['passport_expiry'] ?>" data-target="passport_badge">
                            <div id="passport_badge" class="mt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="sectionC">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Roles (Select Multiple)</label>
                        <select name="roles[]" class="form-select select2-role" multiple>
                            <?php foreach($available_roles as $role): ?>
                                <option value="<?= $role ?>" <?= in_array($role, $current_roles) ? 'selected' : '' ?>><?= $role ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Individual Permission Overrides</h6>
                    <div class="row g-2">
                        <?php foreach($available_permissions as $perm): ?>
                        <div class="col-md-3">
                            <div class="permission-card">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $perm ?>" id="perm_<?= md5($perm) ?>" <?= in_array($perm, $current_permissions) ? 'checked' : '' ?>>
                                    <label class="form-check-label small fw-bold" for="perm_<?= md5($perm) ?>"><?= $perm ?></label>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="sectionD">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Username</label>
                            <input type="text" class="form-control bg-light" value="<?= $staff['username'] ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Account Access</label>
                            <select name="account_status" class="form-select">
                                <option value="Active" <?= $staff['account_status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Locked" <?= $staff['account_status'] == 'Locked' ? 'selected' : '' ?>>Locked</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <div class="alert alert-info py-2">
                                <i class="fas fa-info-circle me-1"></i> <strong>Last Login:</strong> <?= $staff['last_login'] ?: 'Never' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2-role').select2({ width: '100%', placeholder: 'Select Roles' });

    function calculateBadges() {
        $('.expiry-calc').each(function() {
            const container = $('#' + this.dataset.target);
            if (!this.value) { container.empty(); return; }
            
            const today = new Date();
            const expiry = new Date(this.value);
            const diffTime = expiry - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            let badgeClass = 'bg-success', statusText = 'Valid';
            
            if (diffDays < 0) {
                badgeClass = 'bg-danger'; statusText = 'EXPIRED';
            } else if (diffDays <= 30) {
                badgeClass = 'bg-warning text-dark'; statusText = 'EXPIRING SOON';
            }
            
            container.html(`<span class="badge ${badgeClass}"><i class="fas fa-clock me-1"></i> ${statusText} (${diffDays} Days Remaining)</span>`);
        });
    }
    $('.expiry-calc').on('change', calculateBadges);
    calculateBadges();
});
</script>
</body>
</html>