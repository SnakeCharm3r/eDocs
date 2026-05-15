@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    <style>
        body { background: #f4f7f9; }
        .staff-edit-page { color: #25313f; }
        .edit-workspace-shell { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 1rem; align-items: start; }
        .edit-sidebar { position: sticky; top: 18px; }
        .edit-side-panel { background: #fff; border: 1px solid #e0e7ee; border-radius: 8px; box-shadow: 0 2px 10px rgba(15,23,42,.05); overflow: hidden; }
        .edit-side-panel-header { padding: 1rem; border-bottom: 1px solid #e7edf2; background: #fbfcfd; }
        .edit-side-panel-header h6 { margin: 0; font-size: .88rem; font-weight: 700; color: #25313f; }
        .section-nav { padding: .65rem; gap: .25rem; }
        .section-nav .nav-link { font-size: .82rem; padding: .58rem .7rem; color: #596779; border-radius: 7px; display: flex; align-items: center; gap: .55rem; font-weight: 600; }
        .section-nav .nav-link:hover, .section-nav .nav-link.active { background: #edf8f1; color: #007A33; }
        .section-nav .nav-link i { width: 18px; text-align: center; font-size: .82rem; }

        .form-section { border: 1px solid #e0e7ee; border-radius: 8px; margin-bottom: 1rem; scroll-margin-top: 20px; background: #fff; box-shadow: 0 2px 10px rgba(15,23,42,.045); overflow: hidden; }
        .form-section .section-header { padding: .85rem 1rem; border-bottom: 1px solid #e7edf2; background: #fbfcfd; display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .form-section .section-header h6 { margin: 0; font-size: .92rem; font-weight: 700; color: #25313f; }
        .form-section .section-header i { font-size: .92rem; color: #007A33; }
        .form-section .section-header::after { content: 'Manage'; font-size: .68rem; font-weight: 700; text-transform: uppercase; color: #6b7788; background: #eef2f6; border-radius: 999px; padding: .18rem .55rem; }
        .form-section .section-body { padding: 1rem; }

        .edit-profile-header { background: #fff; border: 1px solid #dfe7ee; border-left: 4px solid #007A33; border-radius: 8px; padding: 1.1rem 1.2rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 12px rgba(15,23,42,.055); }
        .edit-avatar { width: 70px; height: 70px; border-radius: 8px; object-fit: cover; border: 1px solid #dce5ec; flex-shrink: 0; }
        .edit-avatar-placeholder { width: 70px; height: 70px; border-radius: 8px; background: #edf8f1; display: flex; align-items: center; justify-content: center; font-size: 1.45rem; font-weight: 800; color: #007A33; flex-shrink: 0; }
        .staff-edit-title { font-size: 1.15rem; font-weight: 800; color: #1f2933; }
        .staff-meta-pill { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid #e2e8f0; background: #f8fafc; color: #596779; border-radius: 999px; padding: .22rem .55rem; font-size: .76rem; }
        .staff-status-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
        .staff-status-card { background: #fff; border: 1px solid #e0e7ee; border-radius: 8px; padding: .75rem .85rem; box-shadow: 0 1px 8px rgba(15,23,42,.04); min-width: 0; }
        .staff-status-card span { display: block; color: #6b7788; font-size: .7rem; font-weight: 800; text-transform: uppercase; margin-bottom: .25rem; }
        .staff-status-card strong { color: #25313f; font-size: .88rem; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .staff-status-card i { color: #007A33; margin-right: .35rem; }

        .form-label { font-size: .74rem; font-weight: 800; color: #657286; margin-bottom: .32rem; text-transform: uppercase; }
        .form-control, .form-select { font-size: .86rem; border-color: #d8e1e8; border-radius: 7px; min-height: 37px; color: #25313f; }
        .form-control:focus, .form-select:focus { border-color: #007A33; box-shadow: 0 0 0 .18rem rgba(0,122,51,.12); }
        textarea.form-control { min-height: 84px; }

        .language-item { background: #fbfcfd; border: 1px solid #e4eaf0; border-radius: 8px; padding: .9rem; margin-bottom: .75rem; }
        .coi-question { background: #fbfcfd; border: 1px solid #e8edf1; border-radius: 8px; padding: .9rem; margin-bottom: .75rem; }
        .coi-question:last-child { margin-bottom: 0; }
        .coi-question-label { font-size: .84rem; color: #3b4654; margin-bottom: .55rem; font-weight: 600; line-height: 1.5; }
        .btn { border-radius: 7px; font-weight: 700; }
        .btn-primary { background: #007A33; border-color: #007A33; }
        .btn-primary:hover, .btn-primary:focus { background: #00662b; border-color: #00662b; }
        .editor-action-panel { padding: .75rem; border-top: 1px solid #e7edf2; background: #fff; }
        @media (max-width: 991.98px) {
            .edit-workspace-shell { display: block; }
            .edit-sidebar { display: none; }
            .staff-status-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575.98px) {
            .edit-profile-header { align-items: flex-start; flex-direction: column; }
            .staff-status-grid { grid-template-columns: 1fr; }
        }
    </style>
@endsection

@section('content')
<div class="page-wrapper staff-edit-page">
    <div class="content container-fluid">

        {{-- Profile mini header --}}
        <div class="edit-profile-header shadow-sm">
            <div>
                @if ($user->profile_picture)
                    <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="Profile" class="edit-avatar">
                @else
                    <div class="edit-avatar-placeholder">
                        {{ strtoupper(substr($user->fname ?? '?', 0, 1) . substr($user->lname ?? '', 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-0 staff-edit-title">{{ trim($user->fname . ' ' . $user->mname . ' ' . $user->lname) }}</h5>
                <div class="text-muted small mt-1 d-flex flex-wrap gap-3">
                    @if ($user->ccbrt_code) <span class="staff-meta-pill"><i class="fas fa-id-badge"></i>{{ $user->ccbrt_code }}</span> @endif
                    @if ($user->email) <span class="staff-meta-pill"><i class="fas fa-envelope"></i>{{ $user->email }}</span> @endif
                    @if (optional($user->department)->dept_name) <span class="staff-meta-pill"><i class="fas fa-building"></i>{{ $user->department->dept_name }}</span> @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="staff-status-grid">
            <div class="staff-status-card">
                <span><i class="fas fa-building"></i>Department</span>
                <strong>{{ optional($user->department)->dept_name ?? 'Not assigned' }}</strong>
            </div>
            <div class="staff-status-card">
                <span><i class="fas fa-briefcase"></i>Job Title</span>
                <strong>{{ optional($user->jobTitle)->job_title ?? 'Not assigned' }}</strong>
            </div>
            <div class="staff-status-card">
                <span><i class="fas fa-calendar-check"></i>Contract End</span>
                <strong>{{ $user->ending_date ?? ($staffContract->end_date ?? 'Not set') }}</strong>
            </div>
        </div>

        <div class="edit-workspace-shell">

            {{-- ===== SIDEBAR ===== --}}
            <div class="edit-sidebar d-none d-lg-block">
                    <div class="edit-side-panel">
                        <div class="edit-side-panel-header"><h6><i class="fas fa-sliders-h me-2 text-success"></i>Staff Editor</h6></div>
                        <div>
                            <nav class="nav flex-column section-nav" id="sectionNav">
                                <a class="nav-link" href="#sec-personal"><i class="fas fa-user"></i>Personal Details</a>
                                <a class="nav-link" href="#sec-professional"><i class="fas fa-briefcase"></i>Professional</a>
                                <a class="nav-link" href="#sec-contract"><i class="fas fa-file-contract"></i>Contract Details</a>
                                <a class="nav-link" href="#sec-address"><i class="fas fa-map-marker-alt"></i>Address & Files</a>
                                <a class="nav-link" href="#sec-education"><i class="fas fa-graduation-cap"></i>Education & Certificates</a>
                                <a class="nav-link" href="#sec-health"><i class="fas fa-heartbeat"></i>Health Details</a>
                                <a class="nav-link" href="#sec-language"><i class="fas fa-language"></i>Languages</a>
                                <a class="nav-link" href="#sec-conflict"><i class="fas fa-balance-scale"></i>Conflict of Interest</a>
                            </nav>
                        </div>
                    <div class="editor-action-panel d-grid">
                        <button type="submit" form="editUserForm" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary mt-2">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>

            {{-- ===== FORM ===== --}}
            <div>
                <form id="editUserForm" action="{{ route('user.update', $user->id) }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                    @csrf
                    @method('PUT')

                    {{-- PERSONAL DETAILS --}}
                    <div class="form-section" id="sec-personal">
                        <div class="section-header">
                            <i class="fas fa-user"></i>
                            <h6>Personal Details</h6>
                        </div>
                        <div class="section-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="fname" class="form-control" value="{{ old('fname', $user->fname) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="mname" class="form-control" value="{{ old('mname', $user->mname) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="lname" class="form-control" value="{{ old('lname', $user->lname) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $user->mobile) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="DOB" class="form-control" value="{{ old('DOB', $user->DOB) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-select">
                                        <option value="">Select</option>
                                        <option value="Male"   {{ old('gender', $user->gender) == 'Male'   ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender', $user->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Marital Status</label>
                                    <select name="marital_status" class="form-select">
                                        <option value="">Select</option>
                                        <option value="Single"  {{ old('marital_status', $user->marital_status) == 'Single'  ? 'selected' : '' }}>Single</option>
                                        <option value="Married" {{ old('marital_status', $user->marital_status) == 'Married' ? 'selected' : '' }}>Married</option>
                                        <option value="Divorced"{{ old('marital_status', $user->marital_status) == 'Divorced'? 'selected' : '' }}>Divorced</option>
                                        <option value="Widowed" {{ old('marital_status', $user->marital_status) == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nationality</label>
                                    <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $user->nationality) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Religion</label>
                                    <input type="text" name="religion" class="form-control" value="{{ old('religion', $user->religion) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">NIN</label>
                                    <input type="text" name="NIN" class="form-control" value="{{ old('NIN', $user->NIN) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">NSSF No</label>
                                    <input type="text" name="nssf_no" class="form-control" value="{{ old('nssf_no', $user->nssf_no) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Passport No</label>
                                    <input type="text" name="passport_no" class="form-control" value="{{ old('passport_no', $user->passport_no) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">TIN Number</label>
                                    <input type="text" name="tin_no" class="form-control" value="{{ old('tin_no', $user->tin_no) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PROFESSIONAL DETAILS --}}
                    <div class="form-section" id="sec-professional">
                        <div class="section-header">
                            <i class="fas fa-briefcase"></i>
                            <h6>Professional Details</h6>
                        </div>
                        <div class="section-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Department <span class="text-danger">*</span></label>
                                    <select name="department" id="department" class="form-select" onchange="getJobTitles()" required>
                                        <option value="" disabled selected>Select a Department</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ old('department', $user->deptId ?? '') == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->dept_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Job Title <span class="text-danger">*</span></label>
                                    <select name="job_title" id="job_title" class="form-select" required>
                                        <option value="" disabled selected>--- Select Job Title ---</option>
                                        @foreach ($jobTitles as $jt)
                                            <option value="{{ $jt->id }}" {{ old('job_title', $user->job_title ?? '') == $jt->id ? 'selected' : '' }}>
                                                {{ $jt->job_title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Employment Type <span class="text-danger">*</span></label>
                                    <select name="employment_typeId" class="form-select" required>
                                        <option value="" disabled selected>Select Employment Type</option>
                                        @foreach ($employmentTypes as $et)
                                            <option value="{{ $et->id }}" {{ old('employment_typeId', $user->employment_typeId ?? '') == $et->id ? 'selected' : '' }}>
                                                {{ $et->employment_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Professional Reg. Number</label>
                                    <input type="text" name="professional_reg_number" class="form-control" value="{{ old('professional_reg_number', $user->professional_reg_number) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CCBRT Code</label>
                                    <input type="text" name="ccbrt_code" class="form-control" placeholder="CCBRT0000" value="{{ old('ccbrt_code', $user->ccbrt_code) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Job Title (HEC Department)</label>
                                    <select name="hec_allocation" class="form-select">
                                        <option value="" disabled selected>--- Select Job Title ---</option>
                                        @foreach ($hec_title as $jt)
                                            <option value="{{ $jt->id }}" {{ old('hec_allocation', $user->hec_allocation ?? '') == $jt->id ? 'selected' : '' }}>
                                                {{ $jt->job_title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @if ($user->hasRole('hr') || $user->hasRole('super-admin'))
                                <div class="col-md-6">
                                    <label class="form-label">Assigned to approve locum?</label>
                                    <select name="approvelocum" class="form-select">
                                        <option value="" disabled selected>--- Select ---</option>
                                        <option value="1" {{ old('approvelocum', $user->approvelocum ?? '') == 1 ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ old('approvelocum', $user->approvelocum ?? '') == 0 ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- CONTRACT DETAILS --}}
                    <div class="form-section" id="sec-contract">
                        <div class="section-header">
                            <i class="fas fa-file-contract"></i>
                            <h6>Staff Contract Details</h6>
                        </div>
                        <div class="section-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Contract Start Date</label>
                                    <input type="date" name="contract[start_date]" id="contractStartDate" class="form-control" value="{{ old('contract.start_date', $staffContract->start_date ?? $user->starting_date) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Contract End Date</label>
                                    <input type="date" name="contract[end_date]" id="contractEndDate" class="form-control" value="{{ old('contract.end_date', $staffContract->end_date ?? $user->ending_date) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Contract Status</label>
                                    <select name="contract[status]" class="form-select">
                                        @foreach(['' => 'Select', 'active' => 'Active', 'pending' => 'Pending', 'expired' => 'Expired', 'terminated' => 'Terminated', 'completed' => 'Completed'] as $value => $label)
                                            <option value="{{ $value }}" {{ old('contract.status', $staffContract->status ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Duration</label>
                                    <input type="text" name="contract[duration]" id="contractDuration" class="form-control" value="{{ old('contract.duration', $staffContract->duration ?? '') }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Probation Period</label>
                                    <input type="text" name="contract[probation_period]" class="form-control" value="{{ old('contract.probation_period', $staffContract->probation_period ?? '') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ADDRESS & FILES --}}
                    <div class="form-section" id="sec-address">
                        <div class="section-header">
                            <i class="fas fa-map-marker-alt"></i>
                            <h6>Address & Files</h6>
                        </div>
                        <div class="section-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Home Address</label>
                                    <input type="text" name="home_address" class="form-control" value="{{ old('home_address', $user->home_address) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">District</label>
                                    <input type="text" name="district" class="form-control" value="{{ old('district', $user->district) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Region</label>
                                    <input type="text" name="region" class="form-control" value="{{ old('region', $user->region) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Place of Birth</label>
                                    <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth', $user->place_of_birth) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">House No</label>
                                    <input type="text" name="house_no" class="form-control" value="{{ old('house_no', $user->house_no) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Street</label>
                                    <input type="text" name="street" class="form-control" value="{{ old('street', $user->street) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Popular Landmark</label>
                                    <input type="text" name="popular_landmark" class="form-control" value="{{ old('popular_landmark', $user->popular_landmark) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Domicile</label>
                                    <input type="text" name="domicile" class="form-control" value="{{ old('domicile', $user->domicile) }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Profile Picture</label>
                                    <input type="file" name="profile_picture" class="form-control" accept="image/*">
                                    @if ($user->profile_picture)
                                        <small class="text-muted mt-1 d-block">
                                            <i class="fas fa-image me-1"></i>Current: <a href="{{ asset('storage/' . $user->profile_picture) }}" target="_blank">View photo</a>
                                        </small>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Employee CV <span class="text-muted fw-normal">(PDF, DOC)</span></label>
                                    <input type="file" name="employee_cv" class="form-control" accept=".pdf,.doc,.docx">
                                    @if ($user->employee_cv)
                                        <small class="text-muted mt-1 d-block">
                                            <i class="fas fa-file me-1"></i>Current: <a href="{{ asset('storage/' . $user->employee_cv) }}" target="_blank">Download CV</a>
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- EDUCATION & CERTIFICATES --}}
                    <div class="form-section" id="sec-education">
                        <div class="section-header">
                            <i class="fas fa-graduation-cap"></i>
                            <h6>Education & Certificates</h6>
                        </div>
                        <div class="section-body">
                            @php
                                $educationLevels = [
                                    'primary' => 'Primary Education',
                                    'o_level' => 'O-Level (Form 4)',
                                    'a_level' => 'A-Level (Form 6)',
                                    'certificate' => 'Certificate',
                                    'diploma' => 'Diploma',
                                    'degree' => 'Degree',
                                    'masters' => 'Masters',
                                    'phd' => 'PhD',
                                ];
                            @endphp
                            @foreach($educationLevels as $level => $label)
                                <div class="language-item">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 small fw-semibold">{{ $label }}</h6>
                                        <div class="d-flex gap-2 small">
                                            @if($user->{"{$level}_certificate"})
                                                <a href="{{ asset('storage/' . $user->{"{$level}_certificate"}) }}" target="_blank">Certificate</a>
                                            @endif
                                            @if(in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd']) && $user->{"{$level}_transcript"})
                                                <a href="{{ asset('storage/' . $user->{"{$level}_transcript"}) }}" target="_blank">Transcript</a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label">Institution</label>
                                            <input type="text" name="{{ $level }}_institution" class="form-control" value="{{ old("{$level}_institution", $user->{"{$level}_institution"}) }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Country</label>
                                            <input type="text" name="{{ $level }}_country" class="form-control" value="{{ old("{$level}_country", $user->{"{$level}_country"}) }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Start Year</label>
                                            <input type="number" name="{{ $level }}_start_year" min="1900" max="{{ date('Y') }}" class="form-control" value="{{ old("{$level}_start_year", $user->{"{$level}_start_year"}) }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Completion Year</label>
                                            <input type="number" name="{{ $level }}_completion_year" min="1900" max="{{ date('Y') }}" class="form-control" value="{{ old("{$level}_completion_year", $user->{"{$level}_completion_year"}) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ $label }} Certificate</label>
                                            <input type="file" name="{{ $level }}_certificate" class="form-control" accept=".pdf">
                                        </div>
                                        @if(in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd']))
                                            <div class="col-md-6">
                                                <label class="form-label">{{ $label }} Transcript</label>
                                                <input type="file" name="{{ $level }}_transcript" class="form-control" accept=".pdf">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- HEALTH DETAILS --}}
                    <div class="form-section" id="sec-health">
                        <div class="section-header">
                            <i class="fas fa-heartbeat"></i>
                            <h6>Health Details</h6>
                        </div>
                        <div class="section-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Physical Disability</label>
                                    <input type="text" name="physical_disability" class="form-control" placeholder="None"
                                        value="{{ old('physical_disability', $healthDetails->physical_disability ?? 'None') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Blood Group</label>
                                    <select name="blood_group" class="form-select">
                                        <option value="">Select</option>
                                        @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'] as $bg)
                                            <option value="{{ $bg }}" {{ old('blood_group', $healthDetails->blood_group ?? '') == $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Health Insurance</label>
                                    <select name="health_insurance" id="health_insurance" class="form-select" onchange="toggleInsuranceDetails()">
                                        <option value="">Select</option>
                                        <option value="Yes" {{ old('health_insurance', $healthDetails->health_insurance ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                        <option value="No"  {{ old('health_insurance', $healthDetails->health_insurance ?? '') == 'No'  ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="insurance_details_div" style="display:{{ old('health_insurance', $healthDetails->health_insurance ?? '') == 'Yes' ? 'block' : 'none' }};">
                                    <label class="form-label">Insurance Name</label>
                                    <input type="text" name="insur_name" class="form-control" value="{{ old('insur_name', $healthDetails->insur_name ?? '') }}">
                                </div>
                                <div class="col-md-6" id="insurance_no_div" style="display:{{ old('health_insurance', $healthDetails->health_insurance ?? '') == 'Yes' ? 'block' : 'none' }};">
                                    <label class="form-label">Insurance Number</label>
                                    <input type="text" name="insur_no" class="form-control" value="{{ old('insur_no', $healthDetails->insur_no ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Major Illness / Surgery</label>
                                    <textarea name="illness_history" class="form-control" rows="3" placeholder="None">{{ old('illness_history', $healthDetails->illness_history ?? 'None') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Allergies</label>
                                    <textarea name="allergies" class="form-control" rows="3" placeholder="None">{{ old('allergies', $healthDetails->allergies ?? 'None') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- LANGUAGE KNOWLEDGE --}}
                    <div class="form-section" id="sec-language">
                        <div class="section-header">
                            <i class="fas fa-language"></i>
                            <h6>Language Knowledge</h6>
                        </div>
                        <div class="section-body">
                            <div id="language-container">
                                @if ($languageKnowledge && $languageKnowledge->count() > 0)
                                    @foreach ($languageKnowledge as $index => $lang)
                                    <div class="language-item" data-index="{{ $index }}">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-3">
                                                <label class="form-label">Language</label>
                                                <input type="text" name="languages[{{ $index }}][language]" class="form-control" value="{{ $lang->language }}">
                                                <input type="hidden" name="languages[{{ $index }}][id]" value="{{ $lang->id }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Speaking</label>
                                                <select name="languages[{{ $index }}][speaking]" class="form-select">
                                                    <option value="">Select</option>
                                                    @foreach (['Excellent','Good','Fair','Poor'] as $lvl)
                                                        <option value="{{ $lvl }}" {{ $lang->speaking == $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Reading</label>
                                                <select name="languages[{{ $index }}][reading]" class="form-select">
                                                    <option value="">Select</option>
                                                    @foreach (['Excellent','Good','Fair','Poor'] as $lvl)
                                                        <option value="{{ $lvl }}" {{ $lang->reading == $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Writing</label>
                                                <select name="languages[{{ $index }}][writing]" class="form-select">
                                                    <option value="">Select</option>
                                                    @foreach (['Excellent','Good','Fair','Poor'] as $lvl)
                                                        <option value="{{ $lvl }}" {{ $lang->writing == $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeLanguageItem(this)">
                                                    <i class="fas fa-trash me-1"></i>Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                @else
                                    <div class="language-item text-muted small" id="language-empty-state">
                                        No language knowledge has been recorded for this staff member.
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addLanguageItem()">
                                <i class="fas fa-plus me-1"></i>Add Language
                            </button>
                        </div>
                    </div>

                    {{-- CONFLICT OF INTEREST --}}
                    <div class="form-section" id="sec-conflict">
                        <div class="section-header">
                            <i class="fas fa-balance-scale"></i>
                            <h6>Conflict of Interest Disclosure</h6>
                        </div>
                        <div class="section-body">

                            <div class="coi-question">
                                <p class="coi-question-label">Are you or a member of your immediate family an officer, director, trustee, partner, employee, or regularly retained consultant of any company that presently has business dealings with CCBRT?</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <select name="conflict_officer_role" id="conflict_officer_role" class="form-select" onchange="toggleOfficerDetails()">
                                            <option value="">Select</option>
                                            <option value="Yes" {{ old('conflict_officer_role', $user->conflict_officer_role) == 'Yes' ? 'selected' : '' }}>Yes</option>
                                            <option value="No"  {{ old('conflict_officer_role', $user->conflict_officer_role) == 'No'  ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8" id="officer_details_div" style="display:{{ old('conflict_officer_role', $user->conflict_officer_role) == 'Yes' ? 'block' : 'none' }};">
                                        <textarea name="officer_details" class="form-control" rows="2" placeholder="Company name, position held, nature of business">{{ old('officer_details', $user->officer_details) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="coi-question">
                                <p class="coi-question-label">Do you or a member of your family have a material financial interest in a company with business dealings with CCBRT?</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <select name="financial_interest" id="financial_interest" class="form-select" onchange="toggleFinancialDetails()">
                                            <option value="">Select</option>
                                            <option value="Yes" {{ old('financial_interest', $user->financial_interest) == 'Yes' ? 'selected' : '' }}>Yes</option>
                                            <option value="No"  {{ old('financial_interest', $user->financial_interest) == 'No'  ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8" id="financial_details_div" style="display:{{ old('financial_interest', $user->financial_interest) == 'Yes' ? 'block' : 'none' }};">
                                        <textarea name="financial_details" class="form-control" rows="2" placeholder="Please provide details">{{ old('financial_details', $user->financial_details) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="coi-question">
                                <p class="coi-question-label">Do you or a member of your family have any other interests that might create a conflict of interest?</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <select name="other_interests" id="other_interests" class="form-select" onchange="toggleInterestDetails()">
                                            <option value="">Select</option>
                                            <option value="Yes" {{ old('other_interests', $user->other_interests) == 'Yes' ? 'selected' : '' }}>Yes</option>
                                            <option value="No"  {{ old('other_interests', $user->other_interests) == 'No'  ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8" id="interest_details_div" style="display:{{ old('other_interests', $user->other_interests) == 'Yes' ? 'block' : 'none' }};">
                                        <textarea name="interest_details" class="form-control" rows="2" placeholder="Please provide details">{{ old('interest_details', $user->interest_details) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="coi-question">
                                <p class="coi-question-label">Please declare CCBRT as your primary employer:</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <select name="primary_employer_ccbrt" id="primary_employer_ccbrt" class="form-select" onchange="togglePrimaryEmployerDetails()">
                                            <option value="">Select</option>
                                            <option value="Yes" {{ old('primary_employer_ccbrt', $user->primary_employer_ccbrt) == 'Yes' ? 'selected' : '' }}>Yes — CCBRT is my primary employer</option>
                                            <option value="No"  {{ old('primary_employer_ccbrt', $user->primary_employer_ccbrt) == 'No'  ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8" id="primary_employer_details_div" style="display:{{ old('primary_employer_ccbrt', $user->primary_employer_ccbrt) == 'No' ? 'block' : 'none' }};">
                                        <textarea name="primary_employer_details" class="form-control" rows="2" placeholder="Please explain">{{ old('primary_employer_details', $user->primary_employer_details) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="coi-question">
                                <p class="coi-question-label">Have you ever been involved in any court proceedings?</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <select name="court_proceedings" id="court_proceedings" class="form-select" onchange="toggleCourtDetails()">
                                            <option value="">Select</option>
                                            <option value="Yes" {{ old('court_proceedings', $user->court_proceedings) == 'Yes' ? 'selected' : '' }}>Yes</option>
                                            <option value="No"  {{ old('court_proceedings', $user->court_proceedings) == 'No'  ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8" id="court_details_div" style="display:{{ old('court_proceedings', $user->court_proceedings) == 'Yes' ? 'block' : 'none' }};">
                                        <textarea name="court_details" class="form-control" rows="2" placeholder="Please provide details">{{ old('court_details', $user->court_details) }}</textarea>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Hidden signature --}}
                    <input type="hidden" name="signature" value="{{ $user->signature ?? '' }}">

                    {{-- Save bar (mobile) --}}
                    <div class="d-flex gap-2 d-lg-none mb-4">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Job titles AJAX
    function getJobTitles() {
        const deptId = $('#department').val();
        if (!deptId) { $('#job_title').html('<option value="" disabled selected>--- Select Job Title ---</option>'); return; }
        $.get('/get-job-titles/' + deptId, function(res) {
            let opts = '<option value="" disabled selected>--- Select Job Title ---</option>';
            res.jobTitles.forEach(jt => opts += `<option value="${jt.id}">${jt.job_title}</option>`);
            $('#job_title').html(opts);
        });
    }

    // Conflict of interest toggles
    function toggleOfficerDetails()        { $('#officer_details_div').toggle($('#conflict_officer_role').val() === 'Yes'); }
    function toggleFinancialDetails()      { $('#financial_details_div').toggle($('#financial_interest').val() === 'Yes'); }
    function toggleInterestDetails()       { $('#interest_details_div').toggle($('#other_interests').val() === 'Yes'); }
    function togglePrimaryEmployerDetails(){ $('#primary_employer_details_div').toggle($('#primary_employer_ccbrt').val() === 'No'); }
    function toggleCourtDetails()          { $('#court_details_div').toggle($('#court_proceedings').val() === 'Yes'); }
    function toggleInsuranceDetails() {
        const v = $('#health_insurance').val() === 'Yes';
        $('#insurance_details_div, #insurance_no_div').toggle(v);
    }

    function calculateContractDuration() {
        const startInput = document.getElementById('contractStartDate');
        const endInput = document.getElementById('contractEndDate');
        const durationInput = document.getElementById('contractDuration');
        if (!startInput || !endInput || !durationInput) return;

        if (!startInput.value || !endInput.value) {
            durationInput.value = '';
            return;
        }

        const startDate = new Date(startInput.value + 'T00:00:00');
        const endDate = new Date(endInput.value + 'T00:00:00');
        if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime()) || endDate < startDate) {
            durationInput.value = '';
            return;
        }

        let months = (endDate.getFullYear() - startDate.getFullYear()) * 12 + (endDate.getMonth() - startDate.getMonth());
        const monthAnchor = new Date(startDate);
        monthAnchor.setMonth(monthAnchor.getMonth() + months);
        if (monthAnchor > endDate) {
            months -= 1;
            monthAnchor.setMonth(monthAnchor.getMonth() - 1);
        }

        const dayMs = 24 * 60 * 60 * 1000;
        const days = Math.round((endDate - monthAnchor) / dayMs);
        const years = Math.floor(months / 12);
        months = months % 12;
        const parts = [];
        if (years > 0) parts.push(years + ' ' + (years === 1 ? 'year' : 'years'));
        if (months > 0) parts.push(months + ' ' + (months === 1 ? 'month' : 'months'));
        if (days > 0 || parts.length === 0) parts.push(days + ' ' + (days === 1 ? 'day' : 'days'));
        durationInput.value = parts.join(' ');
    }

    document.addEventListener('DOMContentLoaded', function () {
        ['contractStartDate', 'contractEndDate'].forEach(function (id) {
            const input = document.getElementById(id);
            if (input) input.addEventListener('change', calculateContractDuration);
        });
        calculateContractDuration();
    });

    // Language rows
    let langIndex = {{ ($languageKnowledge && $languageKnowledge->count() > 0) ? $languageKnowledge->count() : 1 }};
    const lvlOpts = ['Excellent','Good','Fair','Poor'].map(l => `<option value="${l}">${l}</option>`).join('');

    function addLanguageItem() {
        const i = langIndex++;
        const emptyState = document.getElementById('language-empty-state');
        if (emptyState) emptyState.remove();
        const html = `
        <div class="language-item" data-index="${i}">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Language</label>
                    <input type="text" name="languages[${i}][language]" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Speaking</label>
                    <select name="languages[${i}][speaking]" class="form-select"><option value="">Select</option>${lvlOpts}</select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Reading</label>
                    <select name="languages[${i}][reading]" class="form-select"><option value="">Select</option>${lvlOpts}</select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Writing</label>
                    <select name="languages[${i}][writing]" class="form-select"><option value="">Select</option>${lvlOpts}</select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeLanguageItem(this)">
                        <i class="fas fa-trash me-1"></i>Remove
                    </button>
                </div>
            </div>
        </div>`;
        document.getElementById('language-container').insertAdjacentHTML('beforeend', html);
    }

    function removeLanguageItem(btn) {
        const container = document.getElementById('language-container');
        btn.closest('.language-item').remove();
        if (!container.querySelector('.language-item')) {
            container.insertAdjacentHTML('beforeend', '<div class="language-item text-muted small" id="language-empty-state">No language knowledge has been recorded for this staff member.</div>');
        }
    }

    // Sidebar active link highlight on scroll
    document.addEventListener('DOMContentLoaded', function () {
        const sections = document.querySelectorAll('.form-section[id]');
        const links = document.querySelectorAll('.section-nav .nav-link');
        const observer = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    links.forEach(l => l.classList.remove('active'));
                    const active = document.querySelector(`.section-nav a[href="#${e.target.id}"]`);
                    if (active) active.classList.add('active');
                }
            });
        }, { threshold: 0.3 });
        sections.forEach(s => observer.observe(s));
    });
</script>
@endsection
