<?php
include 'db.php';
$clientId = $_GET['id'] ?? 0;

if (!$clientId) exit("<div class='alert alert-danger'>Invalid Client ID.</div>");

/** * 1. FETCH CLIENT & CALCULATE FINANCIALS (PLAN SECTION 3)
 * We calculate Totals, Outstanding, and Monthly Snapshots in one go.
 */

// Fetch Basic Details
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$c = $stmt->fetch();
if (!$c) exit("<div class='alert alert-danger'>Client not found.</div>");

// Calculate Financial Overview (Total Jobs, Invoiced, Paid)
// Formula: Outstanding = Total Invoiced - Total Paid
$finStmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM jobs WHERE client_id = ?) as total_jobs,
        (SELECT SUM(total_amount) FROM invoices WHERE client_id = ? AND status != 'Cancelled' AND status != 'Draft') as total_invoiced,
        (SELECT SUM(amount_paid) FROM payments WHERE client_id = ?) as total_paid,
        (SELECT MAX(invoice_date) FROM invoices WHERE client_id = ?) as last_invoice,
        (SELECT MAX(payment_date) FROM payments WHERE client_id = ?) as last_payment
");
$finStmt->execute([$clientId, $clientId, $clientId, $clientId, $clientId]);
$fin = $finStmt->fetch();

$totalInvoiced = $fin['total_invoiced'] ?? 0;
$totalPaid = $fin['total_paid'] ?? 0;
$outstanding = $totalInvoiced - $totalPaid;

// Calculate Monthly Snapshot (Current Month Only)
$monthStmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM jobs WHERE client_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE())) as month_jobs,
        (SELECT SUM(total_amount) FROM invoices WHERE client_id = ? AND MONTH(invoice_date) = MONTH(CURRENT_DATE())) as month_invoiced,
        (SELECT SUM(amount_paid) FROM payments WHERE client_id = ? AND MONTH(payment_date) = MONTH(CURRENT_DATE())) as month_paid
");
$monthStmt->execute([$clientId, $clientId, $clientId]);
$month = $monthStmt->fetch();

// Fetch Rates (PLAN SECTION 8)
$rateStmt = $pdo->prepare("SELECT * FROM client_rates WHERE client_id = ? ORDER BY service_category ASC");
$rateStmt->execute([$clientId]);
$rates = $rateStmt->fetchAll();
?>


<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white p-3 border-start border-primary border-4">
            <small class="text-muted fw-bold text-uppercase">Total Jobs</small>
            <h3 class="mb-0 fw-bold"><?= number_format($fin['total_jobs']) ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white p-3 border-start border-info border-4">
            <small class="text-muted fw-bold text-uppercase">Total Invoiced</small>
            <h3 class="mb-0 fw-bold text-info"><?= number_format($totalInvoiced, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white p-3 border-start border-success border-4">
            <small class="text-muted fw-bold text-uppercase">Total Paid</small>
            <h3 class="mb-0 fw-bold text-success"><?= number_format($totalPaid, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white p-3 border-start border-danger border-4">
            <small class="text-muted fw-bold text-uppercase">Outstanding</small>
            <h3 class="mb-0 fw-bold text-danger"><?= number_format($outstanding, 2) ?></h3>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-address-card me-2 text-success"></i>Contact Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Email Address</label>
                        <span class="fw-bold"><?= htmlspecialchars($c['email']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Primary Contact</label>
                        <span class="fw-bold"><?= htmlspecialchars($c['mobile_primary']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Office Branch</label>
                        <span class="badge bg-light text-dark border fw-normal">Main Office</span>
                    </div>
                    <div class="col-12">
                        <label class="small text-muted d-block">Physical Address</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($c['address'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-tags me-2 text-success"></i>Service Rates</h6>
                <button class="btn btn-sm btn-outline-success" onclick="openRateModal()">+ New Rate</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Category</th>
                            <th>Description</th>
                            <th>Unit</th>
                            <th>Rate</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rates)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted small">No custom rates defined.</td></tr>
                        <?php else: foreach ($rates as $r): ?>
                            <tr>
                                <td class="ps-3"><span class="badge bg-light text-dark border"><?= $r['service_category'] ?></span></td>
                                <td class="small">
                                    <?php if($r['service_category'] == 'Translation'): ?>
                                        <strong><?= $r['source_lang'] ?></strong> <i class="fas fa-arrow-right mx-1 text-muted"></i> <strong><?= $r['target_lang'] ?></strong>
                                    <?php else: ?>
                                        <?= htmlspecialchars($r['description']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= $r['unit'] ?></td>
                                <td class="fw-bold text-success"><?= number_format($r['rate'], 2) ?> QAR</td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-link text-primary p-0 me-2" onclick='editRate(<?= json_encode($r) ?>)'><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-link text-danger p-0" onclick="deleteRate(<?= $r['id'] ?>)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4 bg-success text-white">
            <div class="card-body">
                <h6 class="small fw-bold text-uppercase opacity-75 mb-3">Monthly Snapshot (Current)</h6>
                <div class="d-flex justify-content-between border-bottom border-white border-opacity-25 pb-2 mb-2">
                    <span class="small">Jobs Created</span>
                    <span class="fw-bold"><?= $month['month_jobs'] ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom border-white border-opacity-25 pb-2 mb-2">
                    <span class="small">Invoiced Amt</span>
                    <span class="fw-bold"><?= number_format($month['month_invoiced'] ?? 0, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="small">Payments Recv</span>
                    <span class="fw-bold"><?= number_format($month['month_paid'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="small text-muted fw-bold text-uppercase mb-3">Activity Timeline</h6>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-light p-2 rounded text-primary me-3"><i class="fas fa-file-invoice"></i></div>
                    <div>
                        <div class="small fw-bold">Last Invoice</div>
                        <div class="small text-muted"><?= $fin['last_invoice'] ? date('d M Y', strtotime($fin['last_invoice'])) : 'No invoices' ?></div>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="bg-light p-2 rounded text-success me-3"><i class="fas fa-receipt"></i></div>
                    <div>
                        <div class="small fw-bold">Last Payment</div>
                        <div class="small text-muted"><?= $fin['last_payment'] ? date('d M Y', strtotime($fin['last_payment'])) : 'No payments' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-folder-open me-2 text-warning"></i>Key Documents</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small">Signed Contract</span>
                        <?php if($c['has_contract']): ?>
                            <span class="badge bg-success-subtle text-success border border-success">Verified</span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border">Missing</span>
                        <?php endif; ?>
                    </li>
                    <li class="d-flex justify-content-between align-items-center mb-0">
                        <span class="small">QID / CR Copy</span>
                        <span class="badge bg-light text-muted border">Pending</span>
                    </li>
                </ul>
                <button class="btn btn-sm btn-light border w-100 mt-3" onclick="loadTabContent('docs')">Manage Documents</button>
            </div>
        </div>
    </div>
</div>