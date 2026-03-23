<?php
session_start();
include 'db.php';

// Security check: If not logged in, stop immediately
if (!isset($_SESSION['user_id'])) { 
    exit; 
}

/**
 * Parameters from AJAX call
 */
$view = $_GET['view'] ?? 'jobs'; 
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$branchFilter = $_GET['branch'] ?? ''; 
$mode = $_GET['mode'] ?? 'active'; // Used for clients view (active/archived)

$params = [];

// --- 1. JOBS SEARCH LOGIC ---
if ($view == 'jobs') {
    $query = "SELECT j.*, c.name as client_name, b.name as branch_name  
              FROM jobs j 
              JOIN clients c ON j.client_id = c.id 
              LEFT JOIN branches b ON j.branch_id = b.id
              WHERE 1=1";
    
    if ($branchFilter !== '') { 
        $query .= " AND j.branch_id = ?"; 
        $params[] = (int)$branchFilter; 
    }
    
    if ($statusFilter !== '') { 
        $query .= " AND j.status = ?"; 
        $params[] = $statusFilter; 
    }
    
    if (!empty($search)) {
        $query .= " AND (j.job_no LIKE ? OR c.name LIKE ?)";
        $params[] = "%$search%"; 
        $params[] = "%$search%";
    }
    
    $query .= " ORDER BY j.created_at DESC LIMIT 50";

// --- 2. CLIENTS SEARCH LOGIC (Updated for 9-Column Layout) ---
} else {
    $is_archived = ($mode === 'archived') ? 1 : 0;

    $query = "SELECT c.*, 
              b.name as branch_name,
              (SELECT SUM(amount_due) FROM jobs WHERE client_id = c.id AND payment_status != 'Paid') as balance,
              (SELECT MAX(created_at) FROM jobs WHERE client_id = c.id) as last_job_date,
              (SELECT COUNT(*) FROM jobs WHERE client_id = c.id) as activity_count
              FROM clients c 
              LEFT JOIN branches b ON c.branch_id = b.id
              WHERE c.is_archived = ?";
    
    $params[] = $is_archived;

    if (!empty($search)) {
        $query .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.mobile_primary LIKE ? OR c.id LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm; 
        $params[] = $searchTerm; 
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY c.id ASC LIMIT 50";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$dataList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle No Results
if(empty($dataList)) {
    $cols = ($view == 'jobs') ? 5 : 9;
    echo '<tr><td colspan="'.$cols.'" class="text-center py-5 text-muted">No records found matching your search.</td></tr>';
    exit;
}

// --- 3. HTML OUTPUT GENERATION ---
foreach($dataList as $row):
    if ($view == 'jobs'): ?>
        <tr>
            <td class="ps-4">
                <div class="fw-bold text-dark"><?= htmlspecialchars($row['job_no']) ?></div>
                <div class="text-muted" style="font-size: 0.75rem;">
                    <i class="far fa-calendar-alt me-1"></i><?= date('d-m-Y', strtotime($row['created_at'])) ?>
                </div>
            </td>
            <td>
                <div class="fw-bold"><?= htmlspecialchars($row['client_name']) ?></div>
                <span class="badge bg-light text-dark fw-normal border" style="font-size: 0.7rem;">
                    <?= htmlspecialchars($row['branch_name'] ?? 'N/A') ?>
                </span>
            </td>
            <td>
                <div class="fw-bold text-success">QR <?= number_format($row['grand_total'] ?? 0, 2) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($row['payment_status'] ?? 'Unpaid') ?></div>
            </td>
            <td>
                <?php 
                    $status = $row['status'] ?? 'Draft';
                    $badgeClass = match($status) {
                        'Draft' => 'bg-warning text-dark',
                        'In Progress' => 'bg-primary',
                        'Ready' => 'bg-success',
                        'Completed' => 'bg-dark',
                        default => 'bg-secondary'
                    };
                ?>
                <span class="badge <?= $badgeClass ?> status-badge"><?= $status ?></span>
            </td>
            <td class="text-end pe-4">
                <div class="btn-group shadow-sm">
                    <a href="view_job.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border bg-white" title="View Detail">
                        <i class="fas fa-eye text-primary"></i>
                    </a>
                    <a href="print_job.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-white border bg-white" title="Print Slip">
                        <i class="fas fa-print"></i>
                    </a>
                </div>
            </td>
        </tr>

    <?php else: ?>
        <tr>
            <td class="ps-4 fw-bold text-muted">#<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></td>
            <td>
                <a href="client_view.php?id=<?= $row['id'] ?>" class="client-link text-decoration-none fw-bold text-dark">
                    <?= htmlspecialchars($row['name']) ?>
                </a>
                <div class="small text-muted"><?= htmlspecialchars($row['client_type']) ?></div>
            </td>
            <td>
                <ul class="list-unstyled mb-0 small">
                    <li><i class="fas fa-phone-alt fa-fw me-2 text-muted"></i><?= htmlspecialchars($row['mobile_primary']) ?></li>
                    <li><i class="fas fa-envelope fa-fw me-2 text-muted"></i><?= $row['email'] ?: '<span class="text-muted">No Email</span>' ?></li>
                </ul>
            </td>
            <td>
                <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['branch_name'] ?? 'Unassigned') ?></span>
            </td>
            <td>
                <?php 
                    $s = $row['status'];
                    $cls = ($s == 'Active') ? 'badge-active' : (($s == 'On Hold') ? 'badge-hold' : 'badge-inactive');
                ?>
                <span class="badge <?= $cls ?>"><?= $s ?></span>
            </td>
            <td>
                <?php $bal = $row['balance'] ?? 0; ?>
                <span class="fw-bold <?= ($bal > 0) ? 'text-danger' : 'text-success' ?>">
                    <?= number_format($bal, 2) ?>
                </span>
            </td>
            <td>
                <div class="small">
                    <span class="fw-bold text-dark"><?= $row['activity_count'] ?></span> <span class="text-muted">Jobs</span>
                </div>
            </td>
            <td class="small">
                <?= $row['last_job_date'] ? date('d M Y', strtotime($row['last_job_date'])) : '<span class="text-muted">Never</span>' ?>
            </td>
            <td class="text-end pe-4">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Actions</button>
                    <ul class="dropdown-menu shadow">
                        <li><a class="dropdown-item" href="client_view.php?id=<?= $row['id'] ?>"><i class="fas fa-eye me-2 text-info"></i> View Profile</a></li>
                        <li><a class="dropdown-item" href="edit_client.php?id=<?= $row['id'] ?>"><i class="fas fa-edit me-2 text-primary"></i> Edit</a></li>
                        
                        <?php if($row['is_archived'] == 0): ?>
                            <li><a class="dropdown-item text-warning" href="#" onclick="toggleArchive(<?= $row['id'] ?>, 'archive')">
                                <i class="fas fa-archive me-2"></i> Move to Archive
                            </a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item text-success" href="#" onclick="toggleArchive(<?= $row['id'] ?>, 'unarchive')">
                                <i class="fas fa-box-open me-2"></i> Restore to Active
                            </a></li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>')"><i class="fas fa-trash-alt me-2"></i> Permanent Delete</a></li>
                    </ul>
                </div>
            </td>
        </tr>
    <?php endif; 
endforeach; ?>