<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Quotation ID is missing.");
}

$quote_id = (int)$_GET['id'];

// Fetch Main Quotation Data
$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
$stmt->execute([$quote_id]);
$quote = $stmt->fetch();

if (!$quote) {
    die("Quotation not found.");
}

// Fetch Quotation Items
$stmtItems = $pdo->prepare("SELECT * FROM quotation_items WHERE quote_id = ?");
$stmtItems->execute([$quote_id]);
$items = $stmtItems->fetchAll();

// Fetch Dropdown Data
$clients = $pdo->query("SELECT id, COALESCE(NULLIF(company_name, ''), name) AS client_name, mobile_primary AS mobile, email, address, internal_notes, requires_lpo, has_contract, contact_person AS attention_person FROM clients WHERE is_active = 1 ORDER BY client_name ASC")->fetchAll();
$whatsappNumbers = $pdo->query("SELECT id, phone_number FROM whatsapp_numbers WHERE is_active = 1 ORDER BY phone_number")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Quotation | <?= htmlspecialchars($quote['quote_no']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .form-card { border-radius: 15px; border: none; box-shadow: 0 5px 25px rgba(0,0,0,0.1); background: #fff; padding: 30px; }
        .client-display-card { background: #fdfdfd; border: 1px solid #dee2e6; border-left: 5px solid #0d6efd; border-radius: 8px; padding: 15px; margin-top: 15px; }
        .table thead th { background: #f8f9fa; font-size: 0.8rem; }
        .editable-terms { background: #fffdf0; border: 1px dashed #ffc107; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid form-card">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold text-primary mb-0">Edit Quotation</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="manage_quotations.php" class="text-decoration-none">Quotation Manager</a></li>
                    <li class="breadcrumb-item active">Edit #<?= htmlspecialchars($quote['quote_no']) ?></li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="manage_quotations.php" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-times me-1"></i> Cancel
            </a>
        </div>
    </div>

    <form id="editQuoteForm">
        <input type="hidden" name="quote_id" value="<?= $quote['id'] ?>">
        <input type="hidden" name="job_no" value="<?= htmlspecialchars($quote['quote_no']) ?>">

        <div class="row mb-4">
            <div class="col-md-7">
                <label class="small fw-bold">SELECT CLIENT:</label>
                <select name="client_id" id="clientSelect" class="form-select" required>
                    <option value="">-- Search --</option>
                    <?php foreach($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" 
                            <?= ($quote['client_id'] == $c['id']) ? 'selected' : '' ?>
                            data-name="<?= htmlspecialchars($c['client_name']) ?>" 
                            data-address="<?= htmlspecialchars($c['address'] ?? '') ?>" 
                            data-mobile="<?= htmlspecialchars($c['mobile'] ?? '') ?>"
                            data-email="<?= htmlspecialchars($c['email'] ?? '') ?>"
                            data-attention="<?= htmlspecialchars($c['attention_person'] ?? 'None') ?>"
                            data-contract="<?= $c['has_contract'] ? 'Active Contract' : 'No active contract' ?>"
                            data-lpo="<?= $c['requires_lpo'] ? 'LPO REQUIRED' : 'Not Required' ?>"
                            data-notes="<?= htmlspecialchars($c['internal_notes'] ?? 'None') ?>">
                            <?= htmlspecialchars($c['client_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div id="clientDisplay" class="client-display-card mt-3">
                    <h5 id="view_company" class="fw-bold text-primary mb-2"></h5>
                    <div class="row small">
                        <div class="col-md-6">
                            <p class="mb-1"><i class="fas fa-map-marker-alt me-2 text-muted"></i><strong>Address:</strong> <span id="view_address"></span></p>
                            <p class="mb-1"><i class="fas fa-user me-2 text-muted"></i><strong>Attention:</strong> <span id="view_attention"></span></p>
                            <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i><strong>Mobile:</strong> <span id="view_mobile"></span></p>
                            <p class="mb-1"><i class="fas fa-envelope me-2 text-muted"></i><strong>Email:</strong> <span id="view_email"></span></p>
                        </div>
                        <div class="col-md-6 border-start">
                            <p class="mb-1"><i class="fas fa-file-contract me-2 text-muted"></i><strong>Contract:</strong> <br><span class="text-danger" id="view_contract"></span></p>
                            <p class="mb-1"><i class="fas fa-receipt me-2 text-muted"></i><strong>LPO:</strong> <span id="view_lpo"></span></p>
                            <p class="mb-1"><i class="fas fa-sticky-note me-2 text-muted"></i><strong>Notes:</strong> <span id="view_notes"></span></p>
                        </div>
                    </div>
                </div>
                
                <input type="text" name="quotation_for" class="form-control mt-3" placeholder="Subject" value="<?= htmlspecialchars($quote['quotation_for']) ?>">
            </div>
            
            <div class="col-md-5 text-end">
                <p class="mb-1 fw-bold">QUOTE NO: <?= htmlspecialchars($quote['quote_no']) ?></p>
                <div class="d-flex justify-content-end align-items-center mt-2">
                    <label class="me-2 fw-bold small"> CLIENT REF:</label>
                    <input type="text" name="client_ref" class="form-control form-control-sm w-50" value="<?= htmlspecialchars($quote['client_ref']) ?>">
                </div>
                <p class="me-2 fw-bold small mt-2"> DATE: <?= date('d-M-Y', strtotime($quote['created_at'])) ?></p>
                
                <div class="d-flex justify-content-end align-items-center mt-2">
                    <label class="me-2 fw-bold small">VALID UNTIL:</label>
                    <input type="date" name="valid_until" class="form-control form-control-sm w-50" value="<?= $quote['valid_until'] ?>">
                </div>

                <div class="d-flex justify-content-end align-items-center mt-2">
                    <label class="me-2 fw-bold small">RECEIVING:</label>
                    <select name="receiving_method" id="receiving_method" class="form-select form-select-sm w-50" onchange="toggleWhatsapp(this.value)">
                        <option value="Walk-In" <?= $quote['receiving_method'] == 'Walk-In' ? 'selected' : '' ?>>Walk-In</option>
                        <option value="Email" <?= $quote['receiving_method'] == 'Email' ? 'selected' : '' ?>>Email</option>
                        <option value="WhatsApp" <?= $quote['receiving_method'] == 'WhatsApp' ? 'selected' : '' ?>>WhatsApp</option>
                    </select>
                </div>
                <div class="d-flex justify-content-end align-items-center mt-2 <?= $quote['receiving_method'] == 'WhatsApp' ? '' : 'd-none' ?>" id="whatsapp_box">
                    <label class="me-2 fw-bold small text-success"><i class="fab fa-whatsapp"></i> RECEIVED BY:</label>
                    <select name="whatsapp_number_id" id="whatsapp_number_id" class="form-select form-select-sm w-50">
                        <?php foreach($whatsappNumbers as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= $quote['whatsapp_number_id'] == $w['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($w['phone_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead>
                    <tr class="text-center">
                        <th width="15%">Service</th>
                        <th width="25%">Description</th>
                        <th width="10%">Actual Pgs</th>
                        <th width="10%">Final Qty</th>
                        <th width="10%">Unit</th> 
                        <th width="10%">Rate</th>
                        <th width="15%">Total</th>
                        <th width="5%"></th>
                    </tr>
                </thead>
                <tbody id="quoteItems">
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td>
                            <select name="type[]" class="form-select form-select-sm" onchange="handleTypeChange(this)">
                                <option value="Translation" <?= $item['service_type'] == 'Translation' ? 'selected' : '' ?>>Translation</option>
                                <option value="Attestation" <?= $item['service_type'] == 'Attestation' ? 'selected' : '' ?>>Attestation</option>
                                <option value="Services" <?= $item['service_type'] == 'Services' ? 'selected' : '' ?>>Services</option>
                            </select>
                        </td>
                        <td>
                            <div class="translation-fields <?= $item['service_type'] == 'Translation' ? 'd-flex' : 'd-none' ?> gap-1">
                                <?php 
                                    $langs = explode(' to ', $item['description']);
                                    $src = $langs[0] ?? '';
                                    $tgt = $langs[1] ?? '';
                                ?>
                                <input type="text" name="src_lang[]" class="form-control form-control-sm" placeholder="From" value="<?= htmlspecialchars($src) ?>">
                                <input type="text" name="target_lang[]" class="form-control form-control-sm" placeholder="To" value="<?= htmlspecialchars($tgt) ?>">
                            </div>
                            <input type="text" name="pro_desc[]" class="form-control form-control-sm pro_desc <?= $item['service_type'] == 'Translation' ? 'd-none' : '' ?>" placeholder="Description" value="<?= htmlspecialchars($item['description']) ?>">
                        </td>
                        <td><input type="number" name="actual_qty[]" class="form-control form-control-sm text-center" value="<?= $item['pages_s'] ?>"></td>
                        <td><input type="number" name="qty[]" class="form-control form-control-sm qty text-center fw-bold" value="<?= $item['qty'] ?>" oninput="calculate()"></td>
                        <td>
                            <select name="unit[]" class="form-select form-select-sm">
                                <option value="Page" <?= $item['unit'] == 'Page' ? 'selected' : '' ?>>Page</option>
                                <option value="Word" <?= $item['unit'] == 'Word' ? 'selected' : '' ?>>Word</option>
                                <option value="Doc" <?= $item['unit'] == 'Doc' ? 'selected' : '' ?>>Doc</option>
                                <option value="Set" <?= $item['unit'] == 'Set' ? 'selected' : '' ?>>Set</option>
                            </select>
                        </td>
                        <td><input type="number" name="rate[]" step="0.01" class="form-control form-control-sm rate text-center" value="<?= $item['rate'] ?>" oninput="calculate()"></td>
                        <td><input type="text" class="form-control-plaintext text-end line-total fw-bold pe-2" readonly value="<?= number_format($item['total'], 2) ?>"></td>
                        <td><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">+ Add Line</button>
                <button type="button" class="btn btn-success btn-sm shadow-sm" id="btn_view_rates" data-bs-toggle="modal" data-bs-target="#ratesModal">
                    <i class="fas fa-tags me-1"></i> Rates
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="mb-3">
                    <label class="fw-bold small text-primary"><i class="fas fa-calendar-alt me-1"></i> DEADLINE:</label>
                    <input type="datetime-local" name="deadline" class="form-control form-control-sm w-50" value="<?= $quote['deadline'] ? date('Y-m-d\TH:i', strtotime($quote['deadline'])) : '' ?>">
                </div>
                <div class="mb-3">
                    <label class="fw-bold small text-primary">REMARKS:</label>
                    <textarea name="additional_notes" class="form-control" rows="3"><?= htmlspecialchars($quote['additional_notes']) ?></textarea>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card border-0 bg-light p-3 shadow-sm">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold text-muted">Subtotal:</span>
                        <span>QR <span id="subtotal_display"><?= number_format($quote['sub_total'], 2) ?></span></span>
                        <input type="hidden" name="sub_total" id="sub_total_val" value="<?= $quote['sub_total'] ?>">
                    </div>
                    <div class="d-flex justify-content-between mb-2 align-items-center">
                        <span class="fw-bold text-muted">Discount:</span>
                        <input type="number" name="discount" id="discount" class="form-control form-control-sm w-50 text-end" value="<?= $quote['discount'] ?>" oninput="calculate()">
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold text-success fs-5">
                        <span>Grand Total:</span>
                        <span>QR <span id="grand_total_display"><?= number_format($quote['grand_total'], 2) ?></span></span>
                        <input type="hidden" name="grand_total" id="grand_total_val" value="<?= $quote['grand_total'] ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg mt-3 w-100 shadow-sm">
                    <i class="fas fa-save me-2"></i> Update Quotation
                </button>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="ratesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light"><h5 class="modal-title fw-bold">Client Rates</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><table class="table table-sm"><thead class="table-dark"><tr><th>Service</th><th>Rate</th><th>Action</th></tr></thead><tbody id="rates_table_body"></tbody></table></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    


$(document).ready(function() {
    $('#clientSelect').select2({ theme: 'bootstrap-5' }).on('change', function() {
        const opt = $(this).find(':selected');
        if($(this).val()){
            $('#view_company').text(opt.data('name'));
            $('#view_address').text(opt.data('address'));
            $('#view_mobile').text(opt.data('mobile'));
            $('#view_email').text(opt.data('email'));
            $('#view_attention').text(opt.data('attention'));
            $('#view_contract').text(opt.data('contract'));
            $('#view_lpo').text(opt.data('lpo'));
            $('#view_notes').text(opt.data('notes'));
            $('#clientDisplay').show();
            fetchClientDetails($(this).val());
        }
    }).trigger('change');
});

function calculate() {
    let sub = 0;
    $('#quoteItems tr').each(function() {
        let q = parseFloat($(this).find('.qty').val()) || 0;
        let r = parseFloat($(this).find('.rate').val()) || 0;
        let total = q * r;
        $(this).find('.line-total').val(total.toFixed(2));
        sub += total;
    });
    $('#subtotal_display').text(sub.toFixed(2));
    $('#sub_total_val').val(sub.toFixed(2));
    let disc = parseFloat($('#discount').val()) || 0;
    let grand = Math.max(0, sub - disc);
    $('#grand_total_display').text(grand.toFixed(2));
    $('#grand_total_val').val(grand.toFixed(2));
}

function handleTypeChange(select) {
    const row = $(select).closest('tr');
    if (select.value === 'Translation') {
        row.find('.translation-fields').removeClass('d-none').addClass('d-flex');
        row.find('.pro_desc').addClass('d-none');
    } else {
        row.find('.translation-fields').addClass('d-none').removeClass('d-flex');
        row.find('.pro_desc').removeClass('d-none');
    }
}

function addRow() {
    const row = `<tr>
        <td><select name="type[]" class="form-select form-select-sm" onchange="handleTypeChange(this)"><option value="Translation">Translation</option><option value="Attestation">Attestation</option><option value="Services">Services</option></select></td>
        <td><div class="translation-fields d-flex gap-1"><input type="text" name="src_lang[]" class="form-control form-control-sm" placeholder="From"><input type="text" name="target_lang[]" class="form-control form-control-sm" placeholder="To"></div><input type="text" name="pro_desc[]" class="form-control form-control-sm pro_desc d-none" placeholder="Description"></td>
        <td><input type="number" name="actual_qty[]" class="form-control form-control-sm text-center" value="1"></td>
        <td><input type="number" name="qty[]" class="form-control form-control-sm qty text-center fw-bold" value="1" oninput="calculate()"></td>
        <td><select name="unit[]" class="form-select form-select-sm"><option value="Page">Page</option><option value="Word">Word</option></select></td>
        <td><input type="number" name="rate[]" step="0.01" class="form-control form-control-sm rate text-center" value="0" oninput="calculate()"></td>
        <td><input type="text" class="form-control-plaintext text-end line-total fw-bold pe-2" readonly value="0.00"></td>
        <td><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
    </tr>`;
    $('#quoteItems').append(row);
}

function removeRow(btn) { if ($('#quoteItems tr').length > 1) { $(btn).closest('tr').remove(); calculate(); } }

function toggleWhatsapp(val) {
    if (val === 'WhatsApp') $('#whatsapp_box').removeClass('d-none');
    else $('#whatsapp_box').addClass('d-none');
}

$('#editQuoteForm').on('submit', function(e) {
    e.preventDefault();
    $.post('update_quotation.php', $(this).serialize(), function(res) {
        alert(res.message);
        if(res.status === 'success') window.location.href = 'manage_quotations.php';
    }, 'json');
});

function fetchClientDetails(clientId) {
    fetch('get_client_rates.php?client_id=' + clientId + '&format=json').then(r => r.json()).then(data => {
        const tb = $('#rates_table_body').empty();
        if (data.rates) {
            data.rates.forEach(r => {
                tb.append(`<tr><td>${r.service_category}</td><td>QR ${r.rate}</td><td><button type="button" class="btn btn-sm btn-primary" onclick='applyRate(${JSON.stringify(r)})'>Apply</button></td></tr>`);
            });
        }
    });
}
</script>
</body>
</html>