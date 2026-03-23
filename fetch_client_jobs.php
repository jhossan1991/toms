<?php
include 'db.php';
$clientId = $_GET['id'] ?? 0;

if (!$clientId) exit("<div class='alert alert-danger text-center'>Invalid Client ID.</div>");

/** * PLAN SECTION 5: JOB HISTORY
 * Fetching jobs with their associated invoice status
 */
$stmt = $pdo->prepare("
    SELECT j.*, 
           i.invoice_no, 
           i.status as inv_status 
    FROM jobs j
    LEFT JOIN invoices i ON j.invoice_id = i.id
    WHERE j.client_id = ? 
    ORDER BY j.created_at DESC
");
$stmt->execute([$clientId]);
$jobs = $stmt->fetchAll();

// Summary counts for the top of the tab
$totalJobs = count($jobs);
$completedJobs = 0;
foreach($jobs as $job) { if($job['status'] == 'Completed') $completedJobs++; }
?>

<div class="row g-2 mb-4 align-items-center bg-light p-3 rounded border">
    <div class="col-md-3">
        <label class="small fw-bold text-muted">Date Range</label>
        <input type="date" id="job_date_start" class="form-control form-control-sm">
    </div>
    <div class="col-md-3">
        <label class="small fw-bold text-muted">Job Status</label>
        <select id="job_status_filter" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="small fw-bold text-muted">Search Jobs</label>
        <input type="text" id="job_search" class="form-control form-control-sm" placeholder="Ref or Title...">
    </div>
    <div class="col-md-2 text-end">
        <label class="d-block">&nbsp;</label>
        <button class="btn btn-sm btn-secondary w-100" onclick="filterJobs()">
            <i class="fas fa-filter me-1"></i> Filter
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="jobsTable">
            <thead class="bg-light">
                <tr class="small text-uppercase text-muted">
                    <th class="ps-3">Job Ref</th>
                    <th>Date</th>
                    <th>Service / Languages</th>
                    <th>Pages</th>
                    <th>Status</th>
                    <th>Invoiced</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jobs)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No job history found for this client.</td></tr>
                <?php else: foreach ($jobs as $j): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-primary">#<?= $j['job_ref'] ?></td>
                        <td class="small"><?= date('d M Y', strtotime($j['created_at'])) ?></td>
                        <td>
                            <div class="fw-bold small"><?= $j['service_type'] ?></div>
                            <div class="text-muted extra-small">
                                <?= $j['source_lang'] ?> <i class="fas fa-arrow-right mx-1"></i> <?= $j['target_lang'] ?>
                            </div>
                        </td>
                        <td class="small">
                            <span class="text-muted">Recv:</span> <?= $j['pages_received'] ?><br>
                            <span class="text-muted">Bill:</span> <strong><?= $j['billable_pages'] ?></strong>
                        </td>
                        <td>
                            <?php 
                                $s = $j['status'];
                                $badge = 'bg-secondary';
                                if($s == 'Completed') $badge = 'bg-success';
                                if($s == 'In Progress') $badge = 'bg-info text-dark';
                                if($s == 'Cancelled') $badge = 'bg-danger';
                            ?>
                            <span class="badge <?= $badge ?> rounded-pill small"><?= $s ?></span>
                        </td>
                        <td>
                            <?php if($j['invoice_id']): ?>
                                <span class="badge bg-light text-dark border small">
                                    <i class="fas fa-file-invoice me-1"></i> <?= $j['invoice_no'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small italic">Not Invoiced</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group">
                                <a href="view_job.php?id=<?= $j['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a>
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item small" href="#"><i class="fas fa-edit me-2"></i>Edit Job</a></li>
                                    <?php if(!$j['invoice_id']): ?>
                                        <li><a class="dropdown-item small text-primary" href="#"><i class="fas fa-plus me-2"></i>Generate Invoice</a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .extra-small { font-size: 0.75rem; }
    .italic { font-style: italic; }
</style>

<script>
function filterJobs() {
    // Here you would typically re-fetch the content with GET parameters 
    // or use a client-side library like DataTables to filter the existing rows.
    console.log("Filtering logic triggered...");
}
</script>