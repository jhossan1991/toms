<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Fetch Job Header Data
$stmt = $pdo->prepare("SELECT j.*, b.name as branch_name FROM jobs j LEFT JOIN branches b ON j.branch_id = b.id WHERE j.id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) die("Job not found.");

// 2. Fetch Job Items
$itemsStmt = $pdo->prepare("SELECT * FROM job_items WHERE job_id = ?");
$itemsStmt->execute([$job_id]);
$items = $itemsStmt->fetchAll();

// 3. Fetch Metadata for Dropdowns
$clients = $pdo->query("SELECT id, COALESCE(NULLIF(company_name, ''), name) AS client_name, mobile_primary AS mobile, email, address, contact_person AS attention_person FROM clients WHERE is_active = 1 ORDER BY client_name ASC")->fetchAll();
$whatsappNumbers = $pdo->query("SELECT id, phone_number FROM whatsapp_numbers WHERE is_active = 1 ORDER BY phone_number")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Job | <?php echo htmlspecialchars($job['job_no']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .form-card { border-radius: 15px; border: none; box-shadow: 0 5px 25px rgba(0,0,0,0.1); background: #fff; padding: 2rem; }
        .payment-box { border: 1px solid #ced4da; background-color: #ffffff; border-radius: 8px; }
        .client-display-card { background: #fdfdfd; border: 1px solid #dee2e6; border-left: 5px solid #198754; border-radius: 8px; padding: 15px; margin-top: 15px; font-size: 0.9rem; }
        .info-label { font-weight: 600; width: 100px; display: inline-block; color: #6c757d; }
        .lang-input-group { display: flex; gap: 5px; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="form-card">
        <form id="jobForm">
            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="fw-bold text-primary mb-0">Update Job <span class="text-muted">#<?php echo htmlspecialchars($job['job_no']); ?></span></h2>
                    <p class="text-muted">Branch: <?php echo htmlspecialchars($job['branch_name'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <label class="fw-bold small d-block">STATUS</label>
                    <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                        <?php 
                        $statuses = ['Draft', 'In Progress', 'Completed', 'Cancelled'];
                        foreach($statuses as $st): ?>
                            <option value="<?php echo $st; ?>" <?php echo ($job['status'] == $st ? 'selected' : ''); ?>><?php echo $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr>

            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label fw-bold">Select Client</label>
                    <select name="client_id" id="clientSelect" class="form-select" required>
                        <?php foreach($clients as $c): ?>
                            <option value="<?php echo $c['id']; ?>" 
                                <?php echo ($c['id'] == $job['client_id'] ? 'selected' : ''); ?>
                                data-name="<?php echo htmlspecialchars($c['client_name']); ?>"
                                data-mobile="<?php echo htmlspecialchars($c['mobile'] ?? ''); ?>" 
                                data-email="<?php echo htmlspecialchars($c['email'] ?? ''); ?>"
                                data-address="<?php echo htmlspecialchars($c['address'] ?? 'N/A'); ?>"
                                data-attention="<?php echo htmlspecialchars($c['attention_person'] ?? 'N/A'); ?>">
                                <?php echo htmlspecialchars($c['client_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="clientDisplay" class="client-display-card">
                        <h6 id="view_company" class="fw-bold mb-2">Company Name</h6>
                        <div><span class="info-label">Attention:</span> <span id="view_attention"></span></div>
                        <div><span class="info-label">Mobile:</span> <span id="view_mobile"></span></div>
                        <div><span class="info-label">Address:</span> <span id="view_address"></span></div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Client Ref / PO #</label>
                            <input type="text" name="client_ref" class="form-control" value="<?php echo htmlspecialchars($job['client_ref'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
    <label class="form-label small fw-bold">Receiving Method</label>
    <select name="receiving_method" id="receiving_method" class="form-select" onchange="toggleWhatsapp(this.value)">
        <option value="Walk-In" <?php echo ($job['receiving_method'] == 'Walk-In' ? 'selected' : ''); ?>>Walk-In</option>
        <option value="WhatsApp" <?php echo ($job['receiving_method'] == 'WhatsApp' ? 'selected' : ''); ?>>WhatsApp</option>
        <option value="Email" <?php echo ($job['receiving_method'] == 'Email' ? 'selected' : ''); ?>>Email</option>
    </select>
</div>

<div class="col-md-6 <?php echo ($job['receiving_method'] == 'WhatsApp' ? '' : 'd-none'); ?>" id="whatsapp_box">
    <label class="form-label small fw-bold">WhatsApp Number (Source)</label>
    <select name="whatsapp_number_id" class="form-select">
        <option value="">-- Select Number --</option>
        <?php foreach($whatsappNumbers as $wn): ?>
            <option value="<?php echo $wn['id']; ?>" <?php echo ($job['whatsapp_number_id'] == $wn['id'] ? 'selected' : ''); ?>>
                <?php echo htmlspecialchars($wn['phone_number']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
                        
                        
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th width="15%">Type</th>
                            <th width="35%">Description / Languages</th>
                            <th width="8%">Actual Qty</th>
                            <th width="8%">Unit</th>
                            <th width="8%">Billing Qty</th>
                            <th width="10%">Rate</th>
                            <th width="12%">Line Total</th>
                            <th width="4%"></th>
                        </tr>
                    </thead>
                    <tbody id="itemRows">
                        <?php foreach($items as $item): 
                            $src = ""; $tgt = "";
                            if ($item['service_type'] == 'Translation' && strpos($item['description'], ' to ') !== false) {
                                $parts = explode(' to ', $item['description']);
                                $src = $parts[0]; $tgt = $parts[1];
                            }
                        ?>
                        <tr>
                            <td>
                                <select name="type[]" class="form-select form-select-sm type-select" onchange="toggleDescription(this)">
                                    <option value="Translation" <?php echo ($item['service_type'] == 'Translation' ? 'selected' : ''); ?>>Translation</option>
                                    <option value="Attestation" <?php echo ($item['service_type'] == 'Attestation' ? 'selected' : ''); ?>>Attestation</option>
                                    <option value="Services" <?php echo ($item['service_type'] == 'Services' ? 'selected' : ''); ?>>Services</option>
                                </select>
                            </td>
                            <td>
                                <div class="lang-fields <?php echo ($item['service_type'] != 'Translation' ? 'd-none' : ''); ?>">
                                    <div class="lang-input-group">
                                        <input type="text" name="src_lang[]" class="form-control form-control-sm" placeholder="From" value="<?php echo htmlspecialchars($src); ?>">
                                        <input type="text" name="target_lang[]" class="form-control form-control-sm" placeholder="To" value="<?php echo htmlspecialchars($tgt); ?>">
                                    </div>
                                </div>
                                <div class="desc-field <?php echo ($item['service_type'] == 'Translation' ? 'd-none' : ''); ?>">
                                    <input type="text" name="pro_desc[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($item['description']); ?>" placeholder="Description">
                                </div>
                            </td>
                            <td><input type="number" name="actual_qty[]" class="form-control form-control-sm text-center" value="<?php echo (float)($item['actual_qty'] ?? 1); ?>"></td>
                            <td>
                                <select name="unit[]" class="form-select form-select-sm">
                                    <option value="Page" <?php echo (($item['unit'] == 'Page') ? 'selected' : ''); ?>>Page</option>
                                    <option value="Doc" <?php echo (($item['unit'] == 'Doc') ? 'selected' : ''); ?>>Doc</option>
                                    <option value="Word" <?php echo (($item['unit'] == 'Word') ? 'selected' : ''); ?>>Word</option>
                                </select>
                            </td>
                            <td><input type="number" name="qty[]" class="form-control form-control-sm qty text-center fw-bold" value="<?php echo (float)$item['qty']; ?>" oninput="calcTotal()"></td>
                            <td><input type="number" name="rate[]" step="0.01" class="form-control form-control-sm rate text-center" value="<?php echo (float)$item['rate']; ?>" oninput="calcTotal()"></td>
                            <td><input type="text" class="form-control-plaintext form-control-sm fw-bold line-total text-end pe-2" readonly value="<?php echo number_format(($item['qty'] * $item['rate']), 2); ?>"></td>
                            <td><button type="button" class="btn btn-link text-danger p-0" onclick="removeRow(this)"><i class="fas fa-trash-alt"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-dark mb-4" onclick="addRow()">+ Add Row</button>
            </div>

            <div class="row">
                <div class="col-md-7">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="p-3 border rounded bg-light">
                                <label class="fw-bold small mb-2 text-primary d-block">
                                    <i class="fas fa-clock me-1"></i> EXPECTED DELIVERY DATE/TIME (DEADLINE)
                                </label>
                                <input type="datetime-local" name="deadline" class="form-control" 
                                       value="<?php echo (!empty($job['deadline']) && $job['deadline'] != '0000-00-00 00:00:00') ? date('Y-m-d\TH:i', strtotime($job['deadline'])) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <label class="fw-bold small mb-2">ADDITIONAL NOTES / DELIVERY INFO</label>
                    <textarea name="delivery_info" class="form-control" rows="5"><?php echo htmlspecialchars($job['delivery_info'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-md-5">
                    <div class="payment-box p-4 shadow-sm">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold">Subtotal:</span>
                            <span id="subtotal_disp">0.00</span>
                            <input type="hidden" name="sub_total" id="sub_total_val" value="<?php echo (float)$job['sub_total']; ?>">
                        </div>
                        <div class="d-flex justify-content-between mb-3 align-items-center">
                            <span class="fw-bold text-danger">Discount:</span>
                            <input type="number" name="discount" id="discount" class="form-control form-control-sm w-25 text-end" value="<?php echo (float)$job['discount']; ?>" oninput="calcTotal()">
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-top pt-2">
                            <span class="h5 fw-bold">Grand Total:</span>
                            <span class="h5 fw-bold text-primary" id="grand_total_disp"><?php echo (float)$job['grand_total']; ?></span>
                            <input type="hidden" name="grand_total" id="grand_total_val" value="<?php echo (float)$job['grand_total']; ?>">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="small fw-bold">Amount Paid</label>
                                <input type="number" name="amount_paid" id="amount_paid" step="0.01" class="form-control" value="<?php echo (float)$job['amount_paid']; ?>" oninput="calcTotal()">
                            </div>
        
                            <div class="col-md-6">
        <label class="small fw-bold">Payment Method</label>
        <select name="payment_method" id="payment_method" class="form-select form-select-sm" onchange="togglePaymentNote(this.value)">
            <option value="Cash" <?= ($job['payment_method'] == 'Cash' ? 'selected' : ''); ?>>Cash</option>
            <option value="Bank Transfer" <?= ($job['payment_method'] == 'Bank Transfer' ? 'selected' : ''); ?>>Bank Transfer</option>
            <option value="Card" <?= ($job['payment_method'] == 'Card' ? 'selected' : ''); ?>>Card</option>
            <option value="Cheque" <?= ($job['payment_method'] == 'Cheque' ? 'selected' : ''); ?>>Cheque</option>
        </select>
    </div>

    <div class="col-md-6 <?= ($job['payment_method'] == 'Cash' ? 'd-none' : ''); ?>" id="payment_ref_box">
        <label class="small fw-bold">Ref / Note</label>
        <input type="text" name="payment_ref" id="payment_ref" 
               value="<?= htmlspecialchars($job['payment_ref'] ?? ''); ?>" 
               class="form-control form-control-sm" 
               placeholder="e.g. Trans ID or Chq #">
    </div>
                        </div>

                        <div class="bg-dark text-white p-3 rounded d-flex justify-content-between align-items-center">
                            <span class="small fw-bold">Balance Due:</span>
                            <span class="fs-4 fw-bold">QR <span id="balance_due_disp"><?php echo (float)$job['amount_due']; ?></span></span>
                            <input type="hidden" name="amount_due" id="amount_due_val" value="<?php echo (float)$job['amount_due']; ?>">
                        </div>
                        <div id="payment_status_badge" class="mt-2 text-center"></div>
                    </div>

                    <div class="mt-4 d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold">Save Changes</button>
                        <a href="manage_jobs.php" class="btn btn-outline-secondary">Discard Changes</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#clientSelect').select2({ theme: 'bootstrap-5' });
        updateClientInfo();
        calcTotal();
        $('#clientSelect').on('select2:select', updateClientInfo);
    });

    function updateClientInfo() {
        const el = $('#clientSelect').find(':selected');
        $('#view_company').text(el.data('name'));
        $('#view_address').text(el.data('address'));
        $('#view_attention').text(el.data('attention'));
        $('#view_mobile').text(el.data('mobile'));
    }
function toggleWhatsapp(method) {
    const box = document.getElementById('whatsapp_box');
    if (method === 'WhatsApp') {
        box.classList.remove('d-none'); // Show it
    } else {
        box.classList.add('d-none');    // Hide it
        // Reset the selection so it doesn't send a number for Email/Walk-in
        $(box).find('select').val('');
    }
}
    function toggleDescription(select) {
        const row = $(select).closest('tr');
        if (select.value === 'Translation') {
            row.find('.lang-fields').removeClass('d-none');
            row.find('.desc-field').addClass('d-none');
        } else {
            row.find('.lang-fields').addClass('d-none');
            row.find('.desc-field').removeClass('d-none');
        }
    }

    function addRow() {
        const rowHTML = `<tr>
            <td>
                <select name="type[]" class="form-select form-select-sm type-select" onchange="toggleDescription(this)">
                    <option value="Translation">Translation</option>
                    <option value="Attestation">Attestation</option>
                    <option value="Services">Services</option>
                </select>
            </td>
            <td>
                <div class="lang-fields">
                    <div class="lang-input-group">
                        <input type="text" name="src_lang[]" class="form-control form-control-sm" placeholder="From">
                        <input type="text" name="target_lang[]" class="form-control form-control-sm" placeholder="To">
                    </div>
                </div>
                <div class="desc-field d-none">
                    <input type="text" name="pro_desc[]" class="form-control form-control-sm" placeholder="Description">
                </div>
            </td>
            <td><input type="number" name="actual_qty[]" class="form-control form-control-sm text-center" value="1"></td>
            <td>
                <select name="unit[]" class="form-select form-select-sm">
                    <option value="Page">Page</option><option value="Doc">Doc</option><option value="Word">Word</option>
                </select>
            </td>
            <td><input type="number" name="qty[]" class="form-control form-control-sm qty text-center fw-bold" value="1" oninput="calcTotal()"></td>
            <td><input type="number" name="rate[]" step="0.01" class="form-control form-control-sm rate text-center" value="0" oninput="calcTotal()"></td>
            <td><input type="text" class="form-control-plaintext form-control-sm fw-bold line-total text-end pe-2" readonly value="0.00"></td>
            <td><button type="button" class="btn btn-link text-danger p-0" onclick="removeRow(this)"><i class="fas fa-trash-alt"></i></button></td>
        </tr>`;
        $('#itemRows').append(rowHTML);
    }

    function removeRow(btn) {
        if ($('#itemRows tr').length > 1) {
            $(btn).closest('tr').remove();
            calcTotal();
        }
    }
function togglePaymentNote(method) {
    const refBox = document.getElementById('payment_ref_box');
    const refInput = document.getElementById('payment_ref');
    
    if (method === 'Cash') {
        refBox.classList.add('d-none'); // Hides the box
        refInput.value = '';            // Clears the input for clean data
    } else {
        refBox.classList.remove('d-none'); // Shows the box
    }
}
    function calcTotal() {
        let subtotal = 0;
        $('#itemRows tr').each(function() {
            const q = parseFloat($(this).find('.qty').val()) || 0;
            const r = parseFloat($(this).find('.rate').val()) || 0;
            const line = q * r;
            $(this).find('.line-total').val(line.toFixed(2));
            subtotal += line;
        });
        const disc = parseFloat($('#discount').val()) || 0;
        const grand = subtotal - disc;
        const paid = parseFloat($('#amount_paid').val()) || 0;
        const due = grand - paid;

        $('#subtotal_disp').text(subtotal.toFixed(2));
        $('#sub_total_val').val(subtotal.toFixed(2));
        $('#grand_total_disp').text(grand.toFixed(2));
        $('#grand_total_val').val(grand.toFixed(2));
        $('#balance_due_disp').text(due.toFixed(2));
        $('#amount_due_val').val(due.toFixed(2));

        const badge = $('#payment_status_badge');
        if (paid >= grand && grand > 0) badge.html('<span class="badge bg-success">FULLY PAID</span>');
        else if (paid > 0) badge.html('<span class="badge bg-warning text-dark">PARTIALLY PAID</span>');
        else badge.html('<span class="badge bg-danger">UNPAID</span>');
    }

    $('#jobForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Saving...');
        $.post('save_job.php', $(this).serialize(), function(data) {
            if(data.status === 'success') {
                window.location.href = 'manage_jobs.php';
            } else {
                alert('Error: ' + data.message);
                btn.prop('disabled', false).text('Save Changes');
            }
        }, 'json');
    });
</script>
</body>
</html>