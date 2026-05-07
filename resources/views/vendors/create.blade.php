@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Page Header --}}
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">
                            <i class="fas fa-user-plus me-2"></i>Add New Vendor
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('procurements.vendors.index') }}">Procurements
                                        - Vendors</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Add New</li>
                            </ol>
                        </nav>
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
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-building me-2"></i>Vendor Information
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
                                enctype="multipart/form-data">
                                @csrf

                                {{-- Basic Information --}}
                                <div class="col-12">
                                    <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Basic Information
                                    </h6>
                                </div>

                                <div class="col-md-6">
                                    <label for="name" class="form-label">Vendor Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name') }}" placeholder="Enter vendor business name" required>
                                    <div class="form-text">Official business name of the vendor</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="type" class="form-label">Vendor Type <span
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
                                    <label for="currency" class="form-label">Preferred Currency</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="">Select Currency</option>
                                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD
                                        </option>
                                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR
                                        </option>
                                        <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP
                                        </option>
                                        <option value="TZS" {{ old('currency') == 'TZS' ? 'selected' : '' }}>TZS
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="owner_name" class="form-label">Owner/CEO Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="owner_name" name="owner_name"
                                        value="{{ old('owner_name') }}" placeholder="Enter owner name" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="industry" class="form-label">Industry <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="industry" name="industry" required>
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
                                        <option value="agricultural"
                                            {{ old('industry') == 'agricultural' ? 'selected' : '' }}>Agricultural Inputs &
                                            Supplies</option>
                                        <option value="consultancy"
                                            {{ old('industry') == 'consultancy' ? 'selected' : '' }}>Consultancy Services
                                        </option>
                                        <option value="legal" {{ old('industry') == 'legal' ? 'selected' : '' }}>Legal
                                            Services</option>
                                        <option value="audit_accounting"
                                            {{ old('industry') == 'audit_accounting' ? 'selected' : '' }}>Audit &
                                            Accounting Services</option>
                                        <option value="engineering"
                                            {{ old('industry') == 'engineering' ? 'selected' : '' }}>Engineering Services
                                        </option>
                                        <option value="architecture"
                                            {{ old('industry') == 'architecture' ? 'selected' : '' }}>Architectural
                                            Services</option>
                                        <option value="software_development"
                                            {{ old('industry') == 'software_development' ? 'selected' : '' }}>Software
                                            Development</option>
                                        <option value="it_services"
                                            {{ old('industry') == 'it_services' ? 'selected' : '' }}>IT Services & Support
                                        </option>
                                        <option value="telecommunications"
                                            {{ old('industry') == 'telecommunications' ? 'selected' : '' }}>
                                            Telecommunications</option>
                                        <option value="transport" {{ old('industry') == 'transport' ? 'selected' : '' }}>
                                            Transport & Logistics</option>
                                        <option value="cleaning_services"
                                            {{ old('industry') == 'cleaning_services' ? 'selected' : '' }}>Cleaning &
                                            Sanitation Services</option>
                                        <option value="security_services"
                                            {{ old('industry') == 'security_services' ? 'selected' : '' }}>Security
                                            Services</option>
                                        <option value="catering" {{ old('industry') == 'catering' ? 'selected' : '' }}>
                                            Catering & Hospitality</option>
                                        <option value="training" {{ old('industry') == 'training' ? 'selected' : '' }}>
                                            Training & Capacity Building</option>
                                        <option value="other" {{ old('industry') == 'other' ? 'selected' : '' }}>Other
                                            (Specify Below)</option>
                                    </select>
                                    <div class="form-text">Select the primary industry category for this vendor</div>
                                </div>

                                {{-- Other Industry Input --}}
                                <div class="col-md-6" id="otherIndustryField" style="display: none;">
                                    <label for="other_industry_specify" class="form-label">Specify Industry <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="other_industry_specify"
                                        name="other_industry_specify" value="{{ old('other_industry_specify') }}"
                                        placeholder="Enter specific industry">
                                    <div class="form-text">Please specify the industry not listed above</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status <span
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
                                    <h6 class="text-primary mb-3"><i class="fas fa-address-book me-2"></i>Contact
                                        Information</h6>
                                </div>

                                <div class="col-md-4">
                                    <label for="contact_person" class="form-label">Contact Person <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person"
                                        value="{{ old('contact_person') }}" placeholder="Full name of contact person"
                                        required>
                                </div>

                                <div class="col-md-4">
                                    <label for="contact_email" class="form-label">Contact Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email"
                                        value="{{ old('contact_email') }}" placeholder="email@example.com" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="contact_phone" class="form-label">Contact Phone <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_phone" name="contact_phone"
                                        value="{{ old('contact_phone') }}" placeholder="+255 712 345 678" required>
                                </div>

                                <div class="col-12">
                                    <label for="address" class="form-label">Address <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" id="address" name="address" rows="3" placeholder="Full physical address"
                                        required>{{ old('address') }}</textarea>
                                </div>

                                {{-- Business Registration --}}
                                <div class="col-12 mt-4">
                                    <h6 class="text-primary mb-3"><i class="fas fa-file-contract me-2"></i>Business
                                        Registration (Tanzania Compliance)</h6>
                                </div>

                                <div class="col-md-6">
                                    <label for="registration_number" class="form-label">Business Registration Number <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="registration_number"
                                        name="registration_number" value="{{ old('registration_number') }}"
                                        placeholder="Business license number" required>
                                    <div class="form-text">Required by BRELA</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="tax_number" class="form-label">Tax Identification Number (TIN) <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="tax_number" name="tax_number"
                                        value="{{ old('tax_number') }}" placeholder="TIN number from TRA" required>
                                    <div class="form-text">Required by Tanzania Revenue Authority</div>
                                </div>

                                {{-- File Upload Section --}}
                                <div class="col-12 mt-4">
                                    <h6 class="text-primary mb-3"><i class="fas fa-paperclip me-2"></i>Contract Documents
                                        <span class="text-danger">*</span>
                                    </h6>
                                </div>

                                <div class="col-md-6">
                                    <label for="documents" class="form-label">Upload Contract Documents <span
                                            class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="documents" name="documents[]"
                                        multiple accept=".pdf,.docx" required>
                                    <div class="form-text">Supported formats: PDF, DOCX (Max: 50MB each) - At least one
                                        contract document required</div>
                                </div>

                                {{-- Uploaded Files Preview --}}
                                <div class="col-12">
                                    <div id="filePreview" class="mt-3">
                                        <p class="text-muted mb-2">No files selected</p>
                                    </div>
                                </div>

                                {{-- Rating --}}
                                <div class="col-md-6">
                                    <label for="rating" class="form-label">Vendor Rating</label>
                                    <select class="form-select" id="rating" name="rating">
                                        <option value="">Select Rating</option>
                                        <option value="1" {{ old('rating') == '1' ? 'selected' : '' }}>★ Poor
                                        </option>
                                        <option value="2" {{ old('rating') == '2' ? 'selected' : '' }}>★★ Fair
                                        </option>
                                        <option value="3" {{ old('rating') == '3' ? 'selected' : '' }}>★★★ Good
                                        </option>
                                        <option value="4" {{ old('rating') == '4' ? 'selected' : '' }}>★★★★ Very Good
                                        </option>
                                        <option value="5" {{ old('rating') == '5' ? 'selected' : '' }}>★★★★★
                                            Excellent</option>
                                    </select>
                                </div>

                                {{-- Form Actions --}}
                                <div class="col-12 mt-4">
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('procurements.vendors.index') }}"
                                            class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
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
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            border-bottom: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        .form-label {
            font-weight: 600;
        }

        .text-primary {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 0.5rem;
        }

        .file-preview-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            margin-bottom: 8px;
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

        .text-danger {
            color: #dc3545 !important;
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

            // Industry "Other" field toggle
            industrySelect.addEventListener('change', function() {
                if (this.value === 'other') {
                    otherIndustryField.style.display = 'block';
                    otherIndustryInput.required = true;
                } else {
                    otherIndustryField.style.display = 'none';
                    otherIndustryInput.required = false;
                }
            });

            // Initialize industry field on page load
            if (industrySelect.value === 'other') {
                otherIndustryField.style.display = 'block';
                otherIndustryInput.required = true;
            }

            // File upload handling
            fileInput.addEventListener('change', function(e) {
                const files = e.target.files;
                filePreview.innerHTML = '';

                if (files.length === 0) {
                    filePreview.innerHTML = '<p class="text-muted mb-2">No files selected</p>';
                    return;
                }

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fileItem = createFilePreviewItem(file);
                    filePreview.appendChild(fileItem);
                }
            });

            function createFilePreviewItem(file) {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-preview-item';

                const fileExtension = file.name.split('.').pop().toLowerCase();
                const fileIcon = fileExtension === 'pdf' ? 'fas fa-file-pdf' : 'fas fa-file-word';
                const fileIconClass = fileExtension === 'pdf' ? 'file-pdf' : 'file-doc';

                fileItem.innerHTML = `
            <div class="file-icon ${fileIconClass}">
                <i class="${fileIcon}"></i>
            </div>
            <div class="file-info">
                <div class="file-name">${file.name}</div>
                <small class="text-muted">${formatFileSize(file.size)}</small>
            </div>
            <button type="button" class="file-remove">
                <i class="fas fa-times"></i>
            </button>
        `;

                fileItem.querySelector('.file-remove').addEventListener('click', function() {
                    removeFileFromInput(file.name);
                    fileItem.remove();
                    if (fileInput.files.length === 0) {
                        filePreview.innerHTML = '<p class="text-muted mb-2">No files selected</p>';
                    }
                });

                return fileItem;
            }

            function removeFileFromInput(filename) {
                const dt = new DataTransfer();
                const files = fileInput.files;

                for (let i = 0; i < files.length; i++) {
                    if (files[i].name !== filename) {
                        dt.items.add(files[i]);
                    }
                }

                fileInput.files = dt.files;
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // File size validation - 50MB limit
            fileInput.addEventListener('change', function() {
                const maxSize = 50 * 1024 * 1024; // 50MB in bytes
                const files = fileInput.files;

                for (let i = 0; i < files.length; i++) {
                    if (files[i].size > maxSize) {
                        alert(`File "${files[i].name}" exceeds 50MB limit.`);
                        removeFileFromInput(files[i].name);
                        const fileItems = filePreview.querySelectorAll('.file-preview-item');
                        fileItems.forEach(item => {
                            if (item.querySelector('.file-name').textContent === files[i].name) {
                                item.remove();
                            }
                        });
                        if (fileInput.files.length === 0) {
                            filePreview.innerHTML = '<p class="text-muted mb-2">No files selected</p>';
                        }
                    }
                }
            });
        });
    </script>
@endpush
