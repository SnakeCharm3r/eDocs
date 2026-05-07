@extends('layouts.template')
@include('sweetalert::alert')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">
                        <i class="fas fa-plus-circle me-2"></i>Create Recruitment Requisition (HR.01)
                    </h3>
                </div>
                <div class="col-auto">
                    <a href="{{ route('recruitment-requisitions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('recruitment-requisitions.store') }}" enctype="multipart/form-data" id="requisitionForm">
            @csrf
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Section 1: Position Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Job Title <span class="text-danger">*</span></label>
                            <input type="text" name="job_title" class="form-control @error('job_title') is-invalid @enderror" 
                                   value="{{ old('job_title') }}" required>
                            @error('job_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Background <span class="text-danger">*</span></label>
                            <select name="background" class="form-control @error('background') is-invalid @enderror" 
                                    id="background" required>
                                <option value="">-- Select --</option>
                                <option value="new_position" {{ old('background') == 'new_position' ? 'selected' : '' }}>New Position</option>
                                <option value="replacement" {{ old('background') == 'replacement' ? 'selected' : '' }}>Replacement</option>
                                <option value="contract_renewal_extension" {{ old('background') == 'contract_renewal_extension' ? 'selected' : '' }}>Contract Renewal/Extension</option>
                            </select>
                            @error('background')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3" id="renewalFields" style="display: none;">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Employee Name</label>
                            <input type="text" name="employee_name" class="form-control" 
                                   value="{{ old('employee_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Current Contract End Date</label>
                            <input type="date" name="current_contract_end_date" class="form-control" 
                                   value="{{ old('current_contract_end_date') }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-control @error('department_id') is-invalid @enderror" required>
                                <option value="">-- Select Department --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->dept_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Responsibility Centre <span class="text-danger">*</span></label>
                            <input type="text" name="responsibility_centre" class="form-control @error('responsibility_centre') is-invalid @enderror" 
                                   value="{{ old('responsibility_centre') }}" required>
                            @error('responsibility_centre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Reports To Position <span class="text-danger">*</span></label>
                            <input type="text" name="reports_to_position" class="form-control @error('reports_to_position') is-invalid @enderror" 
                                   value="{{ old('reports_to_position') }}" required>
                            @error('reports_to_position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contract Type <span class="text-danger">*</span></label>
                            <select name="contract_type" class="form-control @error('contract_type') is-invalid @enderror" required>
                                <option value="">-- Select --</option>
                                <option value="minimal_1_year_employment" {{ old('contract_type') == 'minimal_1_year_employment' ? 'selected' : '' }}>Minimal 1 Year Employment</option>
                                <option value="termed_less_than_1_year_consultant_task" {{ old('contract_type') == 'termed_less_than_1_year_consultant_task' ? 'selected' : '' }}>Termed Less Than 1 Year (Consultant/Task)</option>
                                <option value="health_volunteer_50_basic_min_1_year" {{ old('contract_type') == 'health_volunteer_50_basic_min_1_year' ? 'selected' : '' }}>Health Volunteer (50% Basic, Min 1 Year)</option>
                                <option value="work_exposure_no_pay_max_2x3_months" {{ old('contract_type') == 'work_exposure_no_pay_max_2x3_months' ? 'selected' : '' }}>Work Exposure (No Pay, Max 2x3 Months)</option>
                            </select>
                            @error('contract_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">Budget Allocation</h6>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="checkbox" name="position_approved_in_budget" value="1" 
                                       class="form-check-input" id="position_approved_in_budget"
                                       {{ old('position_approved_in_budget') ? 'checked' : '' }}>
                                <label class="form-check-label" for="position_approved_in_budget">
                                    Position Approved in Budget
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="checkbox" name="funding_available" value="1" 
                                       class="form-check-input" id="funding_available"
                                       {{ old('funding_available') ? 'checked' : '' }}>
                                <label class="form-check-label" for="funding_available">
                                    Funding Available
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Max Monthly Budget</label>
                            <input type="number" name="max_monthly_budget" step="0.01" class="form-control" 
                                   value="{{ old('max_monthly_budget') }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Donor Code</label>
                            <input type="text" name="donor_code" class="form-control" 
                                   value="{{ old('donor_code') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Activity Code</label>
                            <input type="text" name="activity_code" class="form-control" 
                                   value="{{ old('activity_code') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Payroll Accountant</label>
                            <select name="payroll_accountant_user_id" class="form-control">
                                <option value="">-- Select --</option>
                                @foreach($payrollAccountants as $accountant)
                                    <option value="{{ $accountant->id }}" {{ old('payroll_accountant_user_id') == $accountant->id ? 'selected' : '' }}>
                                        {{ $accountant->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Required Starting Date <span class="text-danger">*</span></label>
                            <input type="date" name="required_starting_date" class="form-control @error('required_starting_date') is-invalid @enderror" 
                                   value="{{ old('required_starting_date') }}" required>
                            @error('required_starting_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Job Description File(s)</label>
                            <input type="file" name="attachments[]" class="form-control" multiple 
                                   accept=".pdf,.doc,.docx">
                            <small class="text-muted">Multiple files allowed (PDF, DOC, DOCX, max 5MB each)</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Section 2: Conditions & Justification</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Conditions <span class="text-danger">*</span> (Select at least one)</label>
                        <div class="border p-3 rounded">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="conditions[]" value="1" class="form-check-input" 
                                       id="condition1" {{ in_array('1', old('conditions', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="condition1">
                                    1. Overwhelming medical/operational imperatives
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="conditions[]" value="2" class="form-check-input" 
                                       id="condition2" {{ in_array('2', old('conditions', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="condition2">
                                    2. Safety or reputational risks
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="conditions[]" value="3" class="form-check-input" 
                                       id="condition3" {{ in_array('3', old('conditions', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="condition3">
                                    3. Legal requirement to fill the post
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="conditions[]" value="4" class="form-check-input" 
                                       id="condition4" {{ in_array('4', old('conditions', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="condition4">
                                    4. Evidence of demonstrable financial loss if not filled
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="conditions[]" value="5" class="form-check-input" 
                                       id="condition5" {{ in_array('5', old('conditions', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="condition5">
                                    5. Post is necessary to increase income significantly
                                </label>
                            </div>
                        </div>
                        @error('conditions')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Justification Text <span class="text-danger">*</span></label>
                        <textarea name="justification_text" class="form-control @error('justification_text') is-invalid @enderror" 
                                  rows="6" required placeholder="Elaborate reasoning, business impact, and why actions cannot be absorbed by existing staff/volunteers...">{{ old('justification_text') }}</textarea>
                        <small class="text-muted">Minimum 50 characters required</small>
                        @error('justification_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('recruitment-requisitions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save as Draft
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
            renewalFields.querySelector('input[name="employee_name"]').required = true;
            renewalFields.querySelector('input[name="current_contract_end_date"]').required = true;
        } else {
            renewalFields.style.display = 'none';
            renewalFields.querySelector('input[name="employee_name"]').required = false;
            renewalFields.querySelector('input[name="current_contract_end_date"]').required = false;
        }
    });
    
    // Trigger on page load if value is already set
    if (backgroundSelect.value === 'contract_renewal_extension') {
        renewalFields.style.display = 'block';
    }
});
</script>
@endsection




