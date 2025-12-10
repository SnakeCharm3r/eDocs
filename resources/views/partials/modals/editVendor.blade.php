{{-- Edit Vendor Modal --}}
<div class="modal fade" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editVendorModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Vendor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editVendorForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_name" class="form-label">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_type" class="form-label">Vendor Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_type" name="type" required>
                                <option value="internal">Internal</option>
                                <option value="external">External</option>
                                <option value="goods">Goods Supplier</option>
                                <option value="services">Service Provider</option>
                                <option value="goods_and_services">Goods & Services</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_contact_person" class="form-label">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_contact_person" name="contact_person" required>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_contact_email" class="form-label">Contact Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_contact_email" name="contact_email" required>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_contact_phone" class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" id="edit_contact_phone" name="contact_phone">
                        </div>

                        <div class="col-md-6">
                            <label for="edit_owner_name" class="form-label">Owner/CEO Name</label>
                            <input type="text" class="form-control" id="edit_owner_name" name="owner_name">
                        </div>

                        <div class="col-md-6">
                            <label for="edit_registration_number" class="form-label">Registration Number</label>
                            <input type="text" class="form-control" id="edit_registration_number" name="registration_number">
                        </div>

                        <div class="col-md-6">
                            <label for="edit_tax_number" class="form-label">Tax Number</label>
                            <input type="text" class="form-control" id="edit_tax_number" name="tax_number">
                        </div>

                        <div class="col-md-6">
                            <label for="edit_industry" class="form-label">Industry</label>
                            <input type="text" class="form-control" id="edit_industry" name="industry">
                        </div>

                        <div class="col-md-6">
                            <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_rating" class="form-label">Rating</label>
                            <select class="form-select" id="edit_rating" name="rating">
                                <option value="">Select Rating</option>
                                <option value="1">★ Poor</option>
                                <option value="2">★★ Fair</option>
                                <option value="3">★★★ Good</option>
                                <option value="4">★★★★ Very Good</option>
                                <option value="5">★★★★★ Excellent</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="edit_address" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Vendor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

