<?php
include 'db.php';
$clientId = $_GET['id'] ?? 0;

if (!$clientId) exit("<div class='alert alert-danger text-center'>Invalid Client ID.</div>");

/** * PLAN SECTION 6: INVOICE HISTORY
 * Fetching invoices with running totals for the Bottom Summary
 */
$stmt = $pdo->prepare("
    SELECT * FROM invoices 
    WHERE client_id = ? 
    ORDER BY invoice_date DESC
");
$stmt->execute([$clientId]);
$invoices = $stmt->fetchAll();

// Initialize Totals for Bottom Summary (Section 6)
$sumInvoiced = 0;
$sumPaid = 0;
$sumOutstanding = 0;
?>

<div class="row g-2 mb-4 align-items-center bg-light p-3 rounded border">
    <div class="col-md-4">
        <label class="small fw-bold text-muted">Payment Status</label>
        <select id="inv_status_filter" class="form-select form-select-sm">
            <option value="">All Invoices</option>
            <option value="Unpaid">Unpaid / Overdue</option>
            <option value="Partially Paid">Partially Paid</option>
            <option value="Paid">Fully Paid</option>
            <option value="Cancelled">Cancelled</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="small fw-bold text-muted">Search Invoice</label>
        <input type="text" id="inv_search" class="form-control form-control-sm" placeholder="Invoice # or Reference...">
    </div>
    <div class="col-md-2 text-end">
        <label class="d-block">&nbsp;</label>
        <button class="btn btn-sm btn-dark w-100" onclick="filterInvoices()">
            <i class="fas fa-search me-1"></i> Search
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr class="small text-uppercase text-muted">
                    <th class="ps-3">Invoice #</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Outstanding</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (empty($invoices)): 
                    echo '<tr><td colspan="8" class="text-center py-5 text-muted">No invoices recorded.</td></tr>';
                else: 
                    foreach ($invoices as $inv): 
                        $amt = $inv['total_amount'];
                        $paid = $inv['amount_paid'] ?? 0;
                        $bal = $amt - $paid;
                        
                        // Accumulate totals for bottom summary
                        if($inv['status'] !== 'Cancelled' && $inv['status'] !== 'Draft') {
                            $sumInvoiced += $amt;
                            $sumPaid += $paid;
                            $sumOutstanding += $bal;
                        }

                        // Status Color Logic
                        $status = $inv['status'];
                        $badge = 'bg-secondary';
                        if($status == 'Paid') $badge = 'bg-success';
                        if($status == 'Unpaid' && strtotime($inv['due_date']) < time()) $badge = 'bg-danger'; // Overdue
                        elseif($status == 'Unpaid') $badge = 'bg-warning text-dark';
                ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark"><?= $inv['invoice_no'] ?></td>
                        <td class="small"><?= date('d M Y', strtotime($inv['invoice_date'])) ?></td>
                        <td class="small <?= ($bal > 0 && strtotime($inv['due_date']) < time()) ? 'text-danger fw-bold' : '' ?>">
                            <?= date('d M Y', strtotime($inv['due_date'])) ?>
                        </td>
                        <td class="fw-bold"><?= number_format($amt, 2) ?></td>
                        <td class="text-success small"><?= number_format($paid, 2) ?></td>
                        <td class="text-danger fw-bold"><?= number_format($bal, 2) ?></td>
                        <td>
                            <span class="badge <?= $badge ?> small"><?= $status ?></span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group">
                                <a href="view_invoice.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
                                <a href="print_invoice.php?id=<?= $inv['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print"><i class="fas fa-print"></i></a>
                                <?php if($bal > 0): ?>
                                    <button class="btn btn-sm btn-success" title="Add Payment" onclick="addPaymentForInvoice(<?= $inv['id'] ?>, <?= $bal ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row justify-content-end">
    <div class="col-md-4">
        <div class="card border-0 bg-dark text-white p-3 shadow">
            <div class="d-flex justify-content-between mb-2">
                <span class="small opacity-75">Total Invoiced</span>
                <span class="fw-bold"><?= number_format($sumInvoiced, 2) ?> QAR</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="small opacity-75">Total Paid</span>
                <span class="fw-bold text-success"><?= number_format($sumPaid, 2) ?> QAR</span>
            </div>
            <hr class="my-2 border-secondary">
            <div class="d-flex justify-content-between">
                <span class="fw-bold">Total Outstanding</span>
                <span class="h5 mb-0 fw-bold text-warning"><?= number_format($sumOutstanding, 2) ?> QAR</span>
            </div>
        </div>
    </div>
</div>

<script>
function addPaymentForInvoice(invId, balance) {
    // Redirect to payment page with auto-filled invoice and amount
    window.location.href = `add_payment.php?client_id=<?= $clientId ?>&invoice_id=${invId}&amount=${balance}`;
}
</script>