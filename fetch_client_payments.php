<?php
include 'db.php';
$clientId = $_GET['id'] ?? 0;

if (!$clientId) exit("<div class='alert alert-danger text-center'>Invalid Client ID.</div>");

/** * PLAN SECTION 7: PAYMENT HISTORY
 * Fetching all successful payments with branch and method details
 */
$stmt = $pdo->prepare("
    SELECT p.*, b.name as branch_name 
    FROM payments p
    LEFT JOIN branches b ON p.branch_id = b.id
    WHERE p.client_id = ? 
    ORDER BY p.payment_date DESC
");
$stmt->execute([$clientId]);
$payments = $stmt->fetchAll();

// Calculate total received for this specific list
$totalReceived = 0;
foreach($payments as $p) { $totalReceived += $p['amount_paid']; }
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-success text-white p-3">
            <small class="text-uppercase opacity-75 fw-bold">Total Collections</small>
            <h3 class="mb-0 fw-bold"><?= number_format($totalReceived, 2) ?> <small class="fs-6">QAR</small></h3>
        </div>
    </div>
    <div class="col-md-8 text-md-end d-flex align-items-end justify-content-md-end">
        <button class="btn btn-success shadow-sm" onclick="window.location.href='add_payment.php?client_id=<?= $clientId ?>'">
            <i class="fas fa-plus-circle me-1"></i> Record New Payment
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr class="small text-uppercase text-muted">
                    <th class="ps-3">Receipt No</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Branch</th>
                    <th>Remarks</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No payment records found.</td></tr>
                <?php else: foreach ($payments as $p): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark">
                            <i class="fas fa-receipt me-2 text-muted"></i><?= $p['receipt_no'] ?>
                        </td>
                        <td class="small"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                        <td>
                            <?php 
                                $method = $p['payment_method'];
                                $icon = 'fa-money-bill';
                                if($method == 'Bank' || $method == 'Transfer') $icon = 'fa-university';
                                if($method == 'Cheque') $icon = 'fa-money-check';
                                if($method == 'Card') $icon = 'fa-credit-card';
                            ?>
                            <span class="small"><i class="fas <?= $icon ?> me-2 text-muted"></i><?= $method ?></span>
                        </td>
                        <td class="fw-bold text-success"><?= number_format($p['amount_paid'], 2) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($p['branch_name'] ?? 'Main') ?></td>
                        <td class="small text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($p['remarks']) ?>">
                            <?= htmlspecialchars($p['remarks']) ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group">
                                <a href="print_receipt.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Receipt">
                                    <i class="fas fa-print"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="voidPayment(<?= $p['id'] ?>)" title="Void Payment">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
/**
 * PLAN SECTION 14: AUDIT LOGS
 * Voiding a payment should always trigger a confirmation and be logged.
 */
function voidPayment(id) {
    Swal.fire({
        title: 'Void Payment?',
        text: "This will restore the outstanding balance on the associated invoice(s).",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, void it'
    }).then((result) => {
        if (result.isConfirmed) {
            // Fetch void_payment_ajax.php here
            console.log("Voiding payment ID:", id);
        }
    });
}
</script>