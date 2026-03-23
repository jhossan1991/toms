<?php
include 'db.php';
session_start();

if (!isset($_GET['id'])) { die("Staff ID missing."); }
$staff_id = (int)$_GET['id'];

// 1. Fetch Comprehensive Data (Profiles + Users + Branch)
$query = "SELECT s.*, b.name as branch_name, u.username, u.role, u.account_status, u.last_login 
          FROM staff_profiles s
          LEFT JOIN branches b ON s.branch_id = b.id
          LEFT JOIN users u ON s.id = u.staff_profile_id
          WHERE s.id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

if (!$staff) { die("Staff member not found."); }

// 2. Fetch Workload Data (Tab 3) - Focused on Assignments
// Updated logic: We check the 'jobs' table where this staff is the assigned worker
$workloadQuery = "SELECT 
    COUNT(CASE WHEN status NOT IN ('Completed', 'Cancelled') THEN 1 END) as active_assignments,
    COUNT(CASE WHEN deadline = CURDATE() AND status NOT IN ('Completed', 'Cancelled') THEN 1 END) as due_today,
    COUNT(CASE WHEN deadline < CURDATE() AND status NOT IN ('Completed', 'Cancelled') THEN 1 END) as overdue_assignments
    FROM jobs WHERE assigned_to = ?";
$stmtWork = $pdo->prepare($workloadQuery);
$stmtWork->execute([$staff_id]);
$workload = $stmtWork->fetch();

// 3. Fetch Performance Metrics (Tab 4) - Summing pages from job_items
$perfQuery = "SELECT 
    COUNT(DISTINCT j.id) as total_finished,
    SUM(ji.pages_s) as total_pages,
    AVG(DATEDIFF(j.updated_at, j.created_at)) as avg_days
    FROM jobs j
    LEFT JOIN job_items ji ON j.id = ji.job_id
    WHERE j.assigned_to = ? AND j.status = 'Completed'";
$stmtPerf = $pdo->prepare($perfQuery);
$stmtPerf->execute([$staff_id]);
$performance = $stmtPerf->fetch();
// // 4. Fetch Audit Logs (Tab 5)
$auditQuery = "SELECT a.*, u.username as editor_name 
               FROM audit_logs a 
               LEFT JOIN users u ON a.updated_by = u.id 
               WHERE a.staff_profile_id = ? 
               ORDER BY a.id DESC LIMIT 20"; // Changed created_at to id for safety
$stmtAudit = $pdo->prepare($auditQuery);
$stmtAudit->execute([$staff_id]);
$logs = $stmtAudit->fetchAll();

// Helper for Expiry Badges
function getStatusBadge($date) {
    if (!$date) return '<span class="badge bg-secondary">N/A</span>';
    $diff = (strtotime($date) - time()) / (60 * 60 * 24);
    if ($diff < 0) return '<span class="badge bg-danger">🔴 Expired</span>';
    if ($diff <= 30) return '<span class="badge bg-warning text-dark">🟡 Expiring ('.round($diff).'d)</span>';
    return '<span class="badge bg-success">🟢 Valid</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $staff['full_name'] ?> | Staff Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-header-card { border: none; border-radius: 15px; background: linear-gradient(135deg, #1b1a2f 0%, #258d54 100%); color: white; }
        .nav-tabs .nav-link { color: #555; font-weight: 500; border: none; padding: 12px 20px; transition: 0.3s; }
        .nav-tabs .nav-link.active { color: #258d54; border-bottom: 3px solid #258d54; background: transparent; }
        .info-label { color: #888; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .info-value { color: #333; font-weight: 500; }
        .stat-card { border: none; border-radius: 10px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
        <div class="card profile-header-card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 70px; height: 70px; font-size: 2rem; font-weight: bold;">
                            <?= strtoupper(substr($staff['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold"><?= $staff['full_name'] ?></h3>
                            <p class="mb-0 opacity-75">
                                <span class="me-3"><i class="fas fa-id-badge me-1"></i> <?= $staff['staff_id_code'] ?></span>
                                <span class="me-3"><i class="fas fa-map-marker-alt me-1"></i> <?= $staff['branch_name'] ?></span>
                                <span><i class="fas fa-user-tag me-1"></i> <?= $staff['role'] ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle fw-bold shadow-sm" data-bs-toggle="dropdown">Actions</button>
                            <ul class="dropdown-menu shadow border-0">
                                <li><a class="dropdown-item" href="edit_staff.php?id=<?= $staff['id'] ?>"><i class="fas fa-edit me-2 text-primary"></i>Edit Staff</a></li>
                                <li><a class="dropdown-item" href="assign_task.php?staff_id=<?= $staff['id'] ?>"><i class="fas fa-tasks me-2 text-success"></i>New Assignment</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-key me-2 text-warning"></i>Reset Password</a></li>
                                <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-user-lock me-2"></i>Lock Account</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <hr class="my-3 opacity-25">
                <div class="d-flex flex-wrap gap-4">
                    <div>Status: <span class="badge bg-<?= ($staff['status']=='Active'?'success':'danger') ?>"><?= $staff['status'] ?></span></div>
                    <div>Vacation: <span class="badge bg-<?= ($staff['in_vacation']=='Yes'?'warning text-dark':'info') ?>"><?= $staff['in_vacation'] ?></span></div>
                    <div>QID: <?= getStatusBadge($staff['qid_expiry']) ?></div>
                    <div>Passport: <?= getStatusBadge($staff['passport_expiry']) ?></div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4" id="staffTabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#basicInfo"><i class="fas fa-info-circle me-1"></i> 1. Basic Info</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#systemAccess"><i class="fas fa-shield-alt me-1"></i> 2. System Access</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#workload"><i class="fas fa-briefcase me-1"></i> 3. Workload</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#performance"><i class="fas fa-chart-line me-1"></i> 4. Performance</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#activity"><i class="fas fa-history me-1"></i> 5. Activity Logs</a></li>
        </ul>

        <div class="tab-content bg-white p-4 rounded shadow-sm border">
            <div class="tab-pane fade show active" id="basicInfo">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3 border-bottom pb-2 text-success"><i class="fas fa-user me-2"></i>Personal Details</h6>
                        <div class="row mb-2">
                            <div class="col-5 info-label">Mobile</div>
                            <div class="col-7 info-value"><?= $staff['mobile'] ?: 'N/A' ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 info-label">Email</div>
                            <div class="col-7 info-value"><?= $staff['email'] ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 info-label">Joined Date</div>
                            <div class="col-7 info-value"><?= $staff['date_joined'] ? date('d M Y', strtotime($staff['date_joined'])) : 'N/A' ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 info-label">Sponsor</div>
                            <div class="col-7 info-value"><?= $staff['sponsor_company'] ?: 'N/A' ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 border-start">
                        <h6 class="fw-bold mb-3 border-bottom pb-2 text-success"><i class="fas fa-file-contract me-2"></i>Legal Documents</h6>
                        <div class="row mb-3 align-items-center">
                            <div class="col-5 info-label">QID Number</div>
                            <div class="col-7 info-value">
                                <span class="fw-bold"><?= $staff['qid_number'] ?: 'N/A' ?></span><br>
                                <small class="text-muted">Expires: <?= $staff['qid_expiry'] ?: 'N/A' ?></small>
                            </div>
                        </div>
                        <div class="row mb-3 align-items-center">
                            <div class="col-5 info-label">Passport Number</div>
                            <div class="col-7 info-value">
                                <span class="fw-bold"><?= $staff['passport_number'] ?: 'N/A' ?></span><br>
                                <small class="text-muted">Expires: <?= $staff['passport_expiry'] ?: 'N/A' ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="systemAccess">
                <div class="alert alert-info py-2 small"><i class="fas fa-lock me-2"></i>Access settings are managed by Super Admin.</div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <label class="info-label d-block">Username</label>
                            <span class="info-value fs-6"><?= $staff['username'] ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <label class="info-label d-block">Account Status</label>
                            <span class="info-value"><?= $staff['account_status'] ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <label class="info-label d-block">Last Login</label>
                            <span class="info-value"><?= $staff['last_login'] ?: 'No record' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="workload">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 text-center p-3 stat-card">
                            <h2 class="fw-bold text-primary"><?= $workload['active_assignments'] ?? 0 ?></h2>
                            <span class="text-muted small fw-bold">ACTIVE ASSIGNMENTS</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 text-center p-3 stat-card">
                            <h2 class="fw-bold text-warning"><?= $workload['due_today'] ?? 0 ?></h2>
                            <span class="text-muted small fw-bold">DUE TODAY</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 text-center p-3 stat-card">
                            <h2 class="fw-bold text-danger"><?= $workload['overdue_assignments'] ?? 0 ?></h2>
                            <span class="text-muted small fw-bold">OVERDUE</span>
                        </div>
                    </div>
                </div>
                <div class="text-center p-3">
                    <a href="assign_task.php?staff_id=<?= $staff['id'] ?>" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> Create New Assignment
                    </a>
                </div>
            </div>

            <div class="tab-pane fade" id="performance">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="p-4 border rounded shadow-sm text-center stat-card bg-white">
                            <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                            <h4 class="fw-bold"><?= $performance['total_finished'] ?? 0 ?></h4>
                            <p class="text-muted mb-0">Assignments Finished</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-4 border rounded shadow-sm text-center stat-card bg-white">
                            <i class="fas fa-file-alt fa-2x text-info mb-3"></i>
                            <h4 class="fw-bold"><?= number_format($performance['total_pages'] ?? 0, 1) ?></h4>
                            <p class="text-muted mb-0">Total Pages Processed</p>
                        </div>
                    </div>
                </div>
                <div class="bg-light p-3 rounded border">
                    <h6 class="fw-bold small"><i class="fas fa-clock me-2"></i>Speed Metric</h6>
                    <p class="mb-0 small">Average turnaround: <strong><?= round($performance['avg_days'] ?? 0, 1) ?> days per assignment.</strong></p>
                </div>
            </div>

            <div class="tab-pane fade" id="activity">
                <table class="table table-hover table-sm small">
                    <thead class="table-dark">
                        <tr>
                            <th>Action Type</th>
                            <th>Module</th>
                            <th>Date & Time</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <div class="tab-pane fade" id="activity">
    <h6 class="fw-bold mb-3"><i class="fas fa-history me-2 text-secondary"></i>Profile Change History</h6>
    <table class="table table-hover small">
        <thead class="table-light">
            <tr>
                <th>Field</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Changed By</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($logs) > 0): ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="fw-bold text-capitalize"><?= str_replace('_', ' ', $log['field_changed']) ?></td>
                    <td class="text-danger"><?= htmlspecialchars($log['old_value'] ?? 'empty') ?></td>
                    <td class="text-success"><?= htmlspecialchars($log['new_value'] ?? 'empty') ?></td>
                    <td><?= $log['editor_name'] ?></td>
                    <td class="text-muted"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No edit history found for this profile.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>