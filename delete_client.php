<?php
session_start();
include 'db.php';

// 1. Basic Security & Input
if (!isset($_SESSION['user_id']) || !isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$clientId = (int)$_POST['id'];

try {
    // 2. Check for Jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $jobCount = $stmt->fetchColumn();

    // 3. Check for Payments (if you have a payments table)
    // Note: If you don't have a 'payments' table yet, we can skip this or check invoices.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $invoiceCount = $stmt->fetchColumn();

    // 4. Logic Gate
    if ($jobCount > 0 || $invoiceCount > 0) {
        echo json_encode([
            'status' => 'blocked', 
            'message' => "Cannot delete! This client has $jobCount jobs and $invoiceCount invoices. Would you like to Inactivate them instead?"
        ]);
        exit;
    }

    // 5. Perform actual delete if no records found
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);

    echo json_encode(['status' => 'success', 'message' => 'Client deleted successfully.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}