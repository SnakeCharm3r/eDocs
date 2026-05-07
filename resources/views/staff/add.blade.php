@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-user-plus me-2"></i>Add New Staff Member
                        </h3>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="{{ route('employee.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Staff Details
                        </a>
                    </div>
                </div>
            </div>

            @if (session('error_message'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error_message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-exclamation-triangle"></i> Please fix the following issues:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form id="addStaffForm" action="{{ route('staff.add.handle') }}" method="POST">
                @csrf

                <!-- Info Notice -->
                <div class="alert alert-info" role="alert"
                    style="background-color: #e3f2fd; border-left: 4px solid #007A33; color: #0d47a1;">
                    <i class="fas fa-info-circle"></i> <strong>Important:</strong> Please fill in the staff member's
                    details as they appear on their National Identification Number (NIDA).
                </div>

                <!-- Two Column Layout -->
                <div class="row mb-4">
                    <!-- Left Column: Personal Information -->
                    <div class="col-md-6">
                        <div class="card h-100" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div class="card-header" style="background-color: #007A33; color: white; font-weight: 600;">
                                <i class="fas fa-user"></i> Personal Information
                            </div>
                            <div class="card-body">
                                <!-- Name Fields - Two Columns -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="fname">First Name <span class="text-danger">*</span></label>
                                            <input class="form-control capitalize-input" id="fname" type="text"
                                                name="fname" required placeholder="First Name" value="{{ old('fname') }}"
                                                style="border-color: #ced4da;">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="mname">Middle Name</label>
                                            <input class="form-control capitalize-input" id="mname" type="text"
                                                name="mname" placeholder="Middle Name" value="{{ old('mname') }}"
                                                style="border-color: #ced4da;">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="lname">Last Name <span class="text-danger">*</span></label>
                                            <input class="form-control capitalize-input" id="lname" type="text"
                                                name="lname" required placeholder="Last Name" value="{{ old('lname') }}"
                                                style="border-color: #ced4da;">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="dob">Date of Birth <span class="text-danger">*</span></label>
                                            <input class="form-control" id="dob" type="date" name="DOB"
                                                required value="{{ old('DOB') }}" style="border-color: #ced4da;"
                                                onchange="validateAge()">
                                            <small id="dob-error" class="text-danger" style="display: none;">
                                                <i class="fas fa-exclamation-circle"></i> The staff member must be at
                                                least 18 years old.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact - Two Columns -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="phoneNumber">Phone Number <span class="text-danger">*</span></label>
                                            <input type="tel" id="phoneNumber" name="mobile" class="form-control"
                                                placeholder="699990002" maxlength="13" value="{{ old('mobile') }}">
                                            <input type="hidden" id="country_code" name="country_code"
                                                value="{{ old('country_code') }}">
                                            <input type="hidden" id="full_phone_number" name="full_phone_number"
                                                value="">
                                            <small id="phone-error" class="text-danger" style="display:none;">
                                                <i class="fas fa-exclamation-circle"></i> Please enter a valid phone
                                                number.
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="email">Email <span class="text-danger">*</span></label>
                                            <input class="form-control" type="email" name="email" id="email"
                                                required placeholder="abc@ccbrt.org" value="{{ old('email') }}"
                                                style="border-color: #ced4da;">
                                            <small id="email-message" class="text-danger" style="display: none;"></small>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> Staff members should use their CCBRT
                                                email address.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Password Fields - Two Columns -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3 position-relative">
                                            <label for="password">Password <span class="text-danger">*</span></label>
                                            <input class="form-control" type="password" name="password" id="password"
                                                required placeholder="Enter password"
                                                value="{{ old('password', '123.ccbrt') }}"
                                                style="border-color: #ced4da; padding-right: 40px;">
                                            <span class="password-toggle" id="togglePassword"
                                                style="position: absolute; right: 12px; top: 38px; cursor: pointer; color: #666;">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                            <small id="password-strength-text"
                                                style="display: block; margin-top: 5px; font-weight: 600;"></small>
                                            <small class="form-text text-muted">Default password:
                                                <strong>123.ccbrt</strong> (can be changed)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3 position-relative">
                                            <label for="password_confirmation">Confirm Password <span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control" type="password" name="password_confirmation"
                                                id="password_confirmation" required placeholder="Confirm password"
                                                value="{{ old('password_confirmation', '123.ccbrt') }}"
                                                style="border-color: #ced4da; padding-right: 40px;">
                                            <span class="password-toggle" id="togglePasswordConfirm"
                                                style="position: absolute; right: 12px; top: 38px; cursor: pointer; color: #666;">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                            <small id="password-message" class="text-danger"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Employment Details -->
                    <div class="col-md-6">
                        <div class="card h-100" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div class="card-header" style="background-color: #007A33; color: white; font-weight: 600;">
                                <i class="fas fa-briefcase"></i> Employment Details
                            </div>
                            <div class="card-body">
                                <!-- Employment Type and Department - Two Columns -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="employment_typeId">Employment Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" name="employment_typeId" id="employment_typeId"
                                                required style="border-color: #ced4da;">
                                                <option value="">Select Employment Type</option>
                                                @foreach ($employmentTypes as $employmentType)
                                                    <option value="{{ $employmentType->id }}"
                                                        {{ old('employment_typeId') == $employmentType->id ? 'selected' : '' }}>
                                                        {{ $employmentType->employment_type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="deptId">Department <span class="text-danger">*</span></label>
                                            <select class="form-control" name="deptId" id="deptId" required
                                                style="border-color: #ced4da;">
                                                <option value="">Select Department</option>
                                                @foreach ($departments as $department)
                                                    <option value="{{ $department->id }}"
                                                        {{ old('deptId') == $department->id ? 'selected' : '' }}>
                                                        {{ $department->dept_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Job Title and Professional Reg - Two Columns -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="job_title">Job Title <span class="text-danger">*</span></label>
                                            <select class="form-control" id="job_title" name="job_title" required
                                                style="border-color: #ced4da;" disabled>
                                                <option value="">Select Department First</option>
                                            </select>
                                            <small class="form-text text-muted">Please select a department first to load
                                                job titles.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="professional_reg_number">
                                                Professional Registration Number
                                                <span class="text-danger" id="professional_reg_required"
                                                    style="display: none;">*</span>
                                                <small class="text-muted" id="professional_reg_optional"
                                                    style="display: inline;">(Optional)</small>
                                            </label>
                                            <input class="form-control" type="text" name="professional_reg_number"
                                                id="professional_reg_number" placeholder="e.g. PH123456 or 12345"
                                                value="{{ old('professional_reg_number') }}" maxlength="50"
                                                style="border-color: #ced4da;">
                                            <small class="form-text text-muted" id="professional_reg_help">
                                                Optional: Enter professional registration number if available.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- License Provider - Only for Clinical Job Titles -->
                                <div class="row" id="license_provider_row" style="display: none;">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="license_provider">
                                                License Provider
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" name="license_provider" id="license_provider"
                                                style="border-color: #ced4da;">
                                                <option value="">Select License Provider</option>
                                                <option value="Medical Council of Tanzania"
                                                    {{ old('license_provider') == 'Medical Council of Tanzania' ? 'selected' : '' }}>
                                                    Medical Council of Tanzania (MCT)</option>
                                                <option value="Tanzania Nursing and Midwifery Council"
                                                    {{ old('license_provider') == 'Tanzania Nursing and Midwifery Council' ? 'selected' : '' }}>
                                                    Tanzania Nursing and Midwifery Council (TNMC)</option>
                                                <option value="Tanzania Pharmacy Board"
                                                    {{ old('license_provider') == 'Tanzania Pharmacy Board' ? 'selected' : '' }}>
                                                    Tanzania Pharmacy Board (TPB)</option>
                                                <option value="Tanzania Physiotherapy Council"
                                                    {{ old('license_provider') == 'Tanzania Physiotherapy Council' ? 'selected' : '' }}>
                                                    Tanzania Physiotherapy Council (TPC)</option>
                                                <option value="Tanzania Medical and Dental Council"
                                                    {{ old('license_provider') == 'Tanzania Medical and Dental Council' ? 'selected' : '' }}>
                                                    Tanzania Medical and Dental Council (TMDC)</option>
                                                <option value="Other"
                                                    {{ old('license_provider') == 'Other' ? 'selected' : '' }}>
                                                    Other</option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Select the organization that issued the professional license.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Start & End Dates - Two Columns -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="starting_date">Starting Date <span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control" type="date" name="starting_date"
                                                id="starting_date" required value="{{ old('starting_date') }}"
                                                style="border-color: #ced4da;">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="ending_date">Ending Date <span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control" type="date" name="ending_date"
                                                id="ending_date" required value="{{ old('ending_date') }}"
                                                style="border-color: #ced4da;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Section -->
                <div class="text-center mt-4 mb-4">
                    <button class="btn btn-success btn-lg px-5 fw-semibold" type="submit"
                        style="background-color: #007A33; border-color: #007A33; min-width: 150px;">
                        <i class="fas fa-user-plus"></i> <span id="submitBtnText">Add Staff Member</span>
                        <span id="submitBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"
                            aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .password-toggle:hover {
            color: #007A33;
        }

        .capitalize-input {
            text-transform: capitalize;
        }

        .form-control:focus {
            border-color: #007A33;
            box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.25);
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Set dynamic max date for DOB (18 years ago)
            const todayDate = new Date();
            const maxDate = new Date(todayDate.getFullYear() - 18, todayDate.getMonth(), todayDate.getDate());
            const maxDateStr = maxDate.toISOString().split('T')[0];
            document.getElementById('dob').setAttribute('max', maxDateStr);

            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirmation');

            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });

            togglePasswordConfirm.addEventListener('click', function() {
                const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordConfirm.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });

            // Phone Number Input
            var input = document.querySelector("#phoneNumber");
            var countryCodeInput = document.querySelector("#country_code");
            var fullPhoneNumberInput = document.querySelector("#full_phone_number");
            var iti = window.intlTelInput(input, {
                initialCountry: "tz",
                separateDialCode: true,
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                nationalMode: true,
                maxLength: 13
            });

            // Set country code on load if old value exists
            if (countryCodeInput.value) {
                iti.setCountry(document.querySelector('.iti__selected-flag').getAttribute('title').split(':')[0]
                    .trim().toLowerCase());
            }

            // Update country code and full phone number when country changes
            function updatePhoneData() {
                var countryCode = iti.getSelectedCountryData().dialCode;
                countryCodeInput.value = countryCode;

                if (input.value.trim()) {
                    var fullNumber = iti.getNumber();
                    fullPhoneNumberInput.value = fullNumber;
                } else {
                    fullPhoneNumberInput.value = '';
                }
            }

            // Function to get clean national number
            function getCleanNationalNumber() {
                if (!iti.isValidNumber()) {
                    return input.value.replace(/[^\d]/g, '');
                }

                var nationalNumber = iti.getNumber();
                var cleanNational = nationalNumber.replace(/[^\d]/g, '');
                var countryCode = iti.getSelectedCountryData().dialCode;

                if (cleanNational.startsWith(countryCode)) {
                    cleanNational = cleanNational.substring(countryCode.length);
                }

                if (cleanNational.startsWith('0')) {
                    cleanNational = cleanNational.substring(1);
                }

                return cleanNational;
            }

            input.addEventListener('focus', updatePhoneData);
            input.addEventListener('input', updatePhoneData);
            input.addEventListener('countrychange', updatePhoneData);

            // Email Validation
            function validateEmail() {
                const emailInput = document.getElementById('email');
                const message = document.getElementById('email-message');
                const regex =
                    /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z]{2,})+$/;
            const emailValue = emailInput.value.trim();

            if (!emailValue) {
                message.textContent = 'Email is required.';
                message.style.display = 'block';
                emailInput.style.borderColor = '#dc3545';
                return false;
            } else if (!regex.test(emailValue)) {
                message.textContent = 'Please enter a valid email address (e.g., user@example.com).';
                message.style.display = 'block';
                emailInput.style.borderColor = '#dc3545';
                return false;
            } else if (emailValue.length > 254) {
                message.textContent = 'Email address is too long.';
                message.style.display = 'block';
                emailInput.style.borderColor = '#dc3545';
                return false;
            }

            message.textContent = '';
            message.style.display = 'none';
            emailInput.style.borderColor = '#ced4da';
            return true;
        }

        document.getElementById('email').addEventListener('input', validateEmail);

        // Date of Birth Validation
        function validateAge() {
            const dobInput = document.getElementById('dob');
            const dobError = document.getElementById('dob-error');
            const currentDate = new Date();
            const inputDate = new Date(dobInput.value);
            const minAgeDate = new Date(currentDate.getFullYear() - 18, currentDate.getMonth(),
                currentDate.getDate());

            if (!dobInput.value) {
                dobError.style.display = 'none';
                dobInput.style.borderColor = '#ced4da';
                return true;
            }

            if (inputDate > minAgeDate) {
                dobError.style.display = 'block';
                dobInput.style.borderColor = '#dc3545';
                return false;
            }
            dobError.style.display = 'none';
            dobInput.style.borderColor = '#ced4da';
            return true;
        }

        // Password Matching
        function validatePasswordMatch() {
            const password = document.getElementById('password');
            const passwordConfirmation = document.getElementById('password_confirmation');
            const passwordMessage = document.getElementById('password-message');

            if (password.value && passwordConfirmation.value) {
                if (password.value !== passwordConfirmation.value) {
                    passwordMessage.innerHTML =
                        '<i class="fas fa-exclamation-circle"></i> Passwords do not match!';
                    passwordMessage.style.display = 'block';
                    password.style.borderColor = '#dc3545';
                    passwordConfirmation.style.borderColor = '#dc3545';
                    return false;
                } else {
                    passwordMessage.textContent = '';
                    passwordMessage.style.display = 'none';
                    password.style.borderColor = '#ced4da';
                    passwordConfirmation.style.borderColor = '#ced4da';
                    return true;
                }
            }
            return true;
        }

        // Capitalize First Letter of Name Inputs
        function capitalizeInputs() {
            document.querySelectorAll('.capitalize-input').forEach(input => {
                input.addEventListener('input', function() {
                    let words = this.value.split(' ');
                    for (let i = 0; i < words.length; i++) {
                        if (words[i].length > 0) {
                            words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1)
                                .toLowerCase();
                        }
                    }
                    this.value = words.join(' ');
                });
            });
        }
        capitalizeInputs();

        // Department and Job Title
        const jobTitleSelect = $('#job_title');

        // Initially disable job title if no department is selected
        @if (!old('deptId'))
            jobTitleSelect.prop('disabled', true);
            jobTitleSelect.removeAttr('required');
        @endif

        $('#deptId').change(function() {
            var deptId = $(this).val();

            jobTitleSelect.empty().append('<option value="">Select Job Title</option>');
            jobTitleSelect.prop('disabled', true);
            jobTitleSelect.removeAttr('required');

            if (deptId) {
                jobTitleSelect.html('<option value="">Loading job titles...</option>');

                $.ajax({
                    url: '/job-titles/' + deptId,
                    method: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(data) {
                        jobTitleSelect.empty().append(
                            '<option value="">Select Job Title</option>');

                        if (data && data.length > 0) {
                            window.jobTitlesData = data;

                            $.each(data, function(index, jobTitle) {
                                jobTitleSelect.append('<option value="' + jobTitle
                                    .id +
                                    '" data-clinical="' + (jobTitle
                                        .clinical_or_non_clinical || '') +
                                    '">' + jobTitle.job_title + '</option>');
                            });
                            jobTitleSelect.prop('disabled', false);
                            jobTitleSelect.attr('required', true);

                            @if (old('job_title'))
                                jobTitleSelect.val('{{ old('job_title') }}');
                                jobTitleSelect.trigger('change');
                            @endif
                        } else {
                            jobTitleSelect.append(
                                '<option value="">No job titles available</option>');
                            jobTitleSelect.prop('disabled', true);
                            jobTitleSelect.removeAttr('required');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching job titles:', error);
                        jobTitleSelect.empty().append(
                            '<option value="">Error loading job titles</option>');
                        jobTitleSelect.prop('disabled', true);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to fetch job titles. Please try again.',
                            confirmButtonColor: '#007A33'
                        });
                    }
                });
            } else {
                jobTitleSelect.prop('disabled', true);
                jobTitleSelect.removeAttr('required');
            }
        });

        // Handle job title change to check if clinical
        jobTitleSelect.on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const isClinical = selectedOption.data('clinical') === 'Clinical';

            if (isClinical) {
                $('#professional_reg_number').attr('required', true);
                $('#professional_reg_required').show();
                $('#professional_reg_optional').hide();
                $('#professional_reg_help').text('Required for users in clinical job titles.');
                $('#license_provider_row').show();
                $('#license_provider').attr('required', true);
            } else {
                $('#professional_reg_number').removeAttr('required');
                $('#professional_reg_required').hide();
                $('#professional_reg_optional').show();
                $('#professional_reg_help').text(
                    'Optional: Enter professional registration number if available.');
                $('#license_provider_row').hide();
                $('#license_provider').removeAttr('required');
            }
        });

        // Check on page load if department is already selected
        @if (old('deptId'))
            setTimeout(function() {
                const deptId = $('#deptId').val();
                if (deptId) {
                    $('#deptId').trigger('change');
                    setTimeout(function() {
                        if ($('#job_title').val()) {
                            $('#job_title').trigger('change');
                        }
                    }, 300);
                }
            }, 200);
        @endif

        // Check on page load if job title is already selected
        @if (old('job_title'))
            setTimeout(function() {
                const jobTitleId = $('#job_title').val();
                if (jobTitleId) {
                    const selectedOption = $('#job_title').find('option:selected');
                    const isClinical = selectedOption.data('clinical') === 'Clinical';
                    if (isClinical) {
                        $('#license_provider_row').show();
                        $('#license_provider').attr('required', true);
                    }
                }
            }, 500);
        @endif

        // Start and End Date handling
        const startDateInput = document.getElementById('starting_date');
        const endDateInput = document.getElementById('ending_date');
        const todayStr = new Date().toISOString().split('T')[0];
        startDateInput.setAttribute('max', todayStr);

        startDateInput.addEventListener('change', function() {
            if (this.value) {
                // Only set minimum date to starting date - no maximum restriction
                // This allows ending date to be any number of years after starting date
                endDateInput.min = this.value;

                // If ending date is not set or is before starting date, clear it
                // User can then select any date after the starting date
                if (!endDateInput.value || endDateInput.value < this.value) {
                    endDateInput.value = '';
                }
            }
        });

        // Password Strength
        const passwordInput = document.getElementById('password');
        const strengthText = document.getElementById('password-strength-text');

        function getPasswordStrength(password) {
            let score = 0;
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[\W_]/.test(password)) score++;
            return score;
        }

        function updateStrength() {
            const pwd = passwordInput.value;
            const score = getPasswordStrength(pwd);
            let strength = '';
            let color = '';

            switch (score) {
                case 0:
                case 1:
                case 2:
                    strength = 'Weak';
                    color = '#dc3545';
                    break;
                case 3:
                    strength = 'Moderate';
                    color = '#ffc107';
                    break;
                case 4:
                    strength = 'Strong';
                    color = '#007bff';
                    break;
                case 5:
                    strength = 'Very Strong';
                    color = '#28a745';
                    break;
            }

            strengthText.textContent = pwd.length > 0 ? `Strength: ${strength}` : '';
                strengthText.style.color = color;
            }

            passwordInput.addEventListener('input', function() {
                updateStrength();
                validatePasswordMatch();
            });

            passwordConfirm.addEventListener('input', validatePasswordMatch);

            // Initialize password strength on page load if default value exists
            if (passwordInput.value) {
                updateStrength();
                validatePasswordMatch();
            }

            // Form submit handler
            document.getElementById('addStaffForm').addEventListener('submit', function(e) {
                // Validate phone number on submit
                if (!iti.isValidNumber()) {
                    e.preventDefault();
                    document.getElementById("phone-error").style.display = "block";
                    input.style.borderColor = '#dc3545';
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Phone Number',
                        text: 'Please enter a valid phone number.',
                        confirmButtonColor: '#007A33'
                    });
                    return false;
                } else {
                    var countryCode = iti.getSelectedCountryData().dialCode;
                    countryCodeInput.value = countryCode;

                    var cleanNational = getCleanNationalNumber();
                    input.value = cleanNational;

                    var fullNumber = iti.getNumber();
                    fullPhoneNumberInput.value = fullNumber;
                }

                // Show loading state
                const submitBtn = document.getElementById('submitBtnText');
                const submitBtnSpinner = document.getElementById('submitBtnSpinner');
                submitBtn.textContent = 'Adding Staff...';
                submitBtnSpinner.classList.remove('d-none');
            });
        });
    </script>
@endsection
