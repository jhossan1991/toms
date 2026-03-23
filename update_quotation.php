<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

// Check authorization
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $quote_id = $_POST['quote_id'] ?? null;
    if (!$quote_id) {
        throw new Exception("Quotation ID is missing.");
    }

    // 1. Update Main Table (Added missing fields: whatsapp_number_id, deadline, valid_until, client_ref)
    $sql = "UPDATE quotations SET 
            client_id = ?, 
            client_ref = ?,
            quotation_for = ?, 
            receiving_method = ?, 
            whatsapp_number_id = ?,
            deadline = ?,
            valid_until = ?,
            sub_total = ?, 
            discount = ?, 
            grand_total = ?, 
            additional_notes = ? 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Handle conditional fields
    $whatsapp_id = (!empty($_POST['whatsapp_number_id']) && $_POST['receiving_method'] === 'WhatsApp') ? $_POST['whatsapp_number_id'] : null;
    $deadline    = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $valid_until = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;

    $stmt->execute([
        $_POST['client_id'],
        $_POST['client_ref'] ?? '',
        $_POST['quotation_for'],
        $_POST['receiving_method'],
        $whatsapp_id,
        $deadline,
        $valid_until,
        $_POST['sub_total'],
        $_POST['discount'],
        $_POST['grand_total'],
        $_POST['additional_notes'],
        $quote_id
    ]);

    // 2. Refresh Items (Delete old ones and insert current list)
    $pdo->prepare("DELETE FROM quotation_items WHERE quote_id = ?")->execute([$quote_id]);

    // Fixed column names to match your DB schema (pages_s, unit)
    $sqlItem = "INSERT INTO quotation_items (quote_id, service_type, description, pages_s, qty, unit, rate, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtItem = $pdo->prepare($sqlItem);

    if (isset($_POST['type']) && is_array($_POST['type'])) {
        foreach ($_POST['type'] as $i => $type) {
            $qty    = $_POST['qty'][$i] ?? 0;
            $rate   = $_POST['rate'][$i] ?? 0;
            $unit   = $_POST['unit'][$i] ?? 'Page';
            $actPgs = $_POST['actual_qty'][$i] ?? 0;
            $total  = $qty * $rate;

            // Fix: Reconstruct description logic from the form fields
            if ($type === 'Translation') {
                $src = $_POST['src_lang'][$i] ?? '';
                $tgt = $_POST['target_lang'][$i] ?? '';
                $description = trim("$src to $tgt");
                if ($description === "to") {
                    $description = $_POST['pro_desc'][$i] ?? '';
                }
            } else {
                $description = $_POST['pro_desc'][$i] ?? '';
            }

            $stmtItem->execute([
                $quote_id,
                $type,
                $description,
                $actPgs,
                $qty,
                $unit,
                $rate,
                $total
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Quotation updated successfully!']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}