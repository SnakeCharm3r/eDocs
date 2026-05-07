// resources/views/contracts/fixed-flex/create.blade.php
@extends('layouts.template')

@section('content')
    <div class="container">
        <h1>Create Fix-Flex Contract</h1>
        <form action="{{ route('contracts.fixed-flex.store') }}" method="POST">
            @csrf

            <!-- User Selection -->
            <div class="form-group">
                <label for="user_type">User Type</label>
                <select class="form-control" id="user_type" name="user_type" onchange="toggleUserFields()">
                    <option value="existing">Existing User</option>
                    <option value="new">New User</option>
                </select>
            </div>

            <!-- Existing User Fields -->
            <div id="existing-user-fields">
                <div class="form-group">
                    <label for="user_id">Select Employee</label>
                    <select class="form-control" id="user_id" name="user_id">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->fullName() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- New User Fields -->
            <div id="new-user-fields" style="display: none;">
                <div class="form-group">
                    <label for="name">Employee Name</label>
                    <input type="text" class="form-control" id="name" name="name">
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select class="form-control" id="gender" name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nationality">Nationality</label>
                    <input type="text" class="form-control" id="nationality" name="nationality">
                </div>
            </div>

            <!-- Contract Details -->
            <div class="form-group">
                <label for="department_id">Department</label>
                <select class="form-control" id="department_id" name="department_id" required>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->dept_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="job_title_id">Job Title</label>
                <select class="form-control" id="job_title_id" name="job_title_id" required>
                    @foreach ($jobTitles as $jobTitle)
                        <option value="{{ $jobTitle->id }}">{{ $jobTitle->job_title }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Other fields from your existing form -->
            <div class="form-group">
                <label for="duty_station">Duty Station</label>
                <input type="text" class="form-control" id="duty_station" name="duty_station" required>
            </div>

            <!-- Add all other necessary fields -->

            <button type="submit" class="btn btn-primary">Create Contract</button>
        </form>
    </div>

    <script>
        function toggleUserFields() {
            const userType = document.getElementById('user_type').value;
            const existingFields = document.getElementById('existing-user-fields');
            const newFields = document.getElementById('new-user-fields');

            if (userType === 'existing') {
                existingFields.style.display = 'block';
                newFields.style.display = 'none';
            } else {
                existingFields.style.display = 'none';
                newFields.style.display = 'block';
            }
        }
    </script>
@endsection
