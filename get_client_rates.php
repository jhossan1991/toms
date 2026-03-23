<?php
include 'db.php';

// Get parameters
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$format   = $_GET['format'] ?? 'html';

if ($clientId > 0) {
    try {
        // 1. Fetch Client Info
        $stmtInfo = $pdo->prepare("SELECT 
            has_contract AS contract_details, 
            internal_notes AS client_notes, 
            requires_lpo AS lpo_required 
            FROM clients WHERE id = ?");
        $stmtInfo->execute([$clientId]);
        $clientInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        // 2. Fetch All Rates for this Client
        $stmtRate = $pdo->prepare("SELECT 
            id,
            service_category, 
            source_lang, 
            target_lang, 
            description, 
            unit,
            rate 
            FROM client_rates 
            WHERE client_id = ? 
            ORDER BY service_category ASC, id DESC");
        $stmtRate->execute([$clientId]);
        $rates = $stmtRate->fetchAll(PDO::FETCH_ASSOC);

        // --- FEATURE 1: JSON FORMAT ---
        // Used by Quotation/Job creation to auto-fill prices
        if ($format === 'json') {
            header('Content-Type: application/json');
            echo json_encode([
                'info' => $clientInfo,
                'rates' => $rates
            ]);
            exit;
        }

        // --- FEATURE 2: HTML FORMAT ---
        // Used by the Rate Manager Modal in clients.php
        if (empty($rates)) {
            echo "<tr><td colspan='5' class='text-center text-muted py-4'>
                    <i class='fas fa-info-circle me-1'></i> No specific rates set for this client.
                  </td></tr>";
        } else {
            foreach ($rates as $r) {
                // Securely encode data for the JavaScript 'Edit' function
                $jsData = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                
                // Format the "Service/Pair" column
                if ($r['service_category'] === 'Translation') {
                    $details = "<strong>" . htmlspecialchars($r['source_lang']) . "</strong> 
                                <i class='fas fa-arrow-right mx-1 small text-muted'></i> 
                                <strong>" . htmlspecialchars($r['target_lang']) . "</strong>";
                } else {
                    $details = htmlspecialchars($r['description']);
                }
                
                echo "<tr>
                        <td>
                            <span class='badge bg-light text-dark border fw-normal'>" . 
                                htmlspecialchars($r['service_category']) . 
                            "</span>
                        </td>
                        <td>" . $details . "</td>
                        <td>" . htmlspecialchars($r['unit']) . "</td>
                        <td class='fw-bold text-success'>QR " . number_format($r['rate'], 2) . "</td>
                        <td class='text-end'>
                            <div class='btn-group shadow-sm'>
                                <button type='button' class='btn btn-sm btn-success btn-apply-rate' 
                                        onclick='applyRateToJob($jsData)' style='display:none;'>
                                    Apply
                                </button>

                                <button type='button' class='btn btn-sm btn-white border bg-white text-primary' 
                                        onclick='editExistingRate($jsData)' title='Edit Rate'>
                                    <i class='fas fa-edit'></i>
                                </button>

                                <button type='button' class='btn btn-sm btn-white border bg-white text-danger' 
                                        onclick='deleteRate(" . (int)$r['id'] . ", " . $clientId . ")' title='Delete Rate'>
                                    <i class='fas fa-trash-alt'></i>
                                </button>
                            </div>
                        </td>
                      </tr>";
            }
        }

    } catch (PDOException $e) {
        // Return error as JSON even if HTML was requested, to help debugging
        header('Content-Type: application/json', true, 500);
        echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo "<tr><td colspan='5' class='text-center text-danger'>Invalid Client ID provided.</td></tr>";
}