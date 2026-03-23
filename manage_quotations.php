<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role   = $_SESSION['role'] ?? 'Staff';
$user_branch = $_SESSION['branch_id'] ?? 0;
$user_id     = $_SESSION['user_id'];

// --- 1. FETCH DATA ---
// We use q.quote_no explicitly. Ensure your database actually has data in this column.
$sql = "SELECT q.*, 
        COALESCE(NULLIF(c.company_name, ''), c.name) AS client_name,
        c.mobile_primary, 
        b.name as branch_name,
        u.full_name as creator_name,
        (SELECT COUNT(*) FROM quotation_versions qv WHERE qv.quote_id = q.id) as version_count
        FROM quotations q
        LEFT JOIN clients c ON q.client_id = c.id
        LEFT JOIN branches b ON q.branch_id = b.id
        LEFT JOIN users u ON q.created_by = u.id";

// Branch Filtering for BranchAdmin (Branch Manager)
if ($user_role === 'BranchAdmin') {
    $sql .= " WHERE q.branch_id = " . (int)$user_branch;
}

$sql .= " ORDER BY q.id DESC";
$quotations = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation List | ALHAYIKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; font-size: 0.85rem; }
        .table-card { border-radius: 12px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); background: #fff; margin-top: 20px; }
        .table thead th { background: #f8f9fa; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; padding: 12px; border-bottom: 2px solid #dee2e6; }
        .status-badge { width: 85px; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; padding: 4px 0; border-radius: 50px; display: inline-block; text-align: center; }
        
        /* Status Colors */
        .bg-draft { background-color: #e9ecef; color: #495057; }
        .bg-approved { background-color: #d1e7dd; color: #0f5132; }
        .bg-sent { background-color: #fff3cd; color: #856404; }
        .bg-converted { background-color: #cfe2ff; color: #084298; }
        
        .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; padding: 0; }
        .quote-link { text-decoration: none; font-weight: bold; color: #0d6efd; }
        .quote-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">Quotation Management</h4>
                <p class="text-muted small mb-0">List of all generated client proposals</p>
            </div>
            <a href="add_quotation.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Create Quotation
            </a>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="quoteSearch" class="form-control border-start-0" placeholder="Search Number, Client, or Mobile...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Quotation Number</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Branch</th>
                            <th>Total Amount</th>
                            <th>Valid Until</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th class="text-center">Ver.</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="quoteTableBody">
                        <?php if (empty($quotations)): ?>
                            <tr><td colspan="10" class="text-center py-5 text-muted">No quotations found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($quotations as $q): 
                                $isExpired = (strtotime($q['valid_until']) < time() && $q['status'] !== 'Converted');
                                
                                // PERMISSION CONTROL logic for Edit
                                $canEdit = ($user_role === 'SuperAdmin' || 
                                           ($user_role === 'BranchAdmin' && $q['branch_id'] == $user_branch) || 
                                           ($user_role === 'Staff' && $q['status'] === 'Draft' && $q['created_by'] == $user_id));
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="view_quotation.php?id=<?= $q['id'] ?>" class="quote-link">
                                        <?= !empty($q['quote_no']) ? htmlspecialchars($q['quote_no']) : 'N/A' ?>
                                    </a>
                                </td>
                                <td><?= date('d/m/Y', strtotime($q['created_at'])) ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($q['client_name']) ?></div>
                                    <div class="small text-muted"><?= $q['mobile_primary'] ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $q['branch_name'] ?></span></td>
                                <td class="fw-bold text-dark">QR <?= number_format($q['grand_total'], 2) ?></td>
                                <td>
                                    <span class="<?= $isExpired ? 'text-danger fw-bold' : '' ?>">
                                        <?= date('d/m/Y', strtotime($q['valid_until'])) ?>
                                        <?= $isExpired ? ' <i class="fas fa-clock"></i>' : '' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge bg-<?= strtolower($q['status']) ?>">
                                        <?= $q['status'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($q['creator_name']) ?></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-secondary"><?= $q['version_count'] ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm">
                                        <a href="view_quotation.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-white border btn-action" title="View Profile">
                                            <i class="fas fa-eye text-info"></i>
                                        </a>
                                        
                                        <?php if ($canEdit): ?>
                                        <a href="edit_quotation.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-white border btn-action" title="Edit">
                                            <i class="fas fa-edit text-primary"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function(){
        // Live Search Filter
        $("#quoteSearch").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#quoteTableBody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>
</body>
</html>