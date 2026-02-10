{{-- Position Details for Line Managers --}}
@php
    $isLineManager = $isLineManager ?? false;
@endphp

@if ($isLineManager)
    {{-- Replacement Employee Fields for Line Managers --}}
    <div class="col-md-6" id="replacementEmployeeName" style="display: none;">
        <label for="replacement_employee_id" class="form-label fw-semibold">Employee's Name <span
                class="text-danger">*</span></label>
        <select name="replacement_employee_id" id="replacement_employee_id" class="form-select">
            <option value="">-- Select Employee --</option>
            @foreach ($users as $staff)
                <option value="{{ $staff->id }}" {{ old('replacement_employee_id') == $staff->id ? 'selected' : '' }}
                    data-contract-end-date="{{ $staff->contract_end_date ?? '' }}">
                    {{ $staff->fname }} {{ $staff->mname ?? '' }} {{ $staff->lname }}
                    @if ($staff->username)
                        ({{ $staff->username }})
                    @endif
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">
            <i class="fas fa-info-circle me-1"></i>Select the employee to be replaced
        </small>
        @error('replacement_employee_id')
            <small class="text-danger d-block">{{ $message }}</small>
        @enderror
    </div>

    {{-- Contract Renewal Fields for Line Managers --}}
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
                            <option value="">-- Select Employee --</option>
                            @foreach ($users as $staff)
                                <option value="{{ $staff->id }}"
                                    {{ old('contract_employee_id') == $staff->id ? 'selected' : '' }}
                                    data-contract-end-date="{{ $staff->contract_end_date ?? '' }}">
                                    {{ $staff->fname }} {{ $staff->mname ?? '' }} {{ $staff->lname }}
                                    @if ($staff->username)
                                        ({{ $staff->username }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle me-1"></i>Select the employee whose contract needs renewal
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

    {{-- Department (Read-only for Line Managers, except for New Position) --}}
    <div class="col-md-6" id="departmentField">
        <label for="department" class="form-label fw-semibold">CCBRT Hiring Department <span
                class="text-danger">*</span></label>
        <input type="hidden" name="department" id="department_hidden" value="{{ $departments->first()->id ?? '' }}">
        <input type="text" id="department_display" class="form-control"
            value="{{ $departments->first()->dept_name ?? 'No department assigned' }}" readonly
            style="background-color: #f8f9fa;">
        <small class="text-muted d-block mt-1" id="departmentHelpText">
            <i class="fas fa-info-circle me-1"></i>Your department is automatically assigned
        </small>
        @error('department')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    {{-- New Position Name Field for Line Managers --}}
    <div class="col-md-6" id="newPositionNameField" style="display: none;">
        <label for="new_job_title" class="form-label fw-semibold">Proposed New Position Name <span
                class="text-danger">*</span></label>
        <input type="text" name="new_job_title" id="new_job_title" class="form-control"
            value="{{ old('new_job_title') }}" placeholder="Enter proposed new position name" required>
        <small class="text-muted d-block mt-1">
            <i class="fas fa-info-circle me-1"></i>Enter the name of the new position you are proposing
        </small>
        @error('new_job_title')
            <small class="text-danger d-block">{{ $message }}</small>
        @enderror
    </div>
@endif
