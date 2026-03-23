<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// --- DATA COLLECTION ---
$job_id           = !empty($_POST['job_id']) ? (int)$_POST['job_id'] : null; 
$client_id        = (int)($_POST['client_id'] ?? 0);
$branch_id        = (int)($_SESSION['branch_id'] ?? 1);
$job_no           = $_POST['job_no'] ?? ''; 

$receiving_source = $_POST['receiving_method'] ?? 'Walk-in'; 
$whatsapp_no_id   = !empty($_POST['whatsapp_number_id']) ? (int)$_POST['whatsapp_number_id'] : null;

// NEW: Capture Deadline from the form
$deadline         = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

// Financials
$sub_total        = (float)($_POST['sub_total'] ?? 0);
$discount         = (float)($_POST['discount'] ?? 0);
$grand_total      = (float)($_POST['grand_total'] ?? 0);
$amount_paid      = (float)($_POST['amount_paid'] ?? 0);
$amount_due       = (float)($_POST['amount_due'] ?? 0);

// Status Mapping
$payment_status   = ($amount_due <= 0 && $grand_total > 0) ? 'Paid' : (($amount_paid > 0) ? 'Partially Paid' : 'Unpaid');

$client_ref       = $_POST['client_ref'] ?? '';
$payment_method   = $_POST['payment_method'] ?? 'Cash';
$payment_ref      = $_POST['payment_ref'] ?? '';
$delivery_info    = $_POST['delivery_info'] ?? '';
$additional_notes = $_POST['additional_notes'] ?? '';
$status           = $_POST['status'] ?? 'In Progress';

try {
    $pdo->beginTransaction();

    if ($job_id) {
        // --- UPDATE MAIN JOB ---
                $sql = "UPDATE jobs SET 
    client_id=?, receiving_method=?, whatsapp_number_id=?, status=?, grand_total=?, 
    payment_status=?, delivery_info=?, additional_notes=?, sub_total=?, discount=?, 
    amount_paid=?, amount_due=?, payment_method=?, payment_ref=?, deadline=?, 
    `client_ref`=?, updated_at=NOW() 
    WHERE id=?"; 
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
    $client_id,         // 1
    $receiving_source,  // 2
    $whatsapp_no_id,    // 3
    $status,            // 4
    $grand_total,       // 5
    $payment_status,    // 6
    $delivery_info,     // 7
    $additional_notes,  // 8
    $sub_total,         // 9
    $discount,          // 10
    $amount_paid,       // 11
    $amount_due,        // 12
    $payment_method,    // 13
    $payment_ref,       // 14
    $deadline,          // 15
    $client_ref,        // 16 (Column 28 in your table)
    $job_id             // 17 (The WHERE clause ID)
]);
        
        $pdo->prepare("DELETE FROM job_items WHERE job_id = ?")->execute([$job_id]);
        $message = "Job #$job_no updated.";
    } else {
    $sql = "INSERT INTO jobs (
    job_no,           
    client_id,        
    branch_id,        
    client_ref,       
    receiving_method, 
    whatsapp_number_id,
    deadline,          
    sub_total,         
    discount,          
    grand_total,       
    amount_paid,       
    amount_due,        
    payment_status,    
    payment_method,    
    payment_ref,       
    delivery_info,     
    additional_notes,  
    status,            
    created_by,        
    created_at         
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $job_no,            // 1
    $client_id,         // 2
    $branch_id,         // 3
    $client_ref,        // 4
    $receiving_source,  // 5
    $whatsapp_no_id,    // 6
    $deadline,          // 7
    $sub_total,         // 8
    $discount,          // 9
    $grand_total,       // 10
    $amount_paid,       // 11
    $amount_due,        // 12
    $payment_status,    // 13
    $payment_method,    // 14
    $payment_ref,       // 15
    $delivery_info,     // 16
    $additional_notes,  // 17
    $status,            // 18
    $_SESSION['user_id']// 19
]);

        $job_id = $pdo->lastInsertId();
        $message = "Job $job_no created successfully!";
    }

    // --- SAVE JOB ITEMS ---
    if (isset($_POST['type']) && is_array($_POST['type'])) {
        $itemSql = "INSERT INTO job_items (job_id, service_type, description, pages_s, actual_qty, qty, rate, total) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $itemStmt = $pdo->prepare($itemSql);
    
        foreach ($_POST['type'] as $index => $type) {
            if ($type === 'Translation') {
                $desc = ($_POST['src_lang'][$index] ?? 'English') . " to " . ($_POST['target_lang'][$index] ?? 'Arabic');
            } else {
                $desc = $_POST['pro_desc'][$index] ?? $type;
            }

            $pages_s    = (float)($_POST['actual_qty'][$index] ?? 0); 
            $qty        = (float)($_POST['qty'][$index] ?? 1);
            $actual_qty = $qty; 
            $rate       = (float)($_POST['rate'][$index] ?? 0);
            $total      = $qty * $rate;

            $itemStmt->execute([$job_id, $type, $desc, $pages_s, $actual_qty, $qty, $rate, $total]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => $message, 'job_id' => $job_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}