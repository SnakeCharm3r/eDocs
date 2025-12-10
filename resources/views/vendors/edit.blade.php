@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Header -->
            <div class="page-header d-flex justify-content-between align-items-center">
                <h3 class="page-title text-center">Edit Vendor</h3>
                <a href="{{ route('vendors.index') }}" class="btn btn-secondary">Back to List</a>
            </div>

            <!-- Vendor Edit Form -->
            <div class="card mt-4">
                <div class="card-body">
                    <form action="{{ route('vendors.update', $vendor->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="business_owner">Business Owner</label>
                                    <input type="text" name="business_owner" id="business_owner"
                                        class="form-control @error('business_owner') is-invalid @enderror"
                                        value="{{ old('business_owner', $vendor->business_owner) }}" required>
                                    @error('business_owner')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="contact_person">Contact Person</label>
                                    <input type="text" name="contact_person" id="contact_person"
                                        class="form-control @error('contact_person') is-invalid @enderror"
                                        value="{{ old('contact_person', $vendor->contact_person) }}" required>
                                    @error('contact_person')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="telephone_number">Phone</label>
                                    <input type="text" name="telephone_number" id="telephone_number"
                                        class="form-control @error('telephone_number') is-invalid @enderror"
                                        value="{{ old('telephone_number', $vendor->telephone_number) }}" required>
                                    @error('telephone_number')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="email_address">Email Address</label>
                                    <input type="email" name="email_address" id="email_address"
                                        class="form-control @error('email_address') is-invalid @enderror"
                                        value="{{ old('email_address', $vendor->email_address) }}" required>
                                    @error('email_address')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="physical_address">Physical Address</label>
                                    <input type="text" name="physical_address" id="physical_address"
                                        class="form-control @error('physical_address') is-invalid @enderror"
                                        value="{{ old('physical_address', $vendor->physical_address) }}" required>
                                    @error('physical_address')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contract_file">Contract File (PDF, DOC, DOCX)</label>
                                    <input type="file" name="contract_file" id="contract_file"
                                        class="form-control @error('contract_file') is-invalid @enderror">
                                    @if ($vendor->contract_file)
                                        <a href="{{ Storage::url($vendor->contract_file) }}" class="btn btn-info mt-2"
                                            target="_blank">View Current Contract</a>
                                    @endif
                                    @error('contract_file')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="contract_total_value">Contract Total Value</label>
                                    <input type="number" name="contract_total_value" id="contract_total_value"
                                        class="form-control @error('contract_total_value') is-invalid @enderror"
                                        step="0.01" value="{{ old('contract_total_value', $vendor->contract_total_value) }}">
                                    @error('contract_total_value')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="duration_years">Duration (Years)</label>
                                    <input type="number" name="duration_years" id="duration_years"
                                        class="form-control @error('duration_years') is-invalid @enderror"
                                        value="{{ old('duration_years', $vendor->duration_years) }}">
                                    @error('duration_years')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="likelihood_rating">Likelihood Rating</label>
                                    <input type="number" name="likelihood_rating" id="likelihood_rating"
                                        class="form-control @if($vendor->likelihood_rating <= 3) table-success @elseif($vendor->likelihood_rating <= 6) table-warning @else table-danger @endif @error('likelihood_rating') is-invalid @enderror"
                                        value="{{ old('likelihood_rating', $vendor->likelihood_rating) }}">
                                    @error('likelihood_rating')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="impact_rating">Impact Rating</label>
                                    <input type="number" name="impact_rating" id="impact_rating"
                                        class="form-control @if($vendor->impact_rating <= 3) table-success @elseif($vendor->impact_rating <= 6) table-warning @else table-danger @endif @error('impact_rating') is-invalid @enderror"
                                        value="{{ old('impact_rating', $vendor->impact_rating) }}">
                                    @error('impact_rating')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="overall_risk_score">Overall Risk Score</label>
                                    <input type="number" name="overall_risk_score" id="overall_risk_score"
                                        class="form-control @if($vendor->overall_risk_score <= 3) table-success @elseif($vendor->overall_risk_score <= 6) table-warning @else table-danger @endif @error('overall_risk_score') is-invalid @enderror"
                                        value="{{ old('overall_risk_score', $vendor->overall_risk_score) }}">
                                    @error('overall_risk_score')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-primary">Update Vendor</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
// Configuration
const config = {
    table: null,
    months: ['January','February','March','April','May','June','July','August','September','October','November','December']
};

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        config.table = window.dt_vendorsTable || new DataTable('#vendorsTable');
        setupFilters();
        updateEntriesStatus($('#showEntries').val());
        
        // Initialize popovers for attachment badges
        const popoverTriggerList = document.querySelectorAll('.attachments-badge');
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl, {
            trigger: 'hover',
            placement: 'left',
            html: true,
            delay: { 
                "show": 0,    // Show immediately
                "hide": 5000   // Hide after 500ms delay
            }
        }));

        // Keep popover open when hovering over popover content
        popoverTriggerList.forEach(popoverTriggerEl => {
            let popoverHideTimeout;
            
            popoverTriggerEl.addEventListener('shown.bs.popover', function() {
                const popover = bootstrap.Popover.getInstance(this);
                const popoverElement = popover.tip;
                
                // When mouse enters popover, clear hide timeout
                popoverElement.addEventListener('mouseenter', () => {
                    clearTimeout(popoverHideTimeout);
                });
                
                // When mouse leaves popover, set hide timeout
                popoverElement.addEventListener('mouseleave', () => {
                    popoverHideTimeout = setTimeout(() => {
                        popover.hide();
                    }, 300);
                });
            });
        });

        // Initialize file upload preview for edit modal
        initializeFileUploadPreview();

    }, 500);
});

// Filter Functions
function setupFilters() {
    $('#monthFilter, #statusFilter').on('change', applyFilters);
    $('#showEntries').on('change', function() {
        changePageLength($(this).val());
    });
}

function applyFilters() {
    if (!config.table) return;
    
    const monthFilter = $('#monthFilter').val();
    const statusFilter = $('#statusFilter').val();
    
    config.table.columns().search('');
    
    let activeFilters = [];
    if (monthFilter) {
        config.table.column(9).search(monthFilter, true, false);
        activeFilters.push(`Month: ${config.months[monthFilter-1]}`);
    }
    if (statusFilter) {
        config.table.column(8).search(statusFilter, true, false);
        activeFilters.push(`Status: ${statusFilter}`);
    }
    
    config.table.draw();
    updateFilterStatus(activeFilters);
}

function changePageLength(entries) {
    if (!config.table) return;
    
    config.table.page.len(entries === '-1' ? -1 : parseInt(entries)).draw();
    updateEntriesStatus(entries);
    updateFilterStatus();
}

function updateEntriesStatus(entries) {
    const statusText = entries === '-1' ? 'All entries' : `${entries} per page`;
    $('#showEntries').siblings('.form-label').text(`Show Entries (${statusText})`);
}

function updateFilterStatus(activeFilters = []) {
    const entries = $('#showEntries').val();
    const entriesText = entries === '-1' ? 'All entries' : `${entries} per page`;
    const statusElement = $('#filterStatus');
    
    if (activeFilters.length > 0) {
        statusElement.html(`<i class="fas fa-filter text-success"></i> ${entriesText} | Filters: ${activeFilters.join(', ')}`)
            .removeClass('text-muted').addClass('text-success');
    } else {
        statusElement.html(`<i class="fas fa-list text-muted"></i> ${entriesText}`)
            .removeClass('text-success').addClass('text-muted');
    }
}

function clearFilters() {
    $('#monthFilter, #statusFilter').val('');
    $('#showEntries').val('10');
    
    if (config.table) {
        config.table.columns().search('').page.len(10).draw();
    }
    
    updateEntriesStatus('10');
    updateFilterStatus([]);
}

// Vendor Functions
function editVendor(
    id, name, type, contact_person, contact_phone, contact_email, 
    address, rating, owner_name, registration_number, tax_number, 
    industry, status, currency = '', attachments = []
) {
    console.log('Opening edit modal for:', name);
    
    // Convert status to lowercase to match controller validation
    const statusMap = {
        'Active': 'active',
        'Inactive': 'inactive',
        'Suspended': 'suspended'
    };
    const formattedStatus = statusMap[status] || 'active';
    
    // Fill the form with current data
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name || '';
    document.getElementById('editType').value = type || '';
    document.getElementById('editContactPerson').value = contact_person || '';
    document.getElementById('editContactPhone').value = contact_phone || '';
    document.getElementById('editContactEmail').value = contact_email || '';
    document.getElementById('editAddress').value = address || '';
    document.getElementById('editRating').value = rating || '';
    document.getElementById('editOwnerName').value = owner_name || '';
    document.getElementById('editRegistrationNumber').value = registration_number || '';
    document.getElementById('editTaxNumber').value = tax_number || '';
    document.getElementById('editIndustry').value = industry || '';
    document.getElementById('editStatus').value = formattedStatus;
    document.getElementById('editCurrency').value = currency || '';
    
    // Handle existing files
    displayExistingFiles(attachments);
    
    // Clear file preview and reset file input
    document.getElementById('editFilePreview').innerHTML = '<p class="text-muted mb-2">No new files selected</p>';
    document.getElementById('editDocuments').value = '';
    
    // Clear any removed files tracking
    $('input[name="removed_files[]"]').remove();
    
    clearEditFormErrors();
    showModal('editModal');
}

function displayExistingFiles(attachments) {
    const existingFilesSection = document.getElementById('existingFilesSection');
    const existingFilesList = document.getElementById('existingFilesList');
    
    if (attachments && attachments.length > 0) {
        let filesHTML = '';
        attachments.forEach((attachment, index) => {
            const fileSizeKB = attachment.file_size ? (attachment.file_size / 1024).toFixed(1) : '0';
            const fileType = attachment.file_type || 'file';
            const fileName = attachment.original_name || 'Document';
            
            filesHTML += `
                <div class="existing-file-item d-flex align-items-center justify-content-between mb-2 p-2 border rounded" data-index="${index}">
                    <div class="d-flex align-items-center">
                        <i class="fas ${fileType === 'pdf' ? 'fa-file-pdf text-danger' : 'fa-file-word text-primary'} me-2"></i>
                        <div>
                            <div class="fw-medium" style="font-size: 12px;">${fileName}</div>
                            <small class="text-muted" style="font-size: 11px;">
                                ${fileSizeKB} KB • ${fileType.toUpperCase()}
                            </small>
                        </div>
                    </div>
                    <div>
                        ${attachment.file_path ? `
                        <a href="/storage/${attachment.file_path}" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                            <i class="fas fa-download"></i>
                        </a>
                        ` : ''}
                        <button type="button" onclick="removeExistingFile(${index})" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        existingFilesList.innerHTML = filesHTML;
        existingFilesSection.style.display = 'block';
    } else {
        existingFilesSection.style.display = 'none';
        existingFilesList.innerHTML = '';
    }
}

function removeExistingFile(index) {
    // Add hidden input to track files to be removed
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'removed_files[]';
    hiddenInput.value = index;
    document.getElementById('editForm').appendChild(hiddenInput);
    
    // Remove from UI
    const fileItem = document.querySelector(`.existing-file-item[data-index="${index}"]`);
    if (fileItem) {
        fileItem.style.opacity = '0.5';
        fileItem.style.textDecoration = 'line-through';
        fileItem.querySelector('button').disabled = true;
    }
}

// File upload preview for edit modal
function initializeFileUploadPreview() {
    const editFileInput = document.getElementById('editDocuments');
    const editFilePreview = document.getElementById('editFilePreview');

    if (editFileInput && editFilePreview) {
        editFileInput.addEventListener('change', function(e) {
            const files = e.target.files;
            editFilePreview.innerHTML = '';

            if (files.length === 0) {
                editFilePreview.innerHTML = '<p class="text-muted mb-2">No new files selected</p>';
                return;
            }

            const fileList = document.createElement('div');
            fileList.className = 'border rounded p-3 bg-light';
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileItem = createFilePreviewItem(file);
                fileList.appendChild(fileItem);
            }
            
            editFilePreview.appendChild(fileList);
        });
    }

    function createFilePreviewItem(file) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-preview-item d-flex align-items-center justify-content-between mb-2 p-2 border rounded bg-white';
        
        const fileExtension = file.name.split('.').pop().toLowerCase();
        const fileIcon = fileExtension === 'pdf' ? 'fas fa-file-pdf text-danger' : 'fas fa-file-word text-primary';

        fileItem.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="${fileIcon} me-2"></i>
                <div>
                    <div class="fw-medium" style="font-size: 12px;">${file.name}</div>
                    <small class="text-muted" style="font-size: 11px;">
                        ${(file.size / 1024).toFixed(1)} KB • ${fileExtension.toUpperCase()}
                    </small>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger file-remove">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Add remove functionality
        const removeBtn = fileItem.querySelector('.file-remove');
        removeBtn.addEventListener('click', function() {
            removeFileFromInput(file.name, editFileInput);
            fileItem.remove();
            
            // Check if any files left
            const remainingItems = editFilePreview.querySelectorAll('.file-preview-item');
            if (remainingItems.length === 0) {
                editFilePreview.innerHTML = '<p class="text-muted mb-2">No new files selected</p>';
            }
        });

        return fileItem;
    }

    function removeFileFromInput(filename, fileInput) {
        const dt = new DataTransfer();
        const files = fileInput.files;
        
        for (let i = 0; i < files.length; i++) {
            if (files[i].name !== filename) {
                dt.items.add(files[i]);
            }
        }
        
        fileInput.files = dt.files;
    }
}

// Update vendor details with file upload support
function saveVendor() {
    const saveBtn = $('#saveVendorBtn');
    const originalText = saveBtn.html();
    const vendorId = $('#editId').val();
    
    console.log('Starting update for vendor ID:', vendorId);
    
    saveBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...').prop('disabled', true);

    // Use FormData for file uploads
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('_token', '{{ csrf_token() }}');
    
    // Add all form fields
    formData.append('name', $('#editName').val());
    formData.append('type', $('#editType').val());
    formData.append('contact_person', $('#editContactPerson').val());
    formData.append('contact_phone', $('#editContactPhone').val());
    formData.append('contact_email', $('#editContactEmail').val());
    formData.append('address', $('#editAddress').val());
    formData.append('rating', $('#editRating').val());
    formData.append('owner_name', $('#editOwnerName').val());
    formData.append('registration_number', $('#editRegistrationNumber').val());
    formData.append('tax_number', $('#editTaxNumber').val());
    formData.append('industry', $('#editIndustry').val());
    formData.append('status', $('#editStatus').val()); // Should be 'active' or 'inactive'
    formData.append('currency', $('#editCurrency').val());
    
    // Add new files
    const files = $('#editDocuments')[0].files;
    for (let i = 0; i < files.length; i++) {
        formData.append('documents[]', files[i]);
    }
    
    // Add removed files
    $('input[name="removed_files[]"]').each(function() {
        formData.append('removed_files[]', $(this).val());
    });

    console.log('Sending update request to:', `/vendors/${vendorId}`);
    console.log('Form data fields:');
    for (let [key, value] of formData.entries()) {
        if (key !== 'documents[]') {
            console.log(key, value);
        }
    }

    fetch(`/vendors/${vendorId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            showAlert('success', data.message || 'Vendor updated successfully!');
            closeModal('editModal');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            if (data.errors) {
                showValidationErrors(data.errors);
            } else {
                showAlert('error', data.message || 'Failed to update vendor');
            }
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        showAlert('error', 'Network error occurred while updating vendor.');
    })
    .finally(() => {
        saveBtn.html(originalText).prop('disabled', false);
    });
}

function deleteVendor(
    id, name, type, contact_person, contact_phone, contact_email
) {
    console.log('Opening deactivate modal for:', name);
    
    try {
        // Set the vendor data in modal
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteVendorName').textContent = name;
        document.getElementById('deleteVendorId').textContent = id;
        document.getElementById('deleteVendorType').textContent = type;
        document.getElementById('deleteVendorContact').textContent = `${contact_person} (${contact_phone || 'No phone'})`;
        document.getElementById('deleteVendorEmail').textContent = contact_email;
        
        // Clear previous reason
        document.getElementById('inactiveReason').value = '';
        
        // Reset button state
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.innerHTML = '<i class="fas fa-user-slash me-1"></i>Deactivate Vendor';
        confirmBtn.disabled = false;
        
        // Show the modal
        showModal('deleteModal');
        
    } catch (error) {
        console.error('Error opening delete modal:', error);
        showAlert('error', 'Error opening deletion dialog.');
    }
}

function confirmDelete() {
    const id = $('#deleteId').val();
    const name = $('#deleteVendorName').text();
    const reason = $('#inactiveReason').val() || 'Deactivated by user';
    const btn = $('#confirmDeleteBtn');
    const originalText = btn.html();
    
    btn.html('<i class="fas fa-spinner fa-spin me-1"></i>Deactivating...').prop('disabled', true);

    fetch(`/vendors/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            _token: '{{ csrf_token() }}',
            _method: 'DELETE',
            inactive_reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `✅ Vendor "${name}" deactivated!`);
            closeModal('deleteModal');
            removeVendorFromTable(id);
        } else {
            showAlert('error', data.message || 'Failed to deactivate vendor');
            btn.html(originalText).prop('disabled', false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Network error occurred while deactivating vendor.');
        btn.html(originalText).prop('disabled', false);
    });
}

// Helper Functions
function removeVendorFromTable(vendorId) {
    if (config.table) {
        config.table.rows().every(function() {
            if (this.data()[0] == vendorId) {
                $(this.node()).css({opacity: 0, transform: 'translateX(-100%)'});
                setTimeout(() => this.remove().draw(false), 300);
                return false;
            }
        });
    }
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function showAlert(type, message) {
    typeof Swal !== 'undefined' 
        ? Swal.fire({icon: type, title: type === 'success' ? 'Success!' : 'Error!', text: message, timer: 3000})
        : alert(message);
}

function showValidationErrors(errors) {
    clearEditFormErrors();
    Object.keys(errors).forEach(field => {
        $(`#edit${field.charAt(0).toUpperCase() + field.slice(1)}`).addClass('is-invalid')
            .next('.invalid-feedback').text(errors[field][0]);
    });
    $('#editFormErrors').html(`<strong>Please fix errors:</strong><ul>${Object.values(errors).flat().map(e => `<li>${e}</li>`).join('')}</ul>`).show();
}

function clearEditFormErrors() {
    $('#editForm .is-invalid').removeClass('is-invalid');
    $('#editForm .invalid-feedback').text('');
    $('#editFormErrors').hide();
}

// Event Listeners
$(document).on('click', '.modal-overlay', function(e) {
    if (e.target === this) $(this).hide();
});

// Global Access
Object.assign(window, {editVendor, deleteVendor, saveVendor, confirmDelete, closeModal, clearFilters});
</script>    
@endpush
