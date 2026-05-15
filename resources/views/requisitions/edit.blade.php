@extends('layouts.template')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    .section-title {
        color: #007A33;
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #007A33;
    }
    .section-title i { margin-right: 8px; }
    .card { border: none; }
    .card-body { padding: 1.5rem; }

    /* Position type cards */
    .pos-type-card {
        border: 2px solid #dee2e6; border-radius: 8px;
        padding: 12px 16px; cursor: pointer; transition: all .15s;
        background: #fff; display: flex; align-items: center; gap: 12px;
        font-size: 0.85rem; user-select: none;
    }
    .pos-type-card:hover { border-color: #007A33; background: #f0faf4; }
    .pos-type-card.selected { border-color: #007A33; background: #e8f5ee; box-shadow: 0 0 0 1px #007A33; }
    .pos-type-card input[type=radio] { width: 16px; height: 16px; flex-shrink: 0; cursor: pointer; accent-color: #007A33; }
    .pos-type-icon {
        width: 36px; height: 36px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.95rem; flex-shrink: 0;
        background: rgba(0,122,51,0.08); color: #007A33;
    }

    /* Condition checkboxes */
    .cond-item {
        border: 1px solid #e9ecef; border-radius: 8px;
        padding: 10px 14px; display: flex; align-items: flex-start; gap: 10px;
        font-size: 0.85rem; cursor: pointer; transition: all .15s;
    }
    .cond-item:hover { border-color: #007A33; background: #f8fdf9; }
    .cond-item input[type=checkbox] { margin-top: 2px; width: 16px; height: 16px; flex-shrink: 0; cursor: pointer; accent-color: #007A33; }
    .cond-item.checked { border-color: #007A33; background: #e8f5ee; }

    /* Form labels */
    .field-label { font-size: 0.82rem; font-weight: 600; color: #333; margin-bottom: 5px; }
    .field-hint { font-size: 0.75rem; color: #6c757d; margin-top: 3px; }

    /* Nested detail cards */
    .detail-card {
        background: #f8f9fa; border: 1px solid #e9ecef;
        border-left: 4px solid #007A33; border-radius: 6px; padding: 16px;
    }

    /* Submit bar */
    .submit-bar {
        background: #fff; border-radius: 8px;
        padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;
    }

    /* Select2 tweaks */
    .select2-container--default .select2-selection--multiple,
    .select2-container--default .select2-selection--single {
        border: 1px solid #ced4da; border-radius: .375rem;
        min-height: 36px; background: #fff; font-size: 0.85rem;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #007A33; box-shadow: 0 0 0 .2rem rgba(0,122,51,.15);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 34px; padding-left: 10px; color: #212529; font-size: 0.85rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 34px; }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #e8f5ee; border: 1px solid #007A33; color: #212529;
        border-radius: .25rem; padding: 2px 8px; margin: 3px 3px 3px 0; font-size: 0.8rem;
    }
    .select2-dropdown { border-color: #ced4da; border-radius: .375rem; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
    .select2-results__option--selectable { padding: 6px 10px; font-size: 0.85rem; }
    .select2-results__option--highlighted { background: #e8f5ee !important; color: #212529 !important; }
    .select2-search--dropdown .select2-search__field { border: 1px solid #ced4da; border-radius: .25rem; padding: 5px 8px; }

    .form-control:focus, .form-select:focus {
        border-color: #007A33;
        box-shadow: 0 0 0 .2rem rgba(0,122,51,.15);
    }
    .btn-success { background-color: #007A33; border-color: #007A33; }
    .btn-success:hover { background-color: #006629; border-color: #006629; }
</style>
@endpush

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1" style="color: #333; font-weight: 300;">
                <i class="fas fa-edit me-2" style="color: #007A33;"></i>
                Edit Recruitment Requisition
                <span class="badge bg-secondary ms-2" style="font-size: 0.75rem;">{{ $requisition->access_id }}</span>
            </h4>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                @if($isHecMember)
                    Initiating as <strong>HEC Member</strong>
                @else
                    Initiating as <strong>Line Manager</strong>
                @endif
            </p>
        </div>
        <a href="{{ route('requisitions.show', $requisition->access_id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    {{-- Rejection warning --}}
    @if($requisition->status === 'rejected_for_editing')
    <div class="card shadow-sm mb-4 border-start border-warning border-4">
        <div class="card-body py-3">
            <div class="d-flex align-items-start gap-3">
                <div style="width:40px;height:40px;border-radius:50%;background:rgba(255,193,7,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold text-warning">Rejected for Editing</h6>
                    @if($requisition->rejection_reason)
                        <p class="mb-1"><strong>Reason:</strong> {{ $requisition->rejection_reason }}</p>
                    @endif
                    <div class="d-flex flex-wrap gap-3" style="font-size: 0.82rem;">
                        @if($requisition->rejection_stage)
                        <span><strong>Stage:</strong> {{ ucfirst($requisition->rejection_stage ?? 'Review') }}</span>
                        @endif
                        @if($requisition->rejected_at)
                        <span><strong>Date:</strong> {{ $requisition->rejected_at->format('d M Y, H:i') }}</span>
                        @endif
                        @if($requisition->getDaysUntilExpiration() !== null)
                        <span><strong>Days left:</strong> <span class="badge bg-warning text-dark">{{ $requisition->getDaysUntilExpiration() }}</span></span>
                        @endif
                        @if($requisition->can_edit_until)
                        <span><strong>Edit until:</strong> {{ $requisition->can_edit_until->format('d M Y, H:i') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i> <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-2 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('requisitions.update', $requisition->access_id) }}" method="POST" enctype="multipart/form-data" id="requisitionForm">
        @csrf
        @method('PUT')

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- SECTION A: POSITION DETAILS --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="section-title">
                    <i class="fas fa-briefcase"></i> Position Details
                </h5>

                {{-- Position Type --}}
                <div class="mb-4">
                    <div class="field-label">Position Type <span class="text-danger">*</span></div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="pos-type-card w-100 {{ old('position_type', $requisition->position_type) == 'new_position' ? 'selected' : '' }}" for="new_position">
                                <input type="radio" name="position_type" id="new_position" value="new_position"
                                       {{ old('position_type', $requisition->position_type) == 'new_position' ? 'checked' : '' }}>
                                <div class="pos-type-icon">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">New Position</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Brand new role</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="pos-type-card w-100 {{ old('position_type', $requisition->position_type) == 'replacement' ? 'selected' : '' }}" for="replacement">
                                <input type="radio" name="position_type" id="replacement" value="replacement"
                                       {{ old('position_type', $requisition->position_type) == 'replacement' ? 'checked' : '' }}>
                                <div class="pos-type-icon">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">Replacement</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Replace existing staff</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="pos-type-card w-100 {{ old('position_type', $requisition->position_type) == 'contract_renewal' ? 'selected' : '' }}" for="contract_renewal">
                                <input type="radio" name="position_type" id="contract_renewal" value="contract_renewal"
                                       {{ old('position_type', $requisition->position_type) == 'contract_renewal' ? 'checked' : '' }}>
                                <div class="pos-type-icon">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">Contract Renewal</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Extend/renew contract</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    @error('position_type')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <div class="row g-3">
                    {{-- Department --}}
                    <div class="col-md-6">
                        <div class="field-label">Hiring Department <span class="text-danger">*</span></div>
                        @if($isHecMember)
                            <select name="department_id" id="department_id" class="form-select form-select-sm" required>
                                <option value="">— Select Department —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $requisition->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->dept_name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="department_id" value="{{ $departments->first()?->id }}">
                            <input type="text" class="form-control form-control-sm" value="{{ $departments->first()?->dept_name }}" readonly>
                        @endif
                        @error('department_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Responsibility Centre --}}
                    <div class="col-md-6">
                        <div class="field-label">Responsibility Centre</div>
                        <input type="text" name="responsibility_centre" id="responsibility_centre"
                               class="form-control form-control-sm" value="{{ old('responsibility_centre', $requisition->responsibility_centre) }}"
                               placeholder="Auto-filled from department">
                    </div>

                    {{-- Line Manager (HEC only) --}}
                    @if($isHecMember)
                    <div class="col-md-6">
                        <div class="field-label">Line Manager <span class="text-danger">*</span></div>
                        <select name="line_manager_id" id="line_manager_id" class="form-select form-select-sm" required>
                            <option value="">— Select Line Manager —</option>
                            @if($requisition->line_manager_id)
                                @foreach($lineManagers as $lm)
                                    @if($lm->id == $requisition->line_manager_id)
                                        <option value="{{ $lm->id }}" selected data-job-title-id="{{ $lm->job_title_id ?? '' }}">{{ $lm->name }}</option>
                                    @endif
                                @endforeach
                            @endif
                        </select>
                        <div class="field-hint"><i class="fas fa-info-circle me-1"></i>Auto-populated when department is chosen</div>
                        @error('line_manager_id')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>
                    @endif

                    {{-- Reporting Line --}}
                    <div class="col-md-6">
                        <div class="field-label">Reporting Line (Reports To)</div>
                        <input type="text" name="reporting_line" id="reporting_line" class="form-control form-control-sm"
                               value="{{ old('reporting_line', $requisition->reporting_line) }}" placeholder="e.g., Head of Department">
                    </div>

                    {{-- Required Start Date --}}
                    <div class="col-md-6">
                        <div class="field-label">Required Start Date <span class="text-danger">*</span></div>
                        <input type="date" name="required_start_date" id="required_start_date"
                               class="form-control form-control-sm" value="{{ old('required_start_date', $requisition->required_start_date ? $requisition->required_start_date->format('Y-m-d') : '') }}"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                        @error('required_start_date')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Job Description Upload --}}
                    <div class="col-md-6">
                        <div class="field-label">Job Description (PDF) <span class="text-danger">*</span></div>
                        <input type="file" name="job_description" id="job_description"
                               class="form-control form-control-sm" accept=".pdf">
                        <div class="field-hint">PDF only, max 10MB. Leave empty to keep existing file.</div>
                        @if($requisition->job_description_path)
                            <div class="mt-2">
                                <a href="{{ route('requisitions.download-jd', $requisition->access_id) }}" target="_blank"
                                   class="btn btn-sm btn-outline-success" style="font-size:0.78rem; padding:3px 10px;">
                                    <i class="fas fa-file-pdf me-1"></i>View Current JD
                                </a>
                            </div>
                        @endif
                        @error('job_description')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- ── REPLACEMENT FIELDS ───────────────────────── --}}
                <div id="replacementFields" class="mt-4" style="display:none;">
                    <div class="detail-card" style="border-left-color: #fd7e14;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(253,126,20,0.1);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-exchange-alt" style="color:#fd7e14;"></i>
                            </div>
                            <div>
                                <div class="fw-bold" style="color:#333;">Staff Replacement Details</div>
                                <div class="text-muted" style="font-size:0.75rem;">Select the employee being replaced — details will auto-fill</div>
                            </div>
                        </div>

                        @if(!$isHecMember)
                        <div class="mb-3">
                            <div class="field-label mb-1">How many employees to replace? <span class="text-danger">*</span></div>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="employee_count_mode" id="mode_single" value="single"
                                           {{ !$requisition->employee_ids || count($requisition->employee_ids) <= 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="mode_single"><i class="fas fa-user me-1"></i> One</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="employee_count_mode" id="mode_multiple" value="multiple"
                                           {{ $requisition->employee_ids && count($requisition->employee_ids) > 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="mode_multiple"><i class="fas fa-users me-1"></i> More than one</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="field-label">Who is being replaced? <span class="text-danger">*</span>
                                    <small id="jobTitleAppliesAll" class="text-primary fw-normal d-none">(applies to all selected)</small>
                                </div>
                                <select name="employee_id[]" id="employee_id" class="form-select form-select-sm" multiple></select>
                                <div id="multiEmployeeWarning" class="alert alert-warning mt-2 py-1 px-2 d-none" style="font-size:0.78rem;">
                                    <i class="fas fa-exclamation-triangle me-1"></i> All selected must share the same Job Title.
                                </div>
                                @error('employee_id')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                                @error('employee_id.*')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <div id="rep_emp_card" class="d-none" style="background:#fff;border:1px solid #e9ecef;border-radius:8px;padding:12px 14px;">
                                    <div class="fw-semibold mb-1" id="rep_emp_name" style="color:#333;"></div>
                                    <div class="text-muted" id="rep_emp_title"></div>
                                    <div class="mt-1"><span class="text-muted">Contract ends:</span> <span id="rep_emp_end" class="fw-semibold text-danger"></span></div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="field-label">Their Contract End Date <span class="text-danger">*</span></div>
                                <input type="date" name="current_contract_end_date" id="rep_contract_end_date"
                                       class="form-control form-control-sm" value="{{ old('current_contract_end_date', $requisition->current_contract_end_date ? $requisition->current_contract_end_date->format('Y-m-d') : '') }}">
                                @error('current_contract_end_date')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <div class="field-label">Role to Fill <span class="text-danger">*</span></div>
                                <select name="job_title_id" id="job_title_id" class="form-select form-select-sm">
                                    <option value="">— Select Job Title —</option>
                                    @foreach($jobTitles as $jt)
                                        <option value="{{ $jt->id }}" {{ old('job_title_id', $requisition->job_title_id) == $jt->id ? 'selected' : '' }}>{{ $jt->job_title }}</option>
                                    @endforeach
                                </select>
                                @error('job_title_id')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <div class="field-label">New Contract Type <span class="text-danger">*</span></div>
                                <select name="contract_type" id="contract_type" class="form-select form-select-sm">
                                    <option value="">— Select —</option>
                                    <option value="minimal_1_year"     {{ old('contract_type', $requisition->contract_type) == 'minimal_1_year'     ? 'selected' : '' }}>Minimal 1 year</option>
                                    <option value="termed_less_1_year" {{ old('contract_type', $requisition->contract_type) == 'termed_less_1_year' ? 'selected' : '' }}>Termed &lt; 1 year</option>
                                    <option value="health_volunteer"   {{ old('contract_type', $requisition->contract_type) == 'health_volunteer'   ? 'selected' : '' }}>Health Volunteer</option>
                                    <option value="work_exposure"      {{ old('contract_type', $requisition->contract_type) == 'work_exposure'      ? 'selected' : '' }}>Work Exposure</option>
                                </select>
                                @error('contract_type')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── RENEWAL FIELDS ────────────────────────────── --}}
                <div id="renewalFields" class="mt-4" style="display:none;">
                    <div class="detail-card" style="border-left-color: #17a2b8;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(23,162,184,0.1);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-sync-alt" style="color:#17a2b8;"></i>
                            </div>
                            <div>
                                <div class="fw-bold" style="color:#333;">Contract Renewal Details</div>
                                <div class="text-muted" style="font-size:0.75rem;">Select the employee — their current contract end date will auto-fill</div>
                            </div>
                        </div>

                        @if(!$isHecMember)
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="field-label">Employee for Renewal <span class="text-danger">*</span></div>
                                <select name="employee_id[]" id="renewal_employee_id" class="form-select form-select-sm" multiple></select>
                                @error('employee_id')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <div id="ren_emp_card" class="d-none" style="background:#fff;border:1px solid #e9ecef;border-radius:8px;padding:12px 14px;">
                                    <div class="fw-semibold mb-1" id="ren_emp_name" style="color:#333;"></div>
                                    <div class="text-muted" id="ren_emp_title"></div>
                                    <div class="mt-1 d-flex align-items-center gap-1">
                                        <i class="fas fa-calendar-times text-danger" style="font-size:0.75rem;"></i>
                                        <span class="text-muted">Expires:</span>
                                        <span id="ren_emp_end" class="fw-semibold text-danger"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="field-label">Current Contract End Date <span class="text-danger">*</span></div>
                                <input type="date" name="current_contract_end_date" id="ren_contract_end_date"
                                       class="form-control form-control-sm" value="{{ old('current_contract_end_date', $requisition->current_contract_end_date ? $requisition->current_contract_end_date->format('Y-m-d') : '') }}">
                                <div class="field-hint"><i class="fas fa-magic me-1"></i>Auto-filled when employee is selected</div>
                                @error('current_contract_end_date')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <div class="field-label">Renewal Contract Type <span class="text-danger">*</span></div>
                                <select name="contract_type" id="contract_type_renewal" class="form-select form-select-sm">
                                    <option value="">— Select —</option>
                                    <option value="minimal_1_year"     {{ old('contract_type', $requisition->contract_type) == 'minimal_1_year'     ? 'selected' : '' }}>Minimal 1 year</option>
                                    <option value="termed_less_1_year" {{ old('contract_type', $requisition->contract_type) == 'termed_less_1_year' ? 'selected' : '' }}>Termed &lt; 1 year</option>
                                    <option value="health_volunteer"   {{ old('contract_type', $requisition->contract_type) == 'health_volunteer'   ? 'selected' : '' }}>Health Volunteer</option>
                                    <option value="work_exposure"      {{ old('contract_type', $requisition->contract_type) == 'work_exposure'      ? 'selected' : '' }}>Work Exposure</option>
                                </select>
                                @error('contract_type')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <div class="field-label">Role / Job Title</div>
                                <select name="job_title_id" id="renewal_job_title_id" class="form-select form-select-sm">
                                    <option value="">— Same as current —</option>
                                    @foreach($jobTitles as $jt)
                                        <option value="{{ $jt->id }}" {{ old('job_title_id', $requisition->job_title_id) == $jt->id ? 'selected' : '' }}>{{ $jt->job_title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="alert mt-3 mb-0 py-2 px-3" style="background:#e8f4fd;border:1px solid #bee3f8;border-radius:6px;font-size:0.82rem;color:#1a6f99;">
                            <i class="fas fa-info-circle me-1"></i>
                            Only employees with an existing contract in your department are shown. If the employee is not listed, check their status or contact HR.
                        </div>
                    </div>
                </div>

                {{-- ── NEW POSITION FIELDS ───────────────────────── --}}
                <div id="newPositionFields" class="mt-4" style="display:none;">
                    <div class="detail-card">
                        <div class="field-label mb-3">
                            <i class="fas fa-plus-circle me-1" style="color:#007A33;"></i>
                            New Position Details
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="field-label">Proposed Position Name <span class="text-danger">*</span></div>
                                <input type="text" name="new_job_title" id="new_job_title"
                                       class="form-control form-control-sm" value="{{ old('new_job_title', $requisition->new_job_title) }}"
                                       placeholder="Enter proposed position name">
                                @error('new_job_title')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <div class="field-label">Contract Type <span class="text-danger">*</span></div>
                                <select name="contract_type" id="contract_type_new" class="form-select form-select-sm">
                                    <option value="">— Select —</option>
                                    <option value="minimal_1_year"     {{ old('contract_type', $requisition->contract_type) == 'minimal_1_year'     ? 'selected' : '' }}>Minimal 1 year (employment)</option>
                                    <option value="termed_less_1_year" {{ old('contract_type', $requisition->contract_type) == 'termed_less_1_year' ? 'selected' : '' }}>Termed &lt; 1 year (consultant/task)</option>
                                    <option value="health_volunteer"   {{ old('contract_type', $requisition->contract_type) == 'health_volunteer'   ? 'selected' : '' }}>Health Volunteer (50% basic)</option>
                                    <option value="work_exposure"      {{ old('contract_type', $requisition->contract_type) == 'work_exposure'      ? 'selected' : '' }}>Work Exposure Placement (no pay)</option>
                                </select>
                                @error('contract_type')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- SECTION B: REASONING & JUSTIFICATION --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="section-title">
                    <i class="fas fa-clipboard-check"></i> Reasoning &amp; Justification
                </h5>

                <div class="field-label mb-2">Conditions <span class="text-danger">*</span></div>
                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <label class="cond-item w-100 {{ old('condition_medical_operational', $requisition->condition_medical_operational) ? 'checked' : '' }}" for="cond1">
                            <input type="checkbox" name="condition_medical_operational" id="cond1"
                                   class="cond-check" value="1" {{ old('condition_medical_operational', $requisition->condition_medical_operational) ? 'checked' : '' }}>
                            <div>
                                <i class="fas fa-medkit me-1 text-danger"></i>
                                Overwhelming medical / operational needs
                            </div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="cond-item w-100 {{ old('condition_safety_reputational', $requisition->condition_safety_reputational) ? 'checked' : '' }}" for="cond2">
                            <input type="checkbox" name="condition_safety_reputational" id="cond2"
                                   class="cond-check" value="1" {{ old('condition_safety_reputational', $requisition->condition_safety_reputational) ? 'checked' : '' }}>
                            <div>
                                <i class="fas fa-shield-alt me-1 text-warning"></i>
                                Safety or reputational risks
                            </div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="cond-item w-100 {{ old('condition_legal_requirement', $requisition->condition_legal_requirement) ? 'checked' : '' }}" for="cond3">
                            <input type="checkbox" name="condition_legal_requirement" id="cond3"
                                   class="cond-check" value="1" {{ old('condition_legal_requirement', $requisition->condition_legal_requirement) ? 'checked' : '' }}>
                            <div>
                                <i class="fas fa-gavel me-1 text-info"></i>
                                Legal requirement
                            </div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="cond-item w-100 {{ old('condition_financial_loss', $requisition->condition_financial_loss) ? 'checked' : '' }}" for="cond4">
                            <input type="checkbox" name="condition_financial_loss" id="cond4"
                                   class="cond-check" value="1" {{ old('condition_financial_loss', $requisition->condition_financial_loss) ? 'checked' : '' }}>
                            <div>
                                <i class="fas fa-chart-line me-1 text-danger"></i>
                                Financial loss if position not filled
                            </div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="cond-item w-100 {{ old('condition_increase_income', $requisition->condition_increase_income) ? 'checked' : '' }}" for="cond5">
                            <input type="checkbox" name="condition_increase_income" id="cond5"
                                   class="cond-check" value="1" {{ old('condition_increase_income', $requisition->condition_increase_income) ? 'checked' : '' }}>
                            <div>
                                <i class="fas fa-money-bill-wave me-1 text-success"></i>
                                Needed to increase income
                            </div>
                        </label>
                    </div>
                </div>
                @error('conditions')
                    <small class="text-danger d-block mb-2">{{ $message }}</small>
                @enderror

                <div class="field-label">Elaborate Justification <span class="text-danger">*</span></div>
                <textarea name="elaborate_reason" id="elaborate_reason" class="form-control" rows="4"
                          placeholder="Provide full justification — why this post cannot be filled internally and why recruitment is necessary... (min 50 characters)"
                          required minlength="50">{{ old('elaborate_reason', $requisition->elaborate_reason) }}</textarea>
                <div class="field-hint">Minimum 50 characters required</div>
                @error('elaborate_reason')
                    <small class="text-danger d-block">{{ $message }}</small>
                @enderror
            </div>
        </div>

        {{-- ── SUBMIT BAR ─────────────────────────────────────── --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body submit-bar">
                <a href="{{ route('requisitions.show', $requisition->access_id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times me-1"></i> Cancel
                </a>
                <button type="submit" class="btn btn-success btn-sm js-submit-requisition" style="padding: 8px 24px;">
                    <i class="fas fa-save me-1"></i> Update Requisition
                </button>
            </div>
        </div>

    </form>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Position type card selection highlight ── */
    document.querySelectorAll('.pos-type-card input[type=radio]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.pos-type-card').forEach(c => c.classList.remove('selected'));
            this.closest('.pos-type-card').classList.add('selected');
        });
    });

    /* ── Condition checkbox highlight ── */
    document.querySelectorAll('.cond-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            this.closest('.cond-item').classList.toggle('checked', this.checked);
        });
    });

    const positionTypes     = document.querySelectorAll('input[name="position_type"]');
    const newPositionFields = document.getElementById('newPositionFields');
    const replacementFields = document.getElementById('replacementFields');
    const renewalFields     = document.getElementById('renewalFields');
    const departmentSelect  = document.getElementById('department_id');
    const lineManagerSelect = document.getElementById('line_manager_id');
    const currentUserId     = {{ auth()->id() }};

    // Cached employees for auto-fill
    let employeeCache = {};

    // Previously selected employee IDs for pre-selection on load
    const previousEmployeeIds = @json($requisition->employee_ids ?? ($requisition->employee_id ? [$requisition->employee_id] : []));

    /* ── Init Select2 on a given select element ── */
    function initSelect2El(el, placeholder, multiple) {
        const $el = $(el);
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        if (multiple) el.setAttribute('multiple','multiple');
        else          el.removeAttribute('multiple');
        $el.select2({ placeholder, allowClear: true, width: '100%', closeOnSelect: !multiple });
    }

    const repEmpSel = document.getElementById('employee_id');
    const renEmpSel = document.getElementById('renewal_employee_id');

    if (repEmpSel) {
        const checkedMode = document.querySelector('input[name="employee_count_mode"]:checked');
        initSelect2El(repEmpSel, 'Search employee to replace...', checkedMode && checkedMode.value === 'multiple');
    }
    if (renEmpSel) initSelect2El(renEmpSel, 'Search employee for renewal...', false);

    /* ── Multiple mode toggle (replacement only) ── */
    document.querySelectorAll('input[name="employee_count_mode"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const multiple = this.value === 'multiple';
            $('#employee_id').val(null).trigger('change');
            if (repEmpSel) initSelect2El(repEmpSel, 'Search employee to replace...', multiple);
            const warn = document.getElementById('multiEmployeeWarning');
            const note = document.getElementById('jobTitleAppliesAll');
            if (!multiple) { warn?.classList.add('d-none'); note?.classList.add('d-none'); }
        });
    });

    /* ── Employee selected → auto-fill info card ── */
    $('#employee_id').on('change', function () {
        const id  = $(this).val();
        const ids = Array.isArray(id) ? id : (id ? [id] : []);
        const warn = document.getElementById('multiEmployeeWarning');
        const note = document.getElementById('jobTitleAppliesAll');
        const isMulti = document.querySelector('input[name="employee_count_mode"][value="multiple"]')?.checked;
        if (isMulti && ids.length > 1) { warn?.classList.remove('d-none'); note?.classList.remove('d-none'); }
        else                           { warn?.classList.add('d-none');    note?.classList.add('d-none'); }

        const card = document.getElementById('rep_emp_card');
        if (ids.length >= 1 && employeeCache[ids[0]]) {
            const e = employeeCache[ids[0]];
            document.getElementById('rep_emp_name').textContent  = e.name;
            document.getElementById('rep_emp_title').textContent = e.job_title_name || '';
            document.getElementById('rep_emp_end').textContent   = e.contract_end_date || '—';
            const endInput = document.getElementById('rep_contract_end_date');
            if (endInput && e.contract_end_date) endInput.value = e.contract_end_date;
            // Auto-fill job title
            if (e.job_title_id) {
                const jtSel = document.getElementById('job_title_id');
                if (jtSel) jtSel.value = e.job_title_id;
            }
            card?.classList.remove('d-none');
        } else {
            card?.classList.add('d-none');
        }
    });

    $('#renewal_employee_id').on('change', function () {
        const id = $(this).val()?.[0] || $(this).val();
        const card = document.getElementById('ren_emp_card');
        if (id && employeeCache[id]) {
            const e = employeeCache[id];
            document.getElementById('ren_emp_name').textContent  = e.name;
            document.getElementById('ren_emp_title').textContent = e.job_title_name || '';
            document.getElementById('ren_emp_end').textContent   = e.contract_end_date || '—';
            const endInput = document.getElementById('ren_contract_end_date');
            if (endInput && e.contract_end_date) endInput.value = e.contract_end_date;
            card?.classList.remove('d-none');
        } else {
            card?.classList.add('d-none');
        }
    });

    /* ── Load employees for a department ── */
    function loadEmployeesForDept(deptId, posType) {
        const includeInactive = posType === 'contract_renewal' ? '&include_inactive=1' : '';
        fetch(`{{ route('requisitions.employees') }}?department_id=${deptId}${includeInactive}`)
            .then(r => r.ok ? r.json() : {employees:[]})
            .then(data => {
                employeeCache = {};
                const employees = (data.employees || []).filter(e => e.id !== currentUserId);
                employees.forEach(e => { employeeCache[e.id] = e; });

                // Populate replacement select
                if (repEmpSel) {
                    $('#employee_id').empty();
                    employees.forEach(e => {
                        const opt = new Option(e.name, e.id, false, false);
                        opt.setAttribute('data-contract-end', e.contract_end_date || '');
                        opt.setAttribute('data-job-title-id', e.job_title_id || '');
                        // Pre-select previously selected employees
                        if (previousEmployeeIds && previousEmployeeIds.includes(e.id)) {
                            opt.selected = true;
                        }
                        $('#employee_id').append(opt);
                    });
                    $('#employee_id').trigger('change');
                }
                // Populate renewal select
                if (renEmpSel) {
                    $('#renewal_employee_id').empty();
                    employees.forEach(e => {
                        const opt = new Option(e.name + (e.status_label||''), e.id, false, false);
                        if (previousEmployeeIds && previousEmployeeIds.includes(e.id)) {
                            opt.selected = true;
                        }
                        $('#renewal_employee_id').append(opt);
                    });
                    $('#renewal_employee_id').trigger('change');
                }
            }).catch(() => {});
    }

    /* ── Toggle sections on position type ── */
    function getActiveDeptId() {
        return departmentSelect?.value
            || document.querySelector('input[name="department_id"][type="hidden"]')?.value;
    }

    function disableSection(sectionId) {
        document.querySelectorAll(`#${sectionId} input, #${sectionId} select, #${sectionId} textarea`).forEach(el => {
            el.disabled = true; el.removeAttribute('required');
        });
        document.getElementById(sectionId).style.display = 'none';
    }

    function enableSection(sectionId) {
        document.getElementById(sectionId).style.display = 'block';
        document.querySelectorAll(`#${sectionId} input, #${sectionId} select, #${sectionId} textarea`).forEach(el => {
            el.disabled = false;
        });
    }

    function toggleFields() {
        const selected = document.querySelector('input[name="position_type"]:checked');
        ['replacementFields','renewalFields','newPositionFields'].forEach(disableSection);

        if (!selected) return;

        if (selected.value === 'new_position') {
            enableSection('newPositionFields');
            document.getElementById('contract_type_new')?.setAttribute('required','required');
        } else if (selected.value === 'replacement') {
            enableSection('replacementFields');
            document.getElementById('contract_type')?.setAttribute('required','required');
            document.getElementById('rep_contract_end_date')?.setAttribute('required','required');
            const deptId = getActiveDeptId();
            if (deptId) loadEmployeesForDept(deptId, 'replacement');
        } else if (selected.value === 'contract_renewal') {
            enableSection('renewalFields');
            document.getElementById('contract_type_renewal')?.setAttribute('required','required');
            document.getElementById('ren_contract_end_date')?.setAttribute('required','required');
            const deptId = getActiveDeptId();
            if (deptId) loadEmployeesForDept(deptId, 'contract_renewal');
        }
    }

    positionTypes.forEach(r => r.addEventListener('change', toggleFields));
    toggleFields();

    /* ── Department change (HEC) → reload line managers + employees ── */
    if (departmentSelect) {
        departmentSelect.addEventListener('change', function () {
            const deptId = this.value;
            if (!deptId) return;

            if (lineManagerSelect) {
                fetch(`{{ route('requisitions.line-managers') }}?department_id=${deptId}`)
                    .then(r => r.ok ? r.json() : {line_managers:[]})
                    .then(data => {
                        lineManagerSelect.innerHTML = '<option value="">— Select Line Manager —</option>';
                        (data.line_managers || []).forEach(m => {
                            const opt = document.createElement('option');
                            opt.value = m.id; opt.textContent = m.name;
                            opt.setAttribute('data-job-title-id', m.job_title_id || '');
                            lineManagerSelect.appendChild(opt);
                        });
                        // Auto-select first line manager
                        if (data.line_managers && data.line_managers.length > 0) {
                            lineManagerSelect.value = data.line_managers[0].id;
                        }
                    }).catch(() => {});
            }

            const posType = document.querySelector('input[name="position_type"]:checked')?.value || 'replacement';
            loadEmployeesForDept(deptId, posType);
            loadJobTitlesByDepartment(deptId);
        });
    }

    @if($isLineManager)
    const lineManagerDeptId = {{ $userDepartmentId ?? 'null' }};
    const hiddenDeptInput   = document.querySelector('input[name="department_id"][type="hidden"]');
    const finalDeptId       = lineManagerDeptId || (hiddenDeptInput ? hiddenDeptInput.value : null);
    if (finalDeptId) {
        loadJobTitlesByDepartment(finalDeptId, {{ $requisition->job_title_id ?? 'null' }});
    }
    @endif

    @if($isHecMember)
    // Auto-load job titles on page load if department is pre-selected
    if (departmentSelect && departmentSelect.value) {
        loadJobTitlesByDepartment(departmentSelect.value, {{ $requisition->job_title_id ?? 'null' }});

        // Auto-load line managers on page load
        if (lineManagerSelect) {
            const deptId = departmentSelect.value;
            fetch(`{{ route('requisitions.line-managers') }}?department_id=${deptId}`)
                .then(r => r.ok ? r.json() : {line_managers:[]})
                .then(data => {
                    lineManagerSelect.innerHTML = '<option value="">— Select Line Manager —</option>';
                    (data.line_managers || []).forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.id; opt.textContent = m.name;
                        opt.setAttribute('data-job-title-id', m.job_title_id || '');
                        // Pre-select the previously saved line manager
                        if (m.id == {{ $requisition->line_manager_id ?? 'null' }}) opt.selected = true;
                        lineManagerSelect.appendChild(opt);
                    });
                }).catch(() => {});
        }
    }
    @endif

    /* ── Line manager change → auto-fill job title ── */
    if (lineManagerSelect) {
        lineManagerSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const jobTitleId = selectedOption?.getAttribute('data-job-title-id');
            if (jobTitleId) {
                const jtSel = document.getElementById('job_title_id');
                if (jtSel) jtSel.value = jobTitleId;
            }
        });
    }

    /* ── Submit button loading state ── */
    document.querySelectorAll('.js-submit-requisition').forEach(btn => {
        btn.closest('form')?.addEventListener('submit', function () {
            if (this.dataset.submitting === 'true') return;
            this.dataset.submitting = 'true';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Updating…';
        });
    });
});

function loadJobTitlesByDepartment(deptId, preselectId) {
    if (!deptId) return;
    fetch(`{{ route('requisitions.job-titles') }}?department_id=${deptId}`)
        .then(r => r.ok ? r.json() : {job_titles:[]})
        .then(data => {
            const titles = data.job_titles || data;
            ['job_title_id', 'renewal_job_title_id'].forEach(id => {
                const sel = document.getElementById(id);
                if (!sel) return;
                const current = preselectId || sel.value;
                const defaultLabel = id === 'renewal_job_title_id' ? '— Same as current —' : '— Select Job Title —';
                sel.innerHTML = `<option value="">${defaultLabel}</option>`;
                titles.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.job_title;
                    if (t.id == current) opt.selected = true;
                    sel.appendChild(opt);
                });
            });
        }).catch(() => {});
}
</script>
@endpush
