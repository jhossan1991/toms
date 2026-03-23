<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userBranch = $_SESSION['branch_id'];
$year = date('Y');

// Generate Quote Number: QT-YYYY-0001
$stmt = $pdo->prepare("SELECT quote_no FROM quotations WHERE YEAR(created_at) = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$year]);
$lastQuote = $stmt->fetchColumn();

if ($lastQuote) {
    preg_match('/(\d+)$/', $lastQuote, $m);
    $lastNum = isset($m[1]) ? (int)$m[1] : 0;
    $count = $lastNum + 1;
} else {
    $count = 1;
}
$quoteNo = "QT-" . $year . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);

// Fetch Clients and WhatsApp Numbers
$clients = $pdo->query("SELECT id, COALESCE(NULLIF(company_name, ''), name) AS client_name, mobile_primary AS mobile, email, address, internal_notes, requires_lpo, has_contract, contact_person AS attention_person FROM clients WHERE is_active = 1 ORDER BY client_name ASC")->fetchAll();
$whatsappNumbers = $pdo->query("SELECT id, phone_number FROM whatsapp_numbers WHERE is_active = 1 ORDER BY phone_number")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Quotation | <?= $quoteNo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; display: flex; }
        #content { width: 100%; padding: 20px; transition: all 0.3s; }
        .form-card { border-radius: 15px; border: none; box-shadow: 0 5px 25px rgba(0,0,0,0.1); background: #fff; padding: 30px; }
        .client-display-card { display: none; background: #fdfdfd; border: 1px solid #dee2e6; border-left: 5px solid #0d6efd; border-radius: 8px; padding: 15px; margin-top: 15px; }
        .table thead th { background: #f8f9fa; font-size: 0.8rem; }
        .editable-terms { background: #fffdf0; border: 1px dashed #ffc107; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div id="content">
    <div class="container-fluid form-card">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h2 class="fw-bold text-primary mb-0">New Quotation</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="manage_quotations.php" class="text-decoration-none">Quotation Manager</a></li>
                        <li class="breadcrumb-item active">Create New</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmCancel()">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
            </div>
        </div>

        <form id="quoteForm">
            <div class="row mb-4">
                <div class="col-md-7">
                    <label class="small fw-bold">SELECT CLIENT:</label>
                    <select name="client_id" id="clientSelect" class="form-select" required>
                        <option value="">-- Search by Name, Mobile, or Email --</option>
                        <?php foreach($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" 
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

                    <div id="clientDisplay" class="client-display-card">
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
                    
                    <input type="text" name="quotation_for" class="form-control mt-3" placeholder="Subject / Quotation For: (e.g. Legal Translation of Contracts)">
                </div>
                
                <div class="col-md-5 text-end">
                    <p class="mb-1 fw-bold">QUOTE NO: <?= $quoteNo ?></p>
                    <div class="d-flex justify-content-end align-items-center mt-2">
                        <label class="me-2 fw-bold small"> CLIENT REF:</label>
                        <input type="text" name="client_ref" class="form-control form-control-sm w-50" placeholder="Optional (e.g. LPO #)">
                    </div>
                    <p class="mt-2 fw-bold small"> DATE: <?= date('d-M-Y') ?></p>
                    
                    <div class="d-flex justify-content-end align-items-center mt-2">
                        <label class="me-2 fw-bold small">VALID UNTIL:</label>
                        <input type="date" name="valid_until" class="form-control form-control-sm w-50" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                    </div>

                    <div class="d-flex justify-content-end align-items-center mt-2">
                        <label class="me-2 fw-bold small">RECEIVING:</label>
                        <select name="receiving_method" id="receiving_method" class="form-select form-select-sm w-50" onchange="toggleWhatsapp(this.value)">
                            <option value="Walk-In">Walk-In</option>
                            <option value="Email">Email</option>
                            <option value="WhatsApp">WhatsApp</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end align-items-center mt-2 d-none" id="whatsapp_box">
                        <label class="me-2 fw-bold small text-success"><i class="fab fa-whatsapp"></i> RECEIVED BY:</label>
                        <select name="whatsapp_number_id" id="whatsapp_number_id" class="form-select form-select-sm w-50">
                            <option value="">-- Select Number --</option>
                            <?php foreach($whatsappNumbers as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['phone_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <input type="hidden" name="quote_no" value="<?= $quoteNo ?>">

            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead>
                        <tr class="text-center">
                            <th width="15%">Service</th>
                            <th width="25%">Description</th>
                            <th width="10%">Actual Pgs</th>
                            <th width="10%">Billable Qty</th>
                            <th width="10%">Unit</th> 
                            <th width="10%">Rate</th>
                            <th width="15%">Total</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody id="quoteItems">
                        <tr>
                            <td>
                                <select name="type[]" class="form-select form-select-sm" onchange="handleTypeChange(this)">
                                    <option value="Translation">Translation</option>
                                    <option value="Attestation">Attestation</option>
                                    <option value="Services">Services</option>
                                </select>
                            </td>
                            <td>
                                <div class="translation-fields d-flex gap-1">
                                    <input type="text" name="src_lang[]" class="form-control form-control-sm" placeholder="From">
                                    <input type="text" name="target_lang[]" class="form-control form-control-sm" placeholder="To">
                                </div>
                                <input type="text" name="pro_desc[]" class="form-control form-control-sm d-none pro_desc" placeholder="Description">
                            </td>
                            <td><input type="number" name="actual_qty[]" class="form-control form-control-sm text-center" value="1"></td>
                            <td><input type="number" name="qty[]" class="form-control form-control-sm qty text-center fw-bold" value="1" oninput="calculate()"></td>
                            <td>
                                <select name="unit[]" class="form-select form-select-sm">
                                    <option value="Page">Page</option>
                                    <option value="Word">Word</option>
                                    <option value="Doc">Doc</option>
                                    <option value="Set">Set</option>
                                </select>
                            </td>
                            <td><input type="number" name="rate[]" step="0.01" class="form-control form-control-sm rate text-center" value="0" oninput="calculate()"></td>
                            <td><input type="text" class="form-control-plaintext text-end line-total fw-bold pe-2" readonly value="0.00"></td>
                            <td><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">+ Add Line</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_view_rates" disabled data-bs-toggle="modal" data-bs-target="#ratesModal">
                        <i class="fas fa-tags me-1"></i> View Client Rates
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-md-7">
                    <div class="mb-3">
                        <label class="fw-bold small text-primary mb-1"><i class="fas fa-calendar-alt me-1"></i> DELIVERY DEADLINE:</label>
                        <input type="datetime-local" name="deadline" id="deadlinePicker" class="form-control form-control-sm" style="max-width: 300px;" onchange="updateDeliveryText()">
                        <input type="hidden" name="delivery_info" id="deliveryText">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-primary"><i class="fas fa-file-invoice-dollar me-1"></i> PAYMENT TERMS:</label>
                        <textarea name="payment_terms" id="payment_terms" class="form-control editable-terms" 
                            style="font-size: 0.85rem; line-height: 1.5; resize: none;"
                            oninput="autoResize(this)">1. Payment: Due upon receipt of invoice unless otherwise agreed.
2. Advance Payment: May be required for large projects.
3. Extra Charges: Third-party fees are charged separately.
4. Cancellation: Charges apply for work completed.</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">REMARKS / NOTES:</label>
                        <textarea name="additional_notes" class="form-control" rows="2" placeholder="Specific notes for this quote..."></textarea>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card border-0 bg-light p-3 shadow-sm">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold text-muted">Subtotal:</span>
                            <span>QR <span id="subtotal_display">0.00</span></span>
                            <input type="hidden" name="sub_total" id="sub_total_val">
                        </div>
                        <div class="d-flex justify-content-between mb-2 align-items-center">
                            <span class="fw-bold text-muted">Discount:</span>
                            <div class="input-group input-group-sm w-50">
                                <span class="input-group-text bg-white border-end-0">QR</span>
                                <input type="number" name="discount" id="discount" class="form-control text-end border-start-0" value="0" step="0.01" oninput="calculate()">
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold text-success fs-5">
                            <span>Grand Total:</span>
                            <span>QR <span id="grand_total_display">0.00</span></span>
                            <input type="hidden" name="grand_total" id="grand_total_val">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg mt-3 w-100 shadow-sm">
                        <i class="fas fa-save me-2"></i> Save Quotation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Auto-resize for terms
    autoResize(document.getElementById('payment_terms'));

    // Client Search Matcher
    function matchCustom(params, data) {
        if ($.trim(params.term) === '') return data;
        if (typeof data.text === 'undefined') return null;
        const term = params.term.toLowerCase();
        const text = data.text.toLowerCase();
        const mobile = $(data.element).data('mobile') ? $(data.element).data('mobile').toString().toLowerCase() : '';
        const email = $(data.element).data('email') ? $(data.element).data('email').toLowerCase() : '';
        return (text.indexOf(term) > -1 || mobile.indexOf(term) > -1 || email.indexOf(term) > -1) ? data : null;
    }

    $('#clientSelect').select2({
        theme: 'bootstrap-5',
        matcher: matchCustom
    }).on('select2:select change', function(e) {
        const sel = $(this).find(':selected');
        const cid = $(this).val();
        if (cid) {
            $('#view_company').text(sel.data('name'));
            $('#view_address').text(sel.data('address'));
            $('#view_attention').text(sel.data('attention'));
            $('#view_mobile').text(sel.data('mobile'));
            $('#view_email').text(sel.data('email'));
            $('#view_contract').text(sel.data('contract'));
            $('#view_lpo').text(sel.data('lpo'));
            $('#view_notes').text(sel.data('notes'));
            
            sel.data('lpo').includes('REQUIRED') ? $('#view_lpo').addClass('text-danger fw-bold') : $('#view_lpo').removeClass('text-danger fw-bold');
            $('#clientDisplay').slideDown();
            fetchClientDetails(cid); 
        } else {
            $('#clientDisplay').slideUp();
        }
    });
});

function confirmCancel() {
    if (confirm("Are you sure you want to cancel?")) window.location.href = 'manage_quotations.php';
}

function updateDeliveryText() {
    const p = document.getElementById('deadlinePicker');
    if (p.value) {
        const d = new Date(p.value);
        document.getElementById('deliveryText').value = "Delivery Deadline: " + d.toLocaleString('en-GB');
    }
}

function handleTypeChange(select) {
    const row = $(select).closest('tr');
    if (select.value === 'Translation') {
        row.find('.translation-fields').removeClass('d-none');
        row.find('.pro_desc').addClass('d-none');
    } else {
        row.find('.translation-fields').addClass('d-none');
        row.find('.pro_desc').removeClass('d-none');
    }
}

function calculate() {
    let sub = 0;
    $('#quoteItems tr').each(function() {
        const q = parseFloat($(this).find('.qty').val()) || 0;
        const r = parseFloat($(this).find('.rate').val()) || 0;
        const tot = q * r;
        $(this).find('.line-total').val(tot.toFixed(2));
        sub += tot;
    });
    $('#subtotal_display').text(sub.toFixed(2));
    $('#sub_total_val').val(sub.toFixed(2));
    const disc = parseFloat($('#discount').val()) || 0;
    const grand = Math.max(0, sub - disc);
    $('#grand_total_display').text(grand.toFixed(2));
    $('#grand_total_val').val(grand.toFixed(2));
}

function addRow() {
    const row = $('#quoteItems tr:first').clone();
    row.find('input').val('');
    row.find('.qty, .actual_qty').val(1);
    row.find('.rate').val(0);
    row.find('.line-total').val('0.00');
    row.find('select').prop('selectedIndex', 0);
    row.find('.translation-fields').removeClass('d-none');
    row.find('.pro_desc').addClass('d-none');
    $('#quoteItems').append(row);
}

function removeRow(btn) {
    if ($('#quoteItems tr').length > 1) { $(btn).closest('tr').remove(); calculate(); }
    else alert("Must have at least one item.");
}

let activeRowForRate = null;
$(document).on('click', '.rate, .qty', function() { activeRowForRate = $(this).closest('tr'); });

function fetchClientDetails(clientId) {
    fetch('get_client_rates.php?client_id=' + clientId + '&format=json')
        .then(r => r.json())
        .then(data => {
            const body = $('#rates_table_body').empty();
            if (data.rates && data.rates.length > 0) {
                $('#btn_view_rates').prop('disabled', false).addClass('btn-success').removeClass('btn-outline-secondary');
                data.rates.forEach(rate => {
                    const rateData = { type: rate.service_category, rate: rate.rate, unit: rate.unit || 'Page', desc: rate.source_lang ? `${rate.source_lang} to ${rate.target_lang}` : (rate.description || '') };
                    const rateStr = JSON.stringify(rateData).replace(/'/g, "&apos;");
                    body.append(`<tr><td><strong>${rate.service_category}</strong><br><small>${rateData.desc}</small></td><td class="text-center">QR ${rate.rate}</td><td class="text-end"><button type="button" class="btn btn-sm btn-primary" onclick='applyClientRate(${rateStr})'>Apply</button></td></tr>`);
                });
            } else {
                $('#btn_view_rates').prop('disabled', false).addClass('btn-outline-secondary').removeClass('btn-success');
                body.append('<tr><td colspan="3" class="text-center py-3 text-muted">Standard market rates apply.</td></tr>');
            }
        });
}

function applyClientRate(data) {
    let tr = activeRowForRate || $('#quoteItems tr:last');
    tr.find('select[name="type[]"]').val(data.type).trigger('change');
    tr.find('input[name="rate[]"]').val(data.rate);
    tr.find('select[name="unit[]"]').val(data.unit);
    if(data.type === 'Translation' && data.desc.includes(' to ')) {
        const l = data.desc.split(' to ');
        tr.find('input[name="src_lang[]"]').val(l[0]);
        tr.find('input[name="target_lang[]"]').val(l[1]);
    } else {
        tr.find('input[name="pro_desc[]"]').val(data.desc);
    }
    bootstrap.Modal.getInstance(document.getElementById('ratesModal')).hide();
    calculate(); 
}

function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}

function toggleWhatsapp(val) {
    const b = $('#whatsapp_box'), s = $('#whatsapp_number_id');
    if (val === 'WhatsApp') { b.removeClass('d-none'); s.prop('required', true); }
    else { b.addClass('d-none'); s.prop('required', false).val(''); }
}

$('#quoteForm').on('submit', function(e) {
    e.preventDefault();
    const btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).text('Saving...');
    fetch('save_quotation.php', { method: 'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') { alert(d.message); window.location.href = 'manage_quotations.php'; }
        else { alert("Error: " + d.message); btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Save Quotation'); }
    }).catch(() => { alert("Error saving."); btn.prop('disabled', false); });
});
</script>

<div class="modal fade" id="ratesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Client Specific Rates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-hover">
                    <thead class="table-dark"><tr><th>Service</th><th class="text-center">Rate</th><th class="text-end">Action</th></tr></thead>
                    <tbody id="rates_table_body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>