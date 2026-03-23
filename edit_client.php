<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? die("ID missing");
$message = "";

// 1. FETCH MAIN CLIENT DATA
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c) { die("Client not found"); }

// 2. FETCH DYNAMIC CONTACTS/PHONES
$contactStmt = $pdo->prepare("SELECT * FROM client_phones WHERE client_id = ?");
$contactStmt->execute([$id]);
$extraContacts = $contactStmt->fetchAll();

$currentBranch = $c['branch_id'];

// PREPARE DELIVERY PREFS FOR CHECKBOXES
$saved_prefs = !empty($c['delivery_prefs']) ? explode(',', $c['delivery_prefs']) : [];
$saved_prefs = array_map('trim', $saved_prefs);

/**
 * 3. UPDATE PROCESSING
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        $full_mobile = !empty(trim($_POST['full_mobile'] ?? '')) ? trim($_POST['full_mobile']) : null;
        $full_secondary = !empty(trim($_POST['full_secondary'] ?? '')) ? trim($_POST['full_secondary']) : null;
        $landline = !empty($_POST['phone'] ?? '') ? trim($_POST['phone']) : null;
        $email = !empty($_POST['email'] ?? '') ? trim($_POST['email']) : null;
        
        $requires_lpo = isset($_POST['requires_lpo']) ? 1 : 0;
        $is_vip = isset($_POST['is_vip']) ? 1 : 0;
        
        // Handle Delivery Array to String
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
        $internal_notes = $_POST['internal_notes'] ?? null;

        // Handle File Update
        $file_path = $c['contract_file_path']; 
        if ($has_contract && isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] == 0) {
            $upload_dir = 'uploads/contracts/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext = strtolower(pathinfo($_FILES['contract_file']['name'], PATHINFO_EXTENSION));
            $file_name = "contract_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_ext;
            if (move_uploaded_file($_FILES['contract_file']['tmp_name'], $upload_dir . $file_name)) {
                $file_path = $upload_dir . $file_name;
            }
        }

        $sql = "UPDATE clients SET 
                    client_type = ?, name = ?, nationality = ?, gender = ?, qid_passport = ?, 
                    cr_number = ?, cr_expiry = ?, vat_number = ?, email = ?, website = ?, 
                    landline = ?, mobile_primary = ?, mobile_secondary = ?, address = ?, 
                    city = ?, country = ?, delivery_prefs = ?, internal_notes = ?, tags = ?, 
                    is_vip = ?, contract_expiry = ?, contract_file_path = ?, requires_lpo = ?, 
                    has_contract = ?, status = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['client_type'], $_POST['full_name'], $nationality, $gender, $qid_passport, 
            $cr_number, $cr_expiry, $vat_number, $email, $website, $landline, $full_mobile, 
            $full_secondary, $_POST['address'], $city, $country, $delivery, 
            $internal_notes, $tags, $is_vip, $contract_expiry, $file_path, 
            $requires_lpo, $has_contract, $status, $id
        ]);

        // REFRESH EXTRA CONTACTS
        $pdo->prepare("DELETE FROM client_phones WHERE client_id = ?")->execute([$id]);
        if (isset($_POST['contact_types'])) {
            $contactStmt = $pdo->prepare("INSERT INTO client_phones (client_id, contact_name, department, position, phone_number, phone_type, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['contact_types'] as $key => $type) {
                $phone = !empty(trim($_POST['contact_phones'][$key] ?? '')) ? trim($_POST['contact_phones'][$key]) : null;
                $email_val = !empty(trim($_POST['contact_emails'][$key] ?? '')) ? trim($_POST['contact_emails'][$key]) : null;
                
                if ($phone !== null || $email_val !== null) {
                    $c_name = $_POST['contact_names'][$key] ?? null;
                    $c_dept = $_POST['contact_depts'][$key] ?? null;
                    $c_pos  = $_POST['contact_pos'][$key] ?? null;
                    $contactStmt->execute([$id, $c_name, $c_dept, $c_pos, $phone, $type, $email_val]);
                }
            }
        }

        $pdo->commit();
        header("Location: clients.php?msg=client_updated"); 
        exit;

    } catch (Exception $e) { 
        $pdo->rollBack();
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Client | AlHayiki</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
    <style>
        :root { --brand-green: #258d54; }
        body { background-color: #f8f9fa; }
        .section-label { color: var(--brand-green); font-weight: bold; border-bottom: 2px solid #e8f5e9; margin: 20px 0 15px; padding-bottom: 5px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
        .iti { width: 100%; }
        .contact-row { background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 10px; }
        #contract_details { display: <?= $c['has_contract'] ? 'block' : 'none' ?>; }
        .pref-card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="flex-grow-1">
        <nav class="navbar navbar-light bg-white border-bottom px-4 py-3 shadow-sm">
            <h5 class="mb-0"><i class="fas fa-user-edit me-2 text-success"></i>Edit Client: <?= htmlspecialchars($c['name']) ?></h5>
        </nav>

        <div class="container-fluid p-4">
            <?= $message ?>
            <div class="card shadow-sm border-0">
                <form method="POST" id="clientForm" enctype="multipart/form-data" class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <div class="section-label mt-0">Classification & Status</div>
                            <div class="d-flex gap-2">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="Active" <?= $c['status']=='Active'?'selected':'' ?>>🟢 Active</option>
                                    <option value="On Hold" <?= $c['status']=='On Hold'?'selected':'' ?>>🟠 On Hold</option>
                                    <option value="Inactive" <?= $c['status']=='Inactive'?'selected':'' ?>>🔴 Inactive</option>
                                </select>
                                <div class="form-check form-switch bg-warning bg-opacity-10 px-3 py-1 rounded border">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_vip" id="vipSwitch" value="1" <?= $c['is_vip']?'checked':'' ?>>
                                    <label class="form-check-label fw-bold" for="vipSwitch">⭐ VIP</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Client Type</label>
                            <select name="client_type" id="client_type" class="form-select" onchange="toggleClientType()">
                                <option value="Individual" <?= $c['client_type']=='Individual'?'selected':'' ?>>Individual</option>
                                <option value="Company" <?= $c['client_type']=='Company'?'selected':'' ?>>Company</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label id="mainNameLabel" class="form-label small fw-bold">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($c['name']) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tags</label>
                            <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars($c['tags'] ?? '') ?>">
                        </div>

                        <div id="individual_fields" class="row g-3 m-0 p-0" style="<?= $c['client_type']=='Company'?'display:none;':'' ?>">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="<?= htmlspecialchars($c['nationality'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select</option>
                                    <option value="Male" <?= $c['gender']=='Male'?'selected':'' ?>>Male</option>
                                    <option value="Female" <?= $c['gender']=='Female'?'selected':'' ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">QID / Passport</label>
                                <input type="text" name="qid_passport" class="form-control" value="<?= htmlspecialchars($c['qid_passport'] ?? '') ?>">
                            </div>
                        </div>

                        <div id="company_fields" class="row g-3 m-0 p-0" style="<?= $c['client_type']=='Individual'?'display:none;':'' ?>">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">CR Number</label>
                                <input type="text" name="cr_number" class="form-control" value="<?= htmlspecialchars($c['cr_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">CR Expiry</label>
                                <input type="date" name="cr_expiry" class="form-control" value="<?= $c['cr_expiry'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">VAT Number</label>
                                <input type="text" name="vat_number" class="form-control" value="<?= htmlspecialchars($c['vat_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Website</label>
                                <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($c['website'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="section-label">Contact Details (Primary)</div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Mobile</label>
                            <input type="tel" id="mobile" class="form-control" value="<?= htmlspecialchars($c['mobile_primary']) ?>">
                            <input type="hidden" name="full_mobile" id="full_mobile">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($c['email']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Office/Landline</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($c['landline']) ?>">
                        </div>

                        <div class="section-label d-flex justify-content-between align-items-center">
                            <span id="contactSectionTitle">Additional Contact Info</span>
                            <div id="individual_buttons" class="d-flex gap-2" style="<?= $c['client_type']=='Company'?'display:none;':'' ?>">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="addExtraField('mobile')"><i class="fas fa-plus"></i> Add Mobile</button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="addExtraField('email')"><i class="fas fa-plus"></i> Add Email</button>
                            </div>
                            <button type="button" id="company_add_btn" class="btn btn-outline-success btn-sm" style="<?= $c['client_type']=='Individual'?'display:none;':'' ?>" onclick="addContactRow()"><i class="fas fa-plus"></i> Add Contact Person</button>
                        </div>

                        <div id="contacts-container">
                            <?php foreach($extraContacts as $row): ?>
                                <?php if($c['client_type'] == 'Individual'): ?>
                                    <div class="row g-2 mb-2 contact-row border-start border-3 <?= $row['phone_type']=='Email' ? 'border-info' : 'border-success' ?>">
                                        <div class="col-md-10">
                                            <div class="input-group input-group-sm">
                                                <?php if($row['phone_type'] == 'Email'): ?>
                                                    <span class="input-group-text bg-info text-white"><i class="fas fa-envelope"></i></span>
                                                    <input type="email" name="contact_emails[]" class="form-control" value="<?= htmlspecialchars($row['email']) ?>" required>
                                                    <input type="hidden" name="contact_phones[]" value="">
                                                    <input type="hidden" name="contact_types[]" value="Email">
                                                <?php else: ?>
                                                    <span class="input-group-text bg-success text-white"><i class="fas fa-phone"></i></span>
                                                    <input type="tel" name="contact_phones[]" class="form-control" value="<?= htmlspecialchars($row['phone_number']) ?>" required>
                                                    <select name="contact_types[]" class="form-select" style="max-width: 100px;">
                                                        <option value="Mobile" <?= $row['phone_type']=='Mobile'?'selected':'' ?>>Mobile</option>
                                                        <option value="WhatsApp" <?= $row['phone_type']=='WhatsApp'?'selected':'' ?>>WA</option>
                                                    </select>
                                                    <input type="hidden" name="contact_emails[]" value="">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <input type="hidden" name="contact_names[]" value=""><input type="hidden" name="contact_depts[]" value=""><input type="hidden" name="contact_pos[]" value="">
                                        <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button></div>
                                    </div>
                                <?php else: ?>
                                    <div class="row g-2 mb-2 contact-row border-start border-3 border-success">
                                        <div class="col-md-2"><input type="text" name="contact_names[]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['contact_name']) ?>" required></div>
                                        <div class="col-md-2"><input type="text" name="contact_depts[]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['department']) ?>"></div>
                                        <div class="col-md-2"><input type="text" name="contact_pos[]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['position']) ?>"></div>
                                        <div class="col-md-2"><input type="tel" name="contact_phones[]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['phone_number']) ?>" required></div>
                                        <div class="col-md-3"><input type="email" name="contact_emails[]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['email']) ?>"></div>
                                        <input type="hidden" name="contact_types[]" value="Work">
                                        <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button></div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="section-label">Compliance & Contract</div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded bg-white">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="requires_lpo" id="lpoSwitch" <?= $c['requires_lpo']?'checked':'' ?>>
                                <label class="form-check-label fw-bold" for="lpoSwitch">Requires LPO / PO</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded bg-white">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="has_contract" id="contractSwitch" <?= $c['has_contract']?'checked':'' ?>>
                                <label class="form-check-label fw-bold" for="contractSwitch">Active Service Contract</label>
                            </div>
                        </div>

                        <div class="col-md-12" id="contract_details">
                            <div class="p-3 border-start border-4 border-success bg-white row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-success fw-bold">Contract Expiry</label>
                                    <input type="date" name="contract_expiry" class="form-control" value="<?= $c['contract_expiry'] ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-success fw-bold">Upload New Contract (PDF/Image)</label>
                                    <input type="file" name="contract_file" class="form-control">
                                    <?php if($c['contract_file_path']): ?>
                                        <div class="mt-2 small"><a href="<?= $c['contract_file_path'] ?>" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i> View Current Contract</a></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="section-label">Preferences & Internal Admin Notes</div>
                        
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Delivery Preferences</label>
                            <div class="pref-card d-flex flex-wrap gap-4">
                                <?php 
                                $delivery_options = [
                                    "Digital" => "Digital (Email/WA)", 
                                    "Office_Collection" => "Office Collection", 
                                    "Client_Location" => "Physical Delivery", 
                                    "Deliver_upon_payment" => "Deliver only after Payment"
                                ];
                                foreach($delivery_options as $val => $label): 
                                    $checked = in_array($val, $saved_prefs) ? 'checked' : '';
                                ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="delivery[]" value="<?= $val ?>" id="del_<?= $val ?>" <?= $checked ?>>
                                        <label class="form-check-label" for="del_<?= $val ?>"><?= $label ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Internal Admin Notes (Private)</label>
                            <textarea name="internal_notes" class="form-control" rows="3" placeholder="Add specific instructions, payment history notes, or special handling..."><?= htmlspecialchars($c['internal_notes'] ?? '') ?></textarea>
                        </div>

                        <div class="section-label">Location Address</div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Full Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($c['address']) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">City</label>
                            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($c['city'] ?? 'Doha') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Country</label>
                            <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($c['country'] ?? 'Qatar') ?>">
                        </div>
                    </div>

                    <div class="mt-5 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="manage_jobs.php" class="btn btn-light px-4 border">Cancel</a>
                        <button type="submit" class="btn btn-success px-5 fw-bold">Update Client Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
    }

    function addExtraField(fieldType) {
        const container = document.getElementById('contacts-container');
        const newRow = document.createElement('div');
        newRow.className = 'row g-2 mb-2 contact-row border-start border-3 ' + (fieldType === 'mobile' ? 'border-success' : 'border-info');
        if (fieldType === 'mobile') {
            newRow.innerHTML = `<div class="col-md-10"><div class="input-group input-group-sm"><span class="input-group-text bg-success text-white"><i class="fas fa-phone"></i></span><input type="tel" name="contact_phones[]" class="form-control" placeholder="Mobile" required><select name="contact_types[]" class="form-select" style="max-width: 100px;"><option value="Mobile">Mobile</option><option value="WhatsApp">WA</option></select></div><input type="hidden" name="contact_emails[]" value=""></div><input type="hidden" name="contact_names[]" value=""><input type="hidden" name="contact_depts[]" value=""><input type="hidden" name="contact_pos[]" value=""><div class="col-md-2"><button type="button" class="btn btn-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button></div>`;
        } else {
            newRow.innerHTML = `<div class="col-md-10"><div class="input-group input-group-sm"><span class="input-group-text bg-info text-white"><i class="fas fa-envelope"></i></span><input type="email" name="contact_emails[]" class="form-control" placeholder="Email" required></div><input type="hidden" name="contact_phones[]" value=""><input type="hidden" name="contact_types[]" value="Email"></div><input type="hidden" name="contact_names[]" value=""><input type="hidden" name="contact_depts[]" value=""><input type="hidden" name="contact_pos[]" value=""><div class="col-md-2"><button type="button" class="btn btn-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button></div>`;
        }
        container.appendChild(newRow);
    }

    function addContactRow() {
        const container = document.getElementById('contacts-container');
        const newRow = document.createElement('div');
        newRow.className = 'row g-2 mb-2 contact-row border-start border-3 border-success';
        newRow.innerHTML = `<div class="col-md-2"><input type="text" name="contact_names[]" class="form-control form-control-sm" placeholder="Name" required></div><div class="col-md-2"><input type="text" name="contact_depts[]" class="form-control form-control-sm" placeholder="Dept."></div><div class="col-md-2"><input type="text" name="contact_pos[]" class="form-control form-control-sm" placeholder="Pos."></div><div class="col-md-2"><input type="tel" name="contact_phones[]" class="form-control form-control-sm" placeholder="Mobile" required></div><div class="col-md-3"><input type="email" name="contact_emails[]" class="form-control form-control-sm" placeholder="Email"></div><input type="hidden" name="contact_types[]" value="Work"><div class="col-md-1"><button type="button" class="btn btn-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button></div>`;
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

    document.querySelector("#clientForm").addEventListener("submit", function() {
        document.querySelector("#full_mobile").value = iti1.getNumber();
    });
</script>
</body>
</html>