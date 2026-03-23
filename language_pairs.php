<?php
include 'db.php';
session_start();

// 1. Handle Add New Pair (With Duplicate Check)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_pair'])) {
    $source = trim($_POST['source_language']);
    $target = trim($_POST['target_language']);
    $code = strtoupper(substr($source, 0, 2)) . '-' . strtoupper(substr($target, 0, 2));

    // Check for existing pair
    $check = $pdo->prepare("SELECT id FROM language_pairs WHERE source_language = ? AND target_language = ?");
    $check->execute([$source, $target]);
    
    if ($check->rowCount() > 0) {
        header("Location: language_pairs.php?err=duplicate");
    } else {
        $stmt = $pdo->prepare("INSERT INTO language_pairs (source_language, target_language, pair_code) VALUES (?, ?, ?)");
        $stmt->execute([$source, $target, $code]);
        header("Location: language_pairs.php?msg=added");
    }
    exit();
}

// 2. Handle Edit Pair
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_pair'])) {
    $id = $_POST['pair_id'];
    $source = trim($_POST['source_language']);
    $target = trim($_POST['target_language']);
    $status = $_POST['status'];
    $code = strtoupper(substr($source, 0, 2)) . '-' . strtoupper(substr($target, 0, 2));

    $stmt = $pdo->prepare("UPDATE language_pairs SET source_language = ?, target_language = ?, pair_code = ?, status = ? WHERE id = ?");
    $stmt->execute([$source, $target, $code, $status, $id]);
    header("Location: language_pairs.php?msg=updated");
    exit();
}

// 3. Handle Secure Delete
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'Delete') {
    $id = $_POST['delete_id'];
    // Check if linked to pricing_rules
    $check = $pdo->prepare("SELECT COUNT(*) FROM pricing_rules WHERE language_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        header("Location: language_pairs.php?err=linked");
    } else {
        $stmt = $pdo->prepare("DELETE FROM language_pairs WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: language_pairs.php?msg=deleted");
    }
    exit();
}

$pairs = $pdo->query("SELECT * FROM language_pairs ORDER BY source_language ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Language Pairs | Al Hayiki</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold"><i class="fas fa-language me-2 text-primary"></i>Language Pairs</h3>
            <div class="d-flex gap-2">
                <input type="text" id="pairSearch" class="form-control" placeholder="Search languages..." style="width: 250px;">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPairModal">
                    <i class="fas fa-plus me-2"></i>Add New Pair
                </button>
            </div>
        </div>

        <?php if(isset($_GET['err'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_GET['err'] == 'duplicate' ? 'This language pair already exists!' : 'Cannot delete: Pair is linked to Pricing Rules.' ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0" id="pairTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Pair Code</th>
                            <th>Source</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pairs as $p): ?>
                        <tr>
                            <td class="ps-4"><span class="badge bg-secondary"><?= $p['pair_code'] ?></span></td>
                            <td class="fw-bold source-cell"><?= htmlspecialchars($p['source_language']) ?></td>
                            <td class="target-cell"><?= htmlspecialchars($p['target_language']) ?></td>
                            <td><span class="badge rounded-pill <?= $p['status'] == 'Active' ? 'bg-success' : 'bg-secondary' ?>"><?= $p['status'] ?></span></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border edit-btn" 
                                        data-id="<?= $p['id'] ?>" 
                                        data-source="<?= htmlspecialchars($p['source_language']) ?>" 
                                        data-target="<?= htmlspecialchars($p['target_language']) ?>" 
                                        data-status="<?= $p['status'] ?>">
                                    <i class="fas fa-edit text-primary"></i>
                                </button>
                                <button class="btn btn-sm btn-light border delete-btn" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['source_language']) ?>-<?= htmlspecialchars($p['target_language']) ?>">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addPairModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5>Add Language Pair</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label">Source Language</label><input type="text" name="source_language" class="form-control" required></div>
                    <div class="col-6 mb-3"><label class="form-label">Target Language</label><input type="text" name="target_language" class="form-control" required></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="add_pair" class="btn btn-primary w-100">Save Pair</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editPairModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5>Edit Language Pair</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="pair_id" id="edit_pair_id">
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label">Source</label><input type="text" name="source_language" id="edit_source" class="form-control" required></div>
                    <div class="col-6 mb-3"><label class="form-label">Target</label><input type="text" name="target_language" id="edit_target" class="form-control" required></div>
                </div>
                <div class="mb-3"><label class="form-label">Status</label><select name="status" id="edit_status" class="form-select"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
            </div>
            <div class="modal-footer"><button type="submit" name="edit_pair" class="btn btn-primary w-100">Update Pair</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" class="modal-content">
            <div class="modal-body text-center">
                <p>To delete <b id="del_name"></b>, type <b>Delete</b> below:</p>
                <input type="hidden" name="delete_id" id="del_id">
                <input type="text" name="confirm_delete" class="form-control text-center" placeholder="Type Delete" required>
            </div>
            <div class="modal-footer border-0"><button type="submit" class="btn btn-danger w-100">Confirm</button></div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live Search
$("#pairSearch").on("keyup", function() {
    var val = $(this).val().toLowerCase();
    $("#pairTable tbody tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1)
    });
});

// Edit
$(".edit-btn").click(function() {
    $("#edit_pair_id").val($(this).data('id'));
    $("#edit_source").val($(this).data('source'));
    $("#edit_target").val($(this).data('target'));
    $("#edit_status").val($(this).data('status'));
    new bootstrap.Modal(document.getElementById('editPairModal')).show();
});

// Delete
$(".delete-btn").click(function() {
    $("#del_id").val($(this).data('id'));
    $("#del_name").text($(this).data('name'));
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
});
</script>
</body>
</html>