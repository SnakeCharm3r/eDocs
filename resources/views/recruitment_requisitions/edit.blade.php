@extends('layouts.template')
@include('sweetalert::alert')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">
                        <i class="fas fa-edit me-2"></i>Edit Recruitment Requisition
                    </h3>
                </div>
                <div class="col-auto">
                    <a href="{{ route('recruitment-requisitions.show', $requisition->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('recruitment-requisitions.update', $requisition->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <!-- Same form structure as create.blade.php but with existing values -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Section 1: Position Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Job Title <span class="text-danger">*</span></label>
                            <input type="text" name="job_title" class="form-control" 
                                   value="{{ old('job_title', $requisition->job_title) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Background <span class="text-danger">*</span></label>
                            <select name="background" class="form-control" id="background" required>
                                <option value="new_position" {{ old('background', $requisition->background) == 'new_position' ? 'selected' : '' }}>New Position</option>
                                <option value="replacement" {{ old('background', $requisition->background) == 'replacement' ? 'selected' : '' }}>Replacement</option>
                                <option value="contract_renewal_extension" {{ old('background', $requisition->background) == 'contract_renewal_extension' ? 'selected' : '' }}>Contract Renewal/Extension</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3" id="renewalFields" style="display: {{ $requisition->background === 'contract_renewal_extension' ? 'block' : 'none' }};">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Employee Name</label>
                            <input type="text" name="employee_name" class="form-control" 
                                   value="{{ old('employee_name', $requisition->employee_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Current Contract End Date</label>
                            <input type="date" name="current_contract_end_date" class="form-control" 
                                   value="{{ old('current_contract_end_date', $requisition->current_contract_end_date?->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-control" required>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $requisition->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->dept_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Responsibility Centre <span class="text-danger">*</span></label>
                            <input type="text" name="responsibility_centre" class="form-control" 
                                   value="{{ old('responsibility_centre', $requisition->responsibility_centre) }}" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Reports To Position <span class="text-danger">*</span></label>
                            <input type="text" name="reports_to_position" class="form-control" 
                                   value="{{ old('reports_to_position', $requisition->reports_to_position) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contract Type <span class="text-danger">*</span></label>
                            <select name="contract_type" class="form-control" required>
                                <option value="minimal_1_year_employment" {{ old('contract_type', $requisition->contract_type) == 'minimal_1_year_employment' ? 'selected' : '' }}>Minimal 1 Year Employment</option>
                                <option value="termed_less_than_1_year_consultant_task" {{ old('contract_type', $requisition->contract_type) == 'termed_less_than_1_year_consultant_task' ? 'selected' : '' }}>Termed Less Than 1 Year (Consultant/Task)</option>
                                <option value="health_volunteer_50_basic_min_1_year" {{ old('contract_type', $requisition->contract_type) == 'health_volunteer_50_basic_min_1_year' ? 'selected' : '' }}>Health Volunteer (50% Basic, Min 1 Year)</option>
                                <option value="work_exposure_no_pay_max_2x3_months" {{ old('contract_type', $requisition->contract_type) == 'work_exposure_no_pay_max_2x3_months' ? 'selected' : '' }}>Work Exposure (No Pay, Max 2x3 Months)</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">Budget Allocation</h6>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="checkbox" name="position_approved_in_budget" value="1" 
                                       class="form-check-input" id="position_approved_in_budget"
                                       {{ old('position_approved_in_budget', $requisition->position_approved_in_budget) ? 'checked' : '' }}>
                                <label class="form-check-label" for="position_approved_in_budget">
                                    Position Approved in Budget
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="checkbox" name="funding_available" value="1" 
                                       class="form-check-input" id="funding_available"
                                       {{ old('funding_available', $requisition->funding_available) ? 'checked' : '' }}>
                                <label class="form-check-label" for="funding_available">
                                    Funding Available
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Max Monthly Budget</label>
                            <input type="number" name="max_monthly_budget" step="0.01" class="form-control" 
                                   value="{{ old('max_monthly_budget', $requisition->max_monthly_budget) }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Donor Code</label>
                            <input type="text" name="donor_code" class="form-control" 
                                   value="{{ old('donor_code', $requisition->donor_code) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Activity Code</label>
                            <input type="text" name="activity_code" class="form-control" 
                                   value="{{ old('activity_code', $requisition->activity_code) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Payroll Accountant</label>
                            <select name="payroll_accountant_user_id" class="form-control">
                                <option value="">-- Select --</option>
                                @foreach($payrollAccountants as $accountant)
                                    <option value="{{ $accountant->id }}" {{ old('payroll_accountant_user_id', $requisition->payroll_accountant_user_id) == $accountant->id ? 'selected' : '' }}>
                                        {{ $accountant->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Required Starting Date <span class="text-danger">*</span></label>
                            <input type="date" name="required_starting_date" class="form-control" 
                                   value="{{ old('required_starting_date', $requisition->required_starting_date->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Job Description File(s)</label>
                            <input type="file" name="attachments[]" class="form-control" multiple 
                                   accept=".pdf,.doc,.docx">
                            <small class="text-muted">Multiple files allowed (PDF, DOC, DOCX, max 5MB each)</small>
                        </div>
                    </div>

                    @if($requisition->attachments->count() > 0)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Existing Attachments</label>
                            <div class="border p-3 rounded">
                                @foreach($requisition->attachments as $attachment)
                                    <div class="form-check">
                                        <input type="checkbox" name="delete_attachments[]" value="{{ $attachment->id }}" class="form-check-input" id="delete_{{ $attachment->id }}">
                                        <label class="form-check-label" for="delete_{{ $attachment->id }}">
                                            <a href="{{ Storage::url($attachment->file_path) }}" target="_blank">
                                                {{ $attachment->original_name }}
                                            </a>
                                            <small class="text-muted">({{ $attachment->file_size_human }})</small>
                                        </label>
                                    </div>
                                @endforeach
                                <small class="text-muted">Check to delete attachments</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Section 2: Conditions & Justification</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Conditions <span class="text-danger">*</span></label>
                        <div class="border p-3 rounded">
                            @for($i = 1; $i <= 5; $i++)
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="conditions[]" value="{{ $i }}" class="form-check-input" 
                                           id="condition{{ $i }}" {{ in_array($i, old('conditions', $requisition->conditions ?? [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="condition{{ $i }}">
                                        {{ $i }}. 
                                        @if($i == 1) Overwhelming medical/operational imperatives
                                        @elseif($i == 2) Safety or reputational risks
                                        @elseif($i == 3) Legal requirement to fill the post
                                        @elseif($i == 4) Evidence of demonstrable financial loss if not filled
                                        @elseif($i == 5) Post is necessary to increase income significantly
                                        @endif
                                    </label>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Justification Text <span class="text-danger">*</span></label>
                        <textarea name="justification_text" class="form-control" rows="6" required>{{ old('justification_text', $requisition->justification_text) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('recruitment-requisitions.show', $requisition->id) }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Requisition
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const backgroundSelect = document.getElementById('background');
    const renewalFields = document.getElementById('renewalFields');
    
    backgroundSelect.addEventListener('change', function() {
        if (this.value === 'contract_renewal_extension') {
            renewalFields.style.display = 'block';
        } else {
            renewalFields.style.display = 'none';
        }
    });
});
</script>
@endsection




