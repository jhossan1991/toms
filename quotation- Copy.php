<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userBranch = $_SESSION['branch_id'];
$userRoles  = $_SESSION['roles'] ?? [];
$isMainAdmin = (in_array('Admin', $userRoles) && $userBranch == 1);
$currentBranch = ($isMainAdmin && isset($_GET['branch'])) ? (int)$_GET['branch'] : $userBranch;

$prefixes = [1 => 'HM', 2 => 'HH', 3 => 'PRO'];
$prefix = $prefixes[$currentBranch] ?? 'JOB';
$year = date('Y');

$stmt = $pdo->prepare("SELECT job_no FROM jobs 
                       WHERE branch_id = ? AND YEAR(created_at) = ? 
                       ORDER BY id DESC LIMIT 1");
$stmt->execute([$currentBranch, $year]);
$lastJob = $stmt->fetchColumn();

if ($lastJob) {
    $parts = explode('-', $lastJob);
    $lastNum = (int)end($parts); 
    $count = $lastNum + 1;
} else {
    $count = 1;
}

$jobNo = $prefix . "-" . $year . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);

$clients = $pdo->query("SELECT 
    id, 
    COALESCE(NULLIF(company_name, ''), name) AS client_name, 
    mobile_primary AS mobile, 
    email, 
    address, 
    contact_person AS attention_person 
    FROM clients 
    WHERE is_active = 1 
    ORDER BY client_name ASC")->fetchAll();

// Organized with bullet points and clear line breaks
$defaultTerms = "• Payment: Due upon receipt of invoice (unless otherwise agreed).\n" .
                "• Advance Payment: May be required for large jobs or first-time clients.\n" .
                "• Extra Charges: Government/embassy/third-party fees (if any) are charged separately.\n" .
                "• Cancellation: Charges apply based on work completed; third-party fees are non-refundable.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Quotation | <?= $jobNo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .form-card { border-radius: 15px; border: none; box-shadow: 0 5px 25px rgba(0,0,0,0.1); background: #fff; }
        .table thead th { background: #f8f9fa; font-size: 0.8rem; letter-spacing: 0.5px; }
        .lang-input { width: 44% !important; display: inline-block; }
        .footer-label { font-weight: bold; font-size: 0.85rem; color: #2c3e50; text-transform: uppercase; }
        .editable-delivery { background: #fffdf0; border: 1px dashed #ffc107; font-weight: 500; }
        .client-display-card { display: none; background: #fdfdfd; border: 1px solid #dee2e6; border-left: 5px solid #198754; border-radius: 8px; padding: 15px; margin-top: 15px; font-size: 0.9rem; line-height: 1.6; }
        .client-display-card h5 { color: #198754; font-weight: bold; margin-bottom: 10px; text-transform: uppercase; }
        .info-label { font-weight: 600; width: 100px; display: inline-block; color: #6c757d; }
        .extra-info-box { font-size: 0.85rem; border-left: 5px solid #ffc107; }
        .select2-container--bootstrap-5 .select2-selection { border-top-right-radius: 0; border-bottom-right-radius: 0; }
        .input-group > .select2-container--bootstrap-5 { flex: 1 1 auto; width: 1% !important; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid max-width-lg p-4 form-card">
    <form action="save_job.php" method="POST">
        <div class="row mb-4">
            <div class="col-md-7">
                <h2 class="fw-bold text-success mb-0">QUOTATION</h2>
                <div class="mt-3">
                    <label class="small fw-bold text-uppercase">Search Client:</label>
                    <div class="input-group">
                        <select name="client_id" id="clientSelect" class="form-select" required>
                            <option value="">-- Search by Name, Mobile, or Email --</option>
                            <?php foreach($clients as $c): ?>
                                <option value="<?= $c['id'] ?>" 
                                        data-name="<?= htmlspecialchars($c['client_name']) ?>"
                                        data-mobile="<?= htmlspecialchars($c['mobile'] ?? '') ?>" 
                                        data-email="<?= htmlspecialchars($c['email'] ?? '') ?>"
                                        data-address="<?= htmlspecialchars($c['address'] ?? 'N/A') ?>"
                                        data-attention="<?= htmlspecialchars($c['attention_person'] ?? 'N/A') ?>">
                                    <?= htmlspecialchars($c['client_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-secondary" id="btn_view_rates" disabled data-bs-toggle="modal" data-bs-target="#ratesModal">
                            <i class="fas fa-tags"></i> Rates
                        </button>
                    </div>

                    <div id="clientDisplay" class="client-display-card">
                        <h5 id="view_company">Company Name</h5>
                        <div><span class="info-label">Address:</span> <span id="view_address"></span></div>
                        <div><span class="info-label">Attention:</span> <span id="view_attention" class="fw-bold"></span></div>
                        <div><span class="info-label">Mobile:</span> <span id="view_mobile"></span></div>
                        <div><span class="info-label">Email:</span> <span id="view_email"></span></div>
                    </div>

                    <div id="extraClientInfo" class="alert alert-warning extra-info-box mt-2 d-none">
                        <div class="row">
                            <div class="col-md-4"><strong><i class="fas fa-file-contract"></i> Contract:</strong><br><span id="txt_contract">-</span></div>
                            <div class="col-md-3"><strong><i class="fas fa-receipt"></i> LPO:</strong><br><span id="txt_lpo">-</span></div>
                            <div class="col-md-5"><strong><i class="fas fa-info-circle"></i> Notes:</strong><br><span id="txt_notes" class="fst-italic">-</span></div>
                        </div>
                    </div>

                    <input type="text" name="quotation_for" class="form-control form-control-sm mt-3" placeholder="Quotation For: [e.g. Translation of Educational Certificates]">
                </div>
            </div>
            <div class="col-md-5 text-end">
                <p class="mb-1 fw-bold">DATE: <?= date('d-M-Y') ?></p>
                <p class="mb-1 text-muted">Our Ref: <?= $jobNo ?></p>
                <div class="d-flex justify-content-end align-items-center mt-2">
                    <label class="me-2 fw-bold small">CLIENT REF:</label>
                    <input type="text" name="client_ref" class="form-control form-control-sm w-50" placeholder="Optional Ref No.">
                </div>
            </div>
        </div>

        <input type="hidden" name="job_no" value="<?= $jobNo ?>">
        <input type="hidden" name="branch_id" value="<?= $currentBranch ?>">

        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr class="text-center">
                        <th width="12%">Service</th>
                        <th width="35%">Description</th>
                        <th width="8%">Act. Pgs</th>
                        <th width="8%">Final Qty</th>
                        <th width="10%">Unit</th>
                        <th width="12%">Rate (QAR)</th>
                        <th width="12%">Total</th>
                        <th width="3%"></th>
                    </tr>
                </thead>
                <tbody id="itemRows">
                    <tr>
                        <td>
                            <select name="type[]" class="form-select form-select-sm service-type" onchange="handleTypeChange(this)">
                                <option value="Translation">Translation</option>
                                <option value="Attestation">Attestation</option>
                                <option value="Services">Services</option>
                            </select>
                        </td>
                        <td class="field-container">
                            <div class="translation-fields">
                                <input type="text" name="src_lang[]" class="form-control form-control-sm lang-input src-lang" placeholder="English">
                                <i class="fas fa-arrow-right mx-1 text-muted small"></i>
                                <input type="text" name="target_lang[]" class="form-control form-control-sm lang-input target-lang" placeholder="Arabic">
                            </div>
                            <div class="pro-fields d-none">
                                <input type="text" name="pro_desc[]" class="form-control form-control-sm pro-desc" placeholder="Service description...">
                            </div>
                        </td>
                        <td><input type="number" name="actual_qty[]" class="form-control form-control-sm text-center" value="1"></td>
                        <td><input type="number" name="qty[]" class="form-control form-control-sm qty text-center fw-bold" value="1" oninput="calcTotal()"></td>
                        <td><input type="text" name="unit[]" class="form-control form-control-sm unit-field text-center" value="Page"></td>
                        <td><input type="number" name="rate[]" step="0.01" class="form-control form-control-sm rate text-center" value="0" oninput="calcTotal()"></td>
                        <td><input type="text" class="form-control-plaintext form-control-sm fw-bold line-total text-end pe-2" readonly value="0.00"></td>
                        <td class="text-center"><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow()">+ Add New Line</button>
        </div>

        <div class="row">
            <div class="col-md-7">
            <div class="mb-4">
    <label class="footer-label d-block mb-1 text-secondary">
        <i class="fas fa-file-contract me-1"></i> Payment Terms & Conditions
    </label>
    <textarea 
        name="payment_terms" 
        class="form-control" 
        rows="6" 
        style="font-size: 0.85rem; line-height: 1.6; background-color: #fafafa; border-radius: 8px;" 
        placeholder="Enter terms here..."><?php echo htmlspecialchars($defaultTerms); ?></textarea>
    <div class="form-text">Note: These terms are editable for this specific quotation.</div>
</div>    
      
                <div class="mb-3">
                    <label class="footer-label d-block mb-1">Delivery / Deadline</label>
                    <div class="input-group input-group-sm mb-2" style="max-width: 400px;">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        <input type="datetime-local" id="deadlinePicker" class="form-control" onchange="updateDeliveryText()">
                    </div>
                    <textarea name="delivery_info" id="deliveryText" class="form-control editable-delivery" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="footer-label d-block mb-1 text-primary"><i class="fas fa-edit"></i> Additional Notes (Visible on Quote)</label>
                    <textarea name="additional_notes" class="form-control" rows="2" placeholder="Enter any extra notes to show on the printed quotation..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="footer-label d-block mb-1 text-muted">Internal Admin Notes (Hidden)</label>
                    <textarea name="notes" class="form-control bg-light" rows="2" placeholder="Internal staff reference only..."></textarea>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card border-0 bg-light p-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Subtotal:</span>
                        <span id="subtotal_disp">0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 align-items-center">
                        <span class="fw-bold">Discount:</span>
                        <input type="number" name="discount" id="discount" class="form-control form-control-sm w-50 text-end" value="0" oninput="calcTotal()">
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold text-success fs-4">
                        <span>Grand Total:</span>
                        <span><span id="grand_total_disp">0.00</span> <small style="font-size: 12px">QAR</small></span>
                        <input type="hidden" name="grand_total" id="grand_total_val">
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <button type="submit" name="status" value="Draft" class="btn btn-outline-dark px-4 me-2">Save Draft</button>
                    <button type="submit" name="status" value="In Progress" class="btn btn-success px-5 fw-bold shadow-sm">Generate Quote</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="ratesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="fas fa-tags text-success"></i> Special Rates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr class="table-light">
                            <th>Service Details</th>
                            <th class="text-center">Rate</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="rates_table_body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        function matchCustom(params, data) {
            if ($.trim(params.term) === '') return data;
            if (typeof data.text === 'undefined') return null;
            var term = params.term.toLowerCase();
            var name = data.text.toLowerCase();
            var mobile = $(data.element).data('mobile') ? $(data.element).data('mobile').toString().toLowerCase() : '';
            var email = $(data.element).data('email') ? $(data.element).data('email').toString().toLowerCase() : '';
            if (name.indexOf(term) > -1 || mobile.indexOf(term) > -1 || email.indexOf(term) > -1) return data;
            return null;
        }

        $('#clientSelect').select2({ theme: 'bootstrap-5', matcher: matchCustom });

        $('#clientSelect').on('select2:select', function(e) {
            const el = $(e.params.data.element);
            const clientId = el.val();
            $('#view_company').text(el.data('name'));
            $('#view_address').text(el.data('address'));
            $('#view_attention').text(el.data('attention'));
            $('#view_mobile').text(el.data('mobile'));
            $('#view_email').text(el.data('email'));
            $('#clientDisplay').slideDown(300);
            fetchClientDetails(clientId);
        });
    });

    function fetchClientDetails(clientId) {
        if (!clientId) return;
        fetch('get_client_rates.php?client_id=' + clientId + '&format=json')
            .then(response => response.json())
            .then(data => {
                $('#extraClientInfo').removeClass('d-none');
                $('#txt_contract').text(data.info.contract_details || 'No active contract');
                $('#txt_notes').text(data.info.client_notes || 'None');
                
                let lpoHtml = (data.info.lpo_required == 1) ? '<span class="badge bg-danger">REQUIRED</span>' : '<span class="badge bg-secondary">Not Required</span>';
                $('#txt_lpo').html(lpoHtml);

                let ratesBtn = $('#btn_view_rates');
                let ratesTable = $('#rates_table_body');
                ratesTable.empty();

                if (data.rates && data.rates.length > 0) {
                    ratesBtn.prop('disabled', false).removeClass('btn-outline-secondary').addClass('btn-outline-primary');
                    data.rates.forEach(rate => {
                        let detailText = (rate.service_category === 'Translation') 
                            ? `<small class="text-muted">${rate.source_lang} <i class="fas fa-arrow-right"></i> ${rate.target_lang}</small>`
                            : `<small class="text-muted">${rate.description}</small>`;

                        // Pass whole rate object to apply function
                        let rateJson = JSON.stringify(rate).replace(/'/g, "&apos;");

                        ratesTable.append(`
                            <tr>
                                <td>
                                    <div><strong>${rate.service_category}</strong></div>
                                    ${detailText}
                                </td>
                                <td class="text-center align-middle">
                                    <span class="fw-bold text-success">QR ${rate.rate}</span><br>
                                    <small class="text-uppercase text-muted" style="font-size:10px">Per ${rate.unit || 'Unit'}</small>
                                </td>
                                <td class="text-end align-middle">
                                    <button type="button" class="btn btn-sm btn-success" onclick='applyRate(${rateJson})'>
                                        <i class="fas fa-check"></i> Apply
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    ratesBtn.prop('disabled', false).removeClass('btn-outline-primary').addClass('btn-outline-secondary');
                    ratesTable.append('<tr><td colspan="3" class="text-center text-muted py-3">Standard market rates apply.</td></tr>');
                }
            });
    }

    /**
     * Applied selected rate to the last row or a new row
     */
    function applyRate(rate) {
        let lastRow = $('#itemRows tr').last();
        
        // Check if the current last row is already in use (description or rate not 0)
        const currentType = lastRow.find('.service-type').val();
        const currentRate = parseFloat(lastRow.find('.rate').val()) || 0;
        
        // If row is modified, add a new one
        if (currentRate > 0) {
            addRow();
            lastRow = $('#itemRows tr').last();
        }

        // Apply values
        lastRow.find('.service-type').val(rate.service_category).trigger('change');
        
        if (rate.service_category === 'Translation') {
            lastRow.find('.src-lang').val(rate.source_lang);
            lastRow.find('.target-lang').val(rate.target_lang);
        } else {
            lastRow.find('.pro-desc').val(rate.description);
        }

        lastRow.find('.unit-field').val(rate.unit);
        lastRow.find('.rate').val(rate.rate);

        calcTotal();
        
        // Hide modal
        bootstrap.Modal.getInstance(document.getElementById('ratesModal')).hide();
    }

    function handleTypeChange(select) {
        const row = $(select).closest('tr');
        const type = $(select).val();
        const unitInput = row.find('.unit-field');
        
        if (type === 'Translation') {
            row.find('.translation-fields').removeClass('d-none');
            row.find('.pro-fields').addClass('d-none');
            unitInput.val("Page");
        } else {
            row.find('.translation-fields').addClass('d-none');
            row.find('.pro-fields').removeClass('d-none');
            unitInput.val(type === 'Attestation' ? "Doc" : "Page");
        }
    }

    function addRow() {
        const rowHTML = `<tr>
            <td>
                <select name="type[]" class="form-select form-select-sm service-type" onchange="handleTypeChange(this)">
                    <option value="Translation">Translation</option>
                    <option value="Attestation">Attestation</option>
                    <option value="Services">Services</option>
                </select>
            </td>
            <td class="field-container">
                <div class="translation-fields">
                    <input type="text" name="src_lang[]" class="form-control form-control-sm lang-input src-lang" placeholder="English">
                    <i class="fas fa-arrow-right mx-1 text-muted small"></i>
                    <input type="text" name="target_lang[]" class="form-control form-control-sm lang-input target-lang" placeholder="Arabic">
                </div>
                <div class="pro-fields d-none">
                    <input type="text" name="pro_desc[]" class="form-control form-control-sm pro-desc" placeholder="Service description...">
                </div>
            </td>
            <td><input type="number" name="actual_qty[]" class="form-control form-control-sm text-center" value="1"></td>
            <td><input type="number" name="qty[]" class="form-control form-control-sm qty text-center fw-bold" value="1" oninput="calcTotal()"></td>
            <td><input type="text" name="unit[]" class="form-control form-control-sm unit-field text-center" value="Page"></td>
            <td><input type="number" name="rate[]" step="0.01" class="form-control form-control-sm rate text-center" value="0" oninput="calcTotal()"></td>
            <td><input type="text" class="form-control-plaintext form-control-sm fw-bold line-total text-end pe-2" readonly value="0.00"></td>
            <td class="text-center"><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>
        </tr>`;
        $('#itemRows').append(rowHTML);
    }

    function removeRow(btn) {
        if($('#itemRows tr').length > 1) $(btn).closest('tr').remove();
        calcTotal();
    }

    function calcTotal() {
        let sub = 0;
        $('#itemRows tr').each(function() {
            const q = parseFloat($(this).find('.qty').val()) || 0;
            const r = parseFloat($(this).find('.rate').val()) || 0;
            const line = q * r;
            $(this).find('.line-total').val(line.toFixed(2));
            sub += line;
        });
        const disc = parseFloat($('#discount').val()) || 0;
        const grand = Math.max(0, sub - disc);
        $('#subtotal_disp').text(sub.toFixed(2));
        $('#grand_total_disp').text(grand.toFixed(2));
        $('#grand_total_val').val(grand.toFixed(2));
    }

    function updateDeliveryText() {
        const picker = document.getElementById('deadlinePicker');
        if (!picker.value) return;
        const date = new Date(picker.value);
        const dateOptions = { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' };
        const dateStr = date.toLocaleDateString('en-GB', dateOptions);
        let hours = date.getHours();
        const minutes = date.getMinutes().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12; 
        const timeStr = `${hours}:${minutes} ${ampm}`;
        document.getElementById('deliveryText').value = `${dateStr} at ${timeStr}, subject to receiving confirmation within a reasonable timeframe.`;
    }
</script>
</body>
</html>