@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    @php
        $user = Auth::user();
        $isLineManager = isset($isLineManager) ? $isLineManager : false;
        $employees = isset($employees) ? $employees : collect([]);

        // Auto-populate dates from user table (for requester) or empty for line manager
        if ($isLineManager) {
            $dateOfHire = '';
            $endOfContract = '';
            $displayUser = null;
        } else {
            $dateOfHire = $user->starting_date ? \Carbon\Carbon::parse($user->starting_date)->format('Y-m-d') : '';
            $endOfContract = $user->ending_date ? \Carbon\Carbon::parse($user->ending_date)->format('Y-m-d') : '';
            $displayUser = $user;
        }
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">
            <br>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Employee Clearance Form</h3>
                        </div>
                    </div>
                </div>
            </div>
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Oops!</strong> There were some problems with your submission:
                    <ul class="mt-2 list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form Card -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="{{ route('clearance.review') }}" id="clearanceForm">
                                @csrf

                                @if ($isLineManager)
                                    <!-- Line Manager: Select Employee -->
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <h5 class="section-title mb-3 d-flex align-items-center">
                                                <i class="fas fa-users me-2"></i>Select Employee
                                            </h5>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="card" style="border-left: 4px solid #007A33;">
                                                <div class="card-header bg-light">
                                                    <h5 class="fw-normal mb-0" style="color: #333;">
                                                        <i class="fas fa-user-check me-2" style="color: #007A33;"></i>Employee Selection
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="form-group">
                                                        <label for="employee_select">Select Employee <span class="text-danger">*</span></label>
                                                        <select name="userId" id="employee_select" class="form-control {{ $errors->has('userId') ? 'border-red-500' : '' }}" required onchange="loadUserDetails(this.value)">
                                                            <option value="">-- Select Employee --</option>
                                                            @foreach($employees as $employee)
                                                                <option value="{{ $employee->id }}" {{ old('userId') == $employee->id ? 'selected' : '' }}>
                                                                    {{ $employee->fname }} {{ $employee->mname }} {{ $employee->lname }}
                                                                    ({{ $employee->ccbrt_code ?? 'N/A' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @if ($errors->has('userId'))
                                                            <span class="text-red-500 text-sm">{{ $errors->first('userId') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <input type="hidden" name="userId" value="{{ $user->id }}">
                                @endif

                                <!-- Employee Information Section -->
                                <div class="row mb-4">
                                    {{-- <div class="col-md-12">
                                        <h5 class="section-title mb-3 d-flex align-items-center">
                                            <i class="fas fa-user-circle me-2"></i>Employee Information
                                        </h5>
                                    </div> --}}

                                    <!-- Employee Info Display -->
                                    <div class="col-md-12 mb-4">
                                        <div class="card" style="border-left: 4px solid #007A33;">
                                            <div class="card-header bg-light">
                                                <h5 class="fw-normal mb-0" style="color: #333;">
                                                    <i class="fas fa-id-card me-2" style="color: #007A33;"></i>Employee Details
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Full Name</label>
                                                            <div class="form-control" style="background-color: #f8f9fa; cursor: default;" id="emp_full_name">
                                                                {{ $displayUser ? ($displayUser->fname . ' ' . $displayUser->mname . ' ' . $displayUser->lname) : 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>CCBRT Code</label>
                                                            <div class="form-control" style="background-color: #f8f9fa; cursor: default;" id="emp_code">
                                                                {{ optional($displayUser)->ccbrt_code ?? 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Username</label>
                                                            <div class="form-control" style="background-color: #f8f9fa; cursor: default;" id="emp_username">
                                                                {{ optional($displayUser)->username ?? 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Email</label>
                                                            <div class="form-control" style="background-color: #f8f9fa; cursor: default;" id="emp_email">
                                                                {{ optional($displayUser)->email ?? 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Mobile</label>
                                                            <div class="form-control" style="background-color: #f8f9fa; cursor: default;" id="emp_mobile">
                                                                {{ optional($displayUser)->mobile ?? 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Department</label>
                                                            <div class="form-control" style="background-color: #f8f9fa; cursor: default;" id="emp_department">
                                                                {{ optional($displayUser)->department->dept_name ?? 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Job Title</label>
                                                            <div class="form-control" style="background-color: #f8f9fa; cursor: default;" id="emp_job_title">
                                                                {{ optional($displayUser)->jobTitle->job_title ?? 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Employment Dates Card -->
                                    <div class="col-md-12 mb-4">
                                        <div class="card" style="border-left: 4px solid #007A33;">
                                            <div class="card-header bg-light">
                                                <h5 class="fw-normal mb-0" style="color: #333;">
                                                    <i class="fas fa-calendar-alt me-2" style="color: #007A33;"></i>Employment Dates
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="date_of_hire">Date of Hire <span class="text-danger">*</span></label>
                                                            <input type="date" name="date_of_hire" id="date_of_hire"
                                                                class="form-control {{ $errors->has('date_of_hire') ? 'border-red-500' : '' }}"
                                                                value="{{ old('date_of_hire', $dateOfHire) }}" required>
                                                            @if ($errors->has('date_of_hire'))
                                                                <span class="text-red-500 text-sm">{{ $errors->first('date_of_hire') }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="end_of_contract">End of Contract <span class="text-danger">*</span></label>
                                                            <input type="date" name="end_of_contract" id="end_of_contract"
                                                                class="form-control {{ $errors->has('end_of_contract') ? 'border-red-500' : '' }}"
                                                                value="{{ old('end_of_contract', $endOfContract) }}" required>
                                                            @if ($errors->has('end_of_contract'))
                                                                <span class="text-red-500 text-sm">{{ $errors->first('end_of_contract') }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Exit Interview Questions Section (Hidden for Line Managers) -->
                                @if (!$isLineManager)
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h5 class="section-title mb-3 d-flex align-items-center">
                                            <i class="fas fa-comments me-2"></i>2. Exit Interview Questions
                                        </h5>
                                        <p class="text-muted mb-4">Please answer the following questions about your reason for leaving:</p>
                                    </div>

                                    <!-- Exit Reasons Card -->
                                    <div class="col-md-12 mb-4">
                                        <div class="card" style="border-left: 4px solid #007A33;">
                                            <div class="card-header bg-light">
                                                <h5 class="fw-normal mb-0" style="color: #333;">
                                                    <i class="fas fa-list-check me-2" style="color: #007A33;"></i>What is the primary reason for your exit? (Select all that apply)
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" name="exit_reason[]" value="Better salary/compensation" id="reason_salary" {{ (is_array(old('exit_reason')) && in_array('Better salary/compensation', old('exit_reason'))) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="reason_salary">
                                                                Better salary/compensation
                                                            </label>
                                                        </div>
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" name="exit_reason[]" value="Career advancement opportunities" id="reason_career" {{ (is_array(old('exit_reason')) && in_array('Career advancement opportunities', old('exit_reason'))) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="reason_career">
                                                                Career advancement opportunities
                                                            </label>
                                                        </div>
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" name="exit_reason[]" value="Work-life balance" id="reason_balance" {{ (is_array(old('exit_reason')) && in_array('Work-life balance', old('exit_reason'))) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="reason_balance">
                                                                Work-life balance
                                                            </label>
                                                        </div>
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" name="exit_reason[]" value="Management/leadership issues" id="reason_management" {{ (is_array(old('exit_reason')) && in_array('Management/leadership issues', old('exit_reason'))) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="reason_management">
                                                                Management/leadership issues
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" name="exit_reason[]" value="Job dissatisfaction" id="reason_dissatisfaction" {{ (is_array(old('exit_reason')) && in_array('Job dissatisfaction', old('exit_reason'))) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="reason_dissatisfaction">
                                                                Job dissatisfaction
                                                            </label>
                                                        </div>
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" name="exit_reason[]" value="Relocation" id="reason_relocation" {{ (is_array(old('exit_reason')) && in_array('Relocation', old('exit_reason'))) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="reason_relocation">
                                                                Relocation
                                                            </label>
                                                        </div>
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" name="exit_reason[]" value="Contract ended" id="reason_contract" {{ (is_array(old('exit_reason')) && in_array('Contract ended', old('exit_reason'))) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="reason_contract">
                                                                Contract ended
                                                            </label>
                                                        </div>
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" name="exit_reason[]" value="Personal reasons" id="reason_personal" {{ (is_array(old('exit_reason')) && in_array('Personal reasons', old('exit_reason'))) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="reason_personal">
                                                                Personal reasons
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Details Card -->
                                    <div class="col-md-12 mb-4">
                                        <div class="card" style="border-left: 4px solid #007A33;">
                                            <div class="card-header bg-light">
                                                <h5 class="fw-normal mb-0" style="color: #333;">
                                                    <i class="fas fa-file-alt me-2" style="color: #007A33;"></i>Additional Details
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group mb-4">
                                                    <label for="exit_explanation">Please provide additional details about your reason for leaving:</label>
                                                    <textarea name="exit_explanation" id="exit_explanation" class="form-control {{ $errors->has('exit_explanation') ? 'border-red-500' : '' }}" rows="4" placeholder="Please explain your reason for leaving in detail...">{{ old('exit_explanation') }}</textarea>
                                                    @if ($errors->has('exit_explanation'))
                                                        <span class="text-red-500 text-sm">{{ $errors->first('exit_explanation') }}</span>
                                                    @endif
                                                </div>

                                                <div class="form-group mb-4">
                                                    <label for="suggestions">What suggestions do you have for improving the organization?</label>
                                                    <textarea name="suggestions" id="suggestions" class="form-control {{ $errors->has('suggestions') ? 'border-red-500' : '' }}" rows="3" placeholder="Your suggestions...">{{ old('suggestions') }}</textarea>
                                                    @if ($errors->has('suggestions'))
                                                        <span class="text-red-500 text-sm">{{ $errors->first('suggestions') }}</span>
                                                    @endif
                                                </div>

                                                <div class="form-group mb-0">
                                                    <label for="would_recommend">Would you recommend CCBRT as a place to work? Please explain:</label>
                                                    <textarea name="would_recommend" id="would_recommend" class="form-control {{ $errors->has('would_recommend') ? 'border-red-500' : '' }}" rows="3" placeholder="Your response...">{{ old('would_recommend') }}</textarea>
                                                    @if ($errors->has('would_recommend'))
                                                        <span class="text-red-500 text-sm">{{ $errors->first('would_recommend') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Information Notice -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card" style="border-left: 4px solid #17a2b8;">
                                            <div class="card-header bg-light">
                                                <h5 class="fw-normal mb-0" style="color: #333;">
                                                    <i class="fas fa-info-circle me-2" style="color: #17a2b8;"></i>Important Information
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-0 text-muted">
                                                    @if ($isLineManager)
                                                        After submitting this form, you will need to review and approve your section (Line Manager section), then it will proceed to Finance Officer, IT, and HR departments for approval.
                                                    @else
                                                        After submitting this form, it will be reviewed by your Line Manager, Finance Officer, IT, and HR departments. You will be able to view the progress and details filled by each approver.
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Buttons -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-eye me-2"></i>Review & Submit
                                            </button>
                                            <a href="{{ route('clearance.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-times me-2"></i>Cancel
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (isset($isLineManager) && $isLineManager)
    <script>
        function loadUserDetails(userId) {
            console.log('loadUserDetails called with userId:', userId);
            if (!userId) {
                // Clear all fields if no user selected
                document.getElementById('emp_full_name').textContent = 'N/A';
                document.getElementById('emp_code').textContent = 'N/A';
                document.getElementById('emp_username').textContent = 'N/A';
                document.getElementById('emp_email').textContent = 'N/A';
                document.getElementById('emp_mobile').textContent = 'N/A';
                document.getElementById('emp_department').textContent = 'N/A';
                document.getElementById('emp_job_title').textContent = 'N/A';
                document.getElementById('date_of_hire').value = '';
                document.getElementById('end_of_contract').value = '';
                return;
            }

            // Show loading state
            document.getElementById('emp_full_name').textContent = 'Loading...';
            document.getElementById('emp_code').textContent = 'Loading...';
            document.getElementById('emp_username').textContent = 'Loading...';
            document.getElementById('emp_email').textContent = 'Loading...';
            document.getElementById('emp_mobile').textContent = 'Loading...';
            document.getElementById('emp_department').textContent = 'Loading...';
            document.getElementById('emp_job_title').textContent = 'Loading...';

            // Get CSRF token if available
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

            // Build headers
            const headers = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            };
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }

            fetch(`/api/user-details/${userId}`, {
                method: 'GET',
                headers: headers,
                credentials: 'same-origin'
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('User data received:', data);
                    if (data.success && data.user) {
                        const user = data.user;
                        document.getElementById('emp_full_name').textContent =
                            `${user.fname || ''} ${user.mname || ''} ${user.lname || ''}`.trim() || 'N/A';
                        document.getElementById('emp_code').textContent = user.ccbrt_code || 'N/A';
                        document.getElementById('emp_username').textContent = user.username || 'N/A';
                        document.getElementById('emp_email').textContent = user.email || 'N/A';
                        document.getElementById('emp_mobile').textContent = user.mobile || 'N/A';

                        // Handle department - API returns {dept_name: '...'} or null
                        const deptName = user.department && user.department.dept_name
                            ? user.department.dept_name
                            : (user.department || 'N/A');
                        document.getElementById('emp_department').textContent = deptName;

                        // Handle job_title - API returns {job_title: '...'} or null
                        const jobTitle = user.job_title && user.job_title.job_title
                            ? user.job_title.job_title
                            : (user.job_title || 'N/A');
                        document.getElementById('emp_job_title').textContent = jobTitle;

                        // Auto-populate dates if available
                        if (user.starting_date) {
                            document.getElementById('date_of_hire').value = user.starting_date;
                        } else {
                            document.getElementById('date_of_hire').value = '';
                        }
                        if (user.ending_date) {
                            document.getElementById('end_of_contract').value = user.ending_date;
                        } else {
                            document.getElementById('end_of_contract').value = '';
                        }
                    } else {
                        console.error('API returned unsuccessful response:', data);
                        alert('Failed to load user details: ' + (data.message || 'Unknown error'));
                        // Reset to N/A on error
                        document.getElementById('emp_full_name').textContent = 'N/A';
                        document.getElementById('emp_code').textContent = 'N/A';
                        document.getElementById('emp_username').textContent = 'N/A';
                        document.getElementById('emp_email').textContent = 'N/A';
                        document.getElementById('emp_mobile').textContent = 'N/A';
                        document.getElementById('emp_department').textContent = 'N/A';
                        document.getElementById('emp_job_title').textContent = 'N/A';
                    }
                })
                .catch(error => {
                    console.error('Error loading user details:', error);
                    alert('Error loading user details. Please check the console for details and try again.');
                    // Reset to N/A on error
                    document.getElementById('emp_full_name').textContent = 'N/A';
                    document.getElementById('emp_code').textContent = 'N/A';
                    document.getElementById('emp_username').textContent = 'N/A';
                    document.getElementById('emp_email').textContent = 'N/A';
                    document.getElementById('emp_mobile').textContent = 'N/A';
                    document.getElementById('emp_department').textContent = 'N/A';
                    document.getElementById('emp_job_title').textContent = 'N/A';
                });
        }

        // Load user details if employee is pre-selected (from old input)
        @if (old('userId'))
            document.addEventListener('DOMContentLoaded', function() {
                loadUserDetails({{ old('userId') }});
            });
        @endif
    </script>
    @endif

@endsection

@section('styles')
    <style>
        .page-wrapper {
            padding: 20px;
        }

        .text-muted {
            font-size: 0.95rem;
            color: #6c757d;
        }

        .card {
            border: none;
            border-radius: 8px;
            background-color: #ffffff;
        }

        .card-body {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #007A33;
            margin-bottom: 1.25rem;
            border-bottom: 2px solid #007A33;
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control,
        .form-select {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 0.5rem 0.75rem;
            font-size: 0.95rem;
            font-weight: 400;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007A33;
            box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.25);
        }

        .form-control:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        .form-check {
            margin-bottom: 0.75rem;
        }

        .form-check-input {
            margin-top: 0.25rem;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #007A33;
            border-color: #007A33;
        }

        .form-check-input:focus {
            border-color: #007A33;
            box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.25);
        }

        .form-check-label {
            font-size: 0.95rem;
            color: #333;
            font-weight: 400;
            cursor: pointer;
            margin-left: 0.5rem;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        textarea.form-control:focus {
            border-color: #007A33;
            box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.25);
        }

        .text-danger {
            font-weight: 400;
        }

        /* Red border for invalid inputs */
        .border-red-500 {
            border-color: #dc3545 !important;
        }

        .border-red-500:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .card-header.bg-light {
            background-color: #f8f9fa !important;
        }

        .btn-primary {
            background-color: #007A33;
            border-color: #007A33;
        }

        .btn-primary:hover {
            background-color: #005a25;
            border-color: #005a25;
        }

    </style>
@endsection
