<?php
include 'db.php';
$clientId = $_GET['id'] ?? 0;

if (!$clientId) exit("<div class='alert alert-danger text-center'>Invalid Client ID.</div>");

/** * PLAN SECTION 8: RATE CARD MANAGEMENT
 * Fetching rates categorized by Service Type
 */
$stmt = $pdo->prepare("
    SELECT * FROM client_rates 
    WHERE client_id = ? 
    ORDER BY service_category DESC, source_lang ASC
");
$stmt->execute([$clientId]);
$rates = $stmt->fetchAll();

// Grouping for the UI
$translationRates = array_filter($rates, function($r) { return $r['service_category'] == 'Translation'; });
$otherRates = array_filter($rates, function($r) { return $r['service_category'] != 'Translation'; });
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Standard Rate Card</h5>
        <p class="text-muted small mb-0">These rates will auto-fill during Quotation/Job creation.</p>
    </div>
    <button class="btn btn-success shadow-sm" onclick="openRateModal()">
        <i class="fas fa-plus me-1"></i> Add New Rate
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-language me-2"></i>Translation Services</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="extra-small text-uppercase text-muted">
                    <th class="ps-3">Source</th>
                    <th>Target</th>
                    <th>Billing Basis</th>
                    <th>Rate (QAR)</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($translationRates)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted small">No translation pairs defined.</td></tr>
                <?php else: foreach ($translationRates as $r): ?>
                    <tr>
                        <td class="ps-3 fw-bold"><?= $r['source_lang'] ?></td>
                        <td class="fw-bold"><?= $r['target_lang'] ?></td>
                        <td><span class="badge bg-light text-dark border fw-normal"><?= $r['unit'] ?></span></td>
                        <td class="text-success fw-bold"><?= number_format($r['rate'], 2) ?></td>
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

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold text-info"><i class="fas fa-concierge-bell me-2"></i>PRO & Other Services</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="extra-small text-uppercase text-muted">
                    <th class="ps-3">Service Description</th>
                    <th>Billing Basis</th>
                    <th>Unit Rate</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($otherRates)): ?>
                    <tr><td colspan="4" class="text-center py-4 text-muted small">No other services defined.</td></tr>
                <?php else: foreach ($otherRates as $r): ?>
                    <tr>
                        <td class="ps-3 fw-bold"><?= htmlspecialchars($r['description']) ?></td>
                        <td><span class="badge bg-light text-dark border fw-normal"><?= $r['unit'] ?></span></td>
                        <td class="text-success fw-bold"><?= number_format($r['rate'], 2) ?> QAR</td>
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

<style>
    .extra-small { font-size: 0.7rem; letter-spacing: 0.5px; }
</style>

<script>
/**
 * PLAN SECTION 8: DELETE RULE
 * Strict confirmation for deleting rates
 */
function deleteRate(id) {
    Swal.fire({
        title: 'Delete this rate?',
        text: "Existing quotes won't be affected, but new jobs will use default rates.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`delete_rate_ajax.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('Deleted!', 'Rate has been removed.', 'success');
                    loadTabContent('rates');
                }
            });
        }
    });
}
</script>