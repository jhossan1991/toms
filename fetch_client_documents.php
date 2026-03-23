<?php
include 'db.php';
$clientId = $_GET['id'] ?? 0;

if (!$clientId) exit("<div class='alert alert-danger text-center'>Invalid Client ID.</div>");

/** * PLAN SECTION 9: LEGAL & DOCUMENTS
 * Fetching document status and file paths from client_documents table
 */
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch();

// Fetch uploaded files
$docStmt = $pdo->prepare("SELECT * FROM client_documents WHERE client_id = ? ORDER BY upload_date DESC");
$docStmt->execute([$clientId]);
$docs = $docStmt->fetchAll();

// Check specific document statuses for the summary (Section 9)
$hasCR = !empty($client['cr_copy_path']);
$hasQID = !empty($client['qid_copy_path']);
$hasContract = !empty($client['contract_path']);
?>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-shield-check me-2 text-success"></i>Compliance Status</h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold small">Commercial Registration (CR)</div>
                            <div class="extra-small text-muted">Required for Corporate Clients</div>
                        </div>
                        <span class="badge <?= $hasCR ? 'bg-success' : 'bg-light text-muted border' ?>">
                            <?= $hasCR ? 'Uploaded' : 'Missing' ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold small">QID / Passport Copy</div>
                            <div class="extra-small text-muted">Required for Individuals</div>
                        </div>
                        <span class="badge <?= $hasQID ? 'bg-success' : 'bg-light text-muted border' ?>">
                            <?= $hasQID ? 'Uploaded' : 'Missing' ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold small">Signed Contract / NDA</div>
                            <div class="extra-small text-muted">Legal Service Agreement</div>
                        </div>
                        <span class="badge <?= $hasContract ? 'bg-success' : 'bg-light text-muted border' ?>">
                            <?= $hasContract ? 'Signed' : 'Not Signed' ?>
                        </span>
                    </div>
                </div>

                <hr>

                <form id="uploadDocForm" class="mt-3">
                    <input type="hidden" name="client_id" value="<?= $clientId ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Document Type</label>
                        <select name="doc_type" class="form-select form-select-sm" required>
                            <option value="CR Copy">CR Copy</option>
                            <option value="QID Copy">QID / Passport</option>
                            <option value="Signed Contract">Signed Contract</option>
                            <option value="NDA">NDA Signed</option>
                            <option value="Other">Other Document</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input type="file" name="client_file" class="form-control form-control-sm" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-upload me-1"></i> Upload Document
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-folder-open me-2 text-warning"></i>Document Vault</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="extra-small text-uppercase text-muted">
                            <th class="ps-3">File Name / Type</th>
                            <th>Uploaded</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($docs)): ?>
                            <tr><td colspan="3" class="text-center py-5 text-muted small">No documents found in the vault.</td></tr>
                        <?php else: foreach ($docs as $doc): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold small"><?= htmlspecialchars($doc['document_type']) ?></div>
                                    <div class="extra-small text-muted"><?= htmlspecialchars($doc['file_name']) ?></div>
                                </td>
                                <td class="small"><?= date('d M Y', strtotime($doc['upload_date'])) ?></td>
                                <td class="text-end pe-3">
                                    <a href="<?= $doc['file_path'] ?>" target="_blank" class="btn btn-sm btn-light border" title="View">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button class="btn btn-sm btn-light border text-danger" onclick="deleteDoc(<?= $doc['id'] ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .extra-small { font-size: 0.75rem; }
</style>

<script>
/**
 * PLAN SECTION 14: SECURITY (Delete Rule)
 */
function deleteDoc(id) {
    Swal.fire({
        title: 'Delete Document?',
        text: "This action cannot be undone. The file will be removed from the server.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`delete_doc_ajax.php?id=${id}`)
            .then(() => loadTabContent('docs'));
        }
    });
}

// Handle Upload Submission
document.getElementById('uploadDocForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('upload_doc_ajax.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            loadTabContent('docs');
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
});
</script>