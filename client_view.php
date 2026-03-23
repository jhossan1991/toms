<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get Client ID from URL
$clientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($clientId <= 0) {
    die("Invalid Client ID.");
}

/** * 1. HEADER SECTION (GLOBAL) - PLAN SECTION 1
 * Fetching status and branch to enforce "BUTTON RULES"
 */
$stmt = $pdo->prepare("
    SELECT c.*, b.name as branch_name 
    FROM clients c 
    LEFT JOIN branches b ON c.branch_id = b.id 
    WHERE c.id = ?
");
$stmt->execute([$clientId]);
$client = $stmt->fetch();

if (!$client) {
    die("Client not found.");
}

// PLAN SECTION 1: Status Badge Logic
$status = $client['status'];
$badgeClass = ($status == 'Active') ? 'bg-success' : (($status == 'On Hold') ? 'bg-warning text-dark' : 'bg-danger');

// PLAN SECTION 1: BUTTON RULES
// Disable ALL actions if Status = On Hold or Inactive
$isLocked = ($status == 'On Hold' || $status == 'Inactive');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($client['name']) ?> | Profile</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root { --brand-green: #198754; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        
        /* Header Styling */
        .client-header { background: white; padding: 25px 0; border-bottom: 1px solid #e0e0e0; }
        .breadcrumb { font-size: 0.85rem; }
        
        /* Tab Styling - PLAN SECTION 2 */
        .nav-tabs { border-bottom: 2px solid #dee2e6; }
        .nav-tabs .nav-link { 
            border: none; color: #6c757d; font-weight: 600; padding: 12px 20px; 
            transition: all 0.2s;
        }
        .nav-tabs .nav-link.active { 
            color: var(--brand-green); background: none; 
            border-bottom: 3px solid var(--brand-green); 
        }
        
        /* Tab Content Container */
        .tab-content-wrapper { 
            background: white; border: 1px solid #dee2e6; 
            border-top: none; border-radius: 0 0 12px 12px; 
            min-height: 500px; padding: 30px; 
        }

        /* Responsive UI - PLAN SECTION 16 */
        @media (max-width: 768px) {
            .header-actions { margin-top: 15px; width: 100%; }
            .header-actions .btn { width: 100%; margin-bottom: 8px; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1">
        
        <div class="client-header shadow-sm">
            <div class="container-fluid px-4">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="clients.php" class="text-decoration-none">Directory</a></li>
                                <li class="breadcrumb-item active">#<?= str_pad($client['id'], 4, '0', STR_PAD_LEFT) ?></li>
                            </ol>
                        </nav>
                        <h2 class="fw-bold mb-1">
                            <?= htmlspecialchars($client['name']) ?>
                            <span class="badge <?= $badgeClass ?> ms-2 fs-6 fw-normal"><?= $status ?></span>
                        </h2>
                        <div class="text-muted small">
                            <i class="fas fa-building me-1"></i> <?= htmlspecialchars($client['branch_name'] ?? 'Main Branch') ?> 
                            <span class="mx-2">|</span> 
                            <i class="fas fa-tag me-1"></i> <?= $client['client_type'] ?>
                        </div>
                    </div>
                    
                    <div class="col-md-5 text-md-end header-actions">
                        <div class="btn-group shadow-sm">
                            <a href="edit_client.php?id=<?= $clientId ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                            <button class="btn btn-success btn-sm" onclick="createJob()" <?= $isLocked ? 'disabled' : '' ?>>
                                <i class="fas fa-plus-circle me-1"></i> New Job
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="addPayment()" <?= $isLocked ? 'disabled' : '' ?>>
                                <i class="fas fa-money-bill-wave me-1"></i> Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 mt-4">
            
            <ul class="nav nav-tabs" id="clientTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button">Dashboard</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button">Financials</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs" type="button">Job History</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button">Invoices</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="rates-tab" data-bs-toggle="tab" data-bs-target="#rates" type="button">Rate Card</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs" type="button">Documents</button>
                </li>
            </ul>

            <div class="tab-content-wrapper shadow-sm mb-5" id="clientTabsContent">
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="text-center py-5">
                        <div class="spinner-border text-success" role="status"></div>
                        <div class="mt-2 text-muted">Loading Dashboard...</div>
                    </div>
                </div>
                <div class="tab-pane fade" id="financial" role="tabpanel"></div>
                <div class="tab-pane fade" id="jobs" role="tabpanel"></div>
                <div class="tab-pane fade" id="invoices" role="tabpanel"></div>
                <div class="tab-pane fade" id="rates" role="tabpanel"></div>
                <div class="tab-pane fade" id="docs" role="tabpanel"></div>
            </div>
        </div>
    </div>
</div>

<?php include 'modals/rate_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
/**
 * PLAN SECTION 16: UI BEHAVIOR
 * Handling Tab Persistence and AJAX Loading
 */
document.addEventListener("DOMContentLoaded", function() {
    // 1. Check for persisted tab in localStorage
    const activeTabId = localStorage.getItem('activeClientProfileTab') || 'overview';
    const tabEl = document.querySelector(`#${activeTabId}-tab`);
    
    if (tabEl) {
        const bsTab = new bootstrap.Tab(tabEl);
        bsTab.show();
        loadTabContent(activeTabId);
    }

    // 2. Tab Change Listener
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabBtn => {
        tabBtn.addEventListener('shown.bs.tab', function(event) {
            const targetId = event.target.id.replace('-tab', '');
            localStorage.setItem('activeClientProfileTab', targetId);
            loadTabContent(targetId);
        });
    });
});

/**
 * Dynamic Content Loader - PLAN SECTION 13/16
 */
function loadTabContent(tabName) {
    const container = document.getElementById(tabName);
    const clientId = <?= $clientId ?>;

    // Optional: Add a small fade effect while loading
    container.style.opacity = '0.5';

    // Map tab names to backend API files (Section 13)
    const apiMap = {
        'overview': 'fetch_client_overview.php',
        'financial': 'fetch_client_financial.php',
        'jobs': 'fetch_client_jobs.php',
        'invoices': 'fetch_client_invoices.php',
        'rates': 'fetch_client_rates.php',
        'docs': 'fetch_client_documents.php'
    };

    fetch(`${apiMap[tabName]}?id=${clientId}`)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
        })
        .catch(err => {
            container.innerHTML = `<div class="alert alert-danger">Error loading ${tabName}. Please try again.</div>`;
        });
}

// Action Button Functions
function createJob() {
    window.location.href = `add_job.php?client_id=<?= $clientId ?>`;
}

function addPayment() {
    window.location.href = `add_payment.php?client_id=<?= $clientId ?>`;
}
</script>

</body>
</html>