<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Fetch Job and Items
$stmt = $pdo->prepare("SELECT j.*, b.name as branch_name FROM jobs j LEFT JOIN branches b ON j.branch_id = b.id WHERE j.id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) die("Job not found.");

$itemsStmt = $pdo->prepare("SELECT * FROM job_items WHERE job_id = ?");
$itemsStmt->execute([$job_id]);
$items = $itemsStmt->fetchAll();

// 2. Handle AJAX requests for Translator Lists based on Type
if (isset($_GET['fetch_translators'])) {
    $type = $_GET['type'];
    if ($type == 'Internal') {
        $data = $pdo->query("SELECT id, name FROM users WHERE role = 'Translator' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($type == 'Branch') {
        $data = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    } else { // External/Vendor
        $data = $pdo->query("SELECT id, name FROM vendors WHERE service_type = 'Translation' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $item_ids = $_POST['assign_item_ids'] ?? []; // If empty, it's a whole job assignment
        $type = $_POST['translator_type'];
        $t_id = $_POST['translator_id'];
        $pages = $_POST['assigned_pages'];
        $deadline = $_POST['deadline'];
        $notes = $_POST['notes'];
        $status = $_POST['assignment_status'];

        // If specific items selected, assign many translators to one job (line by line)
        if (!empty($item_ids)) {
            foreach ($item_ids as $iid) {
                $ins = $pdo->prepare("INSERT INTO job_assignments (job_id, item_id, translator_type, translator_id, assigned_pages, deadline, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$job_id, $iid, $type, $t_id, $pages, $deadline, $notes, $status]);
            }
        } else {
            // Assign whole job to one translator
            $ins = $pdo->prepare("INSERT INTO job_assignments (job_id, translator_type, translator_id, assigned_pages, deadline, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$job_id, $type, $t_id, $pages, $deadline, $notes, $status]);
        }

        $pdo->commit();
        $success = "Assignment recorded successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Job #<?= htmlspecialchars($job['job_no']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .assignment-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table-selected { background-color: #e8f4fd !important; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Assigning Job: <span class="text-primary">#<?= $job['job_no'] ?></span></h2>
        <a href="manage_jobs.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if(isset($success)): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-7">
                <div class="card assignment-card mb-4">
                    <div class="card-header bg-white fw-bold">Select Lines to Assign</div>
                    <div class="card-body">
                        <p class="small text-muted">Leave all unchecked to assign the <b>Entire Job</b> to one translator.</p>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Service</th>
                                    <th>Description</th>
                                    <th>Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $item): ?>
                                <tr>
                                    <td><input type="checkbox" name="assign_item_ids[]" value="<?= $item['id'] ?>" class="item-chk"></td>
                                    <td><?= $item['service_type'] ?></td>
                                    <td><?= $item['description'] ?></td>
                                    <td><?= $item['qty'] ?> <?= $item['unit'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card assignment-card p-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assignment Option</label>
                        <select name="assignment_status" class="form-select">
                            <option value="Assigned">New Assignment</option>
                            <option value="Update">Update Assignment</option>
                            <option value="Reassigned">Re-Assign (Cancel Previous)</option>
                            <option value="Hold">Hold Assignment</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Translator Type</label>
                        <select name="translator_type" id="typeSelect" class="form-select" required onchange="loadTranslators()">
                            <option value="">-- Select Type --</option>
                            <option value="Internal">Internal (Employee)</option>
                            <option value="Branch">Branch</option>
                            <option value="External">External / Vendor</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Name</label>
                        <select name="translator_id" id="nameSelect" class="form-select" required disabled>
                            <option value="">Choose Type First</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Assigned Pages</label>
                            <input type="number" step="0.1" name="assigned_pages" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Deadline</label>
                            <input type="datetime-local" name="deadline" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Instructions for translator..."></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Process Assignment</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="card assignment-card mt-4">
        <div class="card-header bg-dark text-white">Received Work & Accounting</div>
        <div class="card-body">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Translator</th>
                        <th>Type</th>
                        <th>Actual Word Count</th>
                        <th>Calc. Pages (250/pg)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $assigns = $pdo->prepare("SELECT a.*, 
                        CASE 
                            WHEN a.translator_type = 'Internal' THEN (SELECT username FROM users WHERE id = a.translator_id)
                            WHEN a.translator_type = 'Branch' THEN (SELECT name FROM branches WHERE id = a.translator_id)
                            ELSE (SELECT name FROM vendors WHERE id = a.translator_id)
                        END as t_name
                        FROM job_assignments a WHERE a.job_id = ?");
                    $assigns->execute([$job_id]);
                    while($row = $assigns->fetch()):
                    ?>
                    <tr>
                        <td><?= $row['t_name'] ?></td>
                        <td><span class="badge bg-info text-dark"><?= $row['translator_type'] ?></span></td>
                        <td>
                            <input type="number" class="form-control form-control-sm word-input" 
                                   data-id="<?= $row['id'] ?>" value="<?= $row['received_word_count'] ?>" 
                                   oninput="calculatePages(this)">
                        </td>
                        <td><span class="calc-pg fw-bold"><?= number_format($row['received_word_count'] / 250, 2) ?></span></td>
                        <td>
                            <button type="button" class="btn btn-success btn-sm" onclick="saveAccounting(<?= $row['id'] ?>, this)">
                                Update Accounting
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function loadTranslators() {
        const type = $('#typeSelect').val();
        const $nameSelect = $('#nameSelect');
        
        if(!type) {
            $nameSelect.prop('disabled', true).html('<option>Choose Type First</option>');
            return;
        }

        $.get('assign_job.php', { id: <?= $job_id ?>, fetch_translators: 1, type: type }, function(data) {
            let html = '<option value="">-- Select Name --</option>';
            data.forEach(item => {
                html += `<option value="${item.id}">${item.name}</option>`;
            });
            $nameSelect.html(html).prop('disabled', false);
        });
    }

    function calculatePages(input) {
        const words = $(input).val();
        const pages = (words / 250).toFixed(2);
        $(input).closest('tr').find('.calc-pg').text(pages);
    }

    function saveAccounting(assignmentId, btn) {
        const words = $(btn).closest('tr').find('.word-input').val();
        // You would create a small update_accounting_action.php for this
        $.post('update_assignment_accounting.php', { id: assignmentId, word_count: words }, function(res) {
            alert('Accounting Updated');
        });
    }

    $('#selectAll').on('change', function() {
        $('.item-chk').prop('checked', this.checked);
    });
</script>
</body>
</html>