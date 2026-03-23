<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

// 1. Authorization & Permission Control
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Roles are defined by Super Admin; verify the user has a valid role to 'Create'
$user_role = $_SESSION['role'] ?? 'Staff'; 
$allowed_roles = ['Staff', 'Admin', 'BranchAdmin', 'SuperAdmin'];

if (!in_array($user_role, $allowed_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'Permission Denied: Your role cannot create quotations.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. Capture Main Quotation Data
    $quote_no         = $_POST['quote_no'] ?? ''; 
    $client_id        = $_POST['client_id'] ?? null;
    $branch_id        = $_SESSION['branch_id'] ?? 1;
    $receiving_method = $_POST['receiving_method'] ?? 'Walk-In';
    $whatsapp_id      = (!empty($_POST['whatsapp_number_id']) && $receiving_method === 'WhatsApp') ? $_POST['whatsapp_number_id'] : null;
    $client_ref       = $_POST['client_ref'] ?? '';
    $quotation_for    = $_POST['quotation_for'] ?? '';
    
    // Financials
    $sub_total        = (float)($_POST['sub_total'] ?? 0);
    $discount         = (float)($_POST['discount'] ?? 0);
    $grand_total      = (float)($_POST['grand_total'] ?? 0);
    
    // Logistics, Dates & Notes
    $deadline         = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $valid_until      = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
    $payment_terms    = $_POST['payment_terms'] ?? ''; 
    $delivery_info    = $_POST['delivery_info'] ?? ''; 
    $additional_notes = $_POST['additional_notes'] ?? '';
    $status           = $_POST['status'] ?? 'Draft'; 
    $created_by       = $_SESSION['user_id'];
    
    // Section 3: Change Reason for Version History
    $change_reason    = $_POST['change_reason'] ?? 'Initial Quotation Creation';

    // 3. Insert into `quotations` table
    // Mapping exactly to your 19-column structure logic
    $sqlQuote = "INSERT INTO quotations (
                    quote_no, client_id, branch_id, receiving_method, 
                    whatsapp_number_id, client_ref, quotation_for, 
                    valid_until, sub_total, discount, grand_total, 
                    deadline, delivery_info, payment_terms, additional_notes, 
                    status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmtQuote = $pdo->prepare($sqlQuote);
    $stmtQuote->execute([
        $quote_no, $client_id, $branch_id, $receiving_method,
        $whatsapp_id, $client_ref, $quotation_for,
        $valid_until, $sub_total, $discount, $grand_total,
        $deadline, $delivery_info, $payment_terms, $additional_notes,
        $status, $created_by
    ]);

    $quote_id = $pdo->lastInsertId();

    // 4. Insert Line Items
    if (isset($_POST['type']) && is_array($_POST['type'])) {
        $sqlItem = "INSERT INTO quotation_items (
                        quote_id, service_type, description, 
                        pages_s, qty, unit, rate, total
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtItem = $pdo->prepare($sqlItem);

        foreach ($_POST['type'] as $index => $type) {
            $qty    = (float)($_POST['qty'][$index] ?? 0);
            $rate   = (float)($_POST['rate'][$index] ?? 0);
            $total  = $qty * $rate;
            $unit   = $_POST['unit'][$index] ?? 'Page';
            $actPgs = (float)($_POST['actual_qty'][$index] ?? 0);

            if ($type === 'Translation') {
                $src = $_POST['src_lang'][$index] ?? '';
                $tgt = $_POST['target_lang'][$index] ?? '';
                $description = (!empty($src) || !empty($tgt)) ? trim("$src to $tgt") : ($_POST['pro_desc'][$index] ?? '');
            } else {
                $description = $_POST['pro_desc'][$index] ?? '';
            }

            $stmtItem->execute([
                $quote_id, $type, $description, $actPgs, $qty, $unit, $rate, $total
            ]);
        }
    }

    // 5. Section 3: Version History Initial Entry
    $sqlVersion = "INSERT INTO quotation_versions (
                    quote_id, 
                    version_number, 
                    changed_by, 
                    change_reason,
                    grand_total_at_time, 
                    data_json
                ) VALUES (?, 1, ?, ?, ?, ?)";
    
    $stmtVersion = $pdo->prepare($sqlVersion);
    $stmtVersion->execute([
        $quote_id, 
        $created_by, 
        $change_reason,
        $grand_total, 
        json_encode($_POST)
    ]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => 'Quotation ' . $quote_no . ' saved successfully and Version 1 created!',
        'quote_id' => $quote_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database Error: ' . $e->getMessage()
    ]);
}