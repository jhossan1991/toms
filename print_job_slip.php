<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Job Header and Client Info
$stmt = $pdo->prepare("SELECT j.*, c.company_name, c.name as client_name, c.mobile_primary, c.email, b.name as branch_name 
                       FROM jobs j 
                       LEFT JOIN clients c ON j.client_id = c.id 
                       LEFT JOIN branches b ON j.branch_id = b.id
                       WHERE j.id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    die("Job not found.");
}

// Fetch Job Items
$itemsStmt = $pdo->prepare("SELECT * FROM job_items WHERE job_id = ?");
$itemsStmt->execute([$job_id]);
$items = $itemsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Slip - <?= $job['job_no'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Courier New', Courier, monospace; }
        .slip-container {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
        }
        .header-section { border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .info-label { font-weight: bold; width: 120px; display: inline-block; }
        .table-items thead { background: #f2f2f2; }
        .footer-sign { margin-top: 50px; }
        .sign-box { border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px; }
        
        /* Print Specific Styles */
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .slip-container { box-shadow: none; border: none; margin: 0; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container no-print mt-3 text-center">
    <button onclick="window.print()" class="btn btn-primary btn-lg"><i class="fas fa-print"></i> Print Job Slip</button>
    <a href="manage_jobs.php" class="btn btn-outline-secondary btn-lg"><i class="fas fa-arrow-left"></i> Back to List</a>
</div>

<div class="slip-container">
    <div class="header-section d-flex justify-content-between align-items-center">
        <div>
            <h1 class="fw-bold mb-0">JOB SLIP</h1>
            <p class="text-muted">Branch: <?= htmlspecialchars($job['branch_name']) ?></p>
        </div>
        <div class="text-end">
            <h4 class="mb-0"><?= $job['job_no'] ?></h4>
            <p>Date: <?= date('d-M-Y', strtotime($job['created_at'])) ?></p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <h6 class="text-uppercase border-bottom pb-1">Client Details</h6>
            <div><span class="info-label">Name:</span> <?= htmlspecialchars($job['company_name'] ?: $job['client_name']) ?></div>
            <div><span class="info-label">Mobile:</span> <?= htmlspecialchars($job['mobile_primary'] ?? 'N/A') ?></div>
            <div><span class="info-label">Ref:</span> <?= htmlspecialchars($job['client_ref'] ?? 'None') ?></div>
        </div>
        <div class="col-6 text-end">
            <h6 class="text-uppercase border-bottom pb-1">Status & Payment</h6>
            <div class="fw-bold text-success"><?= $job['status'] ?></div>
            <div><span class="info-label">Method:</span> <?= $job['payment_method'] ?? 'Cash' ?></div>
            <div class="fw-bold">Amount Due: QR <?= number_format($job['amount_due'], 2) ?></div>
        </div>
    </div>

    <table class="table table-bordered table-items">
        <thead>
            <tr>
                <th>Service</th>
                <th>Description</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= $item['service_type'] ?></td>
                <td>
                    <?php if($item['service_type'] == 'Translation'): ?>
                        <?= $item['source_lang'] ?> to <?= $item['target_lang'] ?>
                    <?php else: ?>
                        <?= $item['description'] ?>
                    <?php endif; ?>
                </td>
                <td class="text-center"><?= $item['qty'] ?></td>
                <td class="text-end"><?= number_format($item['rate'], 2) ?></td>
                <td class="text-end"><?= number_format($item['line_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end fw-bold">Subtotal</td>
                <td class="text-end"><?= number_format($job['grand_total'] + ($job['discount'] ?? 0), 2) ?></td>
            </tr>
            <?php if($job['discount'] > 0): ?>
            <tr>
                <td colspan="4" class="text-end fw-bold">Discount</td>
                <td class="text-end text-danger">- <?= number_format($job['discount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="table-dark">
                <td colspan="4" class="text-end fw-bold">Grand Total (QAR)</td>
                <td class="text-end fw-bold"><?= number_format($job['grand_total'], 2) ?></td>
            </tr>
            <tr>
                <td colspan="4" class="text-end fw-bold">Amount Paid</td>
                <td class="text-end fw-bold text-success"><?= number_format($job['amount_paid'], 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-4">
        <h6><strong>Deadline/Notes:</strong></h6>
        <p class="border p-2 bg-light"><?= nl2br(htmlspecialchars($job['delivery_info'] ?? 'No specific deadline provided.')) ?></p>
    </div>

    <div class="footer-sign d-flex justify-content-between">
        <div class="sign-box">Received By</div>
        <div class="sign-box">Authorized Signature</div>
    </div>

    <div class="mt-5 text-center text-muted small no-print">
        <hr>
        <p>This is a computer-generated job slip.</p>
    </div>
</div>

</body>
</html>