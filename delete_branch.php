<?php
include 'db.php';
session_start();

// 1. Security Check (Super Admin only)
$allowed_roles = ['Super Admin', 'SuperAdmin'];
$user_role = $_SESSION['role'] ?? ($_SESSION['roles'][0] ?? null);
$user_id = $_SESSION['user_id'] ?? 0;

if (!in_array($user_role, $allowed_roles)) {
    die("Unauthorized: Only Super Admins can permanently delete branches.");
}

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid ID.");

try {
    // 2. Safety Check: Verify no linked data exists
    // We check all potential related tables
    $related_tables = ['clients', 'quotations', 'jobs', 'invoices', 'staff_profiles'];
    $linked_data_found = false;
    $blocking_table = "";

    foreach ($related_tables as $table) {
        $check_sql = "SHOW TABLES LIKE '$table'";
        if ($pdo->query($check_sql)->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE branch_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $linked_data_found = true;
                $blocking_table = ucfirst($table);
                break;
            }
        }
    }

    if ($linked_data_found) {
        die("Cannot delete: This branch is linked to existing <b>$blocking_table</b> records. Please deactivate the branch instead.");
    }

    // 3. Perform Deletion
    $pdo->beginTransaction();

    // Get branch name for the audit log before deleting
    $name_stmt = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
    $name_stmt->execute([$id]);
    $branch_name = $name_stmt->fetchColumn();

    $delete_stmt = $pdo->prepare("DELETE FROM branches WHERE id = ?");
    $delete_stmt->execute([$id]);

    // 4. Log the deletion in Audit Logs
    $log_sql = "INSERT INTO audit_logs (updated_by, field_changed, old_value, new_value) 
                VALUES (?, ?, ?, ?)";
    $log_stmt = $pdo->prepare($log_sql);
    $log_stmt->execute([
        $user_id, 
        "Branch Deleted", 
        $branch_name, 
        "Permanently Removed"
    ]);

    $pdo->commit();

    // 5. Redirect to list
    header("Location: branch_list.php?msg=deleted");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Error during deletion: " . $e->getMessage());
}