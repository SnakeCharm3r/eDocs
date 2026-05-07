@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            font-size: 14px;
        }

        .text-danger {
            font-size: 12px;
        }

        .btn {
            margin-right: 10px;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Create Fixed Flex Contract</h3>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('contracts.fixed_flex.store') }}" method="POST">
                                @csrf

                                <!-- User Selection: Existing or New -->
                                <div class="form-group">
                                    <label for="user_type">User Type</label>
                                    <select class="form-control" id="user_type" name="user_type"
                                        onchange="toggleUserFields()">
                                        <option value="existing" {{ old('user_type') == 'existing' ? 'selected' : '' }}>
                                            Existing User</option>
                                        <option value="new" {{ old('user_type') == 'new' ? 'selected' : '' }}>New User
                                        </option>
                                    </select>
                                    @error('user_type')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Existing User Fields -->
                                <div id="existing-user-fields"
                                    style="{{ old('user_type', 'existing') == 'new' ? 'display: none;' : '' }}">
                                    <div class="form-group">
                                        <label for="user_id">Select Employee</label>
                                        <select class="form-control" id="user_id" name="user_id">
                                            <option value="">Select Employee</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}"
                                                    {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->fname }} {{ $user->mname ?? '' }} {{ $user->lname }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('user_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- New User Fields -->
                                <div id="new-user-fields"
                                    style="{{ old('user_type') == 'existing' ? 'display: none;' : '' }}">
                                    <div class="form-group">
                                        <label for="name">Employee Name</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name') }}">
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                    </div>
                                    <div class="form-group">
                                        <label for="date_of_birth">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                            value="{{ old('date_of_birth') }}">
                                        @error('date_of_birth')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select class="form-control" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male
                                            </option>
                                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female
                                            </option>
                                        </select>
                                        @error('gender')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="nationality">Nationality</label>
                                        <input type="text" class="form-control" id="nationality" name="nationality"
                                            value="{{ old('nationality') }}">
                                        @error('nationality')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="department_id">Department</label>
                                    <select name="department_id" id="department_id" class="form-control" required>
                                        <option value="">-- Select Department --</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->dept_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Job Title -->
                                <div class="form-group">
                                    <label for="job_title_id">Job Title</label>
                                    <select class="form-control" id="job_title_id" name="job_title_id">
                                        <option value="">Select Job Title</option>
                                        @foreach ($jobTitles as $jobTitle)
                                            <option value="{{ $jobTitle->id }}"
                                                {{ old('job_title_id') == $jobTitle->id ? 'selected' : '' }}>
                                                {{ $jobTitle->job_title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('job_title_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Other Contract Details -->
                                <div class="form-group">
                                    <label for="duty_station">Duty Station</label>
                                    <input type="text" class="form-control" id="duty_station" name="duty_station"
                                        value="{{ old('duty_station') }}">
                                    @error('duty_station')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="duration">Contract Duration</label>
                                    <input type="text" class="form-control" id="duration" name="duration"
                                        value="{{ old('duration') }}">
                                    @error('duration')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="salary_fixed">Fixed Salary (TZS)</label>
                                    <input type="number" class="form-control" id="salary_fixed" name="salary_fixed"
                                        step="0.01" value="{{ old('salary_fixed') }}">
                                    @error('salary_fixed')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="salary_flexible">Flexible Daily Rate (TZS)</label>
                                    <input type="number" class="form-control" id="salary_flexible"
                                        name="salary_flexible" step="0.01" value="{{ old('salary_flexible') }}">
                                    @error('salary_flexible')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="working_hours">Working Hours</label>
                                    <input type="text" class="form-control" id="working_hours" name="working_hours"
                                        value="{{ old('working_hours') }}">
                                    @error('working_hours')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="probation">Probation Period</label>
                                    <input type="text" class="form-control" id="probation" name="probation"
                                        value="{{ old('probation') }}">
                                    @error('probation')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="contract_date">Contract Date</label>
                                    <input type="date" class="form-control" id="contract_date" name="contract_date"
                                        value="{{ old('contract_date') }}">
                                    @error('contract_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Hidden field for contract type -->
                                <input type="hidden" name="type" value="Fix-Flex contract">

                                <!-- Form Buttons -->
                                <button type="submit" class="btn btn-primary">Save Contract</button>
                                <a href="{{ route('contracts.fixed_flex.index') }}" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleUserFields() {
            const userType = document.getElementById('user_type').value;
            const existingUserFields = document.getElementById('existing-user-fields');
            const newUserFields = document.getElementById('new-user-fields');

            if (userType === 'existing') {
                existingUserFields.style.display = 'block';
                newUserFields.style.display = 'none';
                // Reset new user fields
                document.getElementById('name').value = '';
                document.getElementById('date_of_birth').value = '';
                document.getElementById('gender').value = '';
                document.getElementById('nationality').value = '';
            } else {
                existingUserFields.style.display = 'none';
                newUserFields.style.display = 'block';
                // Reset existing user field
                document.getElementById('user_id').value = '';
            }
        }

        // Run on page load to set initial state
        toggleUserFields();
    </script>
@endsection
