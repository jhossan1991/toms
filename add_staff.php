<?php
include 'db.php';
session_start();

// Section 1.2: Auto-generate Staff ID (STF-001 format) - IMPROVED
$stmt = $pdo->query("SELECT staff_id_code FROM staff_profiles ORDER BY id DESC LIMIT 1");
$last_staff = $stmt->fetch();

if ($last_staff) {
    // Extract the number from 'STF-002' -> 2
    $last_number = (int)str_replace('STF-', '', $last_staff['staff_id_code']);
    $next_id = "STF-" . str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);
} else {
    $next_id = "STF-001";
}

// Fetch branches for selection
$branches = $pdo->query("SELECT id, name FROM branches")->fetchAll();

// Define available permissions for manual selection (Section C)
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
    <title>Add New Staff | Al Hayiki</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        body { overflow-x: hidden; background-color: #f4f7f6; }
        .wrapper { display: flex; width: 100%; align-items: stretch; }
        #content { width: 100%; padding: 20px; transition: all 0.3s; }
        .section-header { border-left: 5px solid #258d54; padding: 10px 15px; margin-bottom: 20px; background: #f8f9fa; font-weight: bold; color: #1b1a2f; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border-radius: 10px; }
        .form-label { font-weight: 600; font-size: 0.9rem; color: #444; }
        .btn-primary { background-color: #258d54; border: none; }
        .btn-primary:hover { background-color: #79c219; }
        .perm-box { background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div id="content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                <div>
                    <h2 class="mb-0"><i class="fas fa-user-plus text-success me-2"></i>Add New Staff Member</h2>
                    <small class="text-muted">Personal Profile & System Access Setup</small>
                </div>
                <a href="staff_list.php" class="btn btn-outline-secondary px-4">
                    <i class="fas fa-list me-2"></i>Staff List
                </a>
            </div>

            <form id="addStaffForm" action="save_staff.php" method="POST">
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="section-header">SECTION A – BASIC INFORMATION</h5>
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label text-muted">Staff ID</label>
                                <input type="text" class="form-control bg-light fw-bold" value="<?php echo $next_id; ?>" name="staff_id_code" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Assigned Branch <span class="text-danger">*</span></label>
                                <select class="form-select" name="branch_id" required>
                                    <option value="">Select Branch</option>
                                    <?php foreach($branches as $b): ?>
                                        <option value="<?php echo $b['id']; ?>"><?php echo $b['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mobile Number</label>
                                <input type="text" class="form-control" name="mobile">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="email_input" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sponsor Company</label>
                                <input type="text" class="form-control" name="sponsor_company">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Working Under Company</label>
                                <input type="text" class="form-control" name="working_under_company">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date Joined</label>
                                <input type="date" class="form-control" name="date_joined" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="section-header">SECTION B – LEGAL DOCUMENT TRACKING</h5>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-white">
                                    <h6 class="fw-bold mb-3"><i class="far fa-id-card text-success me-2"></i>Qatar ID (QID)</h6>
                                    <div class="row g-2">
                                        <div class="col-7">
                                            <label class="small text-muted">QID Number</label>
                                            <input type="text" class="form-control" name="qid_number">
                                        </div>
                                        <div class="col-5">
                                            <label class="small text-muted">Expiry Date</label>
                                            <input type="date" class="form-control expiry-calc" name="qid_expiry" data-target="qid_badge">
                                        </div>
                                    </div>
                                    <span id="qid_badge" class="badge-preview"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-white">
                                    <h6 class="fw-bold mb-3"><i class="fas fa-passport text-success me-2"></i>Passport</h6>
                                    <div class="row g-2">
                                        <div class="col-7">
                                            <label class="small text-muted">Passport Number</label>
                                            <input type="text" class="form-control" name="passport_number">
                                        </div>
                                        <div class="col-5">
                                            <label class="small text-muted">Expiry Date</label>
                                            <input type="date" class="form-control expiry-calc" name="passport_expiry" data-target="passport_badge">
                                        </div>
                                    </div>
                                    <span id="passport_badge" class="badge-preview"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="section-header">SECTION C – ROLE & PERMISSIONS</h5>
                                <div class="mb-3">
                                    <label class="form-label">Roles (Multi-select)</label>
                                    <select class="form-select select2-role" name="roles[]" multiple="multiple" required>
                                        <option value="Super Admin">Super Admin</option>
                                        <option value="Branch Manager">Branch Manager</option>
                                        <option value="Front Desk">Front Desk/ Receptionist</option>
                                        <option value="Translator">Translator</option>
                                        <option value="Reviewer">Reviewer</option>
                                        <option value="Accountant">Accountant</option>
                                        <option value="PRO">PRO</option>
                                        <option value="Support Staff">Support Staff</option>
                                    </select>
                                </div>
                                <label class="form-label">Manual Permission Override</label>
                                <div class="perm-box">
                                    <div class="row">
                                        <?php foreach($available_permissions as $perm): ?>
                                        <div class="col-6 mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $perm ?>" id="p_<?= md5($perm) ?>">
                                                <label class="form-check-label small" for="p_<?= md5($perm) ?>"><?= $perm ?></label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="section-header">SECTION D – SYSTEM ACCESS</h5>
                                <div class="mb-3">
                                    <label class="form-label">Username (Use Email)</label>
                                    <input type="text" class="form-control bg-light" name="username" id="username_display" readonly placeholder="Auto-fills from Email">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Account Status</label>
                                        <select class="form-select" name="account_status">
                                            <option value="Active">Active</option>
                                            <option value="Locked">Locked</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Last Login</label>
                                        <input type="text" class="form-control bg-light" value="Never" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mb-5">
                    <button type="reset" class="btn btn-light px-4 border">Reset Form</button>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow">
                        <i class="fas fa-save me-2"></i>CREATE STAFF RECORD
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 for Multi-select roles
    $('.select2-role').select2({
        placeholder: "Select one or more roles",
        allowClear: true,
        width: '100%'
    });

    // Sync Username with Email input (Section D requirement)
    $('#email_input').on('input', function() {
        $('#username_display').val($(this).val());
    });

    // UI Logic: Live Expiry Badge Calculation
    $('.expiry-calc').on('change', function() {
        const targetBadge = document.getElementById(this.dataset.target);
        if (!this.value) {
            targetBadge.innerHTML = '';
            return;
        }

        const expiryDate = new Date(this.value);
        const today = new Date();
        const diffTime = expiryDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        let badgeClass = 'bg-success';
        let statusText = 'Valid';

        if (diffDays < 0) {
            badgeClass = 'bg-danger';
            statusText = 'EXPIRED';
        } else if (diffDays <= 30) {
            badgeClass = 'bg-warning text-dark';
            statusText = 'EXPIRING SOON';
        }

        targetBadge.innerHTML = `
            <div class="mt-2">
                <span class="badge ${badgeClass} p-2">${statusText}</span> 
                <span class="ms-2 text-muted small fw-bold">${diffDays} days remaining</span>
            </div>`;
    });
});
</script>
</body>
</html>