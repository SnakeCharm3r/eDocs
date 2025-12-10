@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Edit & Resubmit Requisition Form (HR.01)</h3>
                        <div class="alert alert-info">
                            <strong>Note:</strong> This requisition was rejected. Please make the necessary changes and
                            resubmit.
                            @if ($requisition->workflow->histories->where('requisition_status', 2)->first())
                                <br><strong>Rejection Reason:</strong>
                                {{ $requisition->workflow->histories->where('requisition_status', 2)->first()->rejection_reason }}
                            @endif
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('requisitions.resubmit', $requisition->id) }}"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-md-12">
                                        <h4 class="mb-3">1. Position Details to be Filled by Respective Head of Department
                                        </h4>
                                        <table class="table table-bordered mb-4">
                                            <tbody>
                                                <tr style="background-color: #f5f7f5;" id="existingJobTitleRow">
                                                    <td class="fw-bold align-middle" style="width: 30%;">Job Title</td>
                                                    <td>
                                                        <select name="job_title" class="form-control" required>
                                                            <option value="">-- Select Job Title --</option>
                                                            @foreach ($jobTitles as $jobTitle)
                                                                <option value="{{ $jobTitle->id }}"
                                                                    {{ $requisition->job_title_id == $jobTitle->id ? 'selected' : '' }}>
                                                                    {{ $jobTitle->job_title }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('job_title')
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </td>
                                                </tr>

                                                <!-- Background Selection -->
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Background</td>
                                                    <td>
                                                        <div class="form-check form-check-inline">
                                                            <input type="radio" name="background" id="new_position"
                                                                value="new_position" class="form-check-input" required
                                                                {{ $requisition->background == 'new_position' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="new_position">New
                                                                Position</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input type="radio" name="background" id="replacement"
                                                                value="replacement" class="form-check-input"
                                                                {{ $requisition->background == 'replacement' ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="replacement">Replacement</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input type="radio" name="background" id="contract_renewal"
                                                                value="contract_renewal" class="form-check-input"
                                                                {{ $requisition->background == 'contract_renewal' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="contract_renewal">Contract
                                                                Renewal/Extension</label>
                                                        </div>
                                                        @error('background')
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </td>
                                                </tr>

                                                <!-- New Position Job Title Field -->
                                                <tr style="background-color: #f5f7f5; display:none;" id="newPositionFields">
                                                    <td class="fw-bold align-middle">Job Title (New Position)</td>
                                                    <td>
                                                        <input type="text" name="new_job_title" class="form-control"
                                                            value="{{ $requisition->new_job_title }}"
                                                            placeholder="Enter new job title">
                                                        @error('new_job_title')
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </td>
                                                </tr>
                                                <!-- Employee's Name for Replacement -->
                                                <tr style="background-color: #f5f7f5; display:none;"
                                                    id="replacementEmployeeName">
                                                    <td class="fw-bold align-middle">Employee's Name</td>
                                                    <td>
                                                        <select name="replacement_employee_id" id="replacement_employee_id" class="form-control">
                                                            <option value="">-- Select Job Title First --</option>
                                                        </select>
                                                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Select a job title above to see available employees</small>
                                                        @error('replacement_employee_id')
                                                            <small class="text-danger d-block">{{ $message }}</small>
                                                        @enderror
                                                    </td>
                                                </tr>

                                                <!-- Contract Renewal/Extension -->
                                                <tr style="background-color: #f5f7f5; display:none;"
                                                    id="contractRenewalFields">
                                                    <td class="fw-bold align-middle">For Contract Renewal/Extension</td>
                                                    <td>
                                                        <div class="mb-2">
                                                            <label class="form-label">Employee's Name <span
                                                                    class="text-danger">*</span></label>
                                                            <select name="contract_employee_id" id="contract_employee_id" class="form-control">
                                                                <option value="">-- Select Job Title First --</option>
                                                            </select>
                                                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Select a job title above to see available employees</small>
                                                            @error('contract_employee_id')
                                                                <small class="text-danger d-block">{{ $message }}</small>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label class="form-label">Current Contract End Date <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="date" name="contract_end_date"
                                                                class="form-control"
                                                                value="{{ $requisition->contract_end_date }}">
                                                            @error('contract_end_date')
                                                                <small class="text-danger">{{ $message }}</small>
                                                            @enderror
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">CCBRT Hiring Department</td>
                                                    <td>
                                                        <select name="department" class="form-control" required>
                                                            <option value="{{ $requisition->department->id ?? '' }}"
                                                                selected>
                                                                {{ $requisition->department->dept_name ?? 'No Department Assigned' }}
                                                            </option>
                                                        </select>
                                                        @error('department')
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </td>
                                                </tr>

                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">CCBRT Responsibility Centre</td>
                                                    <td>
                                                        <input type="text" name="responsibility_centre"
                                                            class="form-control" required maxlength="100"
                                                            value="{{ $requisition->responsibility_centre }}">
                                                        @error('responsibility_centre')
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </td>
                                                </tr>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Reporting Line</td>
                                                    <td>
                                                        <label class="form-label mb-1">Reports to (Position) <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="reporting_line" class="form-control"
                                                            required maxlength="100"
                                                            value="{{ $requisition->reporting_line }}">
                                                        @error('reporting_line')
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </td>
                                                </tr>
                                                <!-- Contract Type Section -->
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Contract Type</td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input type="radio" name="contract_type"
                                                                id="minimal_1_year" value="minimal_1_year"
                                                                class="form-check-input" required
                                                                {{ $requisition->contract_type == 'minimal_1_year' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="minimal_1_year">Minimal 1
                                                                Year (Employment)</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="radio" name="contract_type"
                                                                id="termed_less_1_year" value="termed_less_1_year"
                                                                class="form-check-input"
                                                                {{ $requisition->contract_type == 'termed_less_1_year' ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="termed_less_1_year">Termed < 1 Year
                                                                    (Consultant/Specific Task)</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="radio" name="contract_type"
                                                                id="health_volunteer" value="health_volunteer"
                                                                class="form-check-input"
                                                                {{ $requisition->contract_type == 'health_volunteer' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="health_volunteer">Health
                                                                Volunteer (50% Basic, Minimal 1 Year)</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="radio" name="contract_type" id="work_exposure"
                                                                value="work_exposure" class="form-check-input"
                                                                {{ $requisition->contract_type == 'work_exposure' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="work_exposure">Work
                                                                Exposure Placement (No Pay, Max 2x3 Months)</label>
                                                        </div>
                                                        @error('contract_type')
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </td>
                                                </tr>
                                                <!-- Job Description Attachment -->
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Attach Job Description (PDF only) <span class="text-danger">*</span></td>
                                                    <td>
                                                        <input type="file" name="job_description_file"
                                                            class="form-control" accept="application/pdf" {{ !$requisition->job_description_file ? 'required' : '' }}>
                                                        @if ($requisition->job_description_file)
                                                            <input type="hidden" name="existing_job_description_file" value="1">
                                                            <small class="text-muted d-block mt-1">
                                                                Current file:
                                                                <a href="{{ asset('storage/' . $requisition->job_description_file) }}"
                                                                    target="_blank">
                                                                    View current file
                                                                </a>
                                                            </small>
                                                        @endif
                                                        <small class="text-muted">Max size: 2MB. PDF format only.</small>
                                                        @error('job_description_file')
                                                            <small class="text-danger d-block">{{ $message }}</small>
                                                        @enderror
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <!-- Checkbox Section (Conditions) -->
                                        <div class="col-md-12 mt-4">
                                            <h4 class="mb-3">2. Tick at Least One of the Below Conditions</h4>
                                            <div class="form-group">
                                                @php
                                                    // Since conditions is a JSON column, Laravel automatically casts it to array
                                                    $selectedConditions = $requisition->conditions ?? [];
                                                @endphp
                                                <div class="form-check">
                                                    <input type="checkbox" name="conditions[]"
                                                        value="medical_operational" class="form-check-input"
                                                        {{ in_array('medical_operational', $selectedConditions) ? 'checked' : '' }}>
                                                    <label class="form-check-label">There are overwhelming medical
                                                        or operational imperatives to fill the post</label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" name="conditions[]"
                                                        value="safety_reputational" class="form-check-input"
                                                        {{ in_array('safety_reputational', $selectedConditions) ? 'checked' : '' }}>
                                                    <label class="form-check-label">There are safety or
                                                        reputational risks to the organization if the post is not
                                                        filled</label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" name="conditions[]" value="legal"
                                                        class="form-check-input"
                                                        {{ in_array('legal', $selectedConditions) ? 'checked' : '' }}>
                                                    <label class="form-check-label">There are legal requirements to
                                                        fill the post</label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" name="conditions[]" value="financial_loss"
                                                        class="form-check-input"
                                                        {{ in_array('financial_loss', $selectedConditions) ? 'checked' : '' }}>
                                                    <label class="form-check-label">There is evidence that not
                                                        filling the post will result in demonstrable financial loss to the
                                                        organisation</label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" name="conditions[]" value="increase_income"
                                                        class="form-check-input"
                                                        {{ in_array('increase_income', $selectedConditions) ? 'checked' : '' }}>
                                                    <label class="form-check-label">The post is necessary to
                                                        increase income significantly</label>
                                                </div>
                                                @error('conditions')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Reasoning Section -->
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="form-label fw-bold">Elaborate Brief Your Reasoning, the
                                                    Business
                                                    Impact and Why Actions Cannot be Absorbed by Existing Staff or Through
                                                    Work
                                                    Exposure Placement or Health Volunteer <span
                                                        class="text-danger">*</span></label>
                                                <textarea name="reasoning" class="form-control" required maxlength="1000" rows="5">{{ $requisition->reasoning }}</textarea>
                                                @error('reasoning')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div>

                                        <p class="text-muted mb-4">Only after step 4, document to be returned to HOD and
                                            forwarded to HR; if no objection HR will start process.</p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-redo"></i> Resubmit Requisition
                                    </button>
                                    <a href="{{ route('requisitions.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to List
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include the same JavaScript as your create view -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const backgroundInputs = document.querySelectorAll('input[name="background"]');
            const newPositionFields = document.getElementById('newPositionFields');
            const existingJobTitleRow = document.getElementById('existingJobTitleRow');
            const replacementEmployeeName = document.getElementById('replacementEmployeeName');
            const contractRenewalFields = document.getElementById('contractRenewalFields');
            const jobTitleSelect = document.querySelector('select[name="job_title"]');
            const replacementEmployeeSelect = document.getElementById('replacement_employee_id');
            const contractEmployeeSelect = document.getElementById('contract_employee_id');

            // Function to load users by job title
            function loadUsersByJobTitle(jobTitleId, targetSelect, currentValue = null) {
                if (!jobTitleId) {
                    targetSelect.innerHTML = '<option value="">-- Select Job Title First --</option>';
                    return;
                }

                // Show loading state
                targetSelect.innerHTML = '<option value="">Loading employees...</option>';
                targetSelect.disabled = true;

                // Fetch users via AJAX
                fetch(`{{ route('requisitions.users-by-job-title') }}?job_title_id=${jobTitleId}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    targetSelect.innerHTML = '<option value="">-- Select Employee --</option>';
                    
                    if (data.users && data.users.length > 0) {
                        data.users.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            option.textContent = user.name + (user.username ? ` (${user.username})` : '');
                            
                            // Preserve current value if it matches
                            if (currentValue && currentValue == user.id) {
                                option.selected = true;
                            }
                            
                            targetSelect.appendChild(option);
                        });
                    } else {
                        targetSelect.innerHTML = '<option value="">No employees found for this job title</option>';
                    }
                    
                    targetSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    targetSelect.innerHTML = '<option value="">Error loading employees</option>';
                    targetSelect.disabled = false;
                });
            }

            // Function to handle job title change
            function handleJobTitleChange() {
                const jobTitleId = jobTitleSelect.value;
                const selectedBackground = document.querySelector('input[name="background"]:checked');
                
                if (!selectedBackground) return;

                const backgroundValue = selectedBackground.value;

                // Only load users if background is replacement or contract_renewal
                if (backgroundValue === 'replacement' && replacementEmployeeName.style.display !== 'none') {
                    const currentValue = replacementEmployeeSelect.getAttribute('data-current-value');
                    loadUsersByJobTitle(jobTitleId, replacementEmployeeSelect, currentValue);
                } else if (backgroundValue === 'contract_renewal' && contractRenewalFields.style.display !== 'none') {
                    const currentValue = contractEmployeeSelect.getAttribute('data-current-value');
                    loadUsersByJobTitle(jobTitleId, contractEmployeeSelect, currentValue);
                }
            }

            function toggleFields() {
                const selected = document.querySelector('input[name="background"]:checked');
                if (!selected) {
                    existingJobTitleRow.style.display = 'none';
                    newPositionFields.style.display = 'none';
                    replacementEmployeeName.style.display = 'none';
                    contractRenewalFields.style.display = 'none';
                    return;
                }

                const backgroundValue = selected.value;

                if (backgroundValue === 'new_position') {
                    newPositionFields.style.display = 'table-row';
                    existingJobTitleRow.style.display = 'none';
                    replacementEmployeeName.style.display = 'none';
                    contractRenewalFields.style.display = 'none';
                } else if (backgroundValue === 'replacement') {
                    replacementEmployeeName.style.display = 'table-row';
                    existingJobTitleRow.style.display = 'table-row';
                    newPositionFields.style.display = 'none';
                    contractRenewalFields.style.display = 'none';
                    
                    // Load users when replacement is selected and job title is already chosen
                    if (jobTitleSelect.value) {
                        handleJobTitleChange();
                    }
                } else if (backgroundValue === 'contract_renewal') {
                    contractRenewalFields.style.display = 'table-row';
                    existingJobTitleRow.style.display = 'table-row';
                    newPositionFields.style.display = 'none';
                    replacementEmployeeName.style.display = 'none';
                    
                    // Load users when contract_renewal is selected and job title is already chosen
                    if (jobTitleSelect.value) {
                        handleJobTitleChange();
                    }
                } else {
                    existingJobTitleRow.style.display = 'table-row';
                    newPositionFields.style.display = 'none';
                    replacementEmployeeName.style.display = 'none';
                    contractRenewalFields.style.display = 'none';
                }
            }

            backgroundInputs.forEach(input => {
                input.addEventListener('change', toggleFields);
            });

            // Listen for job title changes
            if (jobTitleSelect) {
                jobTitleSelect.addEventListener('change', handleJobTitleChange);
            }

            // Preserve current values if they exist
            @if(isset($requisition) && $requisition->replacement_employee_id)
                replacementEmployeeSelect.setAttribute('data-current-value', '{{ $requisition->replacement_employee_id }}');
            @endif
            @if(isset($requisition) && $requisition->contract_employee_id)
                contractEmployeeSelect.setAttribute('data-current-value', '{{ $requisition->contract_employee_id }}');
            @endif

            // Trigger initial state on page load
            toggleFields();
            
            // If job title is already selected and background requires employee selection, load users
            if (jobTitleSelect && jobTitleSelect.value) {
                const selectedBackground = document.querySelector('input[name="background"]:checked');
                if (selectedBackground && (selectedBackground.value === 'replacement' || selectedBackground.value === 'contract_renewal')) {
                    handleJobTitleChange();
                }
            }
        });
    </script>

    <style>
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .card {
            border: none;
            border-radius: 8px;
        }

        .card-body {
            background-color: #f9f9f9;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: middle;
        }

        .form-label {
            font-weight: 600;
            color: #444;
        }

        .form-control,
        .form-select {
            border-radius: 5px;
            border: 1px solid #ced4da;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: border-color 0.2s ease-in-out;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #9ca19c;
            box-shadow: 0 0 5px rgba(156, 161, 156, 0.3);
        }

        .form-check {
            margin-bottom: 0.5rem;
        }

        .form-check-inline {
            margin-right: 1rem;
        }
    </style>
@endsection
