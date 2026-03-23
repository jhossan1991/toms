<?php
include 'db.php';
session_start();

// 1. Security Check
$allowed_roles = ['Super Admin', 'SuperAdmin', 'Admin'];
$user_role = $_SESSION['role'] ?? ($_SESSION['roles'][0] ?? null);
$user_id = $_SESSION['user_id'] ?? 0;

if (!in_array($user_role, $allowed_roles)) {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    
    // Format JSON fields
    $mobiles = json_encode(array_filter($_POST['mobiles'] ?? []));
    $emails = json_encode(array_filter($_POST['emails'] ?? []));
    
    // Format Legal Documents JSON
    $documents = [];
    if (isset($_POST['doc_names'])) {
        foreach ($_POST['doc_names'] as $index => $name) {
            if (!empty($name)) {
                $documents[] = [
                    'name' => $name,
                    'number' => $_POST['doc_numbers'][$index] ?? '',
                    'issue_date' => $_POST['doc_issue_dates'][$index] ?? '',
                    'expiry_date' => $_POST['doc_expiry_dates'][$index] ?? ''
                ];
            }
        }
    }
    $legal_json = json_encode($documents);

    try {
        $pdo->beginTransaction();

        // 2. Get old name for Audit Log
        $old_stmt = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
        $old_stmt->execute([$id]);
        $old_name = $old_stmt->fetchColumn();

        // 3. Update Branch
        $sql = "UPDATE branches SET 
                name = ?, branch_code = ?, is_main_branch = ?, parent_company = ?, 
                status = ?, landline = ?, website = ?, mobile_numbers = ?, 
                emails = ?, manager_id = ?, area = ?, street_name = ?, 
                building_number = ?, zone_number = ?, city = ?, po_box = ?, 
                kahramaa_number = ?, water_number = ?, google_maps_link = ?, 
                legal_documents = ? 
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['name'], $_POST['branch_code'], $_POST['is_main_branch'], $_POST['parent_company'],
            $_POST['status'], $_POST['landline'], $_POST['website'], $mobiles,
            $emails, $_POST['manager_id'] ?: null, $_POST['area'], $_POST['street_name'],
            $_POST['building_number'], $_POST['zone_number'], $_POST['city'], $_POST['po_box'],
            $_POST['kahramaa_number'], $_POST['water_number'], $_POST['google_maps_link'],
            $legal_json, $id
        ]);

        // 4. Log the change
        $log_sql = "INSERT INTO audit_logs (updated_by, field_changed, old_value, new_value) 
                    VALUES (?, ?, ?, ?)";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([$user_id, "Branch Updated", $old_name, $_POST['name']]);

        $pdo->commit();
        header("Location: view_branch.php?id=$id&msg=updated");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error updating branch: " . $e->getMessage());
    }
}