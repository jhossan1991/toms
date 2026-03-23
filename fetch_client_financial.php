<?php
include 'db.php';
$clientId = $_GET['id'] ?? 0;

if (!$clientId) exit("<div class='alert alert-danger'>Invalid Client ID.</div>");

/** * 1. FETCH FINANCIAL DATA (PLAN SECTION 4)
 * Fetching terms, limit, and current totals for the logic check
 */
$stmt = $pdo->prepare("SELECT credit_limit, payment_terms, default_currency, default_discount_pct, default_service_charge_pct FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$fin = $stmt->fetch();

if (!$fin) exit("<div class='alert alert-danger'>Financial record not found.</div>");

// Calculate Current Outstanding for the "Before Invoice" logic check
$calcStmt = $pdo->prepare("
    SELECT 
        (SELECT SUM(total_amount) FROM invoices WHERE client_id = ? AND status != 'Cancelled' AND status != 'Draft') as total_invoiced,
        (SELECT SUM(amount_paid) FROM payments WHERE client_id = ?) as total_paid
");
$calcStmt->execute([$clientId, $clientId]);
$calc = $calcStmt->fetch();

$totalInvoiced = $calc['total_invoiced'] ?? 0;
$totalPaid = $calc['total_paid'] ?? 0;
$currentOutstanding = $totalInvoiced - $totalPaid;
$availableCredit = $fin['credit_limit'] - $currentOutstanding;
$creditUsagePct = $fin['credit_limit'] > 0 ? ($currentOutstanding / $fin['credit_limit']) * 100 : 0;
?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-shield-alt me-2 text-danger"></i>Credit & Terms Control</h6>
            </div>
            <div class="card-body">
                <form id="financialSettingsForm">
                    <input type="hidden" name="client_id" value="<?= $clientId ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Credit Limit (QAR)</label>
                        <div class="input-group">
                            <span class="input-group-text">QR</span>
                            <input type="number" name="credit_limit" class="form-control fw-bold" value="<?= $fin['credit_limit'] ?>">
                        </div>
                        <div class="form-text text-muted">Set to 0 for unlimited / cash-only clients.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Payment Terms</label>
                        <select name="payment_terms" class="form-select">
                            <option value="Cash" <?= $fin['payment_terms'] == 'Cash' ? 'selected' : '' ?>>Cash / Immediate</option>
                            <option value="7 Days" <?= $fin['payment_terms'] == '7 Days' ? 'selected' : '' ?>>7 Days</option>
                            <option value="15 Days" <?= $fin['payment_terms'] == '15 Days' ? 'selected' : '' ?>>15 Days</option>
                            <option value="30 Days" <?= $fin['payment_terms'] == '30 Days' ? 'selected' : '' ?>>30 Days</option>
                            <option value="Custom" <?= $fin['payment_terms'] == 'Custom' ? 'selected' : '' ?>>Custom (See Notes)</option>
                        </select>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Default Discount (%)</label>
                            <input type="number" name="default_discount" class="form-control" value="<?= $fin['default_discount_pct'] ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Service Charge (%)</label>
                            <input type="number" name="default_service_charge" class="form-control" value="<?= $fin['default_service_charge_pct'] ?>">
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary w-100" onclick="updateFinancials()">
                        <i class="fas fa-save me-2"></i> Update Financial Policy
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line me-2 text-info"></i>Credit Utilization</h6>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">Limit Utilization</span>
                        <span class="small fw-bold"><?= number_format($creditUsagePct, 1) ?>%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <?php 
                            $barColor = ($creditUsagePct > 90) ? 'bg-danger' : (($creditUsagePct > 70) ? 'bg-warning' : 'bg-success');
                        ?>
                        <div class="progress-bar <?= $barColor ?>" role="progressbar" style="width: <?= min($creditUsagePct, 100) ?>%"></div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Current Outstanding</span>
                        <span class="fw-bold"><?= number_format($currentOutstanding, 2) ?> QAR</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                        <span class="text-muted small">Total Credit Limit</span>
                        <span class="fw-bold"><?= number_format($fin['credit_limit'], 2) ?> QAR</span>
                    </div>
                    <div class="d-flex justify-content-between pt-1">
                        <span class="text-dark fw-bold">Remaining Available Credit</span>
                        <span class="h5 mb-0 fw-bold <?= $availableCredit < 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($availableCredit, 2) ?> QAR
                        </span>
                    </div>
                </div>

                <div class="mt-4 p-3 border-start border-warning border-4 bg-warning-subtle rounded">
                    <small class="d-block fw-bold text-warning-emphasis">🔴 CREDIT CONTROL LOGIC</small>
                    <small class="text-muted">
                        If (Current Outstanding + New Invoice) > Credit Limit, the system will block invoice creation and require Super Admin approval.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateFinancials() {
    const formData = new FormData(document.getElementById('financialSettingsForm'));
    
    fetch('update_financial_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire('Updated!', 'Financial policy saved successfully.', 'success');
            loadTabContent('financial'); // Refresh the tab
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}
</script>