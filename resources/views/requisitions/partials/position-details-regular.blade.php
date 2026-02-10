{{-- Position Details for Regular Users (Non-HEC, Non-Line Manager) --}}
@php
    $isHecMember = $isHecMember ?? false;
    $isLineManager = $isLineManager ?? false;
@endphp

@if (!$isHecMember && !$isLineManager)
    {{-- Job Title (Existing) --}}
    <div class="col-md-6" id="existingJobTitleRow">
        <label for="job_title" class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
        <select name="job_title" id="job_title" class="form-select" required>
            <option value="">-- Select Job Title --</option>
            @foreach ($jobTitles as $jobTitle)
                <option value="{{ $jobTitle->id }}" {{ old('job_title') == $jobTitle->id ? 'selected' : '' }}>
                    {{ $jobTitle->job_title }}
                </option>
            @endforeach
        </select>
        @error('job_title')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    {{-- New Position Job Title --}}
    <div class="col-md-6" id="newPositionFields" style="display: none;">
        <label for="new_job_title" class="form-label fw-semibold">Job Title (New Position) <span
                class="text-danger">*</span></label>
        <input type="text" name="new_job_title" id="new_job_title" class="form-control"
            value="{{ old('new_job_title') }}" placeholder="Enter new job title" required>
        @error('new_job_title')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    {{-- Replacement Employee --}}
    <div class="col-md-6" id="replacementEmployeeName" style="display: none;">
        <label for="replacement_employee_id" class="form-label fw-semibold">Employee's Name <span
                class="text-danger">*</span></label>
        <select name="replacement_employee_id" id="replacement_employee_id" class="form-select" required>
            <option value="">-- Select Job Title First --</option>
        </select>
        <small class="text-muted d-block mt-1">
            <i class="fas fa-info-circle me-1"></i>Select a job title above to see available employees for that job
            title
        </small>
        <small class="text-info d-block mt-1">
            <i class="fas fa-check-circle me-1"></i>Staff will be loaded automatically from the selected job title
        </small>
        @error('replacement_employee_id')
            <small class="text-danger d-block">{{ $message }}</small>
        @enderror
    </div>

    {{-- Contract Renewal Fields for Regular Users --}}
    <div class="col-12" id="contractRenewalFields" style="display: none;">
        <div class="card" style="border-left: 4px solid #007A33; background-color: #f8f9fa;">
            <div class="card-header bg-light">
                <h6 class="fw-semibold mb-0" style="color: #333;">
                    <i class="fas fa-calendar-alt me-2" style="color: #007A33;"></i>Contract Renewal/Extension Details
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="contract_employee_id" class="form-label fw-semibold">Employee's Name <span
                                class="text-danger">*</span></label>
                        <select name="contract_employee_id" id="contract_employee_id" class="form-select" required>
                            <option value="">-- Select Job Title First --</option>
                        </select>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle me-1"></i>Select a job title above to see available employees
                            for that job title
                        </small>
                        <small class="text-info d-block mt-1">
                            <i class="fas fa-check-circle me-1"></i>Staff will be loaded automatically from the selected
                            job title
                        </small>
                        @error('contract_employee_id')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="contract_end_date" class="form-label fw-semibold">Current Contract End Date <span
                                class="text-danger">*</span></label>
                        <input type="date" name="contract_end_date" id="contract_end_date" class="form-control"
                            value="{{ old('contract_end_date') }}" required>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle me-1"></i>This will be automatically filled when you select an
                            employee
                        </small>
                        @error('contract_end_date')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Department for Regular Users --}}
    <div class="col-md-6">
        <label for="department" class="form-label fw-semibold">CCBRT Hiring Department <span
                class="text-danger">*</span></label>
        <select name="department" id="department" class="form-select" required>
            @if ($departments->isNotEmpty())
                <option value="{{ $departments->first()->id }}" selected>{{ $departments->first()->dept_name }}
                </option>
            @else
                <option value="" disabled>No department assigned</option>
            @endif
        </select>
        @error('department')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
@endif
