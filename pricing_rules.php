<?php
include 'db.php';
session_start();

// 1. Handle Add Rule with Duplicate Prevention
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_rule'])) {
    $service_id = $_POST['service_id'];
    $language_id = (!empty($_POST['language_id'])) ? $_POST['language_id'] : null;
    $price = $_POST['unit_price'];
    $unit = $_POST['unit_type'];
    $status = $_POST['status'];

    $check = $pdo->prepare("SELECT id FROM pricing_rules WHERE service_id = ? AND (language_id = ? OR (language_id IS NULL AND ? IS NULL))");
    $check->execute([$service_id, $language_id, $language_id]);
    
    if ($check->rowCount() > 0) {
        header("Location: pricing_rules.php?err=duplicate");
    } else {
        $stmt = $pdo->prepare("INSERT INTO pricing_rules (service_id, language_id, unit_price, unit_type, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$service_id, $language_id, $price, $unit, $status]);
        header("Location: pricing_rules.php?msg=added");
    }
    exit();
}

// 2. Handle Update Rule (Fully Editable)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_rule'])) {
    $id = $_POST['rule_id'];
    $service_id = $_POST['service_id'];
    $language_id = (!empty($_POST['language_id'])) ? $_POST['language_id'] : null;
    $price = $_POST['unit_price'];
    $unit = $_POST['unit_type'];
    $status = $_POST['status'];

    $check = $pdo->prepare("SELECT id FROM pricing_rules WHERE service_id = ? AND (language_id = ? OR (language_id IS NULL AND ? IS NULL)) AND id != ?");
    $check->execute([$service_id, $language_id, $language_id, $id]);

    if ($check->rowCount() > 0) {
        header("Location: pricing_rules.php?err=duplicate");
    } else {
        $stmt = $pdo->prepare("UPDATE pricing_rules SET service_id = ?, language_id = ?, unit_price = ?, unit_type = ?, status = ? WHERE id = ?");
        $stmt->execute([$service_id, $language_id, $price, $unit, $status, $id]);
        header("Location: pricing_rules.php?msg=updated");
    }
    exit();
}

// 3. Handle Secure Delete
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'Delete') {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM pricing_rules WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: pricing_rules.php?msg=deleted");
    exit();
}

// Fetch Data
$rules = $pdo->query("SELECT p.*, s.service_name, l.source_language, l.target_language 
                      FROM pricing_rules p
                      JOIN service_types s ON p.service_id = s.id
                      LEFT JOIN language_pairs l ON p.language_id = l.id")->fetchAll();

$services = $pdo->query("SELECT id, service_name FROM service_types WHERE status='Active'")->fetchAll();
$languages = $pdo->query("SELECT id, source_language, target_language FROM language_pairs WHERE status='Active'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pricing Rules | Al Hayiki</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
        <?php if(isset($_GET['err']) && $_GET['err'] == 'duplicate'): ?>
            <div class="alert alert-danger"><b>Error!</b> This pricing combination already exists.</div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold"><i class="fas fa-tags me-2 text-success"></i>Pricing Strategy</h3>
            <div class="d-flex gap-2">
                <div class="input-group" style="width: 300px;">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="tableSearch" class="form-control border-start-0 ps-0" placeholder="Search rules...">
                </div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRuleModal">
                    <i class="fas fa-plus me-2"></i>Set New Price
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0" id="pricingTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Service & Language</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="search-target"><b><?= htmlspecialchars($r['service_name']) ?></b></div>
                                <small class="text-muted search-target">
                                    <?= $r['language_id'] ? ($r['source_language'].' → '.$r['target_language']) : 'Standard Service' ?>
                                </small>
                            </td>
                            <td>Per <?= htmlspecialchars($r['unit_type']) ?></td>
                            <td class="fw-bold text-success"><?= number_format($r['unit_price'], 2) ?> QAR</td>
                            <td><span class="badge rounded-pill bg-<?= $r['status'] == 'Active' ? 'success' : 'secondary' ?>"><?= $r['status'] ?></span></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border edit-rule-btn" 
                                        data-id="<?= $r['id'] ?>" 
                                        data-service="<?= $r['service_id'] ?>"
                                        data-language="<?= $r['language_id'] ?>"
                                        data-price="<?= $r['unit_price'] ?>" 
                                        data-unit="<?= $r['unit_type'] ?>"
                                        data-status="<?= $r['status'] ?>">
                                    <i class="fas fa-edit text-primary"></i>
                                </button>
                                <button class="btn btn-sm btn-light border delete-rule-btn" data-id="<?= $r['id'] ?>" data-name="<?= htmlspecialchars($r['service_name']) ?>">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="noResults" style="display: none;">
                            <td colspan="5" class="text-center py-4 text-muted">No matching pricing rules found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addRuleModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Define Pricing Rule</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Service</label>
                    <select name="service_id" id="serviceSelector" class="form-select searchable" required>
                        <option value="">-- Search Service --</option>
                        <?php foreach($services as $s): ?>
                            <option value="<?= $s['id'] ?>" data-name="<?= strtolower($s['service_name']) ?>"><?= $s['service_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="languageField" class="mb-3" style="display: none;">
                    <label class="form-label fw-bold">Language Pair</label>
                    <select name="language_id" id="languageSelector" class="form-select searchable">
                        <option value="">-- No Language --</option>
                        <?php foreach($languages as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= $l['source_language'] ?> to <?= $l['target_language'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Price (QAR)</label>
                        <input type="number" step="0.01" name="unit_price" class="form-control" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Unit</label>
                        <input list="unitOptions" name="unit_type" class="form-control" placeholder="e.g. Page" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="add_rule" class="btn btn-success w-100">Save Pricing Rule</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editRuleModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5>Update Pricing Rule</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="rule_id" id="edit_rule_id">
                <div class="mb-3">
                    <label class="form-label fw-bold">Service</label>
                    <select name="service_id" id="editServiceSelector" class="form-select searchable" required>
                        <?php foreach($services as $s): ?>
                            <option value="<?= $s['id'] ?>" data-name="<?= strtolower($s['service_name']) ?>"><?= $s['service_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="editLanguageField" class="mb-3">
                    <label class="form-label fw-bold">Language Pair</label>
                    <select name="language_id" id="editLanguageSelector" class="form-select searchable">
                        <option value="">-- No Language --</option>
                        <?php foreach($languages as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= $l['source_language'] ?> to <?= $l['target_language'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Price (QAR)</label>
                        <input type="number" step="0.01" name="unit_price" id="edit_price" class="form-control" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Unit</label>
                        <input list="unitOptions" name="unit_type" id="edit_unit" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="edit_rule" class="btn btn-primary w-100">Update Changes</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" class="modal-content text-center">
            <div class="modal-body p-4">
                <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                <p>To delete pricing for <br><b id="del_name"></b>, type <b>Delete</b>:</p>
                <input type="hidden" name="delete_id" id="del_id">
                <input type="text" name="confirm_delete" class="form-control text-center" placeholder="Delete" required autocomplete="off">
            </div>
            <div class="modal-footer border-0"><button type="submit" class="btn btn-danger w-100">Permanent Delete</button></div>
        </form>
    </div>
</div>

<datalist id="unitOptions">
    <option value="Page"><option value="Word"><option value="Document"><option value="Certificate"><option value="Hour">
</datalist>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Live Table Search Logic
    $("#tableSearch").on("keyup", function() {
        let value = $(this).val().toLowerCase();
        let visibleRows = 0;

        $("#pricingTable tbody tr").not("#noResults").filter(function() {
            let match = $(this).text().toLowerCase().indexOf(value) > -1;
            $(this).toggle(match);
            if(match) visibleRows++;
        });

        // Show "No Results" row if nothing matches
        if(visibleRows === 0) {
            $("#noResults").show();
        } else {
            $("#noResults").hide();
        }
    });

    // 2. Initialize Searchable Dropdowns (Select2)
    $('.searchable').each(function() {
        $(this).select2({
            theme: 'bootstrap-5',
            dropdownParent: $(this).closest('.modal'),
            width: '100%'
        });
    });

    // 3. Language Field Toggle Logic
    function checkTranslation(selectId, fieldId) {
        let name = $(selectId).find(':selected').data('name') || "";
        if (name.includes('translation')) { $(fieldId).show(); } else { $(fieldId).hide(); }
    }

    $('#serviceSelector').on('change', function() { checkTranslation('#serviceSelector', '#languageField'); });
    $('#editServiceSelector').on('change', function() { checkTranslation('#editServiceSelector', '#editLanguageField'); });

    // 4. Edit Button Click
    $(".edit-rule-btn").click(function() {
        const btn = $(this);
        $("#edit_rule_id").val(btn.data('id'));
        $("#edit_price").val(btn.data('price'));
        $("#edit_unit").val(btn.data('unit'));
        $("#edit_status").val(btn.data('status'));
        
        $("#editServiceSelector").val(btn.data('service')).trigger('change');
        $("#editLanguageSelector").val(btn.data('language') || "").trigger('change');
        
        checkTranslation('#editServiceSelector', '#editLanguageField');
        new bootstrap.Modal(document.getElementById('editRuleModal')).show();
    });

    // 5. Delete Button Click
    $(".delete-rule-btn").click(function() {
        $("#del_id").val($(this).data('id'));
        $("#del_name").text($(this).data('name'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
});
</script>
</body>
</html>