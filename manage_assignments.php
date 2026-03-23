<div class="container-fluid p-4">
    <h2 class="fw-bold mb-4">Production & Assignment Manager</h2>
    
    <table class="table table-hover shadow-sm bg-white">
        <thead class="table-dark">
            <tr>
                <th>Job #</th>
                <th>Translator</th>
                <th>Category</th>
                <th>Task & Languages</th>
                <th>Volume (Pgs/Wds)</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>HM-2026-0042</td>
                <td><strong>Ahmed Ali</strong></td>
                <td><span class="badge bg-info">Internal</span></td>
                <td>Legal Translation<br><small>En > Ar</small></td>
                <td>10 Pgs / 2500 Wds</td>
                <td>12-Feb 10:00 AM</td>
                <td>
                    <select class="form-select form-select-sm status-select" onchange="updateStatus(1, this.value)">
                        <option value="Assigned" selected>Assigned</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-user-minus"></i> Reassign</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
function updateStatus(assignmentId, newStatus) {
    // Send AJAX to update 'translator_assignments' table
    fetch('update_assignment_status.php', {
        method: 'POST',
        body: JSON.stringify({ id: assignmentId, status: newStatus })
    }).then(res => {
        if(newStatus === 'Completed') {
            alert('Assignment finalized. Recorded in Translator Statement.');
        }
    });
}
</script>