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
                            Recruitment Requisition Form
                        </h3>

                    </div>
                    <div class="col-auto">
                        <a href="{{ route('requisitions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> Please fix the following issues:
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('requisitions.store') }}" enctype="multipart/form-data"
                id="requisitionForm">
                @csrf

                {{-- Section 1: Position Details --}}
                <div class="card shadow-sm mb-4" style="border-left: 4px solid #007A33;">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="card-title mb-0" style="color: #333;">
                            <i class="fas fa-briefcase me-2" style="color: #007A33;"></i>1. Position Details
                        </h5>
                        <small class="text-muted">To be filled by respective Head of Department</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            {{-- Background Selection --}}
                            <div class="col-12">
                                <fieldset>
                                    <legend class="form-label fw-semibold">Background <span class="text-danger">*</span>
                                    </legend>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="form-check p-3 border rounded">
                                                <input type="radio" name="background" id="new_position"
                                                    value="new_position" class="form-check-input" required
                                                    {{ old('background') == 'new_position' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="new_position">
                                                    <i class="fas fa-plus-circle me-2 text-success"></i>New Position
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check p-3 border rounded">
                                                <input type="radio" name="background" id="replacement" value="replacement"
                                                    class="form-check-input"
                                                    {{ old('background') == 'replacement' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="replacement">
                                                    <i class="fas fa-user-friends me-2 text-primary"></i>Replacement
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check p-3 border rounded">
                                                <input type="radio" name="background" id="contract_renewal"
                                                    value="contract_renewal" class="form-check-input"
                                                    {{ old('background') == 'contract_renewal' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="contract_renewal">
                                                    <i class="fas fa-sync-alt me-2 text-info"></i>Contract Renewal/Extension
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    @error('background')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </fieldset>
                            </div>

                            {{-- Job Title (Existing) - Hidden for HEC members and Line Managers --}}
                            <div class="col-md-6" id="existingJobTitleRow"
                                style="{{ $isHecMember || ($isLineManager ?? false) ? 'display: none;' : '' }}">
                                <label for="job_title" class="form-label fw-semibold">Job Title <span
                                        class="text-danger">*</span></label>
                                <select name="job_title" id="job_title" class="form-select"
                                    {{ !$isHecMember && !($isLineManager ?? false) ? 'required' : '' }}>
                                    <option value="">-- Select Job Title --</option>
                                    @foreach ($jobTitles as $jobTitle)
                                        <option value="{{ $jobTitle->id }}"
                                            {{ old('job_title') == $jobTitle->id ? 'selected' : '' }}>
                                            {{ $jobTitle->job_title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('job_title')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- New Position Job Title - Hidden for HEC members and Line Managers --}}
                            <div class="col-md-6" id="newPositionFields" style="display: none;">
                                <label for="new_job_title" class="form-label fw-semibold">Job Title (New Position) <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="new_job_title" id="new_job_title" class="form-control"
                                    value="{{ old('new_job_title') }}" placeholder="Enter new job title"
                                    {{ !$isHecMember && !($isLineManager ?? false) ? 'required' : '' }}>
                                @error('new_job_title')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Replacement Employee - Hidden for Line Managers --}}
                            <div class="col-md-6" id="replacementEmployeeName" style="display: none;">
                                <label for="replacement_employee_id" class="form-label fw-semibold">Employee's Name
                                    @if (!$isHecMember && !($isLineManager ?? false))
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <select name="replacement_employee_id" id="replacement_employee_id" class="form-select"
                                    {{ !$isHecMember && !($isLineManager ?? false) ? 'required' : '' }}>
                                    <option value="">-- Select Job Title First --</option>
                                </select>
                                <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i>Select a job
                                    title above to see available employees for that job title</small>
                                <small class="text-info d-block mt-1"><i class="fas fa-check-circle me-1"></i>Staff will
                                    be loaded automatically from the selected job title</small>
                                @if ($isHecMember)
                                    <small class="text-info d-block mt-1"><i class="fas fa-info-circle me-1"></i>Employee
                                        details will be completed by the line manager</small>
                                @endif
                                @error('replacement_employee_id')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Contract Renewal Fields --}}
                            <div class="col-12" id="contractRenewalFields" style="display: none;">
                                <div class="card" style="border-left: 4px solid #007A33; background-color: #f8f9fa;">
                                    <div class="card-header bg-light">
                                        <h6 class="fw-semibold mb-0" style="color: #333;">
                                            <i class="fas fa-calendar-alt me-2" style="color: #007A33;"></i>Contract
                                            Renewal/Extension Details
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        {{-- @if ($isHecMember)
                                            <div class="alert alert-info mb-3">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <strong>Note:</strong> As an HEC member, employee details will be completed
                                                by the line manager during the approval process.
                                            </div>
                                        @endif --}}
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="contract_employee_id"
                                                    class="form-label fw-semibold">Employee's Name
                                                    @if (!$isHecMember && !($isLineManager ?? false))
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                                @if ($isLineManager ?? false)
                                                    {{-- For line managers, show all staff in department --}}
                                                    <select name="contract_employee_id" id="contract_employee_id"
                                                        class="form-select" required>
                                                        <option value="">-- Select Employee --</option>
                                                        @foreach ($users as $staff)
                                                            <option value="{{ $staff->id }}"
                                                                {{ old('contract_employee_id') == $staff->id ? 'selected' : '' }}>
                                                                {{ $staff->fname }} {{ $staff->mname ?? '' }}
                                                                {{ $staff->lname }}
                                                                @if ($staff->username)
                                                                    ({{ $staff->username }})
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted d-block mt-1"><i
                                                            class="fas fa-info-circle me-1"></i>Select the employee whose
                                                        contract needs renewal</small>
                                                @else
                                                    <select name="contract_employee_id" id="contract_employee_id"
                                                        class="form-select" {{ !$isHecMember ? 'required' : '' }}>
                                                        <option value="">-- Select Job Title First --</option>
                                                    </select>
                                                    <small class="text-muted d-block mt-1"><i
                                                            class="fas fa-info-circle me-1"></i>Select a
                                                        job title above to see available employees for that job
                                                        title</small>
                                                    <small class="text-info d-block mt-1"><i
                                                            class="fas fa-check-circle me-1"></i>Staff will be loaded
                                                        automatically from the selected job title</small>
                                                @endif
                                                @error('contract_employee_id')
                                                    <small class="text-danger d-block">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="contract_end_date" class="form-label fw-semibold">Current
                                                    Contract End Date
                                                    @if (!$isHecMember && !($isLineManager ?? false))
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                                <input type="date" name="contract_end_date" id="contract_end_date"
                                                    class="form-control" value="{{ old('contract_end_date') }}"
                                                    {{ !$isHecMember && !($isLineManager ?? false) ? 'required' : '' }}>
                                                @error('contract_end_date')
                                                    <small class="text-danger d-block">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Department --}}
                            <div class="col-md-6">
                                <label for="department" class="form-label fw-semibold">CCBRT Hiring Department <span
                                        class="text-danger">*</span></label>
                                @if ($isHecMember)
                                    {{-- HEC members can select from their mapped departments --}}
                                    <select name="department" id="department" class="form-select" required
                                        onchange="loadDepartmentData()">
                                        <option value="">-- Select Department --</option>
                                        @forelse ($departments as $dept)
                                            <option value="{{ $dept->id }}"
                                                {{ old('department', request('department')) == $dept->id ? 'selected' : '' }}
                                                data-dept-name="{{ $dept->dept_name }}">
                                                {{ $dept->dept_name }}
                                            </option>
                                        @empty
                                            <option value="" disabled>No departments mapped to your HEC role</option>
                                        @endforelse
                                    </select>
                                    @if ($departments->isNotEmpty())
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Select a department to load available line managers
                                        </small>
                                    @endif
                                    @if ($departments->isEmpty())
                                        <small class="text-warning d-block mt-1">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            No departments are mapped to your HEC member account. Please contact
                                            administrator to map departments.
                                        </small>
                                    @endif
                                @elseif ($isLineManager ?? false)
                                    {{-- Line Managers see their own department (read-only) --}}
                                    <input type="hidden" name="department"
                                        value="{{ $departments->first()->id ?? '' }}">
                                    <input type="text" class="form-control"
                                        value="{{ $departments->first()->dept_name ?? 'No department assigned' }}"
                                        readonly style="background-color: #f8f9fa;">
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle me-1"></i>Your department is automatically assigned
                                    </small>
                                @else
                                    {{-- Regular users see their own department --}}
                                    <select name="department" id="department" class="form-select" required>
                                        @if ($departments->isNotEmpty())
                                            <option value="{{ $departments->first()->id }}" selected>
                                                {{ $departments->first()->dept_name }}
                                            </option>
                                        @else
                                            <option value="" disabled>No department assigned</option>
                                        @endif
                                    </select>
                                @endif
                                @error('department')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Line Manager (for HEC members only) --}}
                            @if ($isHecMember)
                                <div class="col-md-6">
                                    <label for="line_manager" class="form-label fw-semibold">Line Manager (Contract
                                        Expired) <span class="text-danger">*</span></label>
                                    <select name="line_manager" id="line_manager" class="form-select" required disabled>
                                        <option value="">-- Select Department First --</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Only line managers whose contracts have expired are shown. The selected line
                                        manager's contract requires renewal.
                                    </small>
                                    {{-- Hidden field to store job_title_id from selected line manager --}}
                                    <input type="hidden" name="job_title" id="job_title_from_line_manager"
                                        value="">
                                    {{-- Display line manager's job title --}}
                                    <div id="line_manager_job_title_display" class="mt-2" style="display: none;">
                                        <small class="text-muted">
                                            <i class="fas fa-briefcase me-1"></i>
                                            <strong>Job Title:</strong> <span id="line_manager_job_title_text">-</span>
                                        </small>
                                    </div>
                                    @error('line_manager')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            @endif

                            {{-- Responsibility Centre --}}
                            <div class="col-md-6">
                                <label for="responsibility_centre" class="form-label fw-semibold">CCBRT Responsibility
                                    Centre <span class="text-danger">*</span></label>
                                <input type="text" name="responsibility_centre" id="responsibility_centre"
                                    class="form-control" required maxlength="100"
                                    value="{{ old('responsibility_centre') }}" placeholder="Enter responsibility centre">
                                @error('responsibility_centre')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Reporting Line --}}
                            <div class="col-md-6">
                                <label for="reporting_line" class="form-label fw-semibold">Reporting Line (Reports to
                                    Position) <span class="text-danger">*</span></label>
                                <input type="text" name="reporting_line" id="reporting_line" class="form-control"
                                    required maxlength="100" value="{{ old('reporting_line') }}"
                                    placeholder="Enter reporting position">
                                @error('reporting_line')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Contract Type --}}
                            <div class="col-md-6">
                                <fieldset>
                                    <legend class="form-label fw-semibold">Contract Type <span
                                            class="text-danger">*</span></legend>
                                    <div class="border rounded p-3">
                                        <div class="form-check mb-2">
                                            <input type="radio" name="contract_type" id="minimal_1_year"
                                                value="minimal_1_year" class="form-check-input" required
                                                {{ old('contract_type') == 'minimal_1_year' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="minimal_1_year">Minimal 1 Year
                                                (Employment)</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input type="radio" name="contract_type" id="termed_less_1_year"
                                                value="termed_less_1_year" class="form-check-input"
                                                {{ old('contract_type') == 'termed_less_1_year' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="termed_less_1_year">Termed &lt; 1 Year
                                                (Consultant/Specific Task)</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input type="radio" name="contract_type" id="health_volunteer"
                                                value="health_volunteer" class="form-check-input"
                                                {{ old('contract_type') == 'health_volunteer' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="health_volunteer">Health Volunteer (50%
                                                Basic, Minimal 1 Year)</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="radio" name="contract_type" id="work_exposure"
                                                value="work_exposure" class="form-check-input"
                                                {{ old('contract_type') == 'work_exposure' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="work_exposure">Work Exposure Placement
                                                (No
                                                Pay, Max 2x3 Months)</label>
                                        </div>
                                    </div>
                                    @error('contract_type')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </fieldset>
                            </div>

                            {{-- Required Starting Date --}}
                            <div class="col-md-6">
                                <label for="required_start_date" class="form-label fw-semibold">Required Starting
                                    Date</label>
                                <input type="date" name="required_start_date" id="required_start_date"
                                    class="form-control" value="{{ old('required_start_date') }}">
                                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Expected start date for
                                    the position</small>
                                @error('required_start_date')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Job Description File --}}
                            <div class="col-12">
                                <label for="job_description_file" class="form-label fw-semibold">The following documents
                                    to be attached: <span class="text-danger">*</span></label>
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" id="job_description_check" checked
                                        disabled>
                                    <label class="form-check-label" for="job_description_check">
                                        Updated Job/Task Description
                                    </label>
                                </div>
                                <input type="file" name="job_description_file" id="job_description_file"
                                    class="form-control mt-2" accept="application/pdf" required>
                                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Max size: 2MB. PDF format
                                    only.</small>
                                <div id="file-error-message" class="text-danger mt-1" style="display: none;"></div>
                                <div id="file-success-message" class="text-success mt-1" style="display: none;"></div>
                                @error('job_description_file')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Conditions --}}
                <div class="card shadow-sm mb-4" style="border-left: 4px solid #007A33;">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="card-title mb-0" style="color: #333;">
                            <i class="fas fa-check-square me-2" style="color: #007A33;"></i>2. Conditions
                        </h5>
                        <small class="text-muted">Tick at least one of the below conditions</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded h-100">
                                    <input type="checkbox" name="conditions[]" value="medical_operational"
                                        id="medical_operational" class="form-check-input"
                                        {{ in_array('medical_operational', old('conditions', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="medical_operational">
                                        <i class="fas fa-hospital me-2 text-danger"></i>There are overwhelming medical or
                                        operational imperatives to fill the post
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded h-100">
                                    <input type="checkbox" name="conditions[]" value="safety_reputational"
                                        id="safety_reputational" class="form-check-input"
                                        {{ in_array('safety_reputational', old('conditions', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="safety_reputational">
                                        <i class="fas fa-shield-alt me-2 text-warning"></i>There are safety or reputational
                                        risks to the organization if the post is not filled
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded h-100">
                                    <input type="checkbox" name="conditions[]" value="legal" id="legal"
                                        class="form-check-input"
                                        {{ in_array('legal', old('conditions', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="legal">
                                        <i class="fas fa-gavel me-2 text-info"></i>There are legal requirements to fill the
                                        post
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded h-100">
                                    <input type="checkbox" name="conditions[]" value="financial_loss"
                                        id="financial_loss" class="form-check-input"
                                        {{ in_array('financial_loss', old('conditions', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="financial_loss">
                                        <i class="fas fa-dollar-sign me-2 text-danger"></i>There is evidence that not
                                        filling the post will result in demonstrable financial loss to the organisation
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check p-3 border rounded">
                                    <input type="checkbox" name="conditions[]" value="increase_income"
                                        id="increase_income" class="form-check-input"
                                        {{ in_array('increase_income', old('conditions', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="increase_income">
                                        <i class="fas fa-chart-line me-2 text-success"></i>The post is necessary to
                                        increase income significantly
                                    </label>
                                </div>
                            </div>
                        </div>
                        @error('conditions')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- Section 3: Reasoning --}}
                <div class="card shadow-sm mb-4" style="border-left: 4px solid #007A33;">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="card-title mb-0" style="color: #333;">
                            <i class="fas fa-comment-alt me-2" style="color: #007A33;"></i>3. Reasoning & Business Impact
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="reasoning" class="form-label fw-semibold">
                                Elaborate Brief Your Reasoning, the Business Impact and Why Actions Cannot be Absorbed by
                                Existing Staff or Through Work Exposure Placement or Health Volunteer
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="reasoning" id="reasoning" class="form-control" required maxlength="1000" rows="6"
                                placeholder="Please provide detailed reasoning...">{{ old('reasoning') }}</textarea>
                            <small class="text-muted">Maximum 1000 characters</small>
                            @error('reasoning')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="card shadow-sm mb-4" style="border-left: 4px solid #007A33;">
                    <div class="card-body">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Only after step 4, document to be returned to HOD and forwarded to HR; if
                            no objection HR will start process.
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('requisitions.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success" id="submitRequisitionBtn"
                                style="background-color: #007A33; border-color: #007A33;">
                                <i class="fas fa-save me-1"></i> Submit Requisition
                            </button>
                        </div>
                    </div>
                </div>
            </form>
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

        .form-check {
            transition: all 0.2s;
        }

        .form-check:hover {
            background-color: #f8f9fa;
        }

        .form-check-input:checked~.form-check-label {
            color: #007A33;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007A33;
            box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.25);
        }

        .border.rounded {
            transition: all 0.2s;
        }

        .border.rounded:hover {
            border-color: #007A33 !important;
            box-shadow: 0 0 0 0.1rem rgba(0, 122, 51, 0.1);
        }

        .form-control,
        .form-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007A33;
            box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.15);
        }

        .form-label {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card {
            border-radius: 8px;
        }

        .form-check.p-3.border.rounded {
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .form-check.p-3.border.rounded:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }

        fieldset {
            border: none;
            padding: 0;
            margin: 0;
        }

        legend {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.75rem;
            padding: 0;
            width: auto;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
                    try {
                        const backgroundInputs = document.querySelectorAll('input[name="background"]');
                        const newPositionFields = document.getElementById('newPositionFields');
                        const existingJobTitleRow = document.getElementById('existingJobTitleRow');
                        const replacementEmployeeName = document.getElementById('replacementEmployeeName');
                        const contractRenewalFields = document.getElementById('contractRenewalFields');
                        const jobTitleSelect = document.getElementById('job_title');
                        const newJobTitleInput = document.getElementById('new_job_title');
                        const replacementEmployeeSelect = document.getElementById('replacement_employee_id');
                        const contractEmployeeSelect = document.getElementById('contract_employee_id');
                        const isHecMember = @json($isHecMember ?? false);
                        const isLineManager = @json($isLineManager ?? false);

                        /**
                         * LINE MANAGER: load staff from their own department
                         * GET: requisitions.all-staff-in-department
                         */
                        function loadAllStaffForLineManager(targetSelectId) {
                            const targetSelect = document.getElementById(targetSelectId);
                            if (!targetSelect) return;

                            targetSelect.innerHTML = '<option value="">Loading staff from your department...</option>';
                            targetSelect.disabled = true;

                            fetch(`{{ route('requisitions.all-staff-in-department') }}`, {
                                    method: 'GET',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                    }
                                })
                                .then(r => r.json())
                                .then(data => {
                                    targetSelect.innerHTML = '<option value="">-- Select Employee --</option>';

                                    if (data.users && data.users.length > 0) {
                                        data.users.forEach(user => {
                                            const option = document.createElement('option');
                                            option.value = user.id;
                                            option.textContent =
                                                user.name +
                                                (user.username ? ` (${user.username})` : '') +
                                                (user.job_title ? ` - ${user.job_title}` : '');

                                            const oldValue = targetSelect.getAttribute('data-old-value');
                                            if (oldValue && Number(oldValue) === Number(user.id)) {
                                                option.selected = true;
                                            }

                                            targetSelect.appendChild(option);
                                        });
                                    } else {
                                        targetSelect.innerHTML =
                                            '<option value="">No staff found in your department</option>';
                                    }

                                    targetSelect.disabled = false;
                                })
                                .catch(() => {
                                    targetSelect.innerHTML = '<option value="">Error loading staff</option>';
                                    targetSelect.disabled = false;
                                });
                        }

                        /**
                         * HEC MEMBER: load staff by selected DEPARTMENT
                         * GET: requisitions.staff-by-department
                         */
                        function loadStaffForHec(targetSelectId) {
                            const targetSelect = document.getElementById(targetSelectId);
                            const departmentSelect = document.getElementById('department');

                            if (!targetSelect || !departmentSelect) return;

                            const departmentId = departmentSelect.value;
                            if (!departmentId) {
                                targetSelect.innerHTML = '<option value="">Select department first</option>';
                                targetSelect.disabled = true;
                                return;
                            }

                            targetSelect.innerHTML = '<option value="">Loading staff from selected department...</option>';
                            targetSelect.disabled = true;

                            fetch(`{{ route('requisitions.staff-by-department') }}?department_id=${departmentId}`, {
                                    method: 'GET',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                    }
                                })
                                .then(r => r.json())
                                .then(data => {
                                    targetSelect.innerHTML = '<option value="">-- Select Employee --</option>';

                                    if (data.users && data.users.length > 0) {
                                        data.users.forEach(user => {
                                            const option = document.createElement('option');
                                            option.value = user.id;
                                            option.textContent =
                                                user.name +
                                                (user.username ? ` (${user.username})` : '') +
                                                (user.job_title ? ` - ${user.job_title}` : '');

                                            const oldValue = targetSelect.getAttribute('data-old-value');
                                            if (oldValue && Number(oldValue) === Number(user.id)) {
                                                option.selected = true;
                                            }

                                            targetSelect.appendChild(option);
                                        });
                                    } else {
                                        targetSelect.innerHTML =
                                            '<option value="">No staff found for this department</option>';
                                    }

                                    targetSelect.disabled = false;
                                })
                                .catch(() => {
                                    targetSelect.innerHTML = '<option value="">Error loading staff</option>';
                                    targetSelect.disabled = false;
                                });
                        }

                        /**
                         * NORMAL USERS (HOD): load staff by job title
                         * GET: requisitions.users-by-job-title
                         */
                        function loadUsersByJobTitle(jobTitleId, targetSelect) {
                            if (!jobTitleId) {
                                targetSelect.innerHTML = '<option value="">-- Select Job Title First --</option>';
                                return;
                            }

                            targetSelect.innerHTML = '<option value="">Loading staff from selected job title...</option>';
                            targetSelect.disabled = true;

                            fetch(`{{ route('requisitions.users-by-job-title') }}?job_title_id=${jobTitleId}`, {
                                    method: 'GET',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                    }
                                })
                                .then(r => r.json())
                                .then(data => {
                                    targetSelect.innerHTML = '<option value="">-- Select Employee --</option>';

                                    if (data.users && data.users.length > 0) {
                                        data.users.forEach(user => {
                                            const option = document.createElement('option');
                                            option.value = user.id;
                                            option.textContent =
                                                user.name + (user.username ? ` (${user.username})` : '');

                                            const oldValue = targetSelect.getAttribute('data-old-value');
                                            if (oldValue && Number(oldValue) === Number(user.id)) {
                                                option.selected = true;
                                            }

                                            targetSelect.appendChild(option);
                                        });
                                    } else {
                                        targetSelect.innerHTML =
                                            '<option value="">No staff found for this job title</option>';
                                    }

                                    targetSelect.disabled = false;
                                })
                                .catch(() => {
                                    targetSelect.innerHTML = '<option value="">Error loading staff</option>';
                                    targetSelect.disabled = false;
                                });
                        }

                        /**
                         * Job Title change – only for NORMAL users (HOD).
                         * Line Manager & HEC use department-based loading.
                         */
                        function handleJobTitleChange() {
                            if (!jobTitleSelect) return;
                            if (isLineManager || isHecMember) return; // they don't use job-title-based staff lists

                            const jobTitleId = jobTitleSelect.value;

                            if (!jobTitleId) {
                                if (replacementEmployeeSelect) {
                                    replacementEmployeeSelect.innerHTML =
                                        '<option value="">-- Select Job Title First --</option>';
                                }
                                if (contractEmployeeSelect) {
                                    contractEmployeeSelect.innerHTML =
                                        '<option value="">-- Select Job Title First --</option>';
                                }
                                return;
                            }

                            const selectedBackground = document.querySelector('input[name="background"]:checked');
                            if (!selectedBackground) return;
                            const backgroundValue = selectedBackground.value;

                            if (backgroundValue === 'replacement' &&
                                replacementEmployeeName &&
                                replacementEmployeeName.style.display !== 'none') {

                                if (replacementEmployeeSelect) {
                                    loadUsersByJobTitle(jobTitleId, replacementEmployeeSelect);
                                }
                            }

                            if (backgroundValue === 'contract_renewal' &&
                                contractRenewalFields &&
                                contractRenewalFields.style.display !== 'none') {

                                if (contractEmployeeSelect) {
                                    loadUsersByJobTitle(jobTitleId, contractEmployeeSelect);
                                }
                            }
                        }

                        /**
                         * Toggle sections based on Background selection
                         * Here we branch by ROLE: Line Manager, HEC, then default.
                         */
                        function toggleFields() {
                            const selected = document.querySelector('input[name="background"]:checked');

                            if (!selected) {
                                if (existingJobTitleRow) existingJobTitleRow.style.display = 'none';
                                if (newPositionFields) newPositionFields.style.display = 'none';
                                if (replacementEmployeeName) replacementEmployeeName.style.display = 'none';
                                if (contractRenewalFields) contractRenewalFields.style.display = 'none';
                                return;
                            }

                            const value = selected.value;

                            /** ========== LINE MANAGER LOGIC ========== */
                            if (isLineManager) {
                                // Line manager NEVER touches job titles here
                                if (existingJobTitleRow) existingJobTitleRow.style.display = 'none';
                                if (newPositionFields) newPositionFields.style.display = 'none';

                                if (value === 'new_position') {
                                    if (replacementEmployeeName) replacementEmployeeName.style.display = 'none';
                                    if (contractRenewalFields) contractRenewalFields.style.display = 'none';
                                    return;
                                }

                                if (value === 'replacement') {
                                    if (replacementEmployeeName) replacementEmployeeName.style.display = 'block';
                                    if (contractRenewalFields) contractRenewalFields.style.display = 'none';

                                    if (replacementEmployeeSelect) {
                                        replacementEmployeeSelect.setAttribute('required', 'required');
                                    }

                                    // Staff from their own department
                                    loadAllStaffForLineManager('replacement_employee_id');
                                    return;
                                }

                                if (value === 'contract_renewal') {
                                    if (replacementEmployeeName) replacementEmployeeName.style.display = 'none';
                                    if (contractRenewalFields) contractRenewalFields.style.display = 'block';

                                    // contract_employee_id already has $users from backend
                                    if (contractEmployeeSelect) {
                                        contractEmployeeSelect.setAttribute('required', 'required');
                                    }
                                    return;
                                }

                                return;
                            }

                            /** ========== HEC MEMBER LOGIC ========== */
                            if (isHecMember) {
                                // HEC also doesn't pick job_title here – they pick department then staff
                                if (existingJobTitleRow) existingJobTitleRow.style.display = 'none';
                                if (newPositionFields) newPositionFields.style.display = 'none';

                                if (value === 'new_position') {
                                    if (replacementEmployeeName) replacementEmployeeName.style.display = 'none';
                                    if (contractRenewalFields) contractRenewalFields.style.display = 'none';
                                    return;
                                }

                                if (value === 'replacement') {
                                    if (replacementEmployeeName) replacementEmployeeName.style.display = 'block';
                                    if (contractRenewalFields) contractRenewalFields.style.display = 'none';

                                    if (replacementEmployeeSelect) {
                                        replacementEmployeeSelect.setAttribute('required', 'required');
                                    }

                                    // Staff from selected department
                                    loadStaffForHec('replacement_employee_id');
                                    return;
                                }

                                if (value === 'contract_renewal') {
                                    if (replacementEmployeeName) replacementEmployeeName.style.display = 'none';
                                    if (contractRenewalFields) contractRenewalFields.style.display = 'block';

                                    if (contractEmployeeSelect) {
                                        contractEmployeeSelect.setAttribute('required', 'required');
                                    }

                                    loadStaffForHec('contract_employee_id');
                                    return;
                                }

                                return;
                            }

                            /** ========== NORMAL HOD / OTHER USERS ========== */
                            // Default: all job-title-based logic
                            if (existingJobTitleRow) existingJobTitleRow.style.display = 'block';
                            if (newPositionFields) newPositionFields.style.display = 'none';
                            if (replacementEmployeeName) replacementEmployeeName.style.display = 'none';
                            if (contractRenewalFields) contractRenewalFields.style.display = 'none';

                            if (jobTitleSelect) jobTitleSelect.setAttribute('required', 'required');
                            if (newJobTitleInput) newJobTitleInput.removeAttribute('required');

                            if (value === 'new_position') {
                                if (newPositionFields) newPositionFields.style.display = 'block';
                                if (existingJobTitleRow) existingJobTitleRow.style.display = 'none';
                                if (replacementEmployeeName) replacementEmployeeName.style.display = 'none';
                                if (contractRenewalFields) contractRenewalFields.style.display = 'none';

                                if (jobTitleSelect) jobTitleSelect.removeAttribute('required');
                                if (newJobTitleInput) newJobTitleInput.setAttribute('required', 'required');
                                return;
                            }

                            if (value === 'replacement') {
                                if (replacementEmployeeName) replacementEmployeeName.style.display = 'block';
                                if (contractRenewalFields) contractRenewalFields.style.display = 'none';

                                if (replacementEmployeeSelect) {
                                    replacementEmployeeSelect.setAttribute('required', 'required');
                                }

                                if (jobTitleSelect && jobTitleSelect.value) {
                                    loadUsersByJobTitle(jobTitleSelect.value, replacementEmployeeSelect);
                                }
                                return;
                            }

                            if (value === 'contract_renewal') {
                                if (contractRenewalFields) contractRenewalFields.style.display = 'block';
                                if (replacementEmployeeName) replacementEmployeeName.style.display = 'none';

                                if (contractEmployeeSelect) {
                                    contractEmployeeSelect.setAttribute('required', 'required');
                                }

                                if (jobTitleSelect && jobTitleSelect.value) {
                                    loadUsersByJobTitle(jobTitleSelect.value, contractEmployeeSelect);
                                }
                                return;
                            }
                        }

                        // Background change
                        backgroundInputs.forEach(input => {
                            input.addEventListener('change', function() {
                                toggleFields();
                                if (jobTitleSelect && jobTitleSelect.value) {
                                    handleJobTitleChange();
                                }
                            });
                        });

                        // Job title change (HOD only)
                        if (jobTitleSelect) {
                            jobTitleSelect.addEventListener('change', handleJobTitleChange);
                            jobTitleSelect.addEventListener('input', handleJobTitleChange);
                        }

                        /**
                         * Department + Line Manager for HEC
                         * Make loadDepartmentData GLOBAL so onchange="loadDepartmentData()" in HTML works
                         */
                        window.loadDepartmentData = function() {
                            const departmentSelect = document.getElementById('department');
                            const lineManagerSelect = document.getElementById('line_manager');
                            const jobTitleSelectLocal = document.getElementById('job_title');
                            const departmentId = departmentSelect ? departmentSelect.value : null;

                            if (!departmentId || !lineManagerSelect) {
                                if (lineManagerSelect) {
                                    lineManagerSelect.innerHTML =
                                        '<option value="">-- Select Department First --</option>';
                                    lineManagerSelect.disabled = true;
                                }
                                return;
                            }

                            // Line managers (for contract expired) for that department
                            lineManagerSelect.innerHTML = '<option value="">Loading line managers...</option>';
                            lineManagerSelect.disabled = true;

                            fetch(`{{ route('requisitions.line-managers-by-department') }}?department_id=${departmentId}`, {
                                    method: 'GET',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                    }
                                })
                                .then(r => r.json())
                                .then(data => {
                                    lineManagerSelect.innerHTML =
                                        '<option value="">-- Select Line Manager --</option>';

                                    if (data.lineManagers && data.lineManagers.length > 0) {
                                        data.lineManagers.forEach(manager => {
                                            const option = document.createElement('option');
                                            option.value = manager.id;
                                            let label = manager.name;
                                            if (manager.employee_id) {
                                                label += ` (${manager.employee_id})`;
                                            }
                                            if (manager.contract_end_date) {
                                                const d = new Date(manager.contract_end_date);
                                                label += ` - Contract expired: ${d.toLocaleDateString()}`;
                                            } else {
                                                label += ' - Contract expired';
                                            }
                                            option.textContent = label;
                                            if (manager.job_title_id) {
                                                option.dataset.jobTitleId = manager.job_title_id;
                                            }
                                            lineManagerSelect.appendChild(option);
                                        });

                                        if (data.lineManagers.length === 1) {
                                            lineManagerSelect.value = data.lineManagers[0].id;
                                            updateJobTitleFromLineManager(data.lineManagers[0]);
                                            lineManagerSelect.dispatchEvent(new Event('change'));
                                        }
                                    } else {
                                        lineManagerSelect.innerHTML =
                                            '<option value="">No line managers found</option>';
                                    }
                                    lineManagerSelect.disabled = false;
                                })
                                .catch(() => {
                                    lineManagerSelect.innerHTML =
                                        '<option value="">Error loading line managers</option>';
                                    lineManagerSelect.disabled = false;
                                });

                            // Optional: load job titles for department (for HOD use-case)
                            if (jobTitleSelectLocal) {
                                jobTitleSelectLocal.innerHTML = '<option value="">Loading job titles...</option>';
                                jobTitleSelectLocal.disabled = true;

                                fetch(`{{ route('requisitions.job-titles-by-department') }}?department_id=${departmentId}`, {
                                        method: 'GET',
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Accept': 'application/json',
                                        }
                                    })
                                    .then(r => r.json())
                                    .then(data => {
                                        jobTitleSelectLocal.innerHTML =
                                            '<option value="">-- Select Job Title --</option>';

                                        if (data.jobTitles && data.jobTitles.length > 0) {
                                            data.jobTitles.forEach(jobTitle => {
                                                const option = document.createElement('option');
                                                option.value = jobTitle.id;
                                                option.textContent = jobTitle.job_title;
                                                jobTitleSelectLocal.appendChild(option);
                                            });
                                        }
                                        jobTitleSelectLocal.disabled = false;
                                    })
                                    .catch(() => {
                                        jobTitleSelectLocal.innerHTML =
                                            '<option value="">Error loading job titles</option>';
                                        jobTitleSelectLocal.disabled = false;
                                    });
                            };

                            function updateJobTitleFromLineManager(manager) {
                                const jobTitleHiddenField = document.getElementById('job_title_from_line_manager');
                                const jobTitleDisplay = document.getElementById('line_manager_job_title_display');
                                const jobTitleText = document.getElementById('line_manager_job_title_text');

                                if (manager.job_title_id) {
                                    if (jobTitleHiddenField) {
                                        jobTitleHiddenField.value = manager.job_title_id;
                                    }

                                    const deptId = document.getElementById('department')?.value;
                                    if (!deptId) return;

                                    fetch(`{{ route('requisitions.job-titles-by-department') }}?department_id=${deptId}`, {
                                            method: 'GET',
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'application/json',
                                            }
                                        })
                                        .then(r => r.json())
                                        .then(data => {
                                            if (data.jobTitles && data.jobTitles.length > 0) {
                                                const jt = data.jobTitles.find(j => Number(j.id) === Number(manager
                                                    .job_title_id));
                                                if (jt && jobTitleDisplay && jobTitleText) {
                                                    jobTitleText.textContent = jt.job_title;
                                                    jobTitleDisplay.style.display = 'block';
                                                }
                                            }
                                        })
                                        .catch(() => {
                                            if (jobTitleText) {
                                                jobTitleText.textContent = 'Job Title ID: ' + manager.job_title_id;
                                            }
                                            if (jobTitleDisplay) {
                                                jobTitleDisplay.style.display = 'block';
                                            }
                                        });
                                } else {
                                    if (jobTitleHiddenField) jobTitleHiddenField.value = '';
                                    if (jobTitleDisplay) jobTitleDisplay.style.display = 'none';
                                }
                            }

                            const lineManagerSelect = document.getElementById('line_manager');
                            if (lineManagerSelect && isHecMember) {
                                lineManagerSelect.addEventListener('change', function() {
                                    const opt = this.options[this.selectedIndex];
                                    if (opt && opt.value) {
                                        const jobTitleId = opt.dataset.jobTitleId;
                                        if (jobTitleId) {
                                            updateJobTitleFromLineManager({
                                                id: opt.value,
                                                job_title_id: jobTitleId
                                            });
                                        }
                                    } else {
                                        const jobTitleHiddenField = document.getElementById(
                                            'job_title_from_line_manager');
                                        const jobTitleDisplay = document.getElementById(
                                            'line_manager_job_title_display');
                                        if (jobTitleHiddenField) jobTitleHiddenField.value = '';
                                        if (jobTitleDisplay) jobTitleDisplay.style.display = 'none';
                                    }
                                });
                            }

                            const departmentSelect = document.getElementById('department');
                            if (departmentSelect && isHecMember) {
                                departmentSelect.addEventListener('change', function() {
                                    window.loadDepartmentData();

                                    const selectedBackground = document.querySelector(
                                        'input[name="background"]:checked');
                                    if (!selectedBackground) return;

                                    if (selectedBackground.value === 'replacement') {
                                        loadStaffForHec('replacement_employee_id');
                                    } else if (selectedBackground.value === 'contract_renewal') {
                                        loadStaffForHec('contract_employee_id');
                                    }
                                });
                            }

                            // Preserve old values for selects
                            @if (old('replacement_employee_id'))
                                if (replacementEmployeeSelect) {
                                    replacementEmployeeSelect.setAttribute('data-old-value',
                                        '{{ old('replacement_employee_id') }}');
                                }
                            @endif
                            @if (old('contract_employee_id'))
                                if (contractEmployeeSelect) {
                                    contractEmployeeSelect.setAttribute('data-old-value',
                                        '{{ old('contract_employee_id') }}');
                                }
                            @endif

                            // Initial state
                            toggleFields();
                            const initialBackground = document.querySelector('input[name="background"]:checked');
                            if (initialBackground) {
                                if (isHecMember) {
                                    if (initialBackground.value === 'replacement') {
                                        loadStaffForHec('replacement_employee_id');
                                    } else if (initialBackground.value === 'contract_renewal') {
                                        loadStaffForHec('contract_employee_id');
                                    }
                                } else if (isLineManager) {
                                    if (initialBackground.value === 'replacement') {
                                        loadAllStaffForLineManager('replacement_employee_id');
                                    }
                                    // Contract renewal already has $users for line manager
                                } else if (jobTitleSelect && jobTitleSelect.value) {
                                    handleJobTitleChange();
                                }
                            }

                            // ========== FILE VALIDATION ==========
                            const jobDescriptionFile = document.getElementById('job_description_file');
                            const fileErrorMessage = document.getElementById('file-error-message');
                            const fileSuccessMessage = document.getElementById('file-success-message');

                            if (jobDescriptionFile) {
                                jobDescriptionFile.addEventListener('change', function(e) {
                                    const file = e.target.files[0];

                                    if (fileErrorMessage) fileErrorMessage.style.display = 'none';
                                    if (fileSuccessMessage) fileSuccessMessage.style.display = 'none';

                                    if (!file) return;

                                    const maxSize = 2 * 1024 * 1024;
                                    const fileSize = file.size;
                                    const fileName = file.name;
                                    const fileExtension = fileName.split('.').pop().toLowerCase();

                                    if (fileExtension !== 'pdf' || file.type !== 'application/pdf') {
                                        e.target.value = '';
                                        if (fileErrorMessage) {
                                            fileErrorMessage.innerHTML =
                                                '<i class="fas fa-exclamation-circle me-1"></i>Invalid file format. Please upload a PDF file only.';
                                            fileErrorMessage.style.display = 'block';
                                        }
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Invalid File Format',
                                                text: 'Please upload a PDF file only. The selected file is not a PDF.',
                                            });
                                        }
                                        return;
                                    }

                                    if (fileSize > maxSize) {
                                        e.target.value = '';
                                        const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);
                                        if (fileErrorMessage) {
                                            fileErrorMessage.innerHTML =
                                                `<i class="fas fa-exclamation-circle me-1"></i>File size (${fileSizeMB} MB) exceeds 2 MB.`;
                                            fileErrorMessage.style.display = 'block';
                                        }
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'File Too Large',
                                                html: `The file size (${fileSizeMB} MB) exceeds the maximum allowed size of 2 MB.`,
                                            });
                                        }
                                        return;
                                    }

                                    const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);
                                    if (fileSuccessMessage) {
                                        fileSuccessMessage.innerHTML =
                                            `<i class="fas fa-check-circle me-1"></i>File "${fileName}" (${fileSizeMB} MB) is valid and ready to upload.`;
                                        fileSuccessMessage.style.display = 'block';
                                    }
                                });
                            }

                            // ========== FORM SUBMIT VALIDATION ==========
                            const requisitionForm = document.getElementById('requisitionForm');
                            const submitBtn = document.getElementById('submitRequisitionBtn');

                            if (requisitionForm && submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.type = 'submit';

                                requisitionForm.addEventListener('submit', function(e) {
                                    // 1. At least one condition
                                    const conditionCheckboxes = document.querySelectorAll(
                                        'input[name="conditions[]"]:checked');
                                    if (conditionCheckboxes.length === 0) {
                                        e.preventDefault();
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire({
                                                icon: 'warning',
                                                title: 'Condition Required',
                                                text: 'Please select at least one condition before submitting.',
                                            });
                                        } else {
                                            alert('Please select at least one condition before submitting.');
                                        }
                                        return;
                                    }

                                    // 2. Double-check file (browser will also enforce "required")
                                    const fileInput = document.getElementById('job_description_file');
                                    if (fileInput && fileInput.files.length > 0) {
                                        const file = fileInput.files[0];
                                        const maxSize = 2 * 1024 * 1024;
                                        const fileSize = file.size;
                                        const fileName = file.name;
                                        const fileExtension = fileName.split('.').pop().toLowerCase();

                                        if (fileExtension !== 'pdf' || file.type !== 'application/pdf') {
                                            e.preventDefault();
                                            if (typeof Swal !== 'undefined') {
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Invalid File Format',
                                                    text: 'Please upload a PDF file only.',
                                                });
                                            } else {
                                                alert('Please upload a PDF file only.');
                                            }
                                            return;
                                        }

                                        if (fileSize > maxSize) {
                                            e.preventDefault();
                                            const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);
                                            if (typeof Swal !== 'undefined') {
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'File Too Large',
                                                    html: `The file size (${fileSizeMB} MB) exceeds 2 MB.`,
                                                });
                                            } else {
                                                alert(`The file size (${fileSizeMB} MB) exceeds 2 MB.`);
                                            }
                                            return;
                                        }
                                    }
                                    // no preventDefault here => form submits normally if all OK
                                });
                            }

                        } catch (error) {
                            console.error('Error during form initialization:', error);
                            const requisitionForm = document.getElementById('requisitionForm');
                            const submitBtn = document.getElementById('submitRequisitionBtn');
                            if (requisitionForm && submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.type = 'submit';
                            }
                        }
                    });
    </script>
@endpush
