<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$quote_id = $_POST['quote_id'] ?? null;
$new_status = $_POST['status'] ?? null;
$reason = $_POST['reason'] ?? 'Status changed';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Simple Permission Check based on your table
if (in_array($new_status, ['Approved', 'Converted']) && $user_role === 'Staff') {
    echo json_encode(['status' => 'error', 'message' => 'Staff cannot approve or convert quotes.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Update the Main Quotation Status
    $sql = "UPDATE quotations SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_status, $quote_id]);

    // 2. Get the current grand total for the version record
    $sqlTotal = "SELECT grand_total, (SELECT MAX(version_number) FROM quotation_versions WHERE quote_id = ?) as last_v 
                 FROM quotations WHERE id = ?";
    $stmtT = $pdo->prepare($sqlTotal);
    $stmtT->execute([$quote_id, $quote_id]);
    $data = $stmtT->fetch();
    
    $next_version = ($data['last_v'] ?? 1) + 1;

    // 3. Record in Version History (Section 3)
    $sqlV = "INSERT INTO quotation_versions (quote_id, version_number, changed_by, change_reason, grand_total_at_time) 
             VALUES (?, ?, ?, ?, ?)";
    $pdo->prepare($sqlV)->execute([$quote_id, $next_version, $user_id, "Status changed to $new_status: $reason", $data['grand_total']]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => "Quotation is now $new_status"]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}