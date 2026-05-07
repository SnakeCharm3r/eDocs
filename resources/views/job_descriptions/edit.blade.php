@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Edit Job Description</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form action="{{ route('job.description.update', $jobDescription->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table table-bordered mb-4">
                                            <thead>
                                                <tr style="background-color: #9ca19c; color: white; font-size: 1.1rem;">
                                                    <th colspan="2">Job Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle" style="width: 30%;">Employee Type</td>
                                                    <td>
                                                        <select id="employee_type" class="form-select" name="employee_type"
                                                            required>
                                                            <option value="" disabled
                                                                {{ old('employee_type', $jobDescription->employee_type) == '' ? 'selected' : '' }}>
                                                                Select Employee Type</option>
                                                            <option value="existing"
                                                                {{ old('employee_type', $jobDescription->employee_type) == 'existing' ? 'selected' : '' }}>
                                                                Existing Employee</option>
                                                            <option value="new"
                                                                {{ old('employee_type', $jobDescription->employee_type) == 'new' ? 'selected' : '' }}>
                                                                New Job Holder</option>
                                                        </select>
                                                    </td>
                                                </tr>

                                                <tr style="background-color: #f5f7f5;" id="existing_user_section">
                                                    <td class="fw-bold align-middle">Name Job Holder</td>
                                                    <td>
                                                        <select name="user_id" id="user_id" class="form-select">
                                                            <option value="" disabled
                                                                {{ old('user_id', $jobDescription->user_id ?? '') == '' ? 'selected' : '' }}>
                                                                Select User</option>
                                                            @foreach ($users as $user)
                                                                <option value="{{ $user->id }}"
                                                                    data-fname="{{ $user->fname }}"
                                                                    data-mname="{{ $user->mname }}"
                                                                    data-lname="{{ $user->lname }}"
                                                                    data-deptname="{{ $user->department->deptname ?? '' }}"
                                                                    data-region="{{ $user->department->region ?? '' }}"
                                                                    {{ (string) old('user_id', $jobDescription->user_id ?? '') === (string) $user->id ? 'selected' : '' }}>
                                                                    {{ $user->fname }} {{ $user->mname }}
                                                                    {{ $user->lname }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                </tr>

                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Operational Job Title</td>
                                                    <td><input type="text" name="job_title" class="form-control"
                                                            value="{{ old('job_title', $jobDescription->job_title) }}"
                                                            required></td>
                                                </tr>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Technical Job Level</td>
                                                    <td><input type="text" name="technical_job_level"
                                                            class="form-control"
                                                            value="{{ old('technical_job_level', $jobDescription->technical_job_level) }}">
                                                    </td>
                                                </tr>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Reports To</td>
                                                    <td><input type="text" name="reports_to" class="form-control"
                                                            value="{{ old('reports_to', $jobDescription->reports_to) }}">
                                                    </td>
                                                </tr>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Jobs Responsible For</td>
                                                    <td>
                                                        <textarea name="jobs_responsible_for" class="form-control" rows="3">{{ old('jobs_responsible_for', $jobDescription->jobs_responsible_for) }}</textarea>
                                                    </td>
                                                </tr>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Region/Location</td>
                                                    <td>
                                                        <select name="region_location" id="region_location"
                                                            class="form-control">
                                                            <option value="">-- Select Location --</option>
                                                            <option value="Dar es Salaam"
                                                                {{ old('region_location', $data->region_location ?? '') == 'Dar es Salaam' ? 'selected' : '' }}>
                                                                Dar es Salaam</option>
                                                            <option value="Moshi"
                                                                {{ old('region_location', $data->region_location ?? '') == 'Moshi' ? 'selected' : '' }}>
                                                                Moshi</option>
                                                        </select>
                                                    </td>
                                                </tr>

                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Working Hours</td>
                                                    <td><input type="text" name="working_hours" class="form-control"
                                                            value="{{ old('working_hours', $jobDescription->working_hours) }}">
                                                    </td>
                                                </tr>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Job Review Date</td>
                                                    <td><input type="date" name="job_review_date" class="form-control"
                                                            value="{{ old('job_review_date', $jobDescription->job_review_date) }}">
                                                    </td>
                                                </tr>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Job Grade</td>
                                                    <td><input type="text" name="job_grade" class="form-control"
                                                            value="{{ old('job_grade', $jobDescription->job_grade) }}">
                                                    </td>
                                                </tr>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Grade Job Holder</td>
                                                    <td><input type="text" name="grade_job_holder" class="form-control"
                                                            value="{{ old('grade_job_holder', $jobDescription->grade_job_holder) }}">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Reasoning for Difference Between Grade Job
                                                Holder and Job Grade (if any)</label>
                                            <textarea name="grade_difference_reason" class="form-control" rows="3">{{ old('grade_difference_reason', $jobDescription->grade_difference_reason) }}</textarea>
                                        </div>
                                        <table class="table table-bordered mb-4">
                                            <thead>
                                                <tr style="background-color: #9ca19c; color: white; font-size: 1.1rem;">
                                                    <th colspan="2">A. Outputs</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle" style="width: 30%;">Purpose</td>
                                                    <td>
                                                        <textarea name="purpose" class="form-control" rows="3">{{ old('purpose', $jobDescription->purpose) }}</textarea>
                                                    </td>
                                                </tr>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Accountabilities / Key Outputs</td>
                                                    <td>
                                                        <textarea name="accountabilities" class="form-control" rows="3">{{ old('accountabilities', $jobDescription->accountabilities) }}</textarea>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table class="table table-bordered mb-4">
                                            <thead>
                                                <tr style="background-color: #9ca19c; color: white; font-size: 1.1rem;">
                                                    <th colspan="2">B. Inputs</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle" style="width: 30%;">Key
                                                        Qualifications, Experience, Skills & Competencies</td>
                                                    <td>
                                                        <textarea name="qualifications_experience" class="form-control mb-2" rows="3"
                                                            placeholder="Key qualifications, Experience and skills:">{{ old('qualifications_experience', $jobDescription->qualifications_experience) }}</textarea>
                                                        <textarea name="competencies" class="form-control" rows="3" placeholder="Competencies:">{{ old('competencies', $jobDescription->competencies) }}</textarea>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table class="table table-bordered mb-4">
                                            <thead>
                                                <tr style="background-color: #9ca19c; color: white; font-size: 1.1rem;">
                                                    <th colspan="3">Other Dimensions (if applicable)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle" style="width: 33%;">Financial (e.g.,
                                                        Budget, Turnover, Expenses, Assets, Profit)</td>
                                                    <td class="fw-bold align-middle" style="width: 33%;">Employees Managed
                                                        (Direct/Indirect)</td>
                                                    <td class="fw-bold align-middle" style="width: 34%;">Stakeholders
                                                        Managed</td>
                                                </tr>
                                                <tr>
                                                    <td><input type="text" name="financial_details"
                                                            class="form-control"
                                                            value="{{ old('financial_details', $jobDescription->financial_details) }}">
                                                    </td>
                                                    <td><input type="text" name="employees_managed"
                                                            class="form-control"
                                                            value="{{ old('employees_managed', $jobDescription->employees_managed) }}">
                                                    </td>
                                                    <td>
                                                        <textarea name="stakeholders_managed" class="form-control" rows="3">{{ old('stakeholders_managed', $jobDescription->stakeholders_managed) }}</textarea>
                                                    </td>
                                                </tr>
                                                <tr style="background-color: #f5f7f5;">
                                                    <td class="fw-bold align-middle">Organisation / Departmental Structure
                                                    </td>
                                                    <td colspan="2">
                                                        <select name="org_structure" class="form-select">
                                                            <option value="" disabled>Select Option</option>
                                                            <option value="Yes"
                                                                {{ old('org_structure', $jobDescription->org_structure) == 'Yes' ? 'selected' : '' }}>
                                                                Yes</option>
                                                            <option value="No"
                                                                {{ old('org_structure', $jobDescription->org_structure) == 'No' ? 'selected' : '' }}>
                                                                No</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Job Description
                                    </button>
                                    <a href="javascript:history.back();" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('employee_type').addEventListener('change', function() {
            const isNew = this.value === 'new';
            const userSection = document.getElementById('existing_user_section');
            userSection.style.display = isNew ? 'none' : 'table-row';
            if (isNew) {
                document.getElementById('user_id').value = '';
            }
        });

        document.getElementById('user_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const deptname = selectedOption.getAttribute('data-deptname');
            const region = selectedOption.getAttribute('data-region');

            if (deptname && region) {
                document.getElementById('region_location').value = region || '';
            }
        });
    </script>
    <script>
        function toggleUserDropdown() {
            const employeeType = document.getElementById('employee_type').value;
            const userSection = document.getElementById('existing_user_section');

            if (employeeType === 'existing') {
                userSection.style.display = '';
            } else {
                userSection.style.display = 'none';
                document.getElementById('user_id').value = ''; // clear selection
            }
        }

        // Run on page load (for edit case)
        document.addEventListener('DOMContentLoaded', function() {
            toggleUserDropdown();
            document.getElementById('employee_type').addEventListener('change', toggleUserDropdown);
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

        .table thead th {
            border-bottom: none;
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

        .btn-primary {
            background-color: #9ca19c;
            border-color: #9ca19c;
            font-weight: 500;
            transition: background-color 0.2s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #8a918a;
            border-color: #8a918a;
        }

        .btn-outline-secondary {
            border-color: #ced4da;
            color: #666;
        }

        .btn-outline-secondary:hover {
            background-color: #f1f1f1;
            border-color: #b0b0b0;
        }
    </style>
@endsection
