<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (!isset($_POST['job_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing Job ID']);
        exit;
    }

    $job_id = (int)$_POST['job_id'];

    try {
        $pdo->beginTransaction();

        // 1. Update Job Main Record (Including new fields from add_job)
        $updateJob = $pdo->prepare("UPDATE jobs SET 
            client_id        = ?, 
            status           = ?, 
            amount_paid      = ?, 
            amount_due       = ?, 
            grand_total      = ?, 
            discount         = ?, 
            payment_method   = ?, 
            payment_ref      = ?,
            receiving_method = ?,
            whatsapp_number_id = ?,
            delivery_info    = ?,
            additional_notes = ?,
            client_ref       = ?
            WHERE id = ?");
        
        $updateJob->execute([
            $_POST['client_id'],
            $_POST['status'], // e.g., 'Draft', 'In Progress', etc.
            (float)($_POST['amount_paid'] ?? 0),
            (float)($_POST['amount_due'] ?? 0),
            (float)($_POST['grand_total'] ?? 0),
            (float)($_POST['discount'] ?? 0),
            $_POST['payment_method'] ?? 'Cash',
            $_POST['payment_ref'] ?? null,
            $_POST['receiving_method'] ?? 'Walk-In',
            !empty($_POST['whatsapp_number_id']) ? $_POST['whatsapp_number_id'] : null,
            $_POST['delivery_info'] ?? '',
            $_POST['additional_notes'] ?? '',
            $_POST['client_ref'] ?? '',
            $job_id
        ]);

        // 2. Sync Job Items (Delete old ones and Insert current ones)
        // This ensures that if rows were removed in the UI, they are removed in DB
        $pdo->prepare("DELETE FROM job_items WHERE job_id = ?")->execute([$job_id]);

        $itemStmt = $pdo->prepare("INSERT INTO job_items (
            job_id, service_type, description, actual_qty, qty, unit, rate, line_total
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Loop through the arrays sent by the form
        if (isset($_POST['type']) && is_array($_POST['type'])) {
            foreach ($_POST['type'] as $index => $type) {
                
                // Logic to determine description based on service type
                $description = '';
                if ($type === 'Translation') {
                    $src = $_POST['src_lang'][$index] ?? '';
                    $tgt = $_POST['target_lang'][$index] ?? '';
                    $description = $src . " to " . $tgt;
                } else {
                    $description = $_POST['pro_desc'][$index] ?? '';
                }

                $qty = (float)($_POST['qty'][$index] ?? 0);
                $rate = (float)($_POST['rate'][$index] ?? 0);
                $line_total = $qty * $rate;
                $actual_qty = (float)($_POST['actual_qty'][$index] ?? 1);
                $unit = $_POST['unit'][$index] ?? 'Page';

                // Only save rows that have a rate or description to avoid empty data
                if ($rate > 0 || !empty($description)) {
                    $itemStmt->execute([
                        $job_id,
                        $type,
                        $description,
                        $actual_qty,
                        $qty,
                        $unit,
                        $rate,
                        $line_total
                    ]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Job updated successfully!']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}