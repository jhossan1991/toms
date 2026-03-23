<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Capture Form Data
    $full_name          = $_POST['full_name'];
    $email              = $_POST['email'];
    $branch_id          = $_POST['branch_id'];
    $staff_id_code      = $_POST['staff_id_code'];
    
    // Section A & B Data
    $mobile             = $_POST['mobile'];
    $sponsor            = $_POST['sponsor_company'];
    $working_under      = $_POST['working_under_company'];
    $date_joined        = $_POST['date_joined'];
    $status             = $_POST['status'];
    $in_vacation        = $_POST['in_vacation'] ?? 'No';
    $qid_number         = $_POST['qid_number'];
    $qid_expiry         = !empty($_POST['qid_expiry']) ? $_POST['qid_expiry'] : null;
    $passport_number    = $_POST['passport_number'];
    $passport_expiry    = !empty($_POST['passport_expiry']) ? $_POST['passport_expiry'] : null;

    // Section C & D Data (System Access)
    // IMPORTANT: If username is empty/readonly in HTML, we use the email
    $username           = !empty($_POST['username']) ? $_POST['username'] : $_POST['email'];
    $password           = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $account_status     = $_POST['account_status'];
    
    // Handle Multi-select Roles and Permissions
    $roles_array        = isset($_POST['roles']) ? $_POST['roles'] : [];
    $roles_string       = implode(', ', $roles_array); 
    
    $perms_array        = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    $perms_json         = json_encode($perms_array);

    // Audit Data
    $created_by         = $_SESSION['user_id'] ?? 0;

    try {
        $pdo->beginTransaction();

        // 2. Validate Uniqueness (Check both Email and Username)
        $checkEmail = $pdo->prepare("SELECT id FROM staff_profiles WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->rowCount() > 0) {
            throw new Exception("Error: A staff member with this email already exists.");
        }

        $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkUser->execute([$username]);
        if ($checkUser->rowCount() > 0) {
            throw new Exception("Error: This username is already taken.");
        }

        // 3. Insert into staff_profiles (15 Columns - 15 Values)
        $sqlStaff = "INSERT INTO staff_profiles (
            staff_id_code, full_name, mobile, email, sponsor_company, 
            working_under_company, date_joined, branch_id, in_vacation, 
            status, qid_number, qid_expiry, passport_number, passport_expiry, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmtStaff = $pdo->prepare($sqlStaff);
        // COUNT CHECK: 15 variables here matching 15 ? above
        $stmtStaff->execute([
            $staff_id_code, 
            $full_name, 
            $mobile, 
            $email, 
            $sponsor,
            $working_under, 
            $date_joined, 
            $branch_id, 
            $in_vacation,
            $status, 
            $qid_number, 
            $qid_expiry, 
            $passport_number, 
            $passport_expiry, 
            $created_by
        ]);

        $staff_profile_id = $pdo->lastInsertId();

        // 4. Insert into users table (8 Columns - 8 Values)
        $sqlUser = "INSERT INTO users (
            staff_profile_id, 
            username, 
            password, 
            full_name, 
            role, 
            branch_id, 
            permissions, 
            account_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([
            $staff_profile_id, 
            $username, 
            $password, 
            $full_name,    
            $roles_string, 
            $branch_id, 
            $perms_json,   
            $account_status
        ]);

        $pdo->commit();
        header("Location: staff_list.php?msg=StaffAdded");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        // Return to form with the specific error message
        header("Location: add_staff.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}