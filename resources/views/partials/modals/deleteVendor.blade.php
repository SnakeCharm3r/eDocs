{{-- Delete Vendor Modal
<div id="deleteModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-user-slash me-2"></i>Deactivate Vendor</h4>
            <button type="button" onclick="closeModal('deleteModal')" class="btn-close"></button>
        </div>

        <div class="modal-body">
            <input type="hidden" id="deleteId">
            
            <div class="vendor-details mb-4">
                <p class="mb-3">Are you sure you want to deactivate this vendor?</p>
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-primary" id="deleteVendorName"></h6>
                        <div class="row small text-muted">
                            <div class="col-6"><strong>ID:</strong> <span id="deleteVendorId"></span></div>
                            <div class="col-6"><strong>Type:</strong> <span id="deleteVendorType"></span></div>
                            <div class="col-12 mt-2"><strong>Contact:</strong> <span id="deleteVendorContact"></span></div>
                            <div class="col-12 mt-2"><strong>Email:</strong> <span id="deleteVendorEmail"></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="inactiveReason" class="form-label">
                    <i class="fas fa-comment-dots me-1"></i>Reason for Deactivation
                    <small class="text-muted">(Optional)</small>
                </label>
                <textarea id="inactiveReason" class="form-control" rows="3" placeholder="Enter reason for deactivation"></textarea>
            </div>

            <div class="alert alert-info small mb-0">
                <i class="fas fa-info-circle me-2"></i>
                This will change the vendor status to "inactive" and hide them from active listings. 
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" onclick="closeModal('deleteModal')" class="btn btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Cancel
            </button>
            <button type="button" onclick="confirmDelete()" class="btn btn-warning" id="confirmDeleteBtn">
                <i class="fas fa-user-slash me-1"></i>Deactivate Vendor
            </button>
        </div>
    </div>
</div> --}}