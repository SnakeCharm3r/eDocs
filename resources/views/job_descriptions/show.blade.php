@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="job-description-container">
                        <!-- Display General Error/Success Messages -->
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <!-- Form Start -->
                        <form action="{{ route('job-descriptions.update', $jobDescription->id) }}" method="POST"
                            id="jobDescriptionForm">
                            @csrf
                            @method('PUT')

                            <!-- Header -->
                            <header class="jd-header"
                                style="width: 100%; position: relative; display: flex; justify-content: center; align-items: center;">
                                <div style="text-align: center;">
                                    <h2 class="jd-header-subtitle">Job Description</h2>
                                    {{-- <div class="jd-header-position">{{ $jobDescription->operational_job_title ?? 'N/A' }}
                                    </div> --}}
                                </div>
                                <div class="jd-logo" style="position: absolute; right: 0; top: 0;">
                                    <img src="{{ asset('assets/img/ccbrt.JPG') }}" alt="Logo"
                                        style="width: 70px; height: auto;">
                                </div>
                            </header>

                            <!-- Job Details (Read-Only) -->
                            <section class="jd-section">
                                <h3 class="jd-section-header">Job Details</h3>
                                <div class="jd-grid">
                                    <div class="jd-label">Operational Job Title</div>
                                    <div class="jd-value">{{ $jobDescription->job_title ?? 'N/A' }}</div>
                                    <div class="jd-label">Technical Job Level</div>
                                    <div class="jd-value">{{ $jobDescription->technical_job_level ?? 'N/A' }}</div>
                                    <div class="jd-label">Reports To</div>
                                    <div class="jd-value">{{ $jobDescription->reports_to ?? 'N/A' }}</div>
                                    <div class="jd-label">Jobs Responsible For</div>
                                    <div class="jd-value">{{ $jobDescription->jobs_responsible_for ?? 'N/A' }}</div>
                                    <div class="jd-label">Department</div>
                                    <div class="jd-value">{{ $jobDescription->user->department->dept_name ?? 'N/A' }}</div>
                                    <div class="jd-label">Region/Location</div>
                                    <div class="jd-value">{{ $jobDescription->region_location ?? 'N/A' }}</div>
                                    <div class="jd-label">Working Hours</div>
                                    <div class="jd-value">{{ $jobDescription->working_hours ?? 'N/A' }}</div>
                                    <div class="jd-label">Job Review Date</div>
                                    <div class="jd-value">{{ $jobDescription->job_review_date ?? 'N/A' }}</div>
                                    <div class="jd-label">Job Grade</div>
                                    <div class="jd-value">{{ $jobDescription->job_grade ?? 'N/A' }}</div>
                                    <div class="jd-label">Grade Job Holder</div>
                                    <div class="jd-value">{{ $jobDescription->grade_job_holder ?? 'N/A' }}</div>
                                    <div class="jd-label">Name of Job Holder</div>
                                    <div class="jd-value">
                                        {{ $jobDescription->user->fname ?? 'N/A' }}{{ $jobDescription->user->lname ?? '' }}
                                    </div>
                                </div>
                            </section>

                            <!-- Employee Type -->
                            {{-- <div class="jd-label">Employee Type</div>
                            <div class="jd-value">
                                <select name="employee_type" class="form-control" id="employee_type">
                                    <option value="existing"
                                        {{ old('employee_type', $jobDescription->employee_type) == 'existing' ? 'selected' : '' }}>
                                        Existing
                                    </option>
                                    <option value="new"
                                        {{ old('employee_type', $jobDescription->employee_type) == 'new' ? 'selected' : '' }}>
                                        New
                                    </option>
                                </select>
                                @error('employee_type')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div> --}}


                            <!-- Grade Difference Reason Field -->
                            <div class="jd-label">Grade Difference Reason</div>
                            <div class="jd-value">
                                <input type="text" name="grade_difference_reason" class="form-control"
                                    value="{{ old('grade_difference_reason', $jobDescription->grade_difference_reason ?? 'Reprehenderit vitae') }}">
                                @error('grade_difference_reason')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Outputs -->
                            <section class="jd-section">
                                <h3 class="jd-section-header">A: Outputs</h3>
                                <div class="jd-subsection">
                                    <h4 class="jd-subsection-title">Purpose</h4>
                                    <div class="jd-content" contenteditable="true" id="purpose"
                                        style="background-color: #f9f9f9; padding: 1rem; border-radius: 4px; border: 1px solid #ddd; outline: 2px dashed #b3d334;">
                                        {!! old('purpose', $jobDescription->purpose ?? 'N/A') !!}
                                    </div>
                                    @error('purpose')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="hidden" name="purpose" id="purpose_hidden">
                                </div>
                                <div class="jd-subsection">
                                    <h4 class="jd-subsection-title">Accountabilities / Key Outputs</h4>
                                    <div class="jd-content" contenteditable="true" id="accountabilities"
                                        style="background-color: #f9f9f9; padding: 1rem; border-radius: 4px; border: 1px solid #ddd; outline: 2px dashed #b3d334;">
                                        {!! old('accountabilities', $jobDescription->accountabilities ?? 'N/A') !!}
                                    </div>
                                    @error('accountabilities')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="hidden" name="accountabilities" id="accountabilities_hidden">
                                </div>
                            </section>

                            <!-- Inputs -->
                            <section class="jd-section">
                                <h3 class="jd-section-header">B: Inputs</h3>
                                <div class="jd-subsection">
                                    <h4 class="jd-subsection-title">Key Qualifications, Experience & Skills</h4>
                                    <div class="jd-content" contenteditable="true" id="qualifications_experience"
                                        style="background-color: #f9f9f9; padding: 1rem; border-radius: 4px; border: 1px solid #ddd; outline: 2px dashed #b3d334;">
                                        {!! old('qualifications_experience', $jobDescription->qualifications_experience ?? 'N/A') !!}
                                    </div>
                                    @error('qualifications_experience')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="hidden" name="qualifications_experience"
                                        id="qualifications_experience_hidden">
                                </div>
                                <div class="jd-subsection">
                                    <h4 class="jd-subsection-title">Competencies</h4>
                                    <div class="jd-content" contenteditable="true" id="competencies"
                                        style="background-color: #f9f9f9; padding: 1rem; border-radius: 4px; border: 1px solid #ddd; outline: 2px dashed #b3d334;">
                                        {!! old('competencies', $jobDescription->competencies ?? 'N/A') !!}
                                    </div>
                                    @error('competencies')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="hidden" name="competencies" id="competencies_hidden">
                                </div>
                            </section>

                            <!-- Other Dimensions -->
                            <section class="jd-section">
                                <h3 class="jd-section-header">Other Dimensions if applicable</h3>
                                <div class="jd-grid">
                                    <div class="jd-dimension-label">
                                        Financial<br>
                                        <span class="jd-dimension-sublabel">(e.g., Budget, turnover, expenses, assets,
                                            profit)</span>
                                    </div>
                                    <div class="jd-dimension-value" contenteditable="true" id="financial_details"
                                        style="outline: 2px dashed #b3d334; padding: 10px;">
                                        {!! old('financial_details', $jobDescription->financial_details ?? 'N/A') !!}
                                    </div>
                                    @error('financial_details')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="hidden" name="financial_details" id="financial_details_hidden">

                                    <div class="jd-dimension-label">
                                        Employees Managed<br>
                                        <span class="jd-dimension-sublabel">(direct/indirect)</span>
                                    </div>
                                    <div class="jd-dimension-value" contenteditable="true" id="employees_managed"
                                        style="outline: 2px dashed #b3d334; padding: 10px;">
                                        {!! old('employees_managed', $jobDescription->employees_managed ?? 'N/A') !!}
                                    </div>
                                    @error('employees_managed')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="hidden" name="employees_managed" id="employees_managed_hidden">

                                    <div class="jd-dimension-label">Stakeholders Managed</div>
                                    <div class="jd-dimension-value" contenteditable="true" id="stakeholders_managed"
                                        style="outline: 2px dashed #b3d334; padding: 10px;">
                                        {!! old('stakeholders_managed', $jobDescription->stakeholders_managed ?? 'N/A') !!}
                                    </div>
                                    @error('stakeholders_managed')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="hidden" name="stakeholders_managed" id="stakeholders_managed_hidden">
                                </div>
                            </section>

                            <!-- Organizational Structure -->
                            {{-- <section class="jd-section">
                                <h3 class="jd-section-header">Organizational / Departmental Structure</h3>
                                <div class="jd-checkbox-container">
                                    <div class="jd-checkbox-row">
                                        <div class="jd-checkbox">
                                            <input type="radio" name="org_structure_1" value="yes"
                                                id="org_structure_1_yes"
                                                {{ old('org_structure_1', $jobDescription->org_structure_1) == 'yes' ? 'checked' : '' }}
                                                required>
                                            <label for="org_structure_1_yes" class="jd-checkbox-label">Yes</label>
                                        </div>
                                        <div class="jd-checkbox">
                                            <input type="radio" name="org_structure_1" value="no"
                                                id="org_structure_1_no"
                                                {{ old('org_structure_1', $jobDescription->org_structure_1) == 'no' ? 'checked' : '' }}
                                                required>
                                            <label for="org_structure_1_no" class="jd-checkbox-label">No</label>
                                        </div>
                                    </div>
                                    @error('org_structure_1')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror

                                    <div class="jd-checkbox-row">
                                        <div class="jd-checkbox">
                                            <input type="radio" name="org_structure_2" value="yes"
                                                id="org_structure_2_yes"
                                                {{ old('org_structure_2', $jobDescription->org_structure_2) == 'yes' ? 'checked' : '' }}
                                                required>
                                            <label for="org_structure_2_yes" class="jd-checkbox-label">Yes</label>
                                        </div>
                                        <div class="jd-checkbox">
                                            <input type="radio" name="org_structure_2" value="no"
                                                id="org_structure_2_no"
                                                {{ old('org_structure_2', $jobDescription->org_structure_2) == 'no' ? 'checked' : '' }}
                                                required>
                                            <label for="org_structure_2_no" class="jd-checkbox-label">No</label>
                                        </div>
                                    </div>
                                    @error('org_structure_2')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </section> --}}

                            <!-- Submit Button Section -->
                            @php
                                $latestHistory = $jobDescription->workflow?->histories
                                    ?->sortByDesc('created_at')
                                    ->first();
                                $status = $latestHistory?->jd_status ?? null;
                            @endphp

                            <section class="jd-section" style="margin-top: 20px;">
                                <div class="jd-button-container"
                                    style="display: flex; justify-content: flex-end; gap: 12px;">

                                    <!-- Back Button -->
                                    <a href="{{ route('job.description') }}" class="jd-button jd-button-cancel"
                                        style="background-color: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 6px; font-size: 14pt; text-decoration: none; text-align: center; display: flex; align-items: center; gap: 8px; transition: background-color 0.3s;">
                                        <i class="fas fa-arrow-left" style="font-size: 16px;"></i>
                                        <span>Back</span>
                                    </a>

                                    <!-- Submit Button (Only Visible When Status is 0) -->
                                    @role('line-manager|')
                                        @if ($status === 0)
                                            <button type="submit" class="jd-button jd-button-submit"
                                                style="background-color: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 6px; font-size: 14pt; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background-color 0.3s;">
                                                <i class="fas fa-paper-plane" style="font-size: 16px;"></i>
                                                <span>Submit Job Description</span>
                                            </button>
                                        @endif
                                    @endrole
                                    <!-- Download PDF Button (Only for HR Role) -->
                                    @role('hr|line-manager')
                                        @if ($status === 1)
                                            <a href="{{ route('job-descriptions.download', $jobDescription->id) }}"
                                                class="btn btn-sm btn-success"
                                                style=" color: white; padding: 8px 16px; border: none; border-radius: 6px; font-size: 14pt; text-decoration: none; text-align: center; display: flex; align-items: center; gap: 8px; transition: background-color 0.3s;">
                                                <i class="fas fa-download" style="font-size: 16px;"></i>
                                                <span>PDF</span>
                                            </a>
                                        @endif
                                    @endrole

                                </div>
                            </section>

                            <style>
                                /* Hover Effects */
                                .jd-button:hover {
                                    opacity: 0.9;
                                }

                                /* Button Active States */
                                .jd-button-cancel:hover {
                                    background-color: #5a6268;
                                }

                                .jd-button-submit:hover {
                                    background-color: #218838;
                                }

                                .jd-button-download:hover {
                                    background-color: #0056b3;
                                }

                                /* Responsive Design */
                                @media (max-width: 768px) {
                                    .jd-button-container {
                                        flex-direction: column;
                                        align-items: flex-end;
                                    }

                                    .jd-button {
                                        width: auto;
                                        /* Prevent full width */
                                        margin-bottom: 10px;
                                    }
                                }
                            </style>


                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Container */
        .job-description-container {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
            border: 1px solid #ddd;
            max-width: 1150px;
            margin: 20px auto;
            padding: 20px;
        }

        /* Header */
        .jd-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }

        .jd-header-subtitle {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .jd-header-position {
            font-size: 14pt;
            border: 1px solid #ddd;
            padding: 8px 25px;
            display: inline-block;
        }

        /* Section */
        .jd-section {
            margin-bottom: 30px;
        }

        .jd-section-header {
            font-size: 14pt;
            font-weight: bold;
            background-color: #008000;
            color: white;
            padding: 10px;
            margin-bottom: 15px;
        }

        /* Grid Layout for Details and Dimensions */
        .jd-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 10px;
            align-items: start;
        }

        .jd-label,
        .jd-dimension-label {
            font-weight: bold;
            background-color: #b3d334;
            padding: 10px;
            border: 1px solid #ddd;
        }

        .jd-value,
        .jd-dimension-value {
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #fff;
        }

        .jd-dimension-sublabel {
            font-weight: normal;
            font-size: 10pt;
            color: #555;
        }

        /* Subsection */
        .jd-subsection {
            margin-bottom: 20px;
        }

        .jd-subsection-title {
            font-size: 12pt;
            font-weight: bold;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }

        .jd-content {
            padding: 10px 0;
        }

        /* Checkbox Container */
        .jd-checkbox-container {
            padding: 15px;
        }

        .jd-checkbox-row {
            display: flex;
            margin-bottom: 15px;
            justify-content: center;
        }

        .jd-checkbox {
            display: flex;
            align-items: center;
            margin-right: 40px;
        }

        /* Print Styles */
        @media print {
            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }

            .breadcrumb,
            .header,
            .sidebar,
            .footer,
            .text-right {
                display: none !important;
            }

            .page-wrapper,
            .content,
            .container-fluid,
            .row,
            .col-md-12 {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: none !important;
            }

            .job-description-container {
                border: none;
                margin: 0;
                padding: 10px;
                max-width: none;
            }

            .jd-section-header {
                background-color: #008000 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .jd-label,
            .jd-dimension-label {
                background-color: #b3d334 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: A4;
                margin: 1.5cm;
            }
        }
    </style>
    <script>
        // Sync contenteditable fields with hidden inputs
        function syncContentEditable() {
            const fields = [{
                    id: 'purpose',
                    hidden: 'purpose_hidden'
                },
                {
                    id: 'accountabilities',
                    hidden: 'accountabilities_hidden'
                },
                {
                    id: 'qualifications_experience',
                    hidden: 'qualifications_experience_hidden'
                },
                {
                    id: 'competencies',
                    hidden: 'competencies_hidden'
                },
                {
                    id: 'financial_details',
                    hidden: 'financial_details_hidden'
                },
                {
                    id: 'employees_managed',
                    hidden: 'employees_managed_hidden'
                },
                {
                    id: 'stakeholders_managed',
                    hidden: 'stakeholders_managed_hidden'
                }
            ];

            fields.forEach(field => {
                const contentElement = document.getElementById(field.id);
                const hiddenInput = document.getElementById(field.hidden);
                if (contentElement && hiddenInput) {
                    const content = contentElement.innerHTML.trim();
                    hiddenInput.value = content === '' || content === 'N/A' ? '' : content;
                    console.log(`Syncing ${field.id}:`, hiddenInput.value); // Debugging
                } else {
                    console.error(`Element or hidden input not found for ${field.id}`);
                }
            });
        }

        // Attach input event listeners to contenteditable fields
        document.querySelectorAll('[contenteditable="true"]').forEach(element => {
            element.addEventListener('input', syncContentEditable);
            element.addEventListener('blur', syncContentEditable); // Sync on blur for reliability
        });

        // Sync on form submission
        const form = document.getElementById('jobDescriptionForm');
        form.addEventListener('submit', (e) => {
            syncContentEditable();
            const formData = new FormData(form);
            console.log('Form data:', Object.fromEntries(formData));
        });

        // Handle employee type change to enable/disable user selection
        const employeeTypeSelect = document.getElementById('employee_type');
        const userIdSelect = document.getElementById('user_id');

        if (employeeTypeSelect && userIdSelect) {
            employeeTypeSelect.addEventListener('change', () => {
                userIdSelect.disabled = employeeTypeSelect.value === 'new';
                if (employeeTypeSelect.value === 'new') {
                    userIdSelect.value = '';
                }
            });
        } else {
            console.error('Employee type or user ID select not found');
        }

        // Initialize on page load
        window.addEventListener('load', () => {
            if (employeeTypeSelect && userIdSelect) {
                userIdSelect.disabled = employeeTypeSelect.value === 'new';
            }
            syncContentEditable();
        });
    </script>
@endsection
