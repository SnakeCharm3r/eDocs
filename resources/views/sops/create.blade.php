@extends('layouts.template')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet">
    <style>
        .form-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #007A33;
        }

        .required-asterisk {
            color: #dc3545;
        }

        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #007A33;
            background: #f8fff8;
        }

        .file-upload-area.dragover {
            border-color: #007A33;
            background: #e8f5e9;
        }

        .file-preview {
            display: none;
            margin-top: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            {{-- Page Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-plus-circle text-primary me-2"></i>Add New SOP
                    </h4>
                </div>
                <a href="{{ route('sops.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to SOPs
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-exclamation-circle me-2"></i>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('sops.store') }}" method="POST" enctype="multipart/form-data" id="sopForm">
                @csrf

                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>New SOP</h6>
                    </div>
                    <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        {{-- Document Information --}}
                        <div class="form-section">
                            <h5 class="form-section-title">
                                <i class="fas fa-file-alt me-2"></i>Document Information
                            </h5>

                            <div class="mb-3">
                                <label for="title" class="form-label">
                                    SOP Title <span class="required-asterisk">*</span>
                                </label>
                                <input type="text" name="title" id="title"
                                    class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}"
                                    placeholder="Enter the SOP title" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="document_code" class="form-label">SOP Code</label>
                                <input type="text" name="document_code" id="document_code"
                                    class="form-control @error('document_code') is-invalid @enderror"
                                    value="{{ old('document_code') }}"
                                    placeholder="e.g. SOP-ENT-0001 (leave blank to auto-generate)">
                                @error('document_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="version" class="form-label">Version</label>
                                    <input type="text" name="version" id="version"
                                        class="form-control @error('version') is-invalid @enderror"
                                        value="{{ old('version', '1.0') }}" placeholder="e.g., 1.0">
                                    @error('version')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="3" placeholder="Brief description of the SOP...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Department Assignment --}}
                        <div class="form-section">
                            <h5 class="form-section-title">
                                <i class="fas fa-building me-2"></i>Department Assignment
                            </h5>

                            <div class="form-check mb-3" id="applyToAllEntitiesSection">
                                <input type="checkbox" name="apply_to_all_entities" value="1" id="apply_to_all_entities"
                                    class="form-check-input" {{ old('apply_to_all_entities') ? 'checked' : '' }}>
                                <label for="apply_to_all_entities" class="form-check-label">
                                    <strong>Apply to all entities</strong>
                                </label>
                                <small class="text-muted d-block">If checked, this SOP will be visible to all users across every entity. Entity/Division and Owner Department will not be used.</small>
                                @error('apply_to_all_entities')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="entityDivisionFields">
                                <div class="mb-3">
                                    <label class="form-label d-block">
                                        Entity/Division <span class="required-asterisk" id="divisionRequired">*</span>
                                    </label>
                                    <small class="text-muted d-block mb-2">Tick the entity/division(s) that apply. You can select more than one.</small>
                                    <div class="border rounded p-3 bg-light @error('division_ids') is-invalid @enderror" id="division_ids_wrapper">
                                        @foreach ($divisions as $division)
                                            <div class="form-check">
                                                <input type="checkbox" name="division_ids[]" id="division_ids_{{ $division->id }}"
                                                    value="{{ $division->id }}" class="form-check-input division_ids_cb"
                                                    {{ in_array($division->id, old('division_ids', [])) ? 'checked' : '' }}>
                                                <label for="division_ids_{{ $division->id }}" class="form-check-label">{{ $division->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('division_ids')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="owner_department_id" class="form-label">
                                        Owner / Managing Department <span class="required-asterisk" id="ownerDeptRequired">*</span>
                                    </label>
                                    <select name="owner_department_id" id="owner_department_id"
                                        class="form-select @error('owner_department_id') is-invalid @enderror">
                                        <option value="">-- Select department that will manage this SOP --</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}"
                                                {{ old('owner_department_id') == $department->id ? 'selected' : '' }}>
                                                {{ $department->dept_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">The Line Manager of this department owns the SOP. You can then choose visibility below.</small>
                                    @error('owner_department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="visible_to_all_departments" value="1"
                                            id="visible_to_all_departments" class="form-check-input"
                                            {{ old('visible_to_all_departments') ? 'checked' : '' }}>
                                        <label for="visible_to_all_departments" class="form-check-label">
                                            <strong>Visible to all departments in this entity</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">When checked, the SOP will be shown to all users in the selected entity/division. Owner remains the selected department's Line Manager.</small>
                                    @error('visible_to_all_departments')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Validity Period --}}
                        <div class="form-section">
                            <h5 class="form-section-title">
                                <i class="fas fa-calendar-alt me-2"></i>Validity Period
                            </h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="effective_date" class="form-label">
                                        Effective Date <span class="required-asterisk">*</span>
                                    </label>
                                    <input type="date" name="effective_date" id="effective_date"
                                        class="form-control @error('effective_date') is-invalid @enderror"
                                        value="{{ old('effective_date', date('Y-m-d')) }}" required>
                                    @error('effective_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="next_review_date" class="form-label">
                                        Next Review Date <span class="required-asterisk">*</span>
                                    </label>
                                    <input type="date" name="next_review_date" id="next_review_date"
                                        class="form-control @error('next_review_date') is-invalid @enderror"
                                        value="{{ old('next_review_date', old('expiry_date')) }}" required>
                                    <small class="text-muted">When should this SOP be reviewed next?</small>
                                    @error('next_review_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        {{-- File Upload --}}
                        <div class="form-section">
                            <h5 class="form-section-title">
                                <i class="fas fa-upload me-2"></i>Document Upload
                            </h5>

                            <div class="file-upload-area" id="fileUploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h6>Drop file here or click to upload</h6>
                                <p class="text-muted small mb-0">Supported formats: PDF, DOC, DOCX</p>
                                <p class="text-muted small mb-0">Maximum size: 5MB</p>
                                <p class="text-muted small">If upload fails, try a smaller file or ensure server PHP limits (upload_max_filesize, post_max_size) allow at least 5MB.</p>
                                <input type="file" name="pdf" id="pdf" class="d-none"
                                    accept=".pdf,.doc,.docx" required>
                            </div>

                            <div class="file-preview" id="filePreview" style="display: none;">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="fas fa-file-pdf fa-2x text-danger me-3" id="fileIcon"></i>
                                    <div class="flex-grow-1">
                                        <strong id="fileName">-</strong>
                                        <small class="text-muted d-block" id="fileSize">-</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeFile">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            @error('pdf')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Submit Actions --}}
                        <div class="form-section">
                            <button type="submit" class="btn btn-primary w-100 mb-2" id="submitBtn">
                                <i class="fas fa-save me-2"></i>Save SOP
                            </button>
                            <a href="{{ route('sops.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // File upload handling
            const fileUploadArea = document.getElementById('fileUploadArea');
            const fileInput = document.getElementById('pdf');
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const fileIcon = document.getElementById('fileIcon');
            const removeFile = document.getElementById('removeFile');

            fileUploadArea.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    handleFileSelect(this.files[0]);
                }
            });

            removeFile.addEventListener('click', function() {
                fileInput.value = '';
                filePreview.style.display = 'none';
                fileUploadArea.style.display = 'block';
            });

            function handleFileSelect(file) {
                const validTypes = ['application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];

                if (!validTypes.includes(file.type)) {
                    alert('Please upload a PDF, DOC, or DOCX file.');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 10MB.');
                    return;
                }

                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);

                if (file.type === 'application/pdf') {
                    fileIcon.className = 'fas fa-file-pdf fa-2x text-danger me-3';
                } else {
                    fileIcon.className = 'fas fa-file-word fa-2x text-primary me-3';
                }

                fileUploadArea.style.display = 'none';
                filePreview.style.display = 'block';
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // Apply to all entities: when checked, hide entity/owner/departments and clear them
            $('#apply_to_all_entities').on('change', function() {
                const allEntities = $(this).is(':checked');
                if (allEntities) {
                    $('#entityDivisionFields').hide();
                    $('.division_ids_cb').prop('checked', false);
                    $('#owner_department_id').val('').prop('required', false);
                    $('#divisionRequired').hide();
                    $('#ownerDeptRequired').hide();
                    $('#visible_to_all_departments').prop('checked', false);
                } else {
                    $('#entityDivisionFields').show();
                    $('#owner_department_id').prop('required', true);
                    $('#divisionRequired').show();
                    $('#ownerDeptRequired').show();
                }
            });

            if ($('#apply_to_all_entities').is(':checked')) {
                $('#entityDivisionFields').hide();
                $('.division_ids_cb').prop('checked', false);
                $('#owner_department_id').val('').prop('required', false);
                $('#divisionRequired').hide();
                $('#ownerDeptRequired').hide();
            }

            // Form submission with loading state
            $('#sopForm').on('submit', function() {
                const btn = $('#submitBtn');
                btn.prop('disabled', true);
                btn.html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
            });

            // Keep next review date on or after the effective date
            $('#effective_date').on('change', function() {
                const effectiveDate = $(this).val();
                $('#next_review_date').attr('min', effectiveDate);
            });
        });
    </script>
@endpush
