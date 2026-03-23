<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    try {
        // Optional: Add audit log entry here before deleting (Section 14)
        $stmt = $pdo->prepare("DELETE FROM client_rates WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Rate deleted']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
}