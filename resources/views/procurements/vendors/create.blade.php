@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            {{-- Page Header --}}
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">
                            <i class="fas fa-user-plus me-2"></i>Add New Vendor
                        </h3>

                    </div>
                    <div class="col-auto">
                        <a href="{{ route('procurements.vendors.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            {{-- Vendor Form --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 text-dark">
                                </i>Vendor Information
                            </h5>
                        </div>
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> Please fix the following issues:
                                    <ul class="mb-0 mt-2">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            <form action="{{ route('procurements.vendors.store') }}" method="POST" class="row g-3"
                                enctype="multipart/form-data" id="vendorForm">
                                @csrf

                                {{-- Basic Information --}}
                                <div class="col-12">
                                    <h6 class="text-muted mb-3 border-bottom pb-2">
                                        <i class="fas fa-info-circle me-2"></i>Basic Information
                                    </h6>
                                </div>

                                <div class="col-md-6">
                                    <label for="name" class="form-label fw-semibold">Vendor Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name') }}" placeholder="Enter vendor business name" required>
                                    <small class="text-muted">Official business name of the vendor</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="type" class="form-label fw-semibold">Vendor Type <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Select Vendor Type</option>
                                        <option value="internal" {{ old('type') == 'internal' ? 'selected' : '' }}>Internal
                                        </option>
                                        <option value="external" {{ old('type') == 'external' ? 'selected' : '' }}>External
                                        </option>
                                        <option value="goods" {{ old('type') == 'goods' ? 'selected' : '' }}>Goods Supplier
                                        </option>
                                        <option value="services" {{ old('type') == 'services' ? 'selected' : '' }}>Service
                                            Provider</option>
                                        <option value="goods_and_services"
                                            {{ old('type') == 'goods_and_services' ? 'selected' : '' }}>Goods & Services
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="owner_name" class="form-label fw-semibold">Owner/CEO Name</label>
                                    <input type="text" class="form-control" id="owner_name" name="owner_name"
                                        value="{{ old('owner_name') }}" placeholder="Enter owner/CEO full name">
                                </div>

                                <div class="col-md-6">
                                    <label for="industry" class="form-label fw-semibold">Industry</label>
                                    <select class="form-select" id="industry" name="industry">
                                        <option value="">Select Industry Category</option>
                                        <option value="construction"
                                            {{ old('industry') == 'construction' ? 'selected' : '' }}>Construction & Civil
                                            Works</option>
                                        <option value="building_materials"
                                            {{ old('industry') == 'building_materials' ? 'selected' : '' }}>Building
                                            Materials & Supplies</option>
                                        <option value="it_equipment"
                                            {{ old('industry') == 'it_equipment' ? 'selected' : '' }}>IT Equipment &
                                            Hardware</option>
                                        <option value="office_supplies"
                                            {{ old('industry') == 'office_supplies' ? 'selected' : '' }}>Office Supplies &
                                            Stationery</option>
                                        <option value="furniture" {{ old('industry') == 'furniture' ? 'selected' : '' }}>
                                            Furniture & Fixtures</option>
                                        <option value="medical_supplies"
                                            {{ old('industry') == 'medical_supplies' ? 'selected' : '' }}>Medical Supplies
                                            & Equipment</option>
                                        <option value="consultancy"
                                            {{ old('industry') == 'consultancy' ? 'selected' : '' }}>Consultancy Services
                                        </option>
                                        <option value="legal" {{ old('industry') == 'legal' ? 'selected' : '' }}>Legal
                                            Services</option>
                                        <option value="transport" {{ old('industry') == 'transport' ? 'selected' : '' }}>
                                            Transport & Logistics</option>
                                        <option value="security_services"
                                            {{ old('industry') == 'security_services' ? 'selected' : '' }}>Security
                                            Services</option>
                                        <option value="cleaning_services"
                                            {{ old('industry') == 'cleaning_services' ? 'selected' : '' }}>Cleaning &
                                            Sanitation</option>
                                        <option value="catering" {{ old('industry') == 'catering' ? 'selected' : '' }}>
                                            Catering & Hospitality</option>
                                        <option value="training" {{ old('industry') == 'training' ? 'selected' : '' }}>
                                            Training & Capacity Building</option>
                                        <option value="other" {{ old('industry') == 'other' ? 'selected' : '' }}>Other
                                            (Specify)</option>
                                    </select>
                                </div>

                                {{-- Other Industry Input --}}
                                <div class="col-md-6" id="otherIndustryField"
                                    style="display: {{ old('industry') == 'other' ? 'block' : 'none' }};">
                                    <label for="other_industry_specify" class="form-label fw-semibold">Specify Industry
                                        <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="other_industry_specify"
                                        name="other_industry_specify" value="{{ old('other_industry_specify') }}"
                                        placeholder="Enter specific industry">
                                    <small class="text-muted">Please specify the industry not listed above</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label fw-semibold">Status <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active"
                                            {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                                            Inactive</option>
                                    </select>
                                </div>

                                {{-- Contact Information --}}
                                <div class="col-12 mt-4">
                                    <h6 class="text-muted mb-3 border-bottom pb-2">
                                        <i class="fas fa-address-book me-2"></i>Contact Information
                                    </h6>
                                </div>

                                <div class="col-md-4">
                                    <label for="contact_person" class="form-label fw-semibold">Contact Person <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person"
                                        value="{{ old('contact_person') }}" placeholder="Full name of contact person"
                                        required>
                                </div>

                                <div class="col-md-4">
                                    <label for="contact_email" class="form-label fw-semibold">Contact Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email"
                                        value="{{ old('contact_email') }}" placeholder="email@example.com" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="contact_phone" class="form-label fw-semibold">Contact Phone</label>
                                    <input type="text" class="form-control" id="contact_phone" name="contact_phone"
                                        value="{{ old('contact_phone') }}" placeholder="+255 712 345 678">
                                </div>

                                <div class="col-md-6">
                                    <label for="alternative_phone" class="form-label fw-semibold">Alternative
                                        Phone</label>
                                    <input type="text" class="form-control" id="alternative_phone"
                                        name="alternative_phone" value="{{ old('alternative_phone') }}"
                                        placeholder="+255 712 345 679">
                                </div>

                                <div class="col-md-6">
                                    <label for="website" class="form-label fw-semibold">Website URL</label>
                                    <input type="url" class="form-control" id="website" name="website"
                                        value="{{ old('website') }}" placeholder="https://www.example.com">
                                </div>

                                <div class="col-12">
                                    <label for="address" class="form-label fw-semibold">Physical Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" placeholder="Full physical address">{{ old('address') }}</textarea>
                                </div>

                                {{-- Business Registration --}}
                                <div class="col-12 mt-4">
                                    <h6 class="text-muted mb-3 border-bottom pb-2">
                                        <i class="fas fa-file-contract me-2"></i>Business Registration
                                    </h6>
                                </div>

                                <div class="col-md-6">
                                    <label for="registration_number" class="form-label fw-semibold">Business Registration
                                        Number</label>
                                    <input type="text" class="form-control" id="registration_number"
                                        name="registration_number" value="{{ old('registration_number') }}"
                                        placeholder="Business license number">
                                </div>

                                <div class="col-md-6">
                                    <label for="tax_number" class="form-label fw-semibold">Tax Identification Number
                                        (TIN)</label>
                                    <input type="text" class="form-control" id="tax_number" name="tax_number"
                                        value="{{ old('tax_number') }}" placeholder="TIN number">
                                </div>

                                {{-- Additional Business Information --}}
                                <div class="col-12 mt-4">
                                    <h6 class="text-muted mb-3 border-bottom pb-2">
                                        <i class="fas fa-building me-2"></i>Additional Business Information
                                    </h6>
                                </div>

                                <div class="col-md-4">
                                    <label for="years_in_business" class="form-label fw-semibold">Years in
                                        Business</label>
                                    <input type="number" class="form-control" id="years_in_business"
                                        name="years_in_business" value="{{ old('years_in_business') }}"
                                        placeholder="e.g., 5" min="0">
                                </div>

                                <div class="col-md-4">
                                    <label for="number_of_employees" class="form-label fw-semibold">Number of
                                        Employees</label>
                                    <select class="form-select" id="number_of_employees" name="number_of_employees">
                                        <option value="">Select Range</option>
                                        <option value="1-10"
                                            {{ old('number_of_employees') == '1-10' ? 'selected' : '' }}>1-10 employees
                                        </option>
                                        <option value="11-50"
                                            {{ old('number_of_employees') == '11-50' ? 'selected' : '' }}>11-50 employees
                                        </option>
                                        <option value="51-100"
                                            {{ old('number_of_employees') == '51-100' ? 'selected' : '' }}>51-100 employees
                                        </option>
                                        <option value="101-500"
                                            {{ old('number_of_employees') == '101-500' ? 'selected' : '' }}>101-500
                                            employees</option>
                                        <option value="500+"
                                            {{ old('number_of_employees') == '500+' ? 'selected' : '' }}>500+ employees
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="country" class="form-label fw-semibold">Country/Region</label>
                                    <input type="text" class="form-control" id="country" name="country"
                                        value="{{ old('country', 'Tanzania') }}" placeholder="e.g., Tanzania">
                                </div>

                                {{-- Financial Information --}}
                                <div class="col-12 mt-4">
                                    <h6 class="text-muted mb-3 border-bottom pb-2">
                                        <i class="fas fa-money-bill-wave me-2"></i>Financial Information (Optional)
                                    </h6>
                                </div>

                                <div class="col-md-6">
                                    <label for="bank_name" class="form-label fw-semibold">Bank Name</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name"
                                        value="{{ old('bank_name') }}" placeholder="Bank name">
                                </div>

                                <div class="col-md-6">
                                    <label for="bank_account_number" class="form-label fw-semibold">Bank Account
                                        Number</label>
                                    <input type="text" class="form-control" id="bank_account_number"
                                        name="bank_account_number" value="{{ old('bank_account_number') }}"
                                        placeholder="Account number">
                                </div>

                                <div class="col-md-6">
                                    <label for="payment_terms" class="form-label fw-semibold">Payment Terms</label>
                                    <select class="form-select" id="payment_terms" name="payment_terms">
                                        <option value="">Select Payment Terms</option>
                                        <option value="net_15" {{ old('payment_terms') == 'net_15' ? 'selected' : '' }}>
                                            Net 15 days</option>
                                        <option value="net_30" {{ old('payment_terms') == 'net_30' ? 'selected' : '' }}>
                                            Net 30 days</option>
                                        <option value="net_45" {{ old('payment_terms') == 'net_45' ? 'selected' : '' }}>
                                            Net 45 days</option>
                                        <option value="net_60" {{ old('payment_terms') == 'net_60' ? 'selected' : '' }}>
                                            Net 60 days</option>
                                        <option value="cash_on_delivery"
                                            {{ old('payment_terms') == 'cash_on_delivery' ? 'selected' : '' }}>Cash on
                                            Delivery</option>
                                        <option value="advance" {{ old('payment_terms') == 'advance' ? 'selected' : '' }}>
                                            Advance Payment</option>
                                        <option value="other" {{ old('payment_terms') == 'other' ? 'selected' : '' }}>
                                            Other</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="currency" class="form-label fw-semibold">Preferred Currency</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="TZS" {{ old('currency', 'TZS') == 'TZS' ? 'selected' : '' }}>TZS
                                            (Tanzanian Shilling)</option>
                                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD (US
                                            Dollar)</option>
                                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR (Euro)
                                        </option>
                                    </select>
                                </div>

                                {{-- File Upload Section --}}
                                <div class="col-12 mt-4">
                                    <h6 class="text-muted mb-3 border-bottom pb-2">
                                        <i class="fas fa-paperclip me-2"></i>Documents & Attachments
                                    </h6>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="documents" class="form-label fw-semibold">Upload Documents</label>
                                    <input type="file" class="form-control" id="documents" name="documents[]"
                                        multiple accept=".pdf,.doc,.docx">
                                    <small class="text-muted">Supported formats: PDF, DOC, DOCX (Max: 50MB each). You can
                                        select multiple files.</small>
                                </div>

                                {{-- Uploaded Files Preview with Document Names --}}
                                <div class="col-12">
                                    <div id="filePreview" class="mt-3">
                                        <p class="text-muted mb-2">No files selected</p>
                                    </div>
                                </div>

                                {{-- Notes & Remarks --}}
                                <div class="col-12 mt-4">
                                    <h6 class="text-muted mb-3 border-bottom pb-2">
                                        <i class="fas fa-sticky-note me-2"></i>Notes & Remarks
                                    </h6>
                                </div>

                                <div class="col-12">
                                    <label for="notes" class="form-label fw-semibold">Additional Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="4"
                                        placeholder="Any additional information about this vendor...">{{ old('notes') }}</textarea>
                                </div>

                                {{-- Form Actions --}}
                                <div class="col-12 mt-4">
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('procurements.vendors.index') }}"
                                            class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save me-1"></i> Create Vendor
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .card-header {
            border-bottom: 1px solid #dee2e6;
            background: white;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .file-preview-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }

        .file-icon {
            font-size: 20px;
            margin-right: 12px;
            width: 24px;
        }

        .file-pdf {
            color: #dc3545;
        }

        .file-doc {
            color: #0d6efd;
        }

        .file-remove {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            margin-left: auto;
        }

        .file-remove:hover {
            color: #c82333;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('documents');
            const filePreview = document.getElementById('filePreview');
            const industrySelect = document.getElementById('industry');
            const otherIndustryField = document.getElementById('otherIndustryField');
            const otherIndustryInput = document.getElementById('other_industry_specify');
            const vendorForm = document.getElementById('vendorForm');

            // Store selected files with their metadata
            let selectedFiles = new Map();

            // Industry "Other" field toggle
            if (industrySelect) {
                industrySelect.addEventListener('change', function() {
                    if (this.value === 'other') {
                        otherIndustryField.style.display = 'block';
                        otherIndustryInput.required = true;
                    } else {
                        otherIndustryField.style.display = 'none';
                        otherIndustryInput.required = false;
                        otherIndustryInput.value = '';
                    }
                });

                // Initialize on page load
                if (industrySelect.value === 'other') {
                    otherIndustryField.style.display = 'block';
                    otherIndustryInput.required = true;
                }
            }

            // Handle form submission - if industry is "other", use the specified value
            if (vendorForm) {
                vendorForm.addEventListener('submit', function(e) {
                    if (industrySelect && industrySelect.value === 'other') {
                        if (!otherIndustryInput.value.trim()) {
                            e.preventDefault();
                            alert('Please specify the industry when "Other" is selected.');
                            otherIndustryInput.focus();
                            return false;
                        }
                        // Set the industry value to the specified text
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'industry';
                        hiddenInput.value = otherIndustryInput.value.trim();
                        vendorForm.appendChild(hiddenInput);
                        industrySelect.disabled = true;
                    }

                    // Validate document names - check against preview items, not file input
                    const fileItems = filePreview.querySelectorAll('.file-preview-item');
                    const documentNameInputs = Array.from(fileItems).map(item =>
                        item.querySelector('input[name="document_names[]"]')
                    ).filter(input => input !== null);

                    if (fileItems.length > 0) {
                        // Check if all files have names
                        let hasEmptyName = false;
                        let emptyIndex = -1;

                        documentNameInputs.forEach((input, index) => {
                            if (!input.value.trim()) {
                                hasEmptyName = true;
                                emptyIndex = index;
                            }
                        });

                        if (hasEmptyName) {
                            e.preventDefault();
                            const fileName = fileItems[emptyIndex].querySelector('.file-name').textContent;
                            alert(
                                `Please provide a document name for "${fileName}" (e.g., TIN Certificate, Business License, Contract Agreement).`
                                );
                            documentNameInputs[emptyIndex].focus();
                            return false;
                        }

                        // Update the file input to match selected files
                        updateFileInput();
                    }
                });
            }

            // File upload handling
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const files = Array.from(e.target.files);

                    if (files.length === 0) {
                        if (filePreview.querySelectorAll('.file-preview-item').length === 0) {
                            filePreview.innerHTML = '<p class="text-muted mb-2">No files selected</p>';
                        }
                        return;
                    }

                    // Get existing file names to avoid duplicates
                    const existingFileNames = Array.from(filePreview.querySelectorAll('.file-preview-item'))
                        .map(item => {
                            return item.querySelector('.file-name').textContent.trim();
                        });

                    // Add new files that aren't duplicates
                    files.forEach(file => {
                        if (!existingFileNames.includes(file.name)) {
                            addFileToPreview(file);
                        }
                    });

                    // Clear the file input to allow re-selection
                    fileInput.value = '';
                });
            }

            function addFileToPreview(file) {
                const index = selectedFiles.size;
                selectedFiles.set(file.name, file);

                const fileItem = createFilePreviewItem(file, index);
                if (filePreview.innerHTML === '<p class="text-muted mb-2">No files selected</p>') {
                    filePreview.innerHTML = '';
                }
                filePreview.appendChild(fileItem);
            }

            function createFilePreviewItem(file, index) {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-preview-item mb-3 p-3 border rounded';
                fileItem.setAttribute('data-file-name', file.name);
                fileItem.setAttribute('data-file-index', index);

                const fileExtension = file.name.split('.').pop().toLowerCase();
                const fileIcon = fileExtension === 'pdf' ? 'fas fa-file-pdf' : 'fas fa-file-word';
                const fileIconClass = fileExtension === 'pdf' ? 'file-pdf' : 'file-doc';

                fileItem.innerHTML = `
                    <div class="d-flex align-items-start mb-2">
                        <div class="file-icon ${fileIconClass} me-3">
                            <i class="${fileIcon} fa-2x"></i>
                        </div>
                        <div class="file-info flex-grow-1">
                            <div class="file-name fw-semibold mb-1">${file.name}</div>
                            <small class="text-muted">${formatFileSize(file.size)}</small>
                        </div>
                        <button type="button" class="file-remove btn btn-sm btn-outline-danger" title="Remove file">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <label for="document_name_${index}" class="form-label small fw-semibold">Document Name/Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm"
                               id="document_name_${index}"
                               name="document_names[]"
                               placeholder="e.g., TIN Certificate, Business License, Contract Agreement"
                               required>
                        <small class="text-muted">Specify what this document is (e.g., TIN, Business License, etc.)</small>
                    </div>
                `;

                fileItem.querySelector('.file-remove').addEventListener('click', function() {
                    removeFile(file.name);
                    fileItem.remove();
                    updateFileIndices();

                    if (filePreview.querySelectorAll('.file-preview-item').length === 0) {
                        filePreview.innerHTML = '<p class="text-muted mb-2">No files selected</p>';
                    }
                });

                return fileItem;
            }

            function removeFile(fileName) {
                selectedFiles.delete(fileName);
            }

            function updateFileIndices() {
                const fileItems = filePreview.querySelectorAll('.file-preview-item');
                fileItems.forEach((item, newIndex) => {
                    const nameInput = item.querySelector('input[name="document_names[]"]');
                    if (nameInput) {
                        nameInput.id = `document_name_${newIndex}`;
                    }
                    item.setAttribute('data-file-index', newIndex);
                });
            }

            function updateFileInput() {
                // Create a new DataTransfer object and add all selected files
                const dt = new DataTransfer();
                selectedFiles.forEach(file => {
                    dt.items.add(file);
                });
                fileInput.files = dt.files;
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        });
    </script>
@endpush
