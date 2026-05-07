{{-- Position Details for HEC Members --}}
@php
    $isHecMember = $isHecMember ?? false;
@endphp

@if ($isHecMember)
    {{-- Department Selection for HEC Members --}}
    <div class="col-md-6">
        <label for="department" class="form-label fw-semibold">CCBRT Hiring Department <span
                class="text-danger">*</span></label>
        <select name="department" id="department" class="form-select" required onchange="loadDepartmentData()">
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
                <i class="fas fa-info-circle me-1"></i>Select a department to load available line managers
            </small>
        @endif
        @if ($departments->isEmpty())
            <small class="text-warning d-block mt-1">
                <i class="fas fa-exclamation-triangle me-1"></i>
                No departments are mapped to your HEC member account. Please contact administrator to map departments.
            </small>
        @endif
        @error('department')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    {{-- Line Manager Selection for HEC Members (Hidden for New Position) --}}
    <div class="col-md-6" id="lineManagerField">
        <label for="line_manager" class="form-label fw-semibold">Line Manager <span class="text-danger">*</span></label>
        <select name="line_manager" id="line_manager" class="form-select" required disabled>
            <option value="">-- Select Department First --</option>
        </select>
        <input type="hidden" name="job_title" id="job_title_from_line_manager" value="">
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

    {{-- New Position Name Field for HEC Members --}}
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

    {{-- Contract Renewal Fields for HEC Members --}}
    {{-- <div class="col-12" id="contractRenewalFields" style="display: none;">
        <div class="card" style="border-left: 4px solid #007A33; background-color: #f8f9fa;">
            <div class="card-header bg-light">
                <h6 class="fw-semibold mb-0" style="color: #333;">
                    <i class="fas fa-calendar-alt me-2" style="color: #007A33;"></i>Contract Renewal/Extension Details
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> As an HEC member, employee details will be completed by the line manager during the approval process.
                </div>
            </div>
        </div>
    </div> --}}
@endif
