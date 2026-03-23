<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action']; // 'archive' or 'unarchive'
    $status = ($action === 'archive') ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE clients SET is_archived = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        echo json_encode([
            'status' => 'success',
            'message' => ($status ? 'Client moved to archive.' : 'Client restored to active list.')
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
