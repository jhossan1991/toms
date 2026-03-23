<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$currentBranch = isset($_GET['branch']) ? (int)$_GET['branch'] : $_SESSION['branch_id'];

/**
 * 1. AJAX DUPLICATE CHECK
 */
if (isset($_GET['check_duplicate'])) {
    header('Content-Type: application/json');
    $field = $_GET['field'] ?? ''; 
    $value = trim($_GET['value'] ?? '');
    
    if (empty($value) || $value == 'undefined') {
        echo json_encode(['exists' => false]);
        exit;
    }

    try {
        if ($field === 'mobile') {
            $stmt = $pdo->prepare("SELECT name FROM clients 
                                   WHERE (mobile_primary = ? AND mobile_primary != '') 
                                      OR (landline = ? AND landline != '') 
                                   LIMIT 1");
            $stmt->execute([$value, $value]);
            $result = $stmt->fetch();
            echo json_encode(['exists' => (bool)$result, 'name' => $result['name'] ?? '']);
        } elseif ($field === 'email') {
            $stmt = $pdo->prepare("SELECT name FROM clients 
                                   WHERE email = ? AND email != '' 
                                   LIMIT 1");
            $stmt->execute([$value]);
            $result = $stmt->fetch();
            echo json_encode(['exists' => (bool)$result, 'name' => $result['name'] ?? '']);
        }
    } catch (Exception $e) {
        echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/**
 * 2. FORM PROCESSING
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        $client_type = $_POST['client_type'];
        $full_input_name = trim($_POST['full_name']);

        // Both columns get the name now to avoid the 'cannot be null' error
        $name = $full_input_name; 
        $company_name = ($client_type === 'Company') ? $full_input_name : null;
        /* ------------------------ */

        $full_mobile = !empty(trim($_POST['full_mobile'] ?? '')) ? trim($_POST['full_mobile']) : null;
        $full_secondary = !empty(trim($_POST['full_secondary'] ?? '')) ? trim($_POST['full_secondary']) : null;
        $landline = !empty(trim($_POST['phone'] ?? '')) ? trim($_POST['phone']) : null;
        $email = !empty(trim($_POST['email'] ?? '')) ? trim($_POST['email']) : null;
        
        $branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 1;
        $requires_lpo = isset($_POST['requires_lpo']) ? 1 : 0;
        $is_vip = isset($_POST['is_vip']) ? 1 : 0;
        $delivery = isset($_POST['delivery']) ? implode(',', $_POST['delivery']) : null;
        $has_contract = isset($_POST['has_contract']) ? 1 : 0;
        $contract_expiry = ($has_contract && !empty($_POST['contract_expiry'])) ? $_POST['contract_expiry'] : null;

        $nationality = $_POST['nationality'] ?? null;
        $gender = $_POST['gender'] ?? null;
        $qid_passport = $_POST['qid_passport'] ?? null;
        $cr_number = $_POST['cr_number'] ?? null;
        $cr_expiry = !empty($_POST['cr_expiry']) ? $_POST['cr_expiry'] : null;
        $vat_number = $_POST['vat_number'] ?? null;
        $website = $_POST['website'] ?? null;
        $status = $_POST['status'] ?? 'Active';
        $city = $_POST['city'] ?? 'Doha';
        $country = $_POST['country'] ?? 'Qatar';
        $tags = $_POST['tags'] ?? null;

        // Map first contact person name to main table if it exists
        // If it's an Individual, the "Contact Person" is themselves.
        // If it's a Company, take the first person from the dynamic list.
        $main_contact_person = null;
        if ($client_type === 'Individual') {
            $main_contact_person = $full_input_name;
        } elseif (!empty($_POST['contact_names'][0])) {
            $main_contact_person = $_POST['contact_names'][0];
        }

        $file_path = null;
        if ($has_contract && isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] == 0) {
            $upload_dir = 'uploads/contracts/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext = strtolower(pathinfo($_FILES['contract_file']['name'], PATHINFO_EXTENSION));
            $file_name = "contract_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_ext;
            if (move_uploaded_file($_FILES['contract_file']['tmp_name'], $upload_dir . $file_name)) {
                $file_path = $upload_dir . $file_name;
            }
        }

        $sql = "INSERT INTO clients (
                    client_type, name, company_name, contact_person, nationality, gender, qid_passport, cr_number, cr_expiry, vat_number,
                    email, website, landline, mobile_primary, mobile_secondary,
                    address, city, country, delivery_prefs, internal_notes, tags, 
                    is_vip, contract_expiry, contract_file_path, requires_lpo, 
                    has_contract, branch_id, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $client_type, $name, $company_name, $main_contact_person, $nationality, $gender, $qid_passport, 
            $cr_number, $cr_expiry, $vat_number, $email, $website, $landline, $full_mobile, 
            $full_secondary, $_POST['address'], $city, $country, $delivery, 
            $_POST['internal_notes'], $tags, $is_vip, $contract_expiry, $file_path, 
            $requires_lpo, $has_contract, $branch_id, $status
        ]);

        $client_id = $pdo->lastInsertId();

        // SAVE DYNAMIC CONTACT PERSONS / EXTRA PHONES / EMAILS
        if (isset($_POST['contact_types'])) {
            $contactStmt = $pdo->prepare("INSERT INTO client_phones (client_id, contact_name, department, position, phone_number, phone_type, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($_POST['contact_types'] as $key => $type) {
                $phone = !empty(trim($_POST['contact_phones'][$key] ?? '')) ? trim($_POST['contact_phones'][$key]) : null;
                $email_val = !empty(trim($_POST['contact_emails'][$key] ?? '')) ? trim($_POST['contact_emails'][$key]) : null;
                
                if (!empty($phone) || !empty($email_val)) {
                    $c_name = $_POST['contact_names'][$key] ?? null;
                    $c_dept = $_POST['contact_depts'][$key] ?? null;
                    $c_pos  = $_POST['contact_pos'][$key] ?? null;
                    $contactStmt->execute([$client_id, $c_name, $c_dept, $c_pos, $phone, $type, $email_val]);
                }
            }
        }

        $pdo->commit();
        header("Location: clients.php?msg=client_added"); 
        exit;

    } catch (Exception $e) { 
        $pdo->rollBack();
        $message = "<div class='alert alert-danger m-3'><strong>Error:</strong> " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Client | AlHayiki</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
    <style>
        :root { --brand-green: #258d54; }
        body { background-color: #f8f9fa; }
        .section-label { color: var(--brand-green); font-weight: bold; border-bottom: 2px solid #e8f5e9; margin: 20px 0 15px; padding-bottom: 5px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
        .btn-xs { padding: 0.2rem 0.4rem; font-size: 0.75rem; }
        .iti { width: 100%; }
        .dup-warn { display: none; color: #d32f2f; background: #ffebee; padding: 8px; border-radius: 5px; font-size: 0.8rem; margin-top: 5px; border-left: 4px solid #d32f2f; }
        #contract_details { display: none; }
        .contact-row { background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 10px; transition: 0.3s; }
        .contact-row:hover { border-color: var(--brand-green); }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1">
        <nav class="navbar navbar-light bg-white border-bottom px-4 py-3 shadow-sm">
            <h5 class="mb-0"><i class="fas fa-user-plus me-2 text-success"></i>New Client Registration</h5>
        </nav>

        <div class="container-fluid p-4">
            <?= $message ?>
            
            <div class="card shadow-sm border-0">
                <form method="POST" id="clientForm" enctype="multipart/form-data" class="card-body p-4">
                    <input type="hidden" name="branch_id" value="<?= $currentBranch ?>">

                    <div class="row g-3">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <div class="section-label mt-0">Classification & Status</div>
                            <div class="d-flex gap-2">
                                <select name="status" class="form-select form-select-sm border-primary" style="width: 140px;">
                                    <option value="Active">🟢 Active</option>
                                    <option value="On Hold">🟠 On Hold</option>
                                    <option value="Inactive">🔴 Inactive</option>
                                </select>
                                <div class="form-check form-switch bg-warning bg-opacity-10 px-3 py-1 rounded border border-warning border-opacity-25">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_vip" id="vipSwitch" value="1">
                                    <label class="form-check-label fw-bold text-dark" for="vipSwitch">⭐ VIP</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Client Type</label>
                            <select name="client_type" id="client_type" class="form-select" onchange="toggleClientType()">
                                <option value="Individual">Individual</option>
                                <option value="Company">Company</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label id="mainNameLabel" class="form-label small fw-bold">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required placeholder="Enter name">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tags (Comma separated)</label>
                            <input type="text" name="tags" class="form-control" placeholder="VIP, Urgent, Monthly">
                        </div>

                        <div id="individual_fields" class="row g-3 m-0 p-0">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nationality</label>
                                <input type="text" name="nationality" class="form-control" placeholder="e.g. Qatari">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">QID / Passport</label>
                                <input type="text" name="qid_passport" class="form-control" placeholder="ID Number">
                            </div>
                        </div>

                        <div id="company_fields" class="row g-3 m-0 p-0" style="display:none;">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">CR Number</label>
                                <input type="text" name="cr_number" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">CR Expiry</label>
                                <input type="date" name="cr_expiry" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">VAT Number</label>
                                <input type="text" name="vat_number" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Website</label>
                                <input type="url" name="website" class="form-control" placeholder="https://...">
                            </div>
                        </div>

                        <div class="section-label">Contact Details (Primary)</div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Mobile</label>
                            <input type="tel" id="mobile" class="form-control">
                            <input type="hidden" name="full_mobile" id="full_mobile">
                            <div id="mob-dup-msg" class="dup-warn"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" id="email" name="email" class="form-control">
                            <div id="email-dup-msg" class="dup-warn"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Office/Landline</label>
                            <input type="text" name="phone" class="form-control">
                        </div>

                        <div class="section-label d-flex justify-content-between align-items-center">
                            <span id="contactSectionTitle">Additional Mobile / Email</span>
                            <div id="individual_buttons" class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-success btn-xs" onclick="addExtraField('mobile')">
                                    <i class="fas fa-plus me-1"></i> Add Mobile
                                </button>
                                <button type="button" class="btn btn-outline-info btn-xs" onclick="addExtraField('email')">
                                    <i class="fas fa-plus me-1"></i> Add Email
                                </button>
                            </div>
                            <button type="button" id="company_add_btn" class="btn btn-outline-success btn-sm" style="display:none;" onclick="addContactRow()">
                                <i class="fas fa-plus me-1"></i> Add Contact Person
                            </button>
                        </div>
                        <div id="contacts-container"></div>

                        <div class="section-label">Compliance & Contract</div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded bg-white shadow-sm">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="requires_lpo" id="lpoSwitch">
                                <label class="form-check-label fw-bold" for="lpoSwitch">Requires LPO/PO to start work</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded bg-white shadow-sm">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="has_contract" id="contractSwitch">
                                <label class="form-check-label fw-bold" for="contractSwitch">Formal Service Contract exists</label>
                            </div>
                        </div>

                        <div class="col-md-12" id="contract_details">
                            <div class="p-3 border-start border-4 border-success bg-white shadow-sm rounded row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-success fw-bold">Expiry Date</label>
                                    <input type="date" name="contract_expiry" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-success fw-bold">Upload Copy (PDF/IMG)</label>
                                    <input type="file" name="contract_file" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="section-label">Preferences & Address</div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Delivery Methods</label>
                            <div class="p-3 border rounded bg-white d-flex gap-4 flex-wrap">
                                <?php 
                                $options = ["Digital" => "Digital", "Office_Collection" => "Office Collection", "Client_Location" => "Delivery", "Deliver_upon_payment" => "Upon Payment"];
                                foreach($options as $val => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="delivery[]" value="<?= $val ?>" id="<?= $val ?>">
                                        <label class="form-check-label" for="<?= $val ?>"><?= $label ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Address / Street</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">City</label>
                            <input type="text" name="city" class="form-control" value="Doha">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Country</label>
                            <input type="text" name="country" class="form-control" value="Qatar">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Internal Admin Notes (Private)</label>
                            <textarea name="internal_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="mt-5 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="clients.php" class="btn btn-light px-4 border">Cancel</a>
                        <button type="submit" id="submitBtn" class="btn btn-success px-5 fw-bold">Register Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
<script>
    function toggleClientType() {
        const type = document.getElementById('client_type').value;
        const individualBox = document.getElementById('individual_fields');
        const companyBox = document.getElementById('company_fields');
        const mainLabel = document.getElementById('mainNameLabel');
        const contactTitle = document.getElementById('contactSectionTitle');
        const indButtons = document.getElementById('individual_buttons');
        const compBtn = document.getElementById('company_add_btn');

        if (type === 'Company') {
            individualBox.style.display = 'none';
            companyBox.style.display = 'flex';
            mainLabel.innerText = "Company Name *";
            contactTitle.innerText = "Contact Persons (Department/Position)";
            indButtons.style.display = 'none';
            compBtn.style.display = 'block';
        } else {
            individualBox.style.display = 'flex';
            companyBox.style.display = 'none';
            mainLabel.innerText = "Full Name *";
            contactTitle.innerText = "Additional Mobile / Email";
            indButtons.style.display = 'flex';
            compBtn.style.display = 'none';
        }
        document.getElementById('contacts-container').innerHTML = '';
    }

    function addExtraField(fieldType) {
        const container = document.getElementById('contacts-container');
        const newRow = document.createElement('div');
        newRow.className = 'row g-2 mb-2 contact-row shadow-sm border-start border-3 ' + (fieldType === 'mobile' ? 'border-success' : 'border-info');
        
        if (fieldType === 'mobile') {
            newRow.innerHTML = `
                <div class="col-md-10">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-success text-white"><i class="fas fa-phone"></i></span>
                        <input type="tel" name="contact_phones[]" class="form-control" placeholder="Additional Mobile Number" required>
                        <select name="contact_types[]" class="form-select" style="max-width: 100px;">
                            <option value="Mobile">Mobile</option>
                            <option value="WhatsApp">WA</option>
                        </select>
                    </div>
                    <input type="hidden" name="contact_emails[]" value="">
                </div>
                <input type="hidden" name="contact_names[]" value=""><input type="hidden" name="contact_depts[]" value=""><input type="hidden" name="contact_pos[]" value="">
                <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button></div>
            `;
        } else {
            newRow.innerHTML = `
                <div class="col-md-10">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-info text-white"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="contact_emails[]" class="form-control" placeholder="Additional Email Address" required>
                    </div>
                    <input type="hidden" name="contact_phones[]" value="">
                    <input type="hidden" name="contact_types[]" value="Email">
                </div>
                <input type="hidden" name="contact_names[]" value=""><input type="hidden" name="contact_depts[]" value=""><input type="hidden" name="contact_pos[]" value="">
                <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button></div>
            `;
        }
        container.appendChild(newRow);
    }

    function addContactRow() {
        const container = document.getElementById('contacts-container');
        const newRow = document.createElement('div');
        newRow.className = 'row g-2 mb-2 contact-row shadow-sm border-start border-3 border-success';
        newRow.innerHTML = `
            <div class="col-md-2"><input type="text" name="contact_names[]" class="form-control form-control-sm" placeholder="Name" required></div>
            <div class="col-md-2"><input type="text" name="contact_depts[]" class="form-control form-control-sm" placeholder="Dept."></div>
            <div class="col-md-2"><input type="text" name="contact_pos[]" class="form-control form-control-sm" placeholder="Position"></div>
            <div class="col-md-2"><input type="tel" name="contact_phones[]" class="form-control form-control-sm" placeholder="Mobile" required></div>
            <div class="col-md-3"><input type="email" name="contact_emails[]" class="form-control form-control-sm" placeholder="Email"></div>
            <input type="hidden" name="contact_types[]" value="Work">
            <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button></div>
        `;
        container.appendChild(newRow);
    }

    document.querySelector("#contractSwitch").addEventListener("change", function() {
        document.querySelector("#contract_details").style.display = this.checked ? "block" : "none";
    });

    const iti1 = window.intlTelInput(document.querySelector("#mobile"), {
        initialCountry: "qa",
        preferredCountries: ["qa", "sa", "ae"],
        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"
    });

    async function checkDuplicate(field, value, warnId) {
        if (!value || value.trim() === "") return;
        const res = await fetch(`add_client.php?check_duplicate=1&field=${field}&value=${encodeURIComponent(value)}`);
        const data = await res.json();
        const warnBox = document.getElementById(warnId);
        if (data.exists) {
            warnBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> Duplicate found for: <strong>${data.name}</strong>`;
            warnBox.style.display = 'block';
        } else {
            warnBox.style.display = 'none';
        }
    }

    document.querySelector("#mobile").addEventListener('blur', () => {
        if (iti1.isValidNumber()) checkDuplicate('mobile', iti1.getNumber(), 'mob-dup-msg');
    });

    document.querySelector("#email").addEventListener('blur', function() {
        checkDuplicate('email', this.value, 'email-dup-msg');
    });

    document.querySelector("#clientForm").addEventListener("submit", function(e) {
    // 1. Capture the full international number
    const fullMobile = iti1.getNumber();
    document.querySelector("#full_mobile").value = fullMobile;

    // 2. Simple check: if primary mobile is empty and it's an Individual, maybe warn?
    // For now, we just ensure the button shows progress
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Registering...';
    btn.classList.add('disabled'); 
});
</script>
</body>
</html>