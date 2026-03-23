<?php
include 'db.php';
session_start();

// 1. Handle Add New Service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $name = $_POST['service_name'];
    $desc = $_POST['description'];
    $stmt = $pdo->prepare("INSERT INTO service_types (service_name, description) VALUES (?, ?)");
    $stmt->execute([$name, $desc]);
    header("Location: service_types.php?msg=added");
    exit();
}

// 2. Handle Edit Service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_service'])) {
    $id = $_POST['service_id'];
    $name = $_POST['service_name'];
    $desc = $_POST['description'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE service_types SET service_name = ?, description = ?, status = ? WHERE id = ?");
    $stmt->execute([$name, $desc, $status, $id]);
    header("Location: service_types.php?msg=updated");
    exit();
}

// 3. Handle Secure Delete
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'Delete') {
    $id = $_POST['delete_id'];
    // Check if linked to pricing_rules
    $check = $pdo->prepare("SELECT COUNT(*) FROM pricing_rules WHERE service_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        header("Location: service_types.php?err=linked");
    } else {
        $stmt = $pdo->prepare("DELETE FROM service_types WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: service_types.php?msg=deleted");
    }
    exit();
}

$services = $pdo->query("SELECT * FROM service_types ORDER BY service_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Types | Al Hayiki</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold"><i class="fas fa-concierge-bell me-2 text-primary"></i>Service Types</h3>
            <div class="d-flex gap-2">
                <div class="input-group" style="width: 300px;">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="serviceSearch" class="form-control border-start-0" placeholder="Search services...">
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="fas fa-plus me-2"></i>Add New
                </button>
            </div>
        </div>

        <?php if(isset($_GET['err']) && $_GET['err'] == 'linked'): ?>
            <div class="alert alert-danger">Cannot delete: This service is linked to active Pricing Rules.</div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0" id="serviceTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Service Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $s): ?>
                        <tr>
                            <td class="ps-4 fw-bold service-name-cell"><?= htmlspecialchars($s['service_name']) ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($s['description']) ?></td>
                            <td>
                                <span class="badge rounded-pill <?= $s['status'] == 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $s['status'] ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border edit-btn" 
                                        data-id="<?= $s['id'] ?>" 
                                        data-name="<?= htmlspecialchars($s['service_name']) ?>" 
                                        data-desc="<?= htmlspecialchars($s['description']) ?>" 
                                        data-status="<?= $s['status'] ?>">
                                    <i class="fas fa-edit text-primary"></i>
                                </button>
                                <button class="btn btn-sm btn-light border delete-btn" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['service_name']) ?>">
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

<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5>Add New Service</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Service Name</label><input type="text" name="service_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" name="add_service" class="btn btn-primary w-100">Save Service</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5>Edit Service</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="service_id" id="edit_service_id">
                <div class="mb-3"><label class="form-label">Service Name</label><input type="text" name="service_name" id="edit_service_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="edit_description" class="form-control" rows="3"></textarea></div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" name="edit_service" class="btn btn-primary w-100">Update Service</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" class="modal-content">
            <div class="modal-body text-center">
                <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                <p>To delete <b id="del_name"></b>, type <b>Delete</b> below:</p>
                <input type="hidden" name="delete_id" id="del_id">
                <input type="text" name="confirm_delete" class="form-control text-center" placeholder="Type Delete" required>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-danger w-100">Confirm Destruction</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Live Search Logic
$("#serviceSearch").on("keyup", function() {
    var value = $(this).val().toLowerCase();
    $("#serviceTable tbody tr").filter(function() {
        $(this).toggle($(this).find(".service-name-cell").text().toLowerCase().indexOf(value) > -1)
    });
});

// Edit Button Click - Fill Modal
$(".edit-btn").click(function() {
    $("#edit_service_id").val($(this).data('id'));
    $("#edit_service_name").val($(this).data('name'));
    $("#edit_description").val($(this).data('desc'));
    $("#edit_status").val($(this).data('status'));
    new bootstrap.Modal(document.getElementById('editServiceModal')).show();
});

// Delete Button Click - Fill Modal
$(".delete-btn").click(function() {
    $("#del_id").val($(this).data('id'));
    $("#del_name").text($(this).data('name'));
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
});
</script>
</body>
</html>