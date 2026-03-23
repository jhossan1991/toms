<?php
ob_start();
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login.']);
    exit;
}

// Get Data from POST (matching your modal IDs)
$rate_id     = $_POST['form_rate_id'] ?? ''; 
$client_id   = $_POST['form_client_id'] ?? 0;
$service_cat = $_POST['f_cat'] ?? 'Translation';
$source_lang = $_POST['f_source'] ?? '';
$target_lang = $_POST['f_target'] ?? '';
$description = $_POST['f_desc'] ?? '';
$unit        = $_POST['f_unit'] ?? '';
$rate        = $_POST['f_rate'] ?? 0;

try {
    if (empty($rate_id)) {
        // --- DUPLICATE CHECK (Section 8) ---
        if ($service_cat === 'Translation') {
            $stmt = $pdo->prepare("SELECT id FROM client_rates WHERE client_id = ? AND source_lang = ? AND target_lang = ? AND service_category = 'Translation'");
            $stmt->execute([$client_id, $source_lang, $target_lang]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM client_rates WHERE client_id = ? AND description = ? AND service_category = 'PRO/Other'");
            $stmt->execute([$client_id, $description]);
        }

        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'This rate entry already exists for this client.']);
            exit;
        }

        $sql = "INSERT INTO client_rates (client_id, service_category, source_lang, target_lang, description, unit, rate) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$client_id, $service_cat, $source_lang, $target_lang, $description, $unit, $rate]);
        $message = "Rate added successfully";
    } else {
        // --- UPDATE ---
        $sql = "UPDATE client_rates SET service_category=?, source_lang=?, target_lang=?, description=?, unit=?, rate=? WHERE id=? AND client_id=?";
        $pdo->prepare($sql)->execute([$service_cat, $source_lang, $target_lang, $description, $unit, $rate, $rate_id, $client_id]);
        $message = "Rate updated successfully";
    }

    echo json_encode(['status' => 'success', 'message' => $message]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}