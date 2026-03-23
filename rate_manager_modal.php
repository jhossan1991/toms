<div class="modal fade" id="rateManagerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Manage Rates for: <span id="modalClientName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rateForm" class="row g-3 mb-4 p-3 bg-light rounded border">
                    <input type="hidden" id="form_rate_id" name="form_rate_id">
                    <input type="hidden" id="form_client_id" name="form_client_id">
                    
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Service Category</label>
                        <select class="form-select form-select-sm" id="f_cat" name="f_cat" onchange="toggleFormFields()">
                            <option value="Translation">Translation</option>
                            <option value="PRO/Other">PRO/Other</option>
                        </select>
                    </div>

                    <div class="col-md-4 trans-field">
                        <label class="form-label small fw-bold">Source Language</label>
                        <input type="text" class="form-control form-control-sm" id="f_source" name="f_source" placeholder="e.g. Arabic">
                    </div>
                    <div class="col-md-4 trans-field">
                        <label class="form-label small fw-bold">Target Language</label>
                        <input type="text" class="form-control form-control-sm" id="f_target" name="f_target" placeholder="e.g. English">
                    </div>

                    <div class="col-md-8 pro-field" style="display:none;">
                        <label class="form-label small fw-bold">Description</label>
                        <input type="text" class="form-control form-control-sm" id="f_desc" name="f_desc" placeholder="e.g. Chamber Attestation">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Unit</label>
                        <select class="form-select form-select-sm" id="f_unit" name="f_unit">
                            <option value="Page">Page</option>
                            <option value="Word">Word</option>
                            <option value="Document">Document</option>
                            <option value="Hour">Hour</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Rate (QAR)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="f_rate" name="f_rate" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveRateData()">
                            <i class="fas fa-save me-1"></i> Save Rate
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm table-hover border">
                        <thead class="table-secondary">
                            <tr>
                                <th>Service</th>
                                <th>Details</th>
                                <th>Unit</th>
                                <th>Rate</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="ratesTableBody">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>