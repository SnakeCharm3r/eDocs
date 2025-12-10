@include('includes.head')
@include('sweetalert::alert')

<div class="main-wrapper login-body" style="background-color: hsl(0, 0%, 100%);">
    <div class="login-wrapper" style="background-color: #eff8f3; padding: 20px 10px;">
        <div class="container-fluid" style="max-width: 98%; padding: 0 15px;">
            <div class="loginbox row justify-content-center">
                <div class="col-12">
                    <!-- Header -->
                    <div class="text-center mb-4" style="padding: 15px 0;">
                        {{-- <h2 style="font-size: 2rem; color: #007A33; font-weight: 700; margin-bottom: 10px;">
                            <i class="fas fa-user-plus" style="margin-right: 10px;"></i> Create Your Account
                        </h2> --}}
                        <p style="color: #6c757d; font-size: 1.1rem; margin: 0; font-weight: 400;">
                            <i class="fas fa-info-circle" style="margin-right: 8px; color: #007A33;"></i>Fill in your
                            details below
                            to get started with your registration
                        </p>
                    </div>

                    @if (session('error_message'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error_message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong><i class="fas fa-exclamation-triangle"></i> Please fix the following
                                issues:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="registrationForm" action="{{ route('register.handleRegistration') }}" method="POST">
                        @csrf

                        <!-- Info Notice -->
                        <div class="alert alert-info" role="alert"
                            style="background-color: #e3f2fd; border-left: 4px solid #007A33; color: #0d47a1;">
                            <i class="fas fa-info-circle"></i> <strong>Important:</strong> Please fill in your names as
                            they appear on your National Identification Number (NIDA).
                        </div>

                        <!-- Two Column Layout -->
                        <div class="row mb-4">
                            <!-- Left Column: Personal Information -->
                            <div class="col-md-6">
                                <div class="card h-100" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <div class="card-header"
                                        style="background-color: #007A33; color: white; font-weight: 600;">
                                        <i class="fas fa-user"></i> Personal Information
                                    </div>
                                    <div class="card-body">
                                        <!-- Name Fields - Two Columns -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="fname">First Name <span
                                                            class="text-danger">*</span></label>
                                                    <input class="form-control capitalize-input" id="fname"
                                                        type="text" name="fname" required placeholder="First Name"
                                                        value="{{ old('fname') }}" style="border-color: #ced4da;"
                                                        @if ($errors->has('fname')) style="border-color: #dc3545;" @endif>
                                                    @if ($errors->has('fname'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('fname') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="mname">Middle Name</label>
                                                    <input class="form-control capitalize-input" id="mname"
                                                        type="text" name="mname" placeholder="Middle Name"
                                                        value="{{ old('mname') }}" style="border-color: #ced4da;">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="lname">Last Name <span
                                                            class="text-danger">*</span></label>
                                                    <input class="form-control capitalize-input" id="lname"
                                                        type="text" name="lname" required placeholder="Last Name"
                                                        value="{{ old('lname') }}"
                                                        @if ($errors->has('lname')) style="border-color: #dc3545;" @else style="border-color: #ced4da;" @endif>
                                                    @if ($errors->has('lname'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('lname') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="dob">Date of Birth <span
                                                            class="text-danger">*</span></label>
                                                    <input class="form-control" id="dob" type="date"
                                                        name="DOB" required value="{{ old('DOB') }}"
                                                        @if ($errors->has('DOB')) style="border-color: #dc3545;" @else style="border-color: #ced4da;" @endif
                                                        onchange="validateAge()">
                                                    <small id="dob-error" class="text-danger" style="display: none;">
                                                        <i class="fas fa-exclamation-circle"></i> You must be at least
                                                        18 years old.
                                                    </small>
                                                    @if ($errors->has('DOB'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('DOB') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Contact - Two Columns -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="phoneNumber">Phone Number <span
                                                            class="text-danger">*</span></label>
                                                    <input type="tel" id="phoneNumber" name="mobile"
                                                        class="form-control" placeholder="699990002" maxlength="13"
                                                        value="{{ old('mobile') }}" required>
                                                    <input type="hidden" id="country_code" name="country_code"
                                                        value="{{ old('country_code') }}">
                                                    <input type="hidden" id="full_phone_number"
                                                        name="full_phone_number" value="">
                                                    <small id="phone-error" class="text-danger"
                                                        style="display:none;">
                                                        <i class="fas fa-exclamation-circle"></i> Please enter a valid
                                                        phone
                                                        number.
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="email">Email <span
                                                            class="text-danger">*</span></label>
                                                    <input class="form-control" type="email" name="email"
                                                        id="email" required placeholder="abc@ccbrt.org"
                                                        value="{{ old('email') }}"
                                                        @if ($errors->has('email')) style="border-color: #dc3545;" @else style="border-color: #ced4da;" @endif>
                                                    <small id="email-message" class="text-danger"
                                                        style="display: none;"></small>
                                                    @if ($errors->has('email'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('email') }}
                                                        </small>
                                                    @endif
                                                    <small class="form-text text-muted">
                                                        <i class="fas fa-info-circle"></i> Existing staff members
                                                        should use their CCBRT email address.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Password Fields - Two Columns -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3 position-relative">
                                                    <label for="password">Password <span
                                                            class="text-danger">*</span></label>
                                                    <input class="form-control" type="password" name="password"
                                                        id="password" required placeholder="Enter password"
                                                        style="border-color: #ced4da; padding-right: 40px;">
                                                    <span class="password-toggle" id="togglePassword"
                                                        style="position: absolute; right: 12px; top: 38px; cursor: pointer; color: #666;">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                    <small id="password-strength-text"
                                                        style="display: block; margin-top: 5px; font-weight: 600;"></small>
                                                    <small class="form-text text-muted">Password must be at least 6
                                                        characters long.</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3 position-relative">
                                                    <label for="password_confirmation">Confirm Password <span
                                                            class="text-danger">*</span></label>
                                                    <input class="form-control" type="password"
                                                        name="password_confirmation" id="password_confirmation"
                                                        required placeholder="Confirm password"
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
                                    <div class="card-header"
                                        style="background-color: #007A33; color: white; font-weight: 600;">
                                        <i class="fas fa-briefcase"></i> Employment Details
                                    </div>
                                    <div class="card-body">
                                        <!-- Employment Type and Department - Two Columns -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="employment_typeId">Employment Type <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-control" name="employment_typeId"
                                                        id="employment_typeId" required
                                                        @if ($errors->has('employment_typeId')) style="border-color: #dc3545;" @else style="border-color: #ced4da;" @endif>
                                                        <option value="">Select Employment Type</option>
                                                        @foreach ($employmentTypes as $employmentType)
                                                            <option value="{{ $employmentType->id }}"
                                                                {{ old('employment_typeId') == $employmentType->id ? 'selected' : '' }}>
                                                                {{ $employmentType->employment_type }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if ($errors->has('employment_typeId'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('employment_typeId') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="deptId">Department <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-control" name="deptId" id="deptId"
                                                        required
                                                        @if ($errors->has('deptId')) style="border-color: #dc3545;" @else style="border-color: #ced4da;" @endif>
                                                        <option value="">Select Department</option>
                                                        @foreach ($departments as $department)
                                                            <option value="{{ $department->id }}"
                                                                {{ old('deptId') == $department->id ? 'selected' : '' }}>
                                                                {{ $department->dept_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if ($errors->has('deptId'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('deptId') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Job Title and Professional Reg - Two Columns -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="job_title">Job Title <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-control" id="job_title" name="job_title"
                                                        required
                                                        @if ($errors->has('job_title')) style="border-color: #dc3545;" @else style="border-color: #ced4da;" @endif
                                                        disabled>
                                                        <option value="">Select Department First</option>
                                                    </select>
                                                    <small class="form-text text-muted">Please select a department
                                                        first to load job titles.</small>
                                                    @if ($errors->has('job_title'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('job_title') }}
                                                        </small>
                                                    @endif
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
                                                    <input class="form-control" type="text"
                                                        name="professional_reg_number" id="professional_reg_number"
                                                        placeholder="e.g. PH123456 or 12345"
                                                        value="{{ old('professional_reg_number') }}" maxlength="50"
                                                        @if ($errors->has('professional_reg_number')) style="border-color: #dc3545;" @else style="border-color: #ced4da;" @endif>
                                                    <small class="form-text text-muted" id="professional_reg_help">

                                                    </small>
                                                    @if ($errors->has('professional_reg_number'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('professional_reg_number') }}
                                                        </small>
                                                    @endif
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
                                                    <select class="form-control" name="license_provider"
                                                        id="license_provider"
                                                        @if ($errors->has('license_provider')) style="border-color: #dc3545;" @else style="border-color: #ced4da;" @endif>
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
                                                        Select the organization that issued your professional license.
                                                    </small>
                                                    @if ($errors->has('license_provider'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('license_provider') }}
                                                        </small>
                                                    @endif
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
                                                        id="starting_date" required
                                                        value="{{ old('starting_date') }}"
                                                        @if ($errors->has('starting_date')) style="border-color: #dc3545;" @else style="border-color: #ced4da;" @endif>
                                                    @if ($errors->has('starting_date'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('starting_date') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="ending_date">Ending Date <span
                                                            class="text-danger">*</span></label>
                                                    <input class="form-control" type="date" name="ending_date"
                                                        id="ending_date" required value="{{ old('ending_date') }}"
                                                        @if ($errors->has('ending_date')) style="border-color: #dc3545;" @else style="border-color: #ced4da;" @endif>
                                                    @if ($errors->has('ending_date'))
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                            {{ $errors->first('ending_date') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="text-center mt-4 mb-4">
                            <p class="text-muted mb-3" style="font-size: 0.9rem;">
                                By clicking <strong>Sign up</strong>, you agree to CCBRT
                                <a href="#" class="text-success fw-semibold text-decoration-none">Terms</a> and
                                <a href="#" class="text-success fw-semibold text-decoration-none">Privacy
                                    Policy</a>.
                            </p>

                            <button class="btn btn-success btn-lg px-5 fw-semibold" type="button"
                                id="openAgreementsModal"
                                style="background-color: #007A33; border-color: #007A33; min-width: 150px;">
                                <i class="fas fa-user-plus"></i> <span id="submitBtnText">Sign up</span>
                                <span id="submitBtnSpinner" class="spinner-border spinner-border-sm d-none"
                                    role="status" aria-hidden="true"></span>
                            </button>

                            <p class="text-muted mt-3 mb-0" style="font-size: 0.9rem;">
                                Already registered?
                                <a href="{{ route('login') }}" class="text-success fw-semibold text-decoration-none">
                                    <i class="fas fa-sign-in-alt"></i> Log in here
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- User Agreements Modal -->
    <style>
        /* Maximize registration form width */
        .login-wrapper {
            width: 100% !important;
            max-width: 100% !important;
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        .loginbox {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
        }

        .container-fluid {
            width: 100% !important;
            max-width: 98% !important;
            padding-left: 15px !important;
            padding-right: 15px !important;
        }

        .modal-content {
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .policy-details {
            margin-bottom: 1.5rem;
        }

        .policy-details h6 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .policy-content {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            font-size: 1rem;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .modal-footer {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

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

        .form-check-input:checked {
            background-color: #007A33;
            border-color: #007A33;
        }

        #accept-container {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #007A33;
            max-width: 500px;
            margin: 20px auto;
            transition: all 0.3s ease;
        }

        #accept-container:hover {
            border-color: #005a25;
            box-shadow: 0 2px 8px rgba(0, 122, 51, 0.1);
        }

        .card {
            transition: box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }

        /* Two Column Layout Styling */
        @media (min-width: 768px) {
            .col-md-6 .card {
                height: 100%;
            }
        }

        @media (max-width: 767px) {
            .col-md-6 {
                margin-bottom: 1.5rem;
            }
        }
    </style>

    <div class="modal fade" id="userAgreementsModal" tabindex="-1" role="dialog"
        aria-labelledby="userAgreementsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userAgreementsModalLabel">User Agreements and Policies</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="policy-details">
                        <h6>Title</h6>
                        <p id="policy-title" class="fw-bold"></p>
                    </div>
                    <div class="policy-content" id="policy-content">
                        <!-- Policy content will be loaded here -->
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <button id="prev-policy" class="btn btn-outline-secondary" disabled>
                            <i class="fas fa-chevron-left"></i> Back
                        </button>
                        <div class="policy-counter" style="font-size: 16px; color: #666;">
                            Policy <span id="policy-current">1</span> of <span
                                id="policy-total">{{ $policies->count() }}</span>
                        </div>
                        <button id="next-policy" class="btn btn-outline-secondary"
                            {{ $policies->count() > 1 ? '' : 'disabled' }}>
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="form-check mt-3" id="accept-container">
                        <input class="form-check-input" type="checkbox" id="acceptCheckbox"
                            style="width: 18px; height: 18px; margin-right: 10px; cursor: pointer;">
                        <label class="form-check-label" for="acceptCheckbox" style="cursor: pointer;">
                            I have read and accept the <a href="#"
                                class="text-success fw-semibold text-decoration-none">User Agreements and Policies</a>.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="acceptAgreements" disabled
                        style="background-color: #007A33; border-color: #007A33;">
                        <i class="fas fa-check"></i> Accept & Submit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

                    // Phone Number Input with Validation
                    var input = document.querySelector("#phoneNumber");
                    var countryCodeInput = document.querySelector("#country_code");
                    var fullPhoneNumberInput = document.querySelector("#full_phone_number");
                    var phoneError = document.querySelector("#phone-error");
                    var iti = window.intlTelInput(input, {
                        initialCountry: "tz",
                        separateDialCode: true,
                        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                        nationalMode: true,
                        maxLength: 13
                    });
                    
                    // Phone number validation function
                    function validatePhoneNumber() {
                        if (!input.value.trim()) {
                            phoneError.textContent = 'Phone number is required.';
                            phoneError.style.display = 'block';
                            input.style.borderColor = '#dc3545';
                            return false;
                        }
                        
                        if (iti.isValidNumber()) {
                            phoneError.style.display = 'none';
                            input.style.borderColor = '#ced4da';
                            return true;
                        } else {
                            var errorCode = iti.getValidationError();
                            var errorMessage = 'Please enter a valid phone number.';
                            
                            switch(errorCode) {
                                case 0: // Too short
                                    errorMessage = 'Phone number is too short.';
                                    break;
                                case 1: // Too long
                                    errorMessage = 'Phone number is too long.';
                                    break;
                                case 2: // Invalid country code
                                    errorMessage = 'Invalid country code.';
                                    break;
                                case 3: // Invalid number
                                    errorMessage = 'Invalid phone number format.';
                                    break;
                            }
                            
                            phoneError.textContent = errorMessage;
                            phoneError.style.display = 'block';
                            input.style.borderColor = '#dc3545';
                            return false;
                        }
                    }
                    
                    // Validate phone on input and blur
                    input.addEventListener('blur', validatePhoneNumber);
                    input.addEventListener('input', function() {
                        if (input.value.trim() && iti.isValidNumber()) {
                            phoneError.style.display = 'none';
                            input.style.borderColor = '#ced4da';
                        }
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
                    const minAgeDate = new Date(currentDate.getFullYear() - 18, currentDate.getMonth(), currentDate
                        .getDate());

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

                    // Reset job title dropdown
                    jobTitleSelect.empty().append('<option value="">Select Job Title</option>');
                    jobTitleSelect.prop('disabled', true);
                    jobTitleSelect.removeAttr('required');
                    jobTitleSelect.css('border-color', '#ced4da');
                    
                    // Clear any previous job title selection
                    $('#professional_reg_number').removeAttr('required');
                    $('#professional_reg_required').hide();
                    $('#professional_reg_optional').show();
                    $('#license_provider_row').hide();
                    $('#license_provider').removeAttr('required');

                    if (deptId) {
                        jobTitleSelect.html('<option value="">Loading job titles...</option>');
                        jobTitleSelect.prop('disabled', true);

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
                                    jobTitleSelect.css('border-color', '#ced4da');
                                    
                                    // Show message that job title is required
                                    jobTitleSelect.next('small').text('Please select a job title.');

                                    @if (old('job_title'))
                                        jobTitleSelect.val('{{ old('job_title') }}');
                                        jobTitleSelect.trigger('change');
                                    @endif
                                } else {
                                    jobTitleSelect.append(
                                        '<option value="">No job titles available for this department</option>');
                                    jobTitleSelect.prop('disabled', true);
                                    jobTitleSelect.removeAttr('required');
                                    jobTitleSelect.next('small').text('No job titles found for the selected department.');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error fetching job titles:', error);
                                jobTitleSelect.empty().append(
                                    '<option value="">Error loading job titles</option>');
                                jobTitleSelect.prop('disabled', true);
                                jobTitleSelect.removeAttr('required');
                                jobTitleSelect.next('small').text('Failed to load job titles. Please try again.');
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
                        jobTitleSelect.next('small').text('Please select a department first to load job titles.');
                    }
                });
                
                // Validate job title selection
                jobTitleSelect.on('change', function() {
                    if ($(this).val()) {
                        $(this).css('border-color', '#ced4da');
                    } else if ($('#deptId').val()) {
                        $(this).css('border-color', '#dc3545');
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

                    // Policy Modal Logic
                    var policies = @json($policies);
                    var currentPolicyIndex = 0;

                    function updatePolicyDisplay() {
                        if (policies.length > 0) {
                            var policy = policies[currentPolicyIndex];
                            document.getElementById('policy-title').textContent = policy.title || 'No Title';
                            document.getElementById('policy-content').innerHTML = policy.content ||
                                '<p>No content available.</p>';
                            document.getElementById('policy-current').textContent = currentPolicyIndex + 1;
                            document.getElementById('policy-total').textContent = policies.length;
                            document.getElementById('prev-policy').disabled = currentPolicyIndex === 0;
                            document.getElementById('next-policy').disabled = currentPolicyIndex === policies.length - 1;
                            document.getElementById('accept-container').style.display = currentPolicyIndex === policies
                                .length - 1 ? 'flex' : 'none';
                            document.getElementById('acceptCheckbox').checked = false;
                            document.getElementById('acceptAgreements').disabled = true;
                        } else {
                            document.getElementById('policy-title').textContent = 'No Policies Available';
                            document.getElementById('policy-content').innerHTML = '<p>No policies available.</p>';
                            document.getElementById('policy-current').textContent = '0';
                            document.getElementById('policy-total').textContent = '0';
                            document.getElementById('prev-policy').disabled = true;
                            document.getElementById('next-policy').disabled = true;
                            document.getElementById('accept-container').style.display = 'flex';
                        }
                    }

                    document.getElementById('next-policy').addEventListener('click', function() {
                        if (currentPolicyIndex < policies.length - 1) {
                            currentPolicyIndex++;
                            updatePolicyDisplay();
                        }
                    });

                    document.getElementById('prev-policy').addEventListener('click', function() {
                        if (currentPolicyIndex > 0) {
                            currentPolicyIndex--;
                            updatePolicyDisplay();
                        }
                    });

                    document.getElementById('acceptCheckbox').addEventListener('change', function() {
                        document.getElementById('acceptAgreements').disabled = !this.checked;
                    });

                    document.getElementById('acceptAgreements').addEventListener('click', function() {
                        if (document.getElementById('acceptCheckbox').checked) {
                            // Close modal first
                            document.getElementById('userAgreementsModal').querySelector('.btn-close').click();

                            // Show loading state
                            const submitBtn = document.getElementById('openAgreementsModal');
                            const submitBtnText = document.getElementById('submitBtnText');
                            const submitBtnSpinner = document.getElementById('submitBtnSpinner');

                            // Disable button during submission
                            submitBtn.disabled = true;
                            submitBtnText.textContent = 'Submitting...';
                            submitBtnSpinner.classList.remove('d-none');

                            // Submit the form
                            document.getElementById('registrationForm').submit();
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Acceptance Required',
                                text: 'You must accept the User Agreements and Policies to register.',
                                confirmButtonColor: '#007A33'
                            });
                        }
                    });

                    updatePolicyDisplay();

                    // Re-enable submit button if there are validation errors on page load
                    // This ensures users can fix errors and resubmit
                    @if ($errors->any())
                        // Re-enable button when validation errors are present
                        const submitBtn = document.getElementById('openAgreementsModal');
                        const submitBtnText = document.getElementById('submitBtnText');
                        const submitBtnSpinner = document.getElementById('submitBtnSpinner');

                        if (submitBtn) {
                            submitBtn.disabled = false;
                        }
                        if (submitBtnText) {
                            submitBtnText.textContent = 'Sign up';
                        }
                        if (submitBtnSpinner) {
                            submitBtnSpinner.classList.add('d-none');
                        }

                        // Scroll to error message to make it visible
                        setTimeout(function() {
                            const errorAlert = document.querySelector('.alert-danger');
                            if (errorAlert) {
                                errorAlert.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                            }
                        }, 100);
                    @endif

                    // Form Validation and Modal Trigger
                    document.getElementById('openAgreementsModal').addEventListener('click', function(event) {
                        const requiredInputs = document.querySelectorAll('[required]');
                        let isValid = true;
                        let firstError = null;

                        // Phone number formatting - ensure country code is set
                        if (input && input.value && iti) {
                            var countryCode = iti.getSelectedCountryData().dialCode;
                            countryCodeInput.value = countryCode;

                            // Store full number in hidden field for reference
                            var fullNumber = iti.getNumber();
                            fullPhoneNumberInput.value = fullNumber;
                        }

                        requiredInputs.forEach(function(input) {
                            if (!input || input.type === 'hidden') return;
                            if (!input.value || (input.type === 'select-one' && input.value === '')) {
                                if (input.style) {
                                    input.style.borderColor = '#dc3545';
                                }
                                isValid = false;
                                if (!firstError) firstError = input;
                            } else {
                                if (input.style) {
                                    input.style.borderColor = '#ced4da';
                                }
                            }
                        });

                        // Validate phone number
                        if (!validatePhoneNumber()) {
                            isValid = false;
                            if (!firstError) firstError = input;
                        }
                        
                        // Validate job title is selected after department
                        const deptId = $('#deptId').val();
                        const jobTitle = $('#job_title').val();
                        if (deptId && !jobTitle) {
                            isValid = false;
                            $('#job_title').css('border-color', '#dc3545');
                            if (!firstError) firstError = document.getElementById('job_title');
                        } else if (jobTitle) {
                            $('#job_title').css('border-color', '#ced4da');
                        }
                        
                        if (!validateEmail()) {
                            isValid = false;
                            if (!firstError) firstError = document.getElementById('email');
                        }
                        if (!validateAge()) {
                            isValid = false;
                            if (!firstError) firstError = document.getElementById('dob');
                        }
                        if (!validatePasswordMatch()) {
                            isValid = false;
                            if (!firstError) firstError = document.getElementById('password_confirmation');
                        }

                        if (isValid) {
                            var modal = new bootstrap.Modal(document.getElementById('userAgreementsModal'));
                            modal.show();
                        } else {
                            if (firstError) {
                                firstError.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                                firstError.focus();
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                text: 'Please correct the errors in the form before proceeding.',
                                confirmButtonColor: '#007A33'
                            });
                        }
                    });

                    // Form submit handler
                    const registrationForm = document.getElementById('registrationForm');
                    if (registrationForm) {
                        registrationForm.addEventListener('submit', function(e) {
                            // Phone number formatting - ensure country code is set
                            if (input && input.value && iti) {
                                try {
                                    // Ensure country code is set
                                    var countryCode = iti.getSelectedCountryData().dialCode;
                                    if (countryCodeInput) {
                                        countryCodeInput.value = countryCode;
                                    }

                                    // Store full number in hidden field for reference
                                    var fullNumber = iti.getNumber();
                                    if (fullPhoneNumberInput) {
                                        fullPhoneNumberInput.value = fullNumber;
                                    }
                                } catch (err) {
                                    console.error('Error processing phone number:', err);
                                }
                            }
                        });
                    }
        }); // Close DOMContentLoaded
    </script>
</div>
