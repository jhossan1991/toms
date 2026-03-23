<?php
include 'db.php';
session_start();

// Fetch staff for the Manager dropdown
$staff_stmt = $pdo->query("SELECT id, full_name, mobile, email, qid_number FROM staff_profiles WHERE status = 'Active'");
$staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Branch | ALHAYIKI</title>
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
        <form action="save_branch.php" method="POST">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">Add New Branch</h3>
                <div class="btn-group">
                    <a href="branch_list.php" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-success px-4">Save Branch</button>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold"><i class="fas fa-info-circle me-2"></i>Basic Information</div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Branch Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Branch Code</label>
                        <input type="text" name="branch_code" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Branch Type</label>
                        <select name="is_main_branch" class="form-select">
                            <option value="1">Head Office</option>
                            <option value="0" selected>Branch</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Parent Company</label>
                        <input type="text" name="parent_company" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
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
                                <input type="text" name="landline" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Website</label>
                                <input type="text" name="website" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Mobile Number(s)</label>
                                <div id="mobile-container">
                                    <div class="input-group mb-2">
                                        <input type="text" name="mobiles[]" class="form-control" placeholder="Primary Mobile">
                                        <button type="button" class="btn btn-outline-secondary" onclick="addInput('mobile-container', 'mobiles[]')">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Email Address(es)</label>
                                <div id="email-container">
                                    <div class="input-group mb-2">
                                        <input type="email" name="emails[]" class="form-control" placeholder="Email">
                                        <button type="button" class="btn btn-outline-secondary" onclick="addInput('email-container', 'emails[]')">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white fw-bold"><i class="fas fa-user-tie me-2"></i>Branch Manager</div>
                        <div class="card-body row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Select Manager</label>
                                <select name="manager_id" id="managerSelect" class="form-select" onchange="autoFillManager()">
                                    <option value="">-- Select Staff --</option>
                                    <?php foreach($staff_list as $staff): ?>
                                        <option value="<?= $staff['id'] ?>" 
                                                data-mobile="<?= $staff['mobile'] ?>" 
                                                data-email="<?= $staff['email'] ?>" 
                                                data-qid="<?= $staff['qid_number'] ?>">
                                            <?= $staff['full_name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <div class="p-3 bg-light rounded border">
                                    <p class="mb-1 small"><strong>QID:</strong> <span id="mgr_qid">-</span></p>
                                    <p class="mb-1 small"><strong>Mobile:</strong> <span id="mgr_mobile">-</span></p>
                                    <p class="mb-0 small"><strong>Email:</strong> <span id="mgr_email">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Address Details</div>
                <div class="card-body row g-3">
                    <div class="col-md-3"><label class="form-label small fw-bold">Area</label><input type="text" name="area" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Street Name</label><input type="text" name="street_name" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label small fw-bold">Building No.</label><input type="text" name="building_number" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label small fw-bold">Zone</label><input type="text" name="zone_number" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label small fw-bold">City</label><input type="text" name="city" class="form-control" value="Doha"></div>
                    
                    <div class="col-md-2"><label class="form-label small fw-bold">PO Box</label><input type="text" name="po_box" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Kahramaa Number</label><input type="text" name="kahramaa_number" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Water Number</label><input type="text" name="water_number" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Google Map Link</label><input type="url" name="google_maps_link" class="form-control"></div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-contract me-2"></i>Legal & Compliance Documents</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addDocumentRow()">+ Add Other Document</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
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
                                <tr>
                                    <td><input type="text" name="doc_names[]" value="Commercial Registration (CR)" class="form-control form-control-sm" readonly></td>
                                    <td><input type="text" name="doc_numbers[]" class="form-control form-control-sm"></td>
                                    <td><input type="date" name="doc_issue_dates[]" class="form-control form-control-sm"></td>
                                    <td><input type="date" name="doc_expiry_dates[]" class="form-control form-control-sm"></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="doc_names[]" value="Commercial License" class="form-control form-control-sm" readonly></td>
                                    <td><input type="text" name="doc_numbers[]" class="form-control form-control-sm"></td>
                                    <td><input type="date" name="doc_issue_dates[]" class="form-control form-control-sm"></td>
                                    <td><input type="date" name="doc_expiry_dates[]" class="form-control form-control-sm"></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="doc_names[]" value="Civil Defense (Fire Safety)" class="form-control form-control-sm" readonly></td>
                                    <td><input type="text" name="doc_numbers[]" class="form-control form-control-sm"></td>
                                    <td><input type="date" name="doc_issue_dates[]" class="form-control form-control-sm"></td>
                                    <td><input type="date" name="doc_expiry_dates[]" class="form-control form-control-sm"></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="doc_names[]" value="Computer Card" class="form-control form-control-sm" readonly></td>
                                    <td><input type="text" name="doc_numbers[]" class="form-control form-control-sm"></td>
                                    <td><input type="date" name="doc_issue_dates[]" class="form-control form-control-sm"></td>
                                    <td><input type="date" name="doc_expiry_dates[]" class="form-control form-control-sm"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
    container.appendChild(div);
}

function addDocumentRow() {
    const tbody = document.querySelector('#docsTable tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="doc_names[]" class="form-control form-control-sm" placeholder="Document Name"></td>
        <td><input type="text" name="doc_numbers[]" class="form-control form-control-sm"></td>
        <td><input type="date" name="doc_issue_dates[]" class="form-control form-control-sm"></td>
        <td><input type="date" name="doc_expiry_dates[]" class="form-control form-control-sm"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>
    `;
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
async function checkDuplicate(value, type) {
    if(!value) return;
    
    const response = await fetch(`check_duplicate.php?value=${value}&type=${type}`);
    const result = await response.json();
    
    if(result.exists) {
        alert(`This ${type} is already registered to ${result.branch_name}`);
        // Optional: clear the field
    }
}

// Attach to inputs
document.querySelectorAll('input[name="mobiles[]"]').forEach(input => {
    input.addEventListener('blur', (e) => checkDuplicate(e.target.value, 'mobile'));
});
document.querySelectorAll('input[name="emails[]"]').forEach(input => {
    input.addEventListener('blur', (e) => checkDuplicate(e.target.value, 'email'));
});

</script>
</body>
</html>