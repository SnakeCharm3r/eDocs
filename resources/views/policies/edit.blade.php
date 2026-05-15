@extends('layouts.template')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        /* ── Cards ── */
        .form-card {
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            background: #fff;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .form-card .card-head {
            background: linear-gradient(135deg, #f8fdf9 0%, #f0faf3 100%);
            border-bottom: 1px solid #d4edda;
            padding: .65rem 1.15rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .form-card .card-head .step-badge {
            width: 22px; height: 22px;
            border-radius: 50%;
            background: #198754;
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .form-card .card-head .card-title {
            font-size: .82rem;
            font-weight: 600;
            color: #155724;
            margin: 0;
        }
        .form-card .card-inner { padding: 1rem 1.15rem; }

        /* ── Form Elements ── */
        .field-label {
            font-size: .78rem;
            font-weight: 600;
            color: #344054;
            margin-bottom: .3rem;
        }
        .field-label .req { color: #dc3545; }
        .field-hint {
            font-size: .72rem;
            color: #8c8c8c;
            margin-top: .2rem;
        }
        .form-control, .form-select { font-size: .82rem; border-color: #d0d5dd; }
        .form-control:focus, .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25,135,84,.12);
        }

        /* ── Sidebar ── */
        .sidebar-card {
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .sidebar-card .sidebar-head {
            background: #f8f9fa;
            border-bottom: 1px solid #e8e8e8;
            padding: .75rem 1rem;
            color: #344054;
        }
        .sidebar-card .sidebar-head h6 {
            margin: 0;
            font-size: .82rem;
            font-weight: 600;
        }
        .sidebar-body { padding: .85rem 1rem; }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .45rem 0;
            font-size: .78rem;
            border-bottom: 1px solid #f5f5f5;
        }
        .info-row:last-of-type { border-bottom: none; }
        .info-row .info-label { color: #667085; }
        .info-row .info-value { font-weight: 600; color: #344054; text-align: right; max-width: 60%; word-break: break-word; }

        /* ── Entity Chips ── */
        .entity-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
        .entity-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .65rem;
            border: 1px solid #d0d5dd;
            border-radius: 20px;
            font-size: .75rem;
            color: #344054;
            cursor: pointer;
            transition: all .15s ease;
            background: #fff;
            user-select: none;
        }
        .entity-chip:hover { border-color: #198754; background: #f0faf3; }
        .entity-chip.selected { background: #198754; color: #fff; border-color: #198754; }
        .entity-chip input { display: none; }

        /* ── Content Type Toggle ── */
        .content-toggle { display: inline-flex; border: 1px solid #d0d5dd; border-radius: 6px; overflow: hidden; }
        .content-toggle label {
            padding: .35rem .85rem;
            font-size: .78rem;
            cursor: pointer;
            border-right: 1px solid #d0d5dd;
            margin: 0;
            transition: all .15s ease;
            display: flex;
            align-items: center;
            gap: .35rem;
        }
        .content-toggle label:last-child { border-right: none; }
        .content-toggle input:checked + label,
        .content-toggle label.active-toggle {
            background: #198754;
            color: #fff;
        }
        .content-toggle input { display: none; }

        /* ── File Upload ── */
        .upload-zone {
            border: 2px dashed #d0d5dd;
            border-radius: 8px;
            padding: 2rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s ease;
            background: #fafafa;
        }
        .upload-zone:hover, .upload-zone.dragover { border-color: #198754; background: #f0faf3; }
        .upload-zone .upload-icon { font-size: 2rem; color: #98a2b3; margin-bottom: .5rem; }
        .file-attached {
            display: none;
            padding: .65rem;
            background: #f0faf3;
            border: 1px solid #d4edda;
            border-radius: 8px;
            margin-top: .5rem;
        }
        .file-attached.show { display: flex; align-items: center; gap: .75rem; }

        .current-file-bar {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .6rem .85rem;
            background: #e8f5e9;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            margin-bottom: .65rem;
            font-size: .8rem;
        }

        /* ── Quill ── */
        #textContentEditor { min-height: 280px; border-radius: 0 0 8px 8px; }
        .ql-toolbar.ql-snow { border-radius: 8px 8px 0 0; border-color: #d0d5dd; }
        .ql-container.ql-snow { border-color: #d0d5dd; }
    </style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Page Header --}}
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="page-title">Edit Policy</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('policies.index', ['type' => $policyType ?? 'ccbrt']) }}">Policies</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('policies.index', ['type' => $policyType ?? 'ccbrt']) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><strong>Please correct the following:</strong>
                <ul class="mb-0 mt-1 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('policies.update', $policy->id) }}?type={{ $policyType ?? 'ccbrt' }}" method="POST" enctype="multipart/form-data" id="policyForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="policy_type" value="{{ $policyType ?? 'ccbrt' }}">
            <input type="hidden" name="type" value="{{ $policyType ?? 'ccbrt' }}">
            <input type="hidden" name="submission_status" id="submissionStatus" value="{{ old('submission_status', ($policyType ?? 'ccbrt') === 'other_organization' ? $policy->status : 'active') }}">

            <div class="row g-3">
                {{-- ════════════════ MAIN CONTENT ════════════════ --}}
                <div class="col-lg-8">

                    {{-- ── Step 1: Policy Details ── --}}
                    <div class="form-card">
                        <div class="card-head">
                            <span class="step-badge">1</span>
                            <h6 class="card-title">Policy Details</h6>
                        </div>
                        <div class="card-inner">
                            @if (($policyType ?? 'ccbrt') !== 'ccbrt')
                                <div class="mb-3">
                                    <label class="field-label">Policy Type</label>
                                    <input type="text" class="form-control form-control-sm bg-light"
                                        value="Other Organization Policies" readonly disabled>
                                    <div class="field-hint">Policy type cannot be changed after creation.</div>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="field-label">Policy Title <span class="req">*</span></label>
                                    <input type="text" name="title" id="title"
                                        class="form-control form-control-sm @error('title') is-invalid @enderror"
                                        value="{{ old('title', $policy->title) }}"
                                        placeholder="e.g. Data Protection and Privacy Policy" required>
                                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Document Code <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="text" name="document_code" id="document_code"
                                        class="form-control form-control-sm @error('document_code') is-invalid @enderror"
                                        value="{{ old('document_code', $policy->document_code) }}"
                                        placeholder="e.g. HR-001" maxlength="50">
                                    @error('document_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="field-label">Section</label>
                                    @php
                                        $catTreeAll      = ($policyCategories ?? collect())->keyBy('id');
                                        $catTreeByParent = ($policyCategories ?? collect())->sortBy('sort_order')->groupBy(fn($c) => $c->parent_id ?? 0);
                                        $selectedCatId   = old('policy_category_id', $policy->policy_category_id);
                                        $renderCategoryOptions = function ($parentId = 0, $depth = 0) use (&$renderCategoryOptions, $catTreeByParent, $selectedCatId) {
                                            $html = '';

                                            foreach ($catTreeByParent->get($parentId, collect()) as $categoryOption) {
                                                $label = trim(str_repeat('  ', $depth) . ($categoryOption->section_number ? $categoryOption->section_number . '. ' : '') . $categoryOption->name);
                                                $isSelected = (string) $selectedCatId === (string) $categoryOption->id ? ' selected' : '';
                                                $html .= '<option value="' . e($categoryOption->id) . '"' . $isSelected . '>' . e($label) . '</option>';
                                                $html .= $renderCategoryOptions($categoryOption->id, $depth + 1);
                                            }

                                            return $html;
                                        };
                                    @endphp
                                    <select name="policy_category_id" id="policy_category_id" class="d-none">
                                        <option value="">-- Select Section --</option>
                                        {!! $renderCategoryOptions() !!}
                                    </select>
                                    @include('policies._category_picker', [
                                        'catTreeAll'      => $catTreeAll,
                                        'catTreeByParent' => $catTreeByParent,
                                        'selectedCatId'   => $selectedCatId,
                                        'pickerId'        => 'catPickerEdit',
                                        'hiddenId'        => 'policy_category_id',
                                    ])
                                    @error('policy_category_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                @if (($policyType ?? 'ccbrt') !== 'ccbrt')
                                <div class="col-md-4 mb-3">
                                    <label class="field-label">Organization Code</label>
                                    <input type="text" name="organization_code" id="organization_code"
                                        class="form-control form-control-sm @error('organization_code') is-invalid @enderror"
                                        value="{{ old('organization_code', $policy->organization_code ?? '') }}"
                                        placeholder="e.g. NGO, ACAD" maxlength="50">
                                    @error('organization_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                @endif
                            </div>

                            <div class="{{ ($policyType ?? 'ccbrt') !== 'ccbrt' ? 'mb-3' : 'mb-0' }}">
                                <label class="field-label">Description</label>
                                <textarea name="description" id="description" rows="2"
                                    class="form-control form-control-sm @error('description') is-invalid @enderror"
                                    placeholder="Brief summary of the policy purpose and scope">{{ old('description', $policy->description ?? '') }}</textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if (($policyType ?? 'ccbrt') !== 'ccbrt')
                            <div class="row mb-0">
                                <div class="col-md-6 mb-0">
                                    <label class="field-label">Effective Date</label>
                                    <input type="date" name="effective_date" id="effective_date"
                                        class="form-control form-control-sm @error('effective_date') is-invalid @enderror"
                                        value="{{ old('effective_date', optional($policy->effective_date)->format('Y-m-d')) }}">
                                    @error('effective_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-0">
                                    <label class="field-label">Next Review Date</label>
                                    <input type="date" name="next_review_date" id="next_review_date"
                                        class="form-control form-control-sm @error('next_review_date') is-invalid @enderror"
                                        value="{{ old('next_review_date', optional($policy->next_review_date)->format('Y-m-d')) }}">
                                    @error('next_review_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- ── Step 2: Assignment & Visibility (other_organization only) ── --}}
                    @if (($policyType ?? 'ccbrt') === 'ccbrt')
                        <input type="hidden" name="is_global" value="1">
                        <input type="hidden" name="visible_to_users" value="1">
                    @else
                        @php
                            $isApplyToAllEntities = old('apply_to_all_entities', $policy->division_id === null && $policy->is_global) ? true : false;
                            $selectedDivisionIds  = old('division_ids', $policy->divisions->isNotEmpty() ? $policy->divisions->pluck('id')->toArray() : ($policy->division_id ? [$policy->division_id] : []));
                        @endphp
                        <div class="form-card">
                            <div class="card-head">
                                <span class="step-badge">2</span>
                                <h6 class="card-title">Assignment & Visibility</h6>
                            </div>
                            <div class="card-inner">
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="apply_to_all_entities" value="1" id="apply_to_all_entities"
                                        class="form-check-input" {{ $isApplyToAllEntities ? 'checked' : '' }}>
                                    <label for="apply_to_all_entities" class="form-check-label" style="font-size:.82rem;">
                                        <strong>Apply to all entities</strong>
                                        <span class="field-hint d-block">This policy will be visible to every user across all entities</span>
                                    </label>
                                </div>

                                <div id="entityDivisionFields" style="{{ $isApplyToAllEntities ? 'display:none;' : '' }}">
                                    <div class="mb-3">
                                        <label class="field-label">Entity / Division <span class="req" id="divisionRequired">*</span></label>
                                        <div class="entity-chips @error('division_ids') border border-danger rounded p-2 @enderror">
                                            @foreach ($divisions as $division)
                                                <label class="entity-chip {{ in_array($division->id, $selectedDivisionIds) ? 'selected' : '' }}" id="chip_{{ $division->id }}">
                                                    <input type="checkbox" name="division_ids[]" value="{{ $division->id }}"
                                                        class="division_ids_cb" id="division_ids_{{ $division->id }}"
                                                        {{ in_array($division->id, $selectedDivisionIds) ? 'checked' : '' }}>
                                                    <i class="fas fa-building" style="font-size:.65rem;"></i>
                                                    {{ $division->name }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('division_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="form-check mb-3">
                                        <input type="checkbox" name="is_global" value="1" id="is_global"
                                            class="form-check-input" {{ old('is_global', $policy->is_global ?? false) ? 'checked' : '' }}>
                                        <label for="is_global" class="form-check-label" style="font-size:.82rem;">
                                            <strong>All departments in selected entities</strong>
                                            <span class="field-hint d-block">Uncheck to assign to specific departments only</span>
                                        </label>
                                    </div>

                                    <div class="mb-0" id="departmentsSection">
                                        <label class="field-label">Departments <span class="req" id="deptRequired">*</span></label>
                                        <select name="departments[]" id="departments"
                                            class="form-select form-select-sm @error('departments') is-invalid @enderror" multiple>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}"
                                                    {{ in_array($department->id, old('departments', $policy->departments->pluck('id')->toArray())) ? 'selected' : '' }}>
                                                    {{ $department->dept_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('departments') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <input type="hidden" name="visible_to_users" value="0">
                            </div>
                        </div>
                    @endif

                    {{-- ── Step 3: Policy Content ── --}}
                    <div class="form-card">
                        <div class="card-head">
                            <span class="step-badge">{{ ($policyType ?? 'ccbrt') === 'ccbrt' ? '2' : '3' }}</span>
                            <h6 class="card-title">Policy Content</h6>
                        </div>
                        <div class="card-inner">
                            @if (($policyType ?? 'ccbrt') === 'ccbrt')
                                <input type="hidden" name="content_type" id="content_type" value="text">
                            @else
                                <div class="mb-3">
                                    <label class="field-label">Content Format <span class="req">*</span></label>
                                    <div class="content-toggle">
                                        <input type="radio" name="content_type_radio" id="contentTypeText" value="text"
                                            {{ old('content_type', $policy->content_type) == 'text' ? 'checked' : '' }}>
                                        <label for="contentTypeText" class="{{ old('content_type', $policy->content_type) == 'text' ? 'active-toggle' : '' }}">
                                            <i class="fas fa-align-left"></i> Rich Text
                                        </label>
                                        <input type="radio" name="content_type_radio" id="contentTypePdf" value="pdf"
                                            {{ old('content_type', $policy->content_type) == 'pdf' ? 'checked' : '' }}>
                                        <label for="contentTypePdf" class="{{ old('content_type', $policy->content_type) == 'pdf' ? 'active-toggle' : '' }}">
                                            <i class="fas fa-file-pdf"></i> PDF Upload
                                        </label>
                                    </div>
                                    <input type="hidden" name="content_type" id="content_type" value="{{ old('content_type', $policy->content_type) }}">
                                    @error('content_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            {{-- Current PDF bar --}}
                            @if ($policy->content_type === 'pdf' && $policy->pdf_path)
                                <div class="current-file-bar" id="currentFileBar">
                                    <i class="fas fa-file-pdf text-danger fa-lg"></i>
                                    <div class="flex-grow-1">
                                        <strong style="font-size:.8rem;">{{ basename($policy->pdf_path) }}</strong>
                                        <div class="field-hint">Current document — upload a new file to replace it</div>
                                    </div>
                                    <a href="{{ asset('storage/' . $policy->pdf_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            @endif

                            {{-- Text Editor --}}
                            <div id="textContentSection" style="display: {{ old('content_type', $policy->content_type) === 'text' ? 'block' : 'none' }};">
                                <label class="field-label mb-2">Content <span class="req">*</span></label>
                                <div id="textContentEditor"></div>
                                <textarea name="content" id="content" style="display: none;">{{ old('content', $policy->content) }}</textarea>
                                @error('content') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            {{-- PDF Upload --}}
                            <div id="pdfContentSection" style="display: {{ old('content_type', $policy->content_type) === 'pdf' ? 'block' : 'none' }};">
                                <label class="field-label mb-2">Replace Document <span class="text-muted fw-normal">(optional)</span></label>
                                <div class="upload-zone" id="fileUploadArea">
                                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <p class="mb-1 fw-semibold" style="font-size:.85rem;">Drop your PDF here or click to browse</p>
                                    <p class="field-hint mb-0">Accepted: PDF &middot; Max size: 10 MB &middot; Leave empty to keep current</p>
                                    <input type="file" name="pdf" id="pdf" class="d-none" accept=".pdf">
                                </div>
                                <div class="file-attached" id="filePreview">
                                    <i class="fas fa-file-pdf fa-lg text-danger"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small" id="fileName">-</div>
                                        <div class="field-hint" id="fileSize">-</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" id="removeFile" style="width:28px;height:28px;padding:0;">
                                        <i class="fas fa-times" style="font-size:.7rem;"></i>
                                    </button>
                                </div>
                                @error('pdf') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                </div>

                {{-- ════════════════ SIDEBAR ════════════════ --}}
                <div class="col-lg-4">
                    <div class="sidebar-card position-sticky" style="top:80px;">
                        <div class="sidebar-head">
                            <h6><i class="fas fa-file-shield me-2"></i>Policy Info</h6>
                        </div>

                        <div class="sidebar-body">
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-tag me-1"></i> Type</span>
                                <span class="info-value">{{ ($policyType ?? 'ccbrt') === 'ccbrt' ? 'CCBRT' : 'Organization' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-folder me-1"></i> Section</span>
                                <span class="info-value" id="sidebarCategory">
                                    {{ $policy->policyCategory ? $policy->policyCategory->name : '—' }}
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-file me-1"></i> Format</span>
                                <span class="info-value" id="sidebarContentType">
                                    {{ $policy->content_type === 'pdf' ? 'PDF Document' : 'Rich Text' }}
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-circle me-1" style="font-size:.5rem;color:#198754;"></i> Status</span>
                                <span class="info-value">
                                    <span class="badge {{ $policy->isActive() ? 'bg-success text-success' : ($policy->isDraft() ? 'bg-warning text-dark' : 'bg-secondary text-secondary') }} bg-opacity-15" style="font-size:.72rem;">
                                        {{ ucfirst($policy->status) }}
                                    </span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-user me-1"></i> Created by</span>
                                <span class="info-value">{{ $policy->creator ? $policy->creator->fname . ' ' . $policy->creator->lname : 'N/A' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-calendar me-1"></i> Created</span>
                                <span class="info-value">{{ $policy->created_at->format('d M Y') }}</span>
                            </div>
                            @if ($policy->updatedBy)
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-clock me-1"></i> Last updated</span>
                                <span class="info-value">{{ $policy->updated_at->format('d M Y') }}</span>
                            </div>
                            @endif

                            <div class="mt-3">
                                <button type="submit" class="btn btn-success w-100 mb-2" id="submitBtn" data-submission-status="{{ ($policyType ?? 'ccbrt') === 'other_organization' ? ($policy->isArchived() ? 'archived' : 'active') : 'active' }}" style="font-size:.85rem;">
                                    @if (($policyType ?? 'ccbrt') === 'other_organization' && $policy->isDraft())
                                        <i class="fas fa-check-circle me-1"></i>Publish Policy
                                    @else
                                        <i class="fas fa-save me-1"></i>Save Changes
                                    @endif
                                </button>
                                @if (($policyType ?? 'ccbrt') === 'other_organization' && !$policy->isArchived())
                                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100 mb-2" data-submission-status="draft" style="font-size:.78rem;">
                                        <i class="fas fa-save me-1"></i>Save as Draft
                                    </button>
                                @endif
                                <a href="{{ route('policies.index', ['type' => $policyType ?? 'ccbrt']) }}" class="btn btn-outline-secondary btn-sm w-100" style="font-size:.78rem;">
                                    <i class="fas fa-times me-1"></i>Cancel
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
    $(document).ready(function() {
        var lastSubmissionButton = null;

        function resolveSubmissionButton(event) {
            var nativeEvent = event && (event.originalEvent || event);
            if (nativeEvent && nativeEvent.submitter) {
                return nativeEvent.submitter;
            }

            if (lastSubmissionButton) {
                return lastSubmissionButton;
            }

            var activeElement = document.activeElement;
            if (activeElement && activeElement.form && activeElement.form.id === 'policyForm' && activeElement.type === 'submit') {
                return activeElement;
            }

            return document.getElementById('submitBtn');
        }

        $('#policyForm').find('button[type="submit"]').on('click', function() {
            lastSubmissionButton = this;
            if (this.dataset.submissionStatus) {
                $('#submissionStatus').val(this.dataset.submissionStatus);
            }
        });

        // ── Select2 ──
        $('#departments').select2({
            placeholder: 'Search and select departments...',
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        });

        // ── Entity Chip Selection ──
        $('.entity-chip').each(function() {
            var $chip = $(this);
            var $cb   = $chip.find('input[type=checkbox]');
            $chip.on('click', function(e) {
                e.preventDefault();
                $cb.prop('checked', !$cb.prop('checked')).trigger('change');
            });
            $cb.on('change', function() {
                $chip.toggleClass('selected', $cb.is(':checked'));
                updateSidebar();
            });
        });

        // ── Sidebar ──
        function updateSidebar() {
            var ct = $('#content_type').val();
            $('#sidebarContentType').text(ct === 'pdf' ? 'PDF Document' : 'Rich Text');

            var $catLabel = $('#catPickerEdit .cat-dd-label');
            if ($catLabel.length) {
                var txt = $catLabel.text().trim();
                $('#sidebarCategory').text(txt && txt !== '-- Select Section --' ? txt : '—');
            }
        }

        $('input[name="content_type_radio"]').on('change', updateSidebar);
        document.getElementById('policy_category_id').addEventListener('change', updateSidebar);

        // ── Content Type Toggle ──
        $('input[name="content_type_radio"]').on('change', function() {
            var ct = $(this).val();
            $('#content_type').val(ct);
            $('.content-toggle label').removeClass('active-toggle');
            $(this).next('label').addClass('active-toggle');
            if (ct === 'text') {
                $('#textContentSection').show();
                $('#pdfContentSection').hide();
            } else {
                $('#textContentSection').hide();
                $('#pdfContentSection').show();
            }
            updateSidebar();
        });

        // ── Apply to all entities ──
        $('#apply_to_all_entities').on('change', function() {
            if ($(this).is(':checked')) {
                $('#entityDivisionFields').slideUp(200);
                $('.division_ids_cb').prop('checked', false);
                $('.entity-chip').removeClass('selected');
                $('#departments').val(null).trigger('change');
                $('#is_global').prop('checked', false);
            } else {
                $('#entityDivisionFields').slideDown(200);
            }
        });

        // ── Global checkbox ──
        $('#is_global').on('change', function() {
            var g = $(this).is(':checked');
            $('#departments').prop('disabled', g);
            $('#deptRequired').toggle(!g);
            if (g) $('#departments').val(null).trigger('change');
        });
        // Apply initial state
        @if (($policyType ?? 'ccbrt') === 'other_organization')
            if ($('#is_global').is(':checked')) {
                $('#departments').prop('disabled', true);
                $('#deptRequired').hide();
            }
        @endif

        // ── Quill Editor ──
        var quillEditor = null;
        try {
            quillEditor = new Quill('#textContentEditor', {
                theme: 'snow',
                placeholder: 'Start writing your policy content here...',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'indent': '-1' }, { 'indent': '+1' }],
                        [{ 'color': [] }, { 'background': [] }],
                        ['link'], ['clean']
                    ]
                }
            });
            @if ($policy->content_type === 'text' && $policy->content)
                quillEditor.root.innerHTML = {!! json_encode($policy->content) !!};
            @endif
            quillEditor.on('text-change', function() {
                $('#content').val(quillEditor.root.innerHTML.trim());
            });
        } catch (err) {
            $('#textContentEditor').hide();
            $('#content').attr('style', 'display:block !important; min-height:200px; width:100%;');
        }

        // ── Load Departments ──
        function loadDepartmentsForDivisions() {
            var ids = $('input.division_ids_cb:checked').map(function() { return $(this).val(); }).get();
            var $dept = $('#departments');
            $dept.empty().trigger('change');
            if (!ids.length) return;
            var seen = {}, pending = ids.length;
            ids.forEach(function(divId) {
                $.getJSON('/policies/departments-by-division/' + divId, function(data) {
                    data.forEach(function(d) {
                        if (!seen[d.id]) {
                            seen[d.id] = true;
                            $dept.append(new Option(d.dept_name, d.id, false, false));
                        }
                    });
                    if (--pending === 0) $dept.trigger('change');
                });
            });
        }
        $('.division_ids_cb').on('change', loadDepartmentsForDivisions);

        // ── File Upload ──
        var uploadArea = document.getElementById('fileUploadArea');
        var fileInput  = document.getElementById('pdf');
        var preview    = document.getElementById('filePreview');
        var fName      = document.getElementById('fileName');
        var fSize      = document.getElementById('fileSize');
        var removeBtn  = document.getElementById('removeFile');

        if (uploadArea && fileInput) {
            uploadArea.addEventListener('click', function() { fileInput.click(); });
            uploadArea.addEventListener('dragover', function(e) { e.preventDefault(); uploadArea.classList.add('dragover'); });
            uploadArea.addEventListener('dragleave', function() { uploadArea.classList.remove('dragover'); });
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; showFile(e.dataTransfer.files[0]); }
            });
            fileInput.addEventListener('change', function() { if (this.files.length) showFile(this.files[0]); });
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    fileInput.value = '';
                    preview.classList.remove('show');
                    uploadArea.style.display = '';
                });
            }
        }

        function showFile(f) {
            if (f.type !== 'application/pdf') { alert('Only PDF files are accepted.'); return; }
            if (f.size > 10 * 1024 * 1024) { alert('File must be under 10 MB.'); return; }
            fName.textContent = f.name;
            fSize.textContent = fmtSize(f.size);
            uploadArea.style.display = 'none';
            preview.classList.add('show');
        }

        function fmtSize(b) {
            if (!b) return '0 B';
            var i = Math.floor(Math.log(b) / Math.log(1024));
            return (b / Math.pow(1024, i)).toFixed(1) + ' ' + ['B','KB','MB','GB'][i];
        }

        // ── Form Submit ──
        $('#policyForm').on('submit', function(e) {
            var submissionButton = resolveSubmissionButton(e);

            if (quillEditor) $('#content').val(quillEditor.root.innerHTML.trim());
            if (submissionButton && submissionButton.dataset.submissionStatus) {
                $('#submissionStatus').val(submissionButton.dataset.submissionStatus);
            }

            var $clickedButton = submissionButton ? $(submissionButton) : $('#submitBtn');
            $('#policyForm').find('button[type="submit"]').prop('disabled', true);
            $clickedButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
        });

        updateSidebar();
    });
    </script>
@endpush
