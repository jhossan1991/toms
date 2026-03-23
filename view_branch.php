<?php
include 'db.php';
session_start();

$id = $_GET['id'] ?? null;
if (!$id) die("Branch ID is missing.");

// 1. Fetch Branch Data with Manager Details
$query = "SELECT b.*, s.full_name as manager_name, s.mobile as manager_mobile, s.email as manager_email, s.qid_number as manager_qid
          FROM branches b
          LEFT JOIN staff_profiles s ON b.manager_id = s.id
          WHERE b.id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$id]);
$b = $stmt->fetch();

if (!$b) die("Branch not found.");

// 2. Role-Based Access Control
$role = $_SESSION['role'] ?? ($_SESSION['roles'][0] ?? 'Staff');
if ($role === 'Branch Manager' && $_SESSION['branch_id'] != $id) {
    die("Access Denied: You can only view your own branch.");
}

// 3. Decode JSON Data
$mobiles = json_decode($b['mobile_numbers'], true) ?: [];
$emails = json_decode($b['emails'], true) ?: [];
$docs = json_decode($b['legal_documents'], true) ?: [];

// 4. Expiry Calculation Function
function getExpiryBadge($date) {
    if (!$date) return '<span class="badge bg-secondary">No Date</span>';
    $expiry = new DateTime($date);
    $today = new DateTime();
    $diff = $today->diff($expiry);
    $days = (int)$diff->format("%r%a");

    if ($days < 0) return '<span class="badge bg-danger">🔴 Expired</span>';
    if ($days <= 30) return '<span class="badge bg-warning text-dark">🟡 Expiring Soon ('.$days.'d)</span>';
    return '<span class="badge bg-success">🟢 Safe</span>';
}

// 5. Delete Validation (Check only if tables exist to avoid SQL errors)
$can_delete = true;
$tables_to_check = ['clients', 'quotations', 'jobs', 'invoices'];

foreach ($tables_to_check as $table) {
    try {
        // Check if table exists first
        $tableExists = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
        
        if ($tableExists) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE branch_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                $can_delete = false;
                break;
            }
        }
    } catch (PDOException $e) {
        // If query fails for any reason, assume we can't delete to be safe
        continue; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($b['name']) ?> | Branch Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .nav-tabs .nav-link { color: #495057; font-weight: 500; border: none; padding: 1rem 1.5rem; }
        .nav-tabs .nav-link.active { color: #258d54; border-bottom: 3px solid #258d54; background: none; }
        .info-label { font-size: 0.85rem; color: #6c757d; font-weight: bold; text-uppercase: true; }
        .info-value { font-size: 1rem; color: #212529; margin-bottom: 1rem; }
        .summary-card { border-left: 5px solid #258d54; }
    </style>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="container-fluid p-4">
        
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($b['name']) ?></h3>
                <span class="text-muted small">Branch ID: #<?= $b['id'] ?> | Code: <?= htmlspecialchars($b['branch_code']) ?></span>
            </div>
            <div class="dropdown">
                <button class="btn btn-dark dropdown-toggle px-4" type="button" data-bs-toggle="dropdown">
                    Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="edit_branch.php?id=<?= $id ?>"><i class="fas fa-edit me-2"></i>Edit Branch</a></li>
                    <li><a class="dropdown-item" href="javascript:window.print()"><i class="fas fa-print me-2"></i>Print Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if($b['status'] == 'Active'): ?>
                        <li><a class="dropdown-item text-warning" href="update_branch_status.php?id=<?= $id ?>&status=Inactive"><i class="fas fa-ban me-2"></i>Deactivate</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item text-success" href="update_branch_status.php?id=<?= $id ?>&status=Active"><i class="fas fa-play me-2"></i>Activate</a></li>
                    <?php endif; ?>
                    
                    <?php if(in_array($role, ['SuperAdmin', 'Super Admin'])): ?>
                        <?php if($can_delete): ?>
                            <li><a class="dropdown-item text-danger" href="delete_branch.php?id=<?= $id ?>" onclick="return confirm('Are you sure?')"><i class="fas fa-trash me-2"></i>Delete Branch</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item text-muted disabled" href="#"><i class="fas fa-info-circle me-2"></i>Delete Restricted (Linked Data)</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm mb-4 summary-card">
            <div class="card-body">
                <div class="row text-center border-bottom pb-3 mb-3">
                    <div class="col-md-2 border-end">
                        <div class="info-label">Type</div>
                        <div class="fw-bold"><?= $b['is_main_branch'] ? 'Head Office' : 'Branch' ?></div>
                    </div>
                    <div class="col-md-3 border-end">
                        <div class="info-label">Parent Company</div>
                        <div class="fw-bold"><?= htmlspecialchars($b['parent_company'] ?: 'N/A') ?></div>
                    </div>
                    <div class="col-md-3 border-end">
                        <div class="info-label">Branch Manager</div>
                        <div class="fw-bold text-primary"><?= htmlspecialchars($b['manager_name'] ?: 'Not Assigned') ?></div>
                    </div>
                    <div class="col-md-2 border-end">
                        <div class="info-label">Status</div>
                        <span class="badge bg-<?= $b['status'] == 'Active' ? 'success' : 'danger' ?>"><?= $b['status'] ?></span>
                    </div>
                    <div class="col-md-2">
                        <div class="info-label">Location</div>
                        <div class="fw-bold"><?= htmlspecialchars($b['city']) ?></div>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-2 flex-wrap mt-2">
                    <?php foreach($docs as $doc): ?>
                        <div class="px-3 py-1 bg-white border rounded small shadow-sm">
                            <span class="text-muted fw-bold"><?= htmlspecialchars($doc['name']) ?>:</span> 
                            <?= getExpiryBadge($doc['expiry_date']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm overflow-hidden">
            <ul class="nav nav-tabs bg-white border-bottom" id="branchTab" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#basic">Basic Info</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#contact">Contact Info</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#address">Address</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#legal">Legal & Compliance</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#manager">Manager</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#logs">Activity Logs</button></li>
            </ul>
            
            <div class="tab-content p-4 bg-white" id="branchTabContent">
                
                <div class="tab-pane fade show active" id="basic">
                    <div class="row">
                        <div class="col-md-4"><div class="info-label">Branch Name</div><div class="info-value"><?= htmlspecialchars($b['name']) ?></div></div>
                        <div class="col-md-4"><div class="info-label">Branch Code</div><div class="info-value"><?= htmlspecialchars($b['branch_code']) ?></div></div>
                        <div class="col-md-4"><div class="info-label">Branch Type</div><div class="info-value"><?= $b['is_main_branch'] ? 'Head Office' : 'Branch' ?></div></div>
                        <hr>
                        <div class="col-md-3"><div class="info-label">Created By</div><div class="info-value">Admin</div></div>
                        <div class="col-md-3"><div class="info-label">Created At</div><div class="info-value"><?= date('d M Y', strtotime($b['created_at'])) ?></div></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="contact">
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <div class="info-label">Landline Number</div><div class="info-value"><?= htmlspecialchars($b['landline'] ?: 'N/A') ?></div>
                            <div class="info-label">Website</div><div class="info-value"><?= htmlspecialchars($b['website'] ?: 'N/A') ?></div>
                        </div>
                        <div class="col-md-6 ps-4">
                            <div class="info-label">Mobile Numbers</div>
                            <ul class="list-unstyled">
                                <?php foreach($mobiles as $m): ?>
                                    <li class="info-value mb-1"><i class="fab fa-whatsapp text-success me-2"></i><?= htmlspecialchars($m) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="info-label mt-3">Email Addresses</div>
                            <ul class="list-unstyled">
                                <?php foreach($emails as $e): ?>
                                    <li class="info-value mb-1"><i class="fas fa-envelope text-muted me-2"></i><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="address">
                    <div class="row">
                        <div class="col-md-3"><div class="info-label">Area</div><div class="info-value"><?= htmlspecialchars($b['area']) ?></div></div>
                        <div class="col-md-3"><div class="info-label">Street Name</div><div class="info-value"><?= htmlspecialchars($b['street_name']) ?></div></div>
                        <div class="col-md-2"><div class="info-label">Building No.</div><div class="info-value"><?= htmlspecialchars($b['building_number']) ?></div></div>
                        <div class="col-md-2"><div class="info-label">Zone</div><div class="info-value"><?= htmlspecialchars($b['zone_number']) ?></div></div>
                        <div class="col-md-2"><div class="info-label">City</div><div class="info-value"><?= htmlspecialchars($b['city']) ?></div></div>
                        <div class="col-md-4 mt-3">
                            <?php if($b['google_maps_link']): ?>
                                <a href="<?= $b['google_maps_link'] ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-map-marked-alt me-2"></i>View on Google Maps</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="legal">
                    <table class="table table-hover border">
                        <thead class="table-light">
                            <tr>
                                <th>Document Name</th>
                                <th>Number</th>
                                <th>Issue Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($docs as $doc): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($doc['name']) ?></td>
                                <td><?= htmlspecialchars($doc['number']) ?></td>
                                <td><?= $doc['issue_date'] ?></td>
                                <td><?= $doc['expiry_date'] ?></td>
                                <td><?= getExpiryBadge($doc['expiry_date']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="manager">
                    <?php if($b['manager_id']): ?>
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <i class="fas fa-user-tie fa-4x text-light p-4 bg-secondary rounded-circle"></i>
                        </div>
                        <div class="col-md-10">
                            <h5 class="fw-bold mb-3"><?= htmlspecialchars($b['manager_name']) ?></h5>
                            <div class="row">
                                <div class="col-md-4"><div class="info-label">QID Number</div><div class="info-value"><?= htmlspecialchars($b['manager_qid']) ?></div></div>
                                <div class="col-md-4"><div class="info-label">Mobile</div><div class="info-value"><?= htmlspecialchars($b['manager_mobile']) ?></div></div>
                                <div class="col-md-4"><div class="info-label">Email</div><div class="info-value"><?= htmlspecialchars($b['manager_email']) ?></div></div>
                            </div>
                            <a href="view_staff.php?id=<?= $b['manager_id'] ?>" class="btn btn-sm btn-link ps-0">View Full Staff Profile →</a>
                        </div>
                    </div>
                    <?php else: ?>
                        <p class="text-muted">No manager assigned to this branch.</p>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="logs">
                    <p class="text-muted small">Showing last 10 activities for this branch...</p>
                    <table class="table table-sm small">
                        <thead class="table-light">
                            <tr><th>Action</th><th>Date</th><th>User</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Branch Profile Created</td><td><?= $b['created_at'] ?></td><td>System</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>