<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid request.");

$staff_id = (int)$_POST['staff_id'];
$updated_by = $_SESSION['user_id'] ?? 0;

try {
    $pdo->beginTransaction();

    // 1. Fetch OLD DATA for Audit Log (Includes Profile + User data)
    $stmtOld = $pdo->prepare("
        SELECT s.*, u.role as assigned_roles, u.account_status, u.permissions 
        FROM staff_profiles s 
        LEFT JOIN users u ON s.id = u.staff_profile_id 
        WHERE s.id = ?
    ");
    $stmtOld->execute([$staff_id]);
    $old = $stmtOld->fetch();

    if (!$old) throw new Exception("Staff member not found.");

    // 2. Define fields for Audit Comparison
    // 'db_column' => 'post_key'
    $profile_fields = [
        'full_name'             => 'full_name',
        'email'                 => 'email',
        'mobile'                => 'mobile',
        'sponsor_company'       => 'sponsor_company',
        'working_under_company' => 'working_under_company', // ADDED
        'date_joined'           => 'date_joined',           // ADDED
        'branch_id'             => 'branch_id',
        'status'                => 'status',
        'in_vacation'           => 'in_vacation',           // ADDED
        'qid_number'            => 'qid_number',
        'qid_expiry'            => 'qid_expiry',
        'passport_number'       => 'passport_number',
        'passport_expiry'       => 'passport_expiry'
    ];

    // 3. Process Roles and Permissions (Arrays to Strings/JSON)
    $new_roles = isset($_POST['roles']) ? implode(',', $_POST['roles']) : '';
    $new_perms = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : '[]';

    // 4. LOG AUDIT CHANGES (Profile Table)
    foreach ($profile_fields as $col => $post_key) {
        $new_val = $_POST[$post_key] ?? null;
        // Handle date fields to prevent comparison errors with NULL
        if (($new_val ?: null) != ($old[$col] ?: null)) {
            $log = $pdo->prepare("INSERT INTO audit_logs (staff_profile_id, updated_by, field_changed, old_value, new_value) VALUES (?, ?, ?, ?, ?)");
            $log->execute([$staff_id, $updated_by, $col, $old[$col], $new_val]);
        }
    }

    // 5. LOG AUDIT CHANGES (User Roles & Permissions)
    if ($new_roles !== $old['assigned_roles']) {
        $pdo->prepare("INSERT INTO audit_logs (staff_profile_id, updated_by, field_changed, old_value, new_value) VALUES (?, ?, 'roles', ?, ?)")
            ->execute([$staff_id, $updated_by, $old['assigned_roles'], $new_roles]);
    }
    if ($new_perms !== $old['permissions']) {
        $pdo->prepare("INSERT INTO audit_logs (staff_profile_id, updated_by, field_changed, old_value, new_value) VALUES (?, ?, 'permissions', ?, ?)")
            ->execute([$staff_id, $updated_by, $old['permissions'], $new_perms]);
    }

    // 6. UPDATE STAFF_PROFILES
    $sqlProfile = "UPDATE staff_profiles SET 
        full_name = ?, email = ?, mobile = ?, sponsor_company = ?, 
        working_under_company = ?, date_joined = ?, branch_id = ?, 
        status = ?, in_vacation = ?, qid_number = ?, qid_expiry = ?, 
        passport_number = ?, passport_expiry = ? 
        WHERE id = ?";
    
    $pdo->prepare($sqlProfile)->execute([
        $_POST['full_name'], $_POST['email'], $_POST['mobile'], $_POST['sponsor_company'],
        $_POST['working_under_company'], 
        !empty($_POST['date_joined']) ? $_POST['date_joined'] : null,
        $_POST['branch_id'], $_POST['status'], $_POST['in_vacation'], $_POST['qid_number'], 
        !empty($_POST['qid_expiry']) ? $_POST['qid_expiry'] : null,
        $_POST['passport_number'], 
        !empty($_POST['passport_expiry']) ? $_POST['passport_expiry'] : null,
        $staff_id
    ]);

    // 7. UPDATE USERS table
    $sqlUser = "UPDATE users SET role = ?, account_status = ?, permissions = ? WHERE staff_profile_id = ?";
    $pdo->prepare($sqlUser)->execute([
        $new_roles, 
        $_POST['account_status'], 
        $new_perms,
        $staff_id
    ]);

    $pdo->commit();
    header("Location: view_staff.php?id=$staff_id&msg=success");

} catch (Exception $e) {
    $pdo->rollBack();
    die("Update Failed: " . $e->getMessage());
}