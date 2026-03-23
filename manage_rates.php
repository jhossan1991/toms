<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) exit('Unauthorized');

/**
 * ADD OR UPDATE RATE
 * Note: Aligned with database schema: rate, unit, service_category
 */
if (isset($_POST['add_rate']) || isset($_POST['f_rate'])) {
    // Collect variables (using null coalescing to support both Form and AJAX names)
    $client_id   = $_POST['client_id'] ?? $_POST['form_client_id'];
    $category    = $_POST['service_category'] ?? $_POST['f_cat'];
    $unit        = $_POST['unit'] ?? $_POST['f_unit'] ?? 'Per Word';
    $rate        = $_POST['rate_per_unit'] ?? $_POST['f_rate'] ?? 0;
    
    // Logic for language vs description
    $source = ($category == 'Translation') ? ($_POST['source_lang'] ?? $_POST['f_source']) : null;
    $target = ($category == 'Translation') ? ($_POST['target_lang'] ?? $_POST['f_target']) : null;
    $desc   = ($category == 'PRO/Other')   ? ($_POST['description'] ?? $_POST['f_desc']) : null;

    // FIXED: Added 'unit' column and ensured 'rate' is used instead of 'rate_per_unit'
    $sql = "INSERT INTO client_rates (client_id, service_category, source_lang, target_lang, description, unit, rate) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        if ($stmt->execute([$client_id, $category, $source, $target, $desc, $unit, $rate])) {
            // Check if request is AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['status' => 'success']);
                exit;
            }
            header("Location: clients.php?msg=RateAdded");
            exit;
        }
    } catch (PDOException $e) {
        // Handle duplicate entries (defined by our unique index)
        if ($e->getCode() == 23000) {
            echo "Error: This rate already exists for this client.";
        } else {
            echo "Database Error: " . $e->getMessage();
        }
        exit;
    }
}

/**
 * DELETE RATE
 */
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM client_rates WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: clients.php?msg=RateDeleted");
        exit;
    }
}
?>