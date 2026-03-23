<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$quote_id = $_GET['id'] ?? null;
if (!$quote_id) {
    die("Invalid Quotation ID");
}

// 1. Fetch Quotation and Client Details
// FIX: Changed u.name to u.full_name to match your database schema
$stmt = $pdo->prepare("SELECT q.*, 
                       c.company_name, c.name as client_person, c.mobile_primary, c.email,
                       u.full_name as creator_name, b.name as branch_name
                       FROM quotations q 
                       JOIN clients c ON q.client_id = c.id 
                       JOIN users u ON q.created_by = u.id 
                       JOIN branches b ON q.branch_id = b.id
                       WHERE q.id = ?");
$stmt->execute([$quote_id]);
$quote = $stmt->fetch();

if (!$quote) { die("Quotation not found."); }

// 2. Fetch Line Items
$itemsStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quote_id = ?");
$itemsStmt->execute([$quote_id]);
$line_items = $itemsStmt->fetchAll();

// 3. User Permissions Logic
// FIX: Changed $_SESSION['role'] to match your 'users' table column
$user_role = $_SESSION['role'] ?? 'Staff'; 
$user_branch = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'];

$isOwner = ($quote['created_by'] == $user_id);
$isSameBranch = ($quote['branch_id'] == $user_branch);

// Note: Your DB uses 'SuperAdmin', 'BranchAdmin', etc. 
// I have adjusted these to match your enum values in the SQL
$canEdit    = ($user_role === 'SuperAdmin' || $user_role === 'BranchAdmin' || ($user_role === 'Staff' && $quote['status'] === 'Draft' && $isOwner));
$canApprove = ($user_role === 'SuperAdmin' || $user_role === 'BranchAdmin');
$canConvert = ($user_role === 'SuperAdmin' || $user_role === 'BranchAdmin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation Profile | <?= $quote['quote_no'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .flex-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex-grow: 1; padding: 25px; }
        .status-badge { font-size: 0.75rem; padding: 6px 15px; border-radius: 50px; text-transform: uppercase; font-weight: bold; }
        .card { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); margin-bottom: 20px; }
        .section-title { font-size: 0.9rem; font-weight: 700; color: #495057; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; }
        .info-label { font-size: 0.75rem; color: #6c757d; margin-bottom: 2px; }
        .info-value { font-weight: 600; color: #212529; }
    </style>
</head>
<body>

<div class="flex-wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0 text-dark">Quotation Profile</h4>
            <div class="btn-group shadow-sm">
                <?php if ($canEdit): ?>
                    <a href="edit_quotation.php?id=<?= $quote_id ?>" class="btn btn-white border bg-white"><i class="fas fa-edit me-1 text-primary"></i> Edit</a>
                <?php endif; ?>
                <button class="btn btn-white border bg-white"><i class="fas fa-envelope me-1 text-info"></i> Email</button>
                <a href="print_pdf.php?id=<?= $quote_id ?>" class="btn btn-white border bg-white"><i class="fas fa-file-pdf me-1 text-danger"></i> PDF</a>
                <button class="btn btn-white border bg-white"><i class="fab fa-whatsapp me-1 text-success"></i> WhatsApp</button>
                <?php if ($canConvert && $quote['status'] !== 'Converted'): ?>
                    <button class="btn btn-success"><i class="fas fa-exchange-alt me-1"></i> Convert to Job</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card p-4">
            <div class="row align-items-center">
                <div class="col-md-4 border-end">
                    <div class="d-flex align-items-center mb-2">
                        <h2 class="fw-bold mb-0 me-3"><?= $quote['quote_no'] ?></h2>
                        <span class="status-badge bg-info text-white"><?= $quote['status'] ?></span>
                    </div>
                    <div class="info-label">CLIENT</div>
                    <div class="info-value fs-5"><?= htmlspecialchars($quote['company_name'] ?: $quote['client_person']) ?></div>
                    <div class="text-muted small"><?= $quote['email'] ?> | <?= $quote['mobile_primary'] ?></div>
                </div>
                <div class="col-md-8">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="info-label">DATE</div>
                            <div class="info-value"><?= date('d M Y', strtotime($quote['created_at'])) ?></div>
                        </div>
                        <div class="col-3 border-start">
                            <div class="info-label">VALID UNTIL</div>
                            <div class="info-value text-danger"><?= date('d M Y', strtotime($quote['valid_until'])) ?></div>
                        </div>
                        <div class="col-2 border-start">
                            <div class="info-label">VERSION</div>
                            <div class="info-value">V 1.0</div>
                        </div>
                        <div class="col-4 border-start">
                            <div class="info-label">TOTAL AMOUNT</div>
                            <div class="info-value fs-4 text-success">QR <?= number_format($quote['grand_total'], 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-list-ul me-2"></i>Service Breakdown</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Service Type</th>
                            <th>Description / Languages</th>
                            <th class="text-center">Actual Pgs</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Unit</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end pe-4">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($line_items as $item): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?= $item['service_type'] ?></td>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td class="text-center"><?= $item['pages_s'] ?></td>
                            <td class="text-center"><?= $item['qty'] ?></td>
                            <td class="text-center"><?= $item['unit'] ?></td>
                            <td class="text-end">QR <?= number_format($item['rate'], 2) ?></td>
                            <td class="text-end pe-4 fw-bold">QR <?= number_format($item['total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="6" class="text-end fw-bold py-2">Sub-Total</td>
                            <td class="text-end pe-4 py-2">QR <?= number_format($quote['sub_total'], 2) ?></td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end fw-bold py-2 text-danger">Discount</td>
                            <td class="text-end pe-4 py-2 text-danger">- QR <?= number_format($quote['discount'], 2) ?></td>
                        </tr>
                        <tr class="fs-5 border-top border-dark">
                            <td colspan="6" class="text-end fw-bold py-3">GRAND TOTAL</td>
                            <td class="text-end pe-4 py-3 fw-bold text-success">QR <?= number_format($quote['grand_total'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card p-3">
                    <div class="section-title"><i class="fas fa-history me-2"></i>Version History</div>
                    <table class="table table-sm table-borderless small mb-0">
                        <thead class="text-muted">
                            <tr>
                                <th>Ver.</th>
                                <th>Changed By</th>
                                <th>Date</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1.0</td>
                                <td><?= $quote['creator_name'] ?></td>
                                <td><?= date('d/m/y', strtotime($quote['created_at'])) ?></td>
                                <td>Initial Creation</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-3">
                    <div class="section-title"><i class="fas fa-tasks me-2"></i>Activity Log</div>
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-2 d-flex justify-content-between">
                            <span><i class="fas fa-plus-circle text-primary me-2"></i> Created by <strong><?= $quote['creator_name'] ?></strong></span>
                            <span class="text-muted"><?= date('d M, H:i', strtotime($quote['created_at'])) ?></span>
                        </li>
                        <li class="mb-2 d-flex justify-content-between opacity-50">
                            <span><i class="fas fa-paper-plane text-muted me-2"></i> Not yet sent to client</span>
                        </li>
                        <li class="d-flex justify-content-between opacity-50">
                            <span><i class="fas fa-check-double text-muted me-2"></i> Not yet approved</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if(!empty($quote['additional_notes'])): ?>
        <div class="card p-3 bg-light">
            <div class="section-title">Additional Notes</div>
            <p class="small mb-0 text-dark"><?= nl2br(htmlspecialchars($quote['additional_notes'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>