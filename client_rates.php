<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
if (!$clientId) die("Error: No client ID specified.");

// --- FETCH CLIENT INFO ---
$stmt = $pdo->prepare("SELECT name FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch();
if (!$client) die("Error: Client not found.");

// --- HANDLE POST: SAVE RATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $category = $_POST['service_category'];
    $unit     = $_POST['unit'];
    $rate     = $_POST['rate'];
    $id       = $_POST['rate_id'] ?? ''; // Default to empty string

    if ($category == 'Translation') {
        $source = $_POST['source_lang'];
        $target = $_POST['target_lang'];
        // For Translation, description usually reflects the pair
        $desc   = $source . " to " . $target;
    } else {
        $source = null;
        $target = null;
        $desc   = $_POST['description'];
    }

    // Use !empty to ensure we only UPDATE if a real ID exists
    if (!empty($id)) {
        // --- UPDATE ---
        $sql = "UPDATE client_rates SET 
                service_category=?, 
                source_lang=?, 
                target_lang=?, 
                description=?, 
                unit=?, 
                rate=? 
                WHERE id=? AND client_id=?";
        $pdo->prepare($sql)->execute([$category, $source, $target, $desc, $unit, $rate, $id, $clientId]);
    } else {
        // --- INSERT ---
        $sql = "INSERT INTO client_rates (client_id, service_category, source_lang, target_lang, description, unit, rate) 
                VALUES (?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([$clientId, $category, $source, $target, $desc, $unit, $rate]);
    }
    header("Location: client_rates.php?client_id=$clientId&msg=Rate Updated");
    exit;
}

// --- HANDLE DELETE ---
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM client_rates WHERE id=? AND client_id=?")->execute([$_GET['delete_id'], $clientId]);
    header("Location: client_rates.php?client_id=$clientId&msg=Rate Deleted");
    exit;
}

// --- FETCH RATES ---
$rates = $pdo->prepare("SELECT * FROM client_rates WHERE client_id = ? ORDER BY id DESC");
$rates->execute([$clientId]);
$all_rates = $rates->fetchAll(PDO::FETCH_ASSOC);

$translations = array_filter($all_rates, fn($r) => $r['service_category'] == 'Translation');
$others       = array_filter($all_rates, fn($r) => $r['service_category'] == 'PRO/Other');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Card | <?= htmlspecialchars($client['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-size: 0.85rem; }
        .table-card { border: none; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); margin-bottom: 2rem; }
        .table thead { background: #f8f9fa; border-top: 1px solid #eee; }
        .table thead th { font-weight: 600; text-transform: uppercase; font-size: 0.75rem; color: #666; }
        .category-header { border-left: 4px solid #198754; padding-left: 15px; margin-bottom: 15px; }
        .pro-border { border-left-color: #0d6efd; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Client Rate Card</h2>
            <span class="badge bg-success">Client: <?= htmlspecialchars($client['name']) ?></span>
        </div>
        <div>
            <a href="index.php?view=clients" class="btn btn-outline-secondary btn-sm me-2">Back</a>
            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#rateModal" onclick="resetForm()">
                <i class="fas fa-plus me-1"></i> Add New Rate
            </button>
        </div>
    </div>

    <div class="category-header">
        <h5 class="fw-bold mb-0 text-success">Translation Services</h5>
    </div>
    <div class="card table-card">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Source Language</th>
                    <th>Target Language</th>
                    <th>Rate / Unit</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($translations as $r): ?>
                <tr>
                    <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($r['source_lang']) ?></td>
                    <td class="fw-bold text-primary"><?= htmlspecialchars($r['target_lang']) ?></td>
                    <td>QR <?= number_format($r['rate'], 2) ?> <small class="text-muted">/ <?= htmlspecialchars($r['unit']) ?></small></td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-light border" onclick='editRate(<?= json_encode($r) ?>)'><i class="fas fa-edit text-primary"></i></button>
                        <a href="?client_id=<?= $clientId ?>&delete_id=<?= $r['id'] ?>" class="btn btn-sm btn-light border" onclick="return confirm('Are you sure you want to delete this rate?')"><i class="fas fa-trash text-danger"></i></a>
                    </td>
                </tr>
                <?php endforeach; if(empty($translations)) echo "<tr><td colspan='4' class='text-center p-4 text-muted'>No translations defined.</td></tr>"; ?>
            </tbody>
        </table>
    </div>

    <div class="category-header pro-border">
        <h5 class="fw-bold mb-0 text-primary">PRO Services / Others</h5>
    </div>
    <div class="card table-card">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Description</th>
                    <th>Rate / Unit</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($others as $r): ?>
                <tr>
                    <td class="ps-4 fw-bold"><?= htmlspecialchars($r['description']) ?></td>
                    <td>QR <?= number_format($r['rate'], 2) ?> <small class="text-muted">/ <?= htmlspecialchars($r['unit']) ?></small></td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-light border" onclick='editRate(<?= json_encode($r) ?>)'><i class="fas fa-edit text-primary"></i></button>
                        <a href="?client_id=<?= $clientId ?>&delete_id=<?= $r['id'] ?>" class="btn btn-sm btn-light border" onclick="return confirm('Are you sure?')"><i class="fas fa-trash text-danger"></i></a>
                    </td>
                </tr>
                <?php endforeach; if(empty($others)) echo "<tr><td colspan='3' class='text-center p-4 text-muted'>No PRO services defined.</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="rateModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content shadow border-0">
            <div class="modal-header border-0">
                <h5 class="fw-bold mb-0">Manage Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="save_rate">
                <input type="hidden" name="rate_id" id="rate_id">
                
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Service Type</label>
                    <select name="service_category" id="service_category" class="form-select" onchange="toggleFields()">
                        <option value="Translation">Translation</option>
                        <option value="PRO/Other">PRO Service / Other</option>
                    </select>
                </div>

                <div id="translationFields">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="small fw-bold mb-1">Source Language</label>
                            <input type="text" name="source_lang" id="source_lang" class="form-control" placeholder="English">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold mb-1">Target Language</label>
                            <input type="text" name="target_lang" id="target_lang" class="form-control" placeholder="Arabic">
                        </div>
                    </div>
                </div>

                <div id="proFields" style="display:none;">
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Service Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2" placeholder="e.g. Chamber Attestation"></textarea>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label class="small fw-bold mb-1">Unit</label>
                        <select name="unit" id="unit" class="form-select">
                            <option value="Page">Page</option>
                            <option value="Word">Word</option>
                            <option value="Document">Document</option>
                            <option value="Application">Application</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold mb-1">Rate (QAR)</label>
                        <input type="number" name="rate" id="rate" class="form-control" required step="0.01">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-success px-4">Save Rate</button>
            </div>
        </form>
    </div>
</div>



<script>
function toggleFields() {
    const cat = document.getElementById('service_category').value;
    document.getElementById('translationFields').style.display = (cat === 'Translation') ? 'block' : 'none';
    document.getElementById('proFields').style.display = (cat === 'PRO/Other') ? 'block' : 'none';
}

function editRate(data) {
    document.getElementById('rate_id').value = data.id;
    document.getElementById('service_category').value = data.service_category;
    document.getElementById('source_lang').value = data.source_lang || '';
    document.getElementById('target_lang').value = data.target_lang || '';
    document.getElementById('description').value = data.description || '';
    document.getElementById('unit').value = data.unit;
    document.getElementById('rate').value = data.rate;
    toggleFields();
    new bootstrap.Modal(document.getElementById('rateModal')).show();
}

function resetForm() {
    document.getElementById('rate_id').value = '';
    document.getElementById('source_lang').value = '';
    document.getElementById('target_lang').value = '';
    document.getElementById('description').value = '';
    document.getElementById('rate').value = '';
    document.getElementById('service_category').value = 'Translation';
    toggleFields();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>