<?php
include 'db.php';
session_start();

$id = $_GET['id'] ?? null;
if (!$id) die("Branch ID is missing.");

// 1. Fetch Existing Branch Data
$stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
$stmt->execute([$id]);
$b = $stmt->fetch();
if (!$b) die("Branch not found.");

// 2. Fetch staff for the Manager dropdown
$staff_stmt = $pdo->query("SELECT id, full_name, mobile, email, qid_number FROM staff_profiles WHERE status = 'Active'");
$staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Decode JSON fields
$mobiles = json_decode($b['mobile_numbers'], true) ?: [];
$emails = json_decode($b['emails'], true) ?: [];
$docs = json_decode($b['legal_documents'], true) ?: [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Branch | <?= htmlspecialchars($b['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .section-title { border-bottom: 2px solid #258d54; padding-bottom: 5px; margin-bottom: 15px; color: #258d54; font-weight: bold; }
        .form-label.small { font-size: 0.85rem; }
    </style>
</head>
<body class="bg-light">
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="container-fluid p-4">
        <form action="update_branch.php" method="POST">
            <input type="hidden" name="id" value="<?= $b['id'] ?>">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">Edit Branch: <?= htmlspecialchars($b['name']) ?></h3>
                <div class="btn-group">
                    <a href="view_branch.php?id=<?= $id ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Update Branch</button>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold"><i class="fas fa-info-circle me-2"></i>Basic Information</div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Branch Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($b['name']) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Branch Code</label>
                        <input type="text" name="branch_code" class="form-control" value="<?= htmlspecialchars($b['branch_code']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Branch Type</label>
                        <select name="is_main_branch" class="form-select">
                            <option value="1" <?= $b['is_main_branch'] ? 'selected' : '' ?>>Head Office</option>
                            <option value="0" <?= !$b['is_main_branch'] ? 'selected' : '' ?>>Branch</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Parent Company</label>
                        <input type="text" name="parent_company" class="form-control" value="<?= htmlspecialchars($b['parent_company']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" <?= $b['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $b['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white fw-bold"><i class="fas fa-phone me-2"></i>Contact Information</div>
                        <div class="card-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Landline</label>
                                <input type="text" name="landline" class="form-control" value="<?= htmlspecialchars($b['landline']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Website</label>
                                <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($b['website']) ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Mobile Number(s)</label>
                                <div id="mobile-container">
                                    <?php foreach($mobiles as $m): ?>
                                    <div class="input-group mb-2">
                                        <input type="text" name="mobiles[]" class="form-control" value="<?= htmlspecialchars($m) ?>" onblur="checkDuplicate(this.value, 'mobile', <?= $id ?>)">
                                        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">-</button>
                                    </div>
                                    <?php endforeach; ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addInput('mobile-container', 'mobiles[]')">+ Add Mobile</button>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Email Address(es)</label>
                                <div id="email-container">
                                    <?php foreach($emails as $e): ?>
                                    <div class="input-group mb-2">
                                        <input type="email" name="emails[]" class="form-control" value="<?= htmlspecialchars($e) ?>" onblur="checkDuplicate(this.value, 'email', <?= $id ?>)">
                                        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">-</button>
                                    </div>
                                    <?php endforeach; ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addInput('email-container', 'emails[]')">+ Add Email</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white fw-bold"><i class="fas fa-user-tie me-2"></i>Branch Manager</div>
                        <div class="card-body">
                            <select name="manager_id" id="managerSelect" class="form-select mb-3" onchange="autoFillManager()">
                                <option value="">-- Select Manager --</option>
                                <?php foreach($staff_list as $s): ?>
                                    <option value="<?= $s['id'] ?>" 
                                            data-mobile="<?= $s['mobile'] ?>" 
                                            data-email="<?= $s['email'] ?>" 
                                            data-qid="<?= $s['qid_number'] ?>"
                                            <?= $b['manager_id'] == $s['id'] ? 'selected' : '' ?>>
                                        <?= $s['full_name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="p-3 bg-light rounded border">
                                <p class="mb-1 small"><strong>QID:</strong> <span id="mgr_qid">-</span></p>
                                <p class="mb-1 small"><strong>Mobile:</strong> <span id="mgr_mobile">-</span></p>
                                <p class="mb-0 small"><strong>Email:</strong> <span id="mgr_email">-</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Address Details</div>
                <div class="card-body row g-3">
                    <div class="col-md-3"><label class="form-label small fw-bold">Area</label><input type="text" name="area" class="form-control" value="<?= $b['area'] ?>"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Street Name</label><input type="text" name="street_name" class="form-control" value="<?= $b['street_name'] ?>"></div>
                    <div class="col-md-2"><label class="form-label small fw-bold">Building No.</label><input type="text" name="building_number" class="form-control" value="<?= $b['building_number'] ?>"></div>
                    <div class="col-md-2"><label class="form-label small fw-bold">Zone</label><input type="text" name="zone_number" class="form-control" value="<?= $b['zone_number'] ?>"></div>
                    <div class="col-md-2"><label class="form-label small fw-bold">City</label><input type="text" name="city" class="form-control" value="<?= $b['city'] ?>"></div>
                    
                    <div class="col-md-2"><label class="form-label small fw-bold">PO Box</label><input type="text" name="po_box" class="form-control" value="<?= $b['po_box'] ?>"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Kahramaa Number</label><input type="text" name="kahramaa_number" class="form-control" value="<?= $b['kahramaa_number'] ?>"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Water Number</label><input type="text" name="water_number" class="form-control" value="<?= $b['water_number'] ?>"></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Google Map Link</label><input type="url" name="google_maps_link" class="form-control" value="<?= $b['google_maps_link'] ?>"></div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-contract me-2"></i>Legal & Compliance Documents</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addDocumentRow()">+ Add Other Document</button>
                </div>
                <div class="card-body">
                    <table class="table table-bordered align-middle" id="docsTable">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th width="25%">Document Name</th>
                                <th width="25%">Document Number</th>
                                <th width="20%">Issue Date</th>
                                <th width="20%">Expiry Date</th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach($docs as $doc): 
                                $is_readonly = in_array($doc['name'], ['Commercial Registration (CR)', 'Commercial License', 'Civil Defense (Fire Safety)', 'Computer Card']) ? 'readonly' : '';
                            ?>
                            <tr>
                                <td><input type="text" name="doc_names[]" value="<?= $doc['name'] ?>" class="form-control form-control-sm" <?= $is_readonly ?>></td>
                                <td><input type="text" name="doc_numbers[]" value="<?= $doc['number'] ?>" class="form-control form-control-sm"></td>
                                <td><input type="date" name="doc_issue_dates[]" value="<?= $doc['issue_date'] ?>" class="form-control form-control-sm"></td>
                                <td><input type="date" name="doc_expiry_dates[]" value="<?= $doc['expiry_date'] ?>" class="form-control form-control-sm"></td>
                                <td><?php if(!$is_readonly): ?><button type="button" class="btn btn-sm text-danger" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button><?php endif; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function addInput(containerId, name) {
    const container = document.getElementById(containerId);
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `<input type="${name.includes('emails') ? 'email' : 'text'}" name="${name}" class="form-control">
                     <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">-</button>`;
    container.insertBefore(div, container.lastElementChild);
}

function addDocumentRow() {
    const tbody = document.querySelector('#docsTable tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="doc_names[]" class="form-control form-control-sm" placeholder="Document Name"></td>
        <td><input type="text" name="doc_numbers[]" class="form-control form-control-sm"></td>
        <td><input type="date" name="doc_issue_dates[]" class="form-control form-control-sm"></td>
        <td><input type="date" name="doc_expiry_dates[]" class="form-control form-control-sm"></td>
        <td><button type="button" class="btn btn-sm text-danger" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>`;
    tbody.appendChild(row);
}

function autoFillManager() {
    const select = document.getElementById('managerSelect');
    const selectedOption = select.options[select.selectedIndex];
    if(selectedOption.value !== "") {
        document.getElementById('mgr_qid').innerText = selectedOption.getAttribute('data-qid') || 'N/A';
        document.getElementById('mgr_mobile').innerText = selectedOption.getAttribute('data-mobile') || 'N/A';
        document.getElementById('mgr_email').innerText = selectedOption.getAttribute('data-email') || 'N/A';
    } else {
        document.getElementById('mgr_qid').innerText = '-';
        document.getElementById('mgr_mobile').innerText = '-';
        document.getElementById('mgr_email').innerText = '-';
    }
}

async function checkDuplicate(value, type, currentId) {
    if(!value) return;
    const response = await fetch(`check_duplicate.php?value=${value}&type=${type}&exclude_id=${currentId}`);
    const result = await response.json();
    if(result.exists) {
        alert(`Warning: This ${type} is already registered to ${result.branch_name}`);
    }
}

// Initialize manager info on load
window.onload = autoFillManager;
</script>
</body>
</html>