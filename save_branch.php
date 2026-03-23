<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid request.");

try {
    // 1. Process and Clean Contacts (Arrays to JSON)
    $inputMobiles = isset($_POST['mobiles']) ? array_values(array_filter($_POST['mobiles'])) : [];
    $inputEmails = isset($_POST['emails']) ? array_values(array_filter($_POST['emails'])) : [];

    // 2. VALIDATION: Check for duplicate Mobiles or Emails across all branches
    foreach ($inputMobiles as $mobile) {
        $stmt = $pdo->prepare("SELECT name FROM branches WHERE mobile_numbers LIKE ?");
        // Search for the exact string inside the JSON array structure
        $stmt->execute(['%"' . $mobile . '"%']); 
        if ($branch = $stmt->fetch()) {
            die("Error: The mobile number <b>$mobile</b> is already assigned to branch: <b>" . $branch['name'] . "</b>");
        }
    }

    foreach ($inputEmails as $email) {
        $stmt = $pdo->prepare("SELECT name FROM branches WHERE emails LIKE ?");
        $stmt->execute(['%"' . $email . '"%']);
        if ($branch = $stmt->fetch()) {
            die("Error: The email address <b>$email</b> is already assigned to branch: <b>" . $branch['name'] . "</b>");
        }
    }

    $mobiles_json = json_encode($inputMobiles);
    $emails_json = json_encode($inputEmails);

    // 3. Process Dynamic Legal Documents
    $legal_docs = [];
    if (isset($_POST['doc_names'])) {
        foreach ($_POST['doc_names'] as $key => $name) {
            if (!empty($name) || !empty($_POST['doc_numbers'][$key])) {
                $legal_docs[] = [
                    'name' => $name,
                    'number' => $_POST['doc_numbers'][$key] ?? '',
                    'issue_date' => $_POST['doc_issue_dates'][$key] ?? '',
                    'expiry_date' => $_POST['doc_expiry_dates'][$key] ?? ''
                ];
            }
        }
    }
    $documents_json = json_encode($legal_docs);

    // 4. Begin Database Transaction
    $pdo->beginTransaction();

    $sql = "INSERT INTO branches (
                name, branch_code, is_main_branch, parent_company, status, 
                landline, mobile_numbers, emails, website,
                area, street_name, building_number, zone_number, city, po_box,
                kahramaa_number, water_number, google_maps_link,
                manager_id, legal_documents
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, 
                ?, ?
            )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $_POST['name'],
        $_POST['branch_code'],
        (int)$_POST['is_main_branch'],
        $_POST['parent_company'] ?? null,
        $_POST['status'],
        
        $_POST['landline'],
        $mobiles_json,
        $emails_json,
        $_POST['website'] ?? null,

        $_POST['area'],
        $_POST['street_name'],
        $_POST['building_number'],
        $_POST['zone_number'],
        $_POST['city'],
        $_POST['po_box'] ?? null,

        $_POST['kahramaa_number'],
        $_POST['water_number'],
        $_POST['google_maps_link'],

        !empty($_POST['manager_id']) ? $_POST['manager_id'] : null,
        $documents_json
    ]);

    $new_branch_id = $pdo->lastInsertId();
    $pdo->commit();

    // Redirect to list with success message
    header("Location: branch_list.php?msg=added&id=" . $new_branch_id);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error saving branch: " . $e->getMessage());
}