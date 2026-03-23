<?php
include 'db.php';

$value = $_GET['value'] ?? '';
$type = $_GET['type'] ?? '';
$exclude_id = $_GET['exclude_id'] ?? 0; // NEW: ID to ignore

$column = ($type === 'mobile') ? 'mobile_numbers' : 'emails';

// Query adds "AND id != ?" to avoid flagging itself
$stmt = $pdo->prepare("SELECT name FROM branches WHERE $column LIKE ? AND id != ? LIMIT 1");
$stmt->execute(['%"' . $value . '"%', $exclude_id]);
$branch = $stmt->fetch();

echo json_encode([
    'exists' => (bool)$branch,
    'branch_name' => $branch ? $branch['name'] : ''
]);