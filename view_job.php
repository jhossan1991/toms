<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Job Header
$stmt = $pdo->prepare("SELECT j.*, b.name as branch_name, COALESCE(NULLIF(c.company_name, ''), c.name) AS client_name 
                       FROM jobs j 
                       LEFT JOIN branches b ON j.branch_id = b.id 
                       LEFT JOIN clients c ON j.client_id = c.id
                       WHERE j.id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) die("Job not found.");

// Fetch Job Items
$itemsStmt = $pdo->prepare("SELECT * FROM job_items WHERE job_id = ?");
$itemsStmt->execute([$job_id]);
$items = $itemsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Job | <?= $job['job_no'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; padding: 20px; }
        .card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .status-header { background: #fff; padding: 20px; border-radius: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

<div class="container">
    <div class="status-header shadow-sm">
        <div>
            <h2 class="mb-0">Job #<?= $job['job_no'] ?></h2>
            <span class="badge bg-primary"><?= $job['status'] ?></span>
            <span class="text-muted ms-3"><i class="fas fa-building"></i> <?= $job['branch_name'] ?></span>
        </div>
        <div>
            <a href="edit_job.php?id=<?= $job['id'] ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Job</a>
            <button onclick="cancelJob(<?= $job['id'] ?>)" class="btn btn-outline-danger"><i class="fas fa-ban"></i> Cancel</button>
            <a href="manage_jobs.php" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card p-4">
                <h5 class="fw-bold border-bottom pb-2">Client Details</h5>
                <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($job['client_name']) ?></p>
                <p class="mb-1"><strong>Ref:</strong> <?= htmlspecialchars($job['client_ref'] ?: 'N/A') ?></p>
                <p class="mb-1"><strong>Method:</strong> <?= htmlspecialchars($job['receiving_method']) ?></p>
            </div>
            
            <div class="card p-4 bg-dark text-white">
                <h5 class="fw-bold border-bottom pb-2 text-white">Financial Summary</h5>
                <div class="d-flex justify-content-between mb-2"><span>Total:</span> <span>QR <?= number_format($job['grand_total'], 2) ?></span></div>
                <div class="d-flex justify-content-between mb-2 text-success"><span>Paid:</span> <span>QR <?= number_format($job['amount_paid'], 2) ?></span></div>
                <div class="d-flex justify-content-between fw-bold border-top pt-2"><span>Balance:</span> <span>QR <?= number_format($job['amount_due'], 2) ?></span></div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card p-4">
                <h5 class="fw-bold border-bottom pb-2">Services Rendered</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td><?= $item['service_type'] ?></td>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td class="text-center"><?= $item['qty'] ?></td>
                            <td class="text-end"><?= number_format($item['rate'], 2) ?></td>
                            <td class="text-end"><?= number_format($item['line_total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="mt-3">
                    <label class="fw-bold">Notes / Delivery Info:</label>
                    <div class="p-3 bg-light rounded"><?= nl2br(htmlspecialchars($job['delivery_info'])) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function cancelJob(jobId) {
    if(confirm('Are you sure you want to cancel this job?')) {
        $.post('update_job_action.php', { 
            job_id: jobId, 
            status: 'Cancelled',
            client_id: '<?= $job['client_id'] ?>', // Sending existing data to satisfy backend
            grand_total: '<?= $job['grand_total'] ?>'
        }, function(data) {
            if(data.status === 'success') location.reload();
            else alert(data.message);
        }, 'json');
    }
}

document.addEventListener("DOMContentLoaded", function() {
    // Automatically load Overview tab on page load
    loadTabContent('overview', <?= $clientId ?>);
});

function loadTabContent(tabName, id) {
    const container = document.getElementById(tabName);
    fetch(`fetch_client_overview.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
        });
}

</script>
</body>
</html>