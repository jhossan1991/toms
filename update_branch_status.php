<?php
include 'db.php';
session_start();

// 1. Security Check
$allowed_roles = ['Super Admin', 'SuperAdmin', 'Admin'];
$user_role = $_SESSION['role'] ?? ($_SESSION['roles'][0] ?? null);
$user_id = $_SESSION['user_id'] ?? 0;

if (!in_array($user_role, $allowed_roles)) {
    die("Unauthorized: Only Admins can change branch status.");
}

// 2. Input Validation
$id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

if (!$id || !in_array($status, ['Active', 'Inactive'])) {
    die("Invalid request parameters.");
}

try {
    $pdo->beginTransaction();

    // 3. Get current status for Audit Log
    $stmt = $pdo->prepare("SELECT name, status FROM branches WHERE id = ?");
    $stmt->execute([$id]);
    $branch = $stmt->fetch();

    if (!$branch) {
        throw new Exception("Branch not found.");
    }

    // 4. Update Status
    $update = $pdo->prepare("UPDATE branches SET status = ? WHERE id = ?");
    $update->execute([$status, $id]);

    // 5. Log the activity (Using your audit_logs table structure)
    $log_sql = "INSERT INTO audit_logs (updated_by, field_changed, old_value, new_value) 
                VALUES (?, ?, ?, ?)";
    $log_stmt = $pdo->prepare($log_sql);
    $log_stmt->execute([
        $user_id, 
        "Branch Status (" . $branch['name'] . ")", 
        $branch['status'], 
        $status
    ]);

    $pdo->commit();

    // 6. Redirect back with success message
    $msg = ($status == 'Active') ? 'activated' : 'deactivated';
    header("Location: branch_list.php?msg=$msg");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error updating status: " . $e->getMessage());
}