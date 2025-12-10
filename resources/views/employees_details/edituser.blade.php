@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <h3 class="page-title mb-0">
                                <i class="fas fa-user-edit me-2"></i>Edit User Details
                            </h3>
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Info Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            @if($user->profile_picture)
                                <img src="{{ asset('storage/' . $user->profile_picture) }}" 
                                     alt="Profile Picture" 
                                     class="rounded-circle" 
                                     style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #28a745;">
                            @else
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 100px; height: 100px; font-size: 2.5rem;">
                                    {{ strtoupper(substr($user->fname, 0, 1) . substr($user->lname, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="col-md-10">
                            <h4 class="mb-1">{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</h4>
                            <p class="text-muted mb-1">
                                <i class="fas fa-id-card me-2"></i><strong>CCBRT Code:</strong> {{ $user->ccbrt_code ?? 'N/A' }}
                            </p>
                            <p class="text-muted mb-1">
                                <i class="fas fa-envelope me-2"></i><strong>Email:</strong> {{ $user->email }}
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-building me-2"></i><strong>Department:</strong> {{ optional($user->department)->dept_name ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Messages -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('user.update', $user->id) }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                @csrf
                @method('PUT')

                <!-- PERSONAL DETAILS -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Personal Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="fname" class="form-label">First Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="fname" id="fname" class="form-control"
                                    value="{{ old('fname', $user->fname) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label for="mname" class="form-label">Middle Name</label>
                                <input type="text" name="mname" id="mname" class="form-control"
                                    value="{{ old('mname', $user->mname) }}">
                            </div>


                            <div class="col-md-4">
                                <label for="lname" class="form-label">Last Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="lname" id="lname" class="form-control"
                                    value="{{ old('lname', $user->lname) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label for="username" class="form-label">Username <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control"
                                    value="{{ old('username', $user->username) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label for="DOB" class="form-label">Date of Birth</label>
                                <input type="date" name="DOB" id="DOB" class="form-control"
                                    value="{{ old('DOB', $user->DOB) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male" {{ old('gender', $user->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender', $user->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="marital_status" class="form-label">Marital Status</label>
                                <select class="form-select" id="marital_status" name="marital_status">
                                    <option value="">Select Marital Status</option>
                                    <option value="Single" {{ old('marital_status', $user->marital_status) == 'Single' ? 'selected' : '' }}>Single</option>
                                    <option value="Married" {{ old('marital_status', $user->marital_status) == 'Married' ? 'selected' : '' }}>Married</option>
                                    <option value="Divorced" {{ old('marital_status', $user->marital_status) == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                                    <option value="Widowed" {{ old('marital_status', $user->marital_status) == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="nationality" class="form-label">Nationality</label>
                                <input type="text" name="nationality" id="nationality" class="form-control"
                                    value="{{ old('nationality', $user->nationality) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="religion" class="form-label">Religion</label>
                                <input type="text" name="religion" id="religion" class="form-control"
                                    value="{{ old('religion', $user->religion) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="passport_no" class="form-label">Passport No</label>
                                <input type="text" name="passport_no" id="passport_no" class="form-control"
                                    value="{{ old('passport_no', $user->passport_no) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="tin_no" class="form-label">TIN Number</label>
                                <input type="text" name="tin_no" id="tin_no" class="form-control"
                                    value="{{ old('tin_no', $user->tin_no) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="mobile" class="form-label">Mobile</label>
                                <input type="text" name="mobile" id="mobile" class="form-control"
                                    value="{{ old('mobile', $user->mobile) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="NIN" class="form-label">NIN</label>
                                <input type="text" name="NIN" id="NIN" class="form-control"
                                    value="{{ old('NIN', $user->NIN) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="nssf_no" class="form-label">NSSF No</label>
                                <input type="text" name="nssf_no" id="nssf_no" class="form-control"
                                    value="{{ old('nssf_no', $user->nssf_no) }}">
                            </div>
                            <div class="col-md-4">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control"
                                    value="{{ old('email', $user->email) }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PROFESSIONAL DETAILS -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-briefcase me-2"></i>Professional Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="department" name="department" onchange="getJobTitles()"
                                    required>
                                    <option value="" disabled selected>Select a Department</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}"
                                            {{ old('department', $user->deptId ?? '') == $department->id ? 'selected' : '' }}>
                                            {{ $department->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="job_title" class="form-label">Job Title <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="job_title" name="job_title" required>
                                    <option value="" disabled selected>--- Select Job Title ---</option>
                                    @foreach ($jobTitles as $jobTitle)
                                        <option value="{{ $jobTitle->id }}"
                                            {{ old('job_title', $user->job_title ?? '') == $jobTitle->id ? 'selected' : '' }}>
                                            {{ $jobTitle->job_title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="employment_typeId" class="form-label">Employment Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="employment_typeId" name="employment_typeId" required>
                                    <option value="" disabled selected>Select Employment Type</option>
                                    @foreach ($employmentTypes as $employmentType)
                                        <option value="{{ $employmentType->id }}"
                                            {{ old('employment_typeId', $user->employment_typeId ?? '') == $employmentType->id ? 'selected' : '' }}>
                                            {{ $employmentType->employment_type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="professional_reg_number" class="form-label">Professional Reg. Number</label>
                                <input type="text" name="professional_reg_number" id="professional_reg_number"
                                    class="form-control"
                                    value="{{ old('professional_reg_number', $user->professional_reg_number) }}">
                            </div>

                            <div class="col-md-6">
                                <label for="ccbrt_code" class="form-label">CCBRT Code</label>
                                <input type="text" name="ccbrt_code" id="ccbrt_code" class="form-control"
                                    placeholder="CCBRT0000" value="{{ old('ccbrt_code', $user->ccbrt_code) }}">
                            </div>

                            <div class="col-md-6">
                                <label for="hec_allocation" class="form-label">Job Title (HEC Department)</label>
                                <select class="form-select" id="hec_allocation" name="hec_allocation">
                                    <option value="" disabled selected>--- Select Job Title ---</option>
                                    @foreach ($hec_title as $jobTitle)
                                        <option value="{{ $jobTitle->id }}"
                                            {{ old('hec_allocation', $user->hec_allocation ?? '') == $jobTitle->id ? 'selected' : '' }}>
                                            {{ $jobTitle->job_title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($user->hasRole('hr') || $user->hasRole('super-admin'))
                                <div class="col-md-6">
                                    <label for="approvelocum" class="form-label">Is assigned to approve locum?</label>
                                    <select class="form-select" id="approvelocum" name="approvelocum">
                                        <option value="" disabled selected>--- Select ---</option>
                                        <option value="1"
                                            {{ old('approvelocum', $user->approvelocum ?? '') == 1 ? 'selected' : '' }}>Yes
                                        </option>
                                        <option value="0"
                                            {{ old('approvelocum', $user->approvelocum ?? '') == 0 ? 'selected' : '' }}>No
                                        </option>
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- ADDRESS & OTHER DETAILS -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>Address & Other Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="home_address" class="form-label">Home Address</label>
                                <input type="text" name="home_address" id="home_address" class="form-control"
                                    value="{{ old('home_address', $user->home_address) }}">
                            </div>

                            <div class="col-md-6">
                                <label for="district" class="form-label">District</label>
                                <input type="text" name="district" id="district" class="form-control"
                                    value="{{ old('district', $user->district) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="region" class="form-label">Region</label>
                                <input type="text" name="region" id="region" class="form-control"
                                    value="{{ old('region', $user->region) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="place_of_birth" class="form-label">Place of Birth</label>
                                <input type="text" name="place_of_birth" id="place_of_birth" class="form-control"
                                    value="{{ old('place_of_birth', $user->place_of_birth) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="house_no" class="form-label">House No</label>
                                <input type="text" name="house_no" id="house_no" class="form-control"
                                    value="{{ old('house_no', $user->house_no) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="popular_landmark" class="form-label">Popular Landmark</label>
                                <input type="text" name="popular_landmark" id="popular_landmark" class="form-control"
                                    value="{{ old('popular_landmark', $user->popular_landmark) }}">
                            </div>

                            <div class="col-md-4">
                                <label for="street" class="form-label">Street</label>
                                <input type="text" name="street" id="street" class="form-control"
                                    value="{{ old('street', $user->street) }}">
                            </div>

                            <div class="col-md-6">
                                <label for="domicile" class="form-label">Domicile</label>
                                <input type="text" name="domicile" id="domicile" class="form-control"
                                    value="{{ old('domicile', $user->domicile) }}">
                            </div>

                            <div class="col-md-6">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                <input type="file" name="profile_picture" id="profile_picture" class="form-control"
                                    accept="image/*">
                                @if ($user->profile_picture)
                                    <small class="text-muted mt-2 d-block">Current: <a href="{{ asset('storage/' . $user->profile_picture) }}" target="_blank">View</a></small>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="starting_date" class="form-label">Starting Date</label>
                                <input type="date" name="starting_date" id="starting_date" class="form-control"
                                    value="{{ old('starting_date', $user->starting_date) }}">
                            </div>

                            <div class="col-md-6">
                                <label for="ending_date" class="form-label">Ending Date</label>
                                <input type="date" name="ending_date" id="ending_date" class="form-control"
                                    value="{{ old('ending_date', $user->ending_date) }}">
                            </div>

                            <div class="col-md-12">
                                <label for="employee_cv" class="form-label">Employee CV (PDF, DOC)</label>
                                <input type="file" name="employee_cv" id="employee_cv" class="form-control"
                                    accept=".pdf,.doc,.docx">

                                @if ($user->employee_cv)
                                    <a href="{{ asset('storage/' . $user->employee_cv) }}" target="_blank"
                                        class="mt-2 d-block">
                                        Download current CV
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HEALTH DETAILS -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-heartbeat me-2"></i>Health Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="physical_disability" class="form-label">Physical Disability</label>
                                <input type="text" name="physical_disability" id="physical_disability" class="form-control"
                                    value="{{ old('physical_disability', $healthDetails->physical_disability ?? 'None') }}" placeholder="None">
                            </div>
                            <div class="col-md-4">
                                <label for="blood_group" class="form-label">Blood Group</label>
                                <select class="form-select" id="blood_group" name="blood_group">
                                    <option value="">Select Blood Group</option>
                                    <option value="A+" {{ old('blood_group', $healthDetails->blood_group ?? '') == 'A+' ? 'selected' : '' }}>A+</option>
                                    <option value="A-" {{ old('blood_group', $healthDetails->blood_group ?? '') == 'A-' ? 'selected' : '' }}>A-</option>
                                    <option value="B+" {{ old('blood_group', $healthDetails->blood_group ?? '') == 'B+' ? 'selected' : '' }}>B+</option>
                                    <option value="B-" {{ old('blood_group', $healthDetails->blood_group ?? '') == 'B-' ? 'selected' : '' }}>B-</option>
                                    <option value="AB+" {{ old('blood_group', $healthDetails->blood_group ?? '') == 'AB+' ? 'selected' : '' }}>AB+</option>
                                    <option value="AB-" {{ old('blood_group', $healthDetails->blood_group ?? '') == 'AB-' ? 'selected' : '' }}>AB-</option>
                                    <option value="O+" {{ old('blood_group', $healthDetails->blood_group ?? '') == 'O+' ? 'selected' : '' }}>O+</option>
                                    <option value="O-" {{ old('blood_group', $healthDetails->blood_group ?? '') == 'O-' ? 'selected' : '' }}>O-</option>
                                    <option value="Unknown" {{ old('blood_group', $healthDetails->blood_group ?? '') == 'Unknown' ? 'selected' : '' }}>Unknown</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="health_insurance" class="form-label">Health Insurance</label>
                                <select class="form-select" id="health_insurance" name="health_insurance" onchange="toggleInsuranceDetails()">
                                    <option value="">Select</option>
                                    <option value="Yes" {{ old('health_insurance', $healthDetails->health_insurance ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('health_insurance', $healthDetails->health_insurance ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="insurance_details_div" style="display: {{ old('health_insurance', $healthDetails->health_insurance ?? '') == 'Yes' ? 'block' : 'none' }};">
                                <label for="insur_name" class="form-label">Insurance Name</label>
                                <input type="text" name="insur_name" id="insur_name" class="form-control"
                                    value="{{ old('insur_name', $healthDetails->insur_name ?? '') }}">
                            </div>
                            <div class="col-md-6" id="insurance_no_div" style="display: {{ old('health_insurance', $healthDetails->health_insurance ?? '') == 'Yes' ? 'block' : 'none' }};">
                                <label for="insur_no" class="form-label">Insurance Number</label>
                                <input type="text" name="insur_no" id="insur_no" class="form-control"
                                    value="{{ old('insur_no', $healthDetails->insur_no ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="illness_history" class="form-label">Major Illness/Surgery</label>
                                <textarea name="illness_history" id="illness_history" class="form-control" rows="3" placeholder="None">{{ old('illness_history', $healthDetails->illness_history ?? 'None') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="allergies" class="form-label">Allergies</label>
                                <textarea name="allergies" id="allergies" class="form-control" rows="3" placeholder="None">{{ old('allergies', $healthDetails->allergies ?? 'None') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LANGUAGE KNOWLEDGE -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-language me-2"></i>Language Knowledge
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="language-container">
                            @if($languageKnowledge && $languageKnowledge->count() > 0)
                                @foreach($languageKnowledge as $index => $lang)
                                    <div class="language-item mb-3 p-3 border rounded" data-index="{{ $index }}">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Language</label>
                                                <input type="text" name="languages[{{ $index }}][language]" class="form-control" value="{{ $lang->language }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Speaking</label>
                                                <select name="languages[{{ $index }}][speaking]" class="form-select" required>
                                                    <option value="">Select</option>
                                                    <option value="Excellent" {{ $lang->speaking == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                    <option value="Good" {{ $lang->speaking == 'Good' ? 'selected' : '' }}>Good</option>
                                                    <option value="Fair" {{ $lang->speaking == 'Fair' ? 'selected' : '' }}>Fair</option>
                                                    <option value="Poor" {{ $lang->speaking == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Reading</label>
                                                <select name="languages[{{ $index }}][reading]" class="form-select" required>
                                                    <option value="">Select</option>
                                                    <option value="Excellent" {{ $lang->reading == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                    <option value="Good" {{ $lang->reading == 'Good' ? 'selected' : '' }}>Good</option>
                                                    <option value="Fair" {{ $lang->reading == 'Fair' ? 'selected' : '' }}>Fair</option>
                                                    <option value="Poor" {{ $lang->reading == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Writing</label>
                                                <select name="languages[{{ $index }}][writing]" class="form-select" required>
                                                    <option value="">Select</option>
                                                    <option value="Excellent" {{ $lang->writing == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                                                    <option value="Good" {{ $lang->writing == 'Good' ? 'selected' : '' }}>Good</option>
                                                    <option value="Fair" {{ $lang->writing == 'Fair' ? 'selected' : '' }}>Fair</option>
                                                    <option value="Poor" {{ $lang->writing == 'Poor' ? 'selected' : '' }}>Poor</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-danger btn-sm remove-language" onclick="removeLanguageItem(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" name="languages[{{ $index }}][id]" value="{{ $lang->id }}">
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="language-item mb-3 p-3 border rounded" data-index="0">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Language</label>
                                            <input type="text" name="languages[0][language]" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Speaking</label>
                                            <select name="languages[0][speaking]" class="form-select" required>
                                                <option value="">Select</option>
                                                <option value="Excellent">Excellent</option>
                                                <option value="Good">Good</option>
                                                <option value="Fair">Fair</option>
                                                <option value="Poor">Poor</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Reading</label>
                                            <select name="languages[0][reading]" class="form-select" required>
                                                <option value="">Select</option>
                                                <option value="Excellent">Excellent</option>
                                                <option value="Good">Good</option>
                                                <option value="Fair">Fair</option>
                                                <option value="Poor">Poor</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Writing</label>
                                            <select name="languages[0][writing]" class="form-select" required>
                                                <option value="">Select</option>
                                                <option value="Excellent">Excellent</option>
                                                <option value="Good">Good</option>
                                                <option value="Fair">Fair</option>
                                                <option value="Poor">Poor</option>
                                            </select>
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-danger btn-sm remove-language" onclick="removeLanguageItem(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-success btn-sm mt-2" onclick="addLanguageItem()">
                            <i class="fas fa-plus me-2"></i>Add Language
                        </button>
                    </div>
                </div>

                <!-- SIGNATURE - HIDDEN -->
                <input type="hidden" name="signature" id="signature-input" value="{{ $user->signature ?? '' }}">

                <!-- CONFLICT OF INTEREST -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Conflict of Interest Disclosure
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="conflict_officer_role" class="form-label">Are you or a member of your immediate family an officer, director, trustee, partner, employee, or regularly retained consultant of any company that presently has business dealings with CCBRT?</label>
                                <select class="form-select" id="conflict_officer_role" name="conflict_officer_role" onchange="toggleOfficerDetails()">
                                    <option value="">Select</option>
                                    <option value="Yes" {{ old('conflict_officer_role', $user->conflict_officer_role) == 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('conflict_officer_role', $user->conflict_officer_role) == 'No' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="officer_details_div" style="display: {{ old('conflict_officer_role', $user->conflict_officer_role) == 'Yes' ? 'block' : 'none' }};">
                                <label for="officer_details" class="form-label">Company name, position held, and nature of the business:</label>
                                <textarea name="officer_details" id="officer_details" class="form-control" rows="3">{{ old('officer_details', $user->officer_details) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="financial_interest" class="form-label">Do you or a member of your family have a material financial interest in a company with business dealings with CCBRT?</label>
                                <select class="form-select" id="financial_interest" name="financial_interest" onchange="toggleFinancialDetails()">
                                    <option value="">Select</option>
                                    <option value="Yes" {{ old('financial_interest', $user->financial_interest) == 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('financial_interest', $user->financial_interest) == 'No' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="financial_details_div" style="display: {{ old('financial_interest', $user->financial_interest) == 'Yes' ? 'block' : 'none' }};">
                                <label for="financial_details" class="form-label">Please provide the details:</label>
                                <textarea name="financial_details" id="financial_details" class="form-control" rows="3">{{ old('financial_details', $user->financial_details) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="other_interests" class="form-label">Do you or a member of your family have any other interests that might create a conflict of interest?</label>
                                <select class="form-select" id="other_interests" name="other_interests" onchange="toggleInterestDetails()">
                                    <option value="">Select</option>
                                    <option value="Yes" {{ old('other_interests', $user->other_interests) == 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('other_interests', $user->other_interests) == 'No' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="interest_details_div" style="display: {{ old('other_interests', $user->other_interests) == 'Yes' ? 'block' : 'none' }};">
                                <label for="interest_details" class="form-label">Please provide details below:</label>
                                <textarea name="interest_details" id="interest_details" class="form-control" rows="3">{{ old('interest_details', $user->interest_details) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="primary_employer_ccbrt" class="form-label">Please declare CCBRT as your primary employer:</label>
                                <select class="form-select" id="primary_employer_ccbrt" name="primary_employer_ccbrt" onchange="togglePrimaryEmployerDetails()">
                                    <option value="">Select</option>
                                    <option value="Yes" {{ old('primary_employer_ccbrt', $user->primary_employer_ccbrt) == 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('primary_employer_ccbrt', $user->primary_employer_ccbrt) == 'No' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="primary_employer_details_div" style="display: {{ old('primary_employer_ccbrt', $user->primary_employer_ccbrt) == 'No' ? 'block' : 'none' }};">
                                <label for="primary_employer_details" class="form-label">Please explain:</label>
                                <textarea name="primary_employer_details" id="primary_employer_details" class="form-control" rows="3">{{ old('primary_employer_details', $user->primary_employer_details) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="court_proceedings" class="form-label">Have you ever been involved in any court proceedings?</label>
                                <select class="form-select" id="court_proceedings" name="court_proceedings" onchange="toggleCourtDetails()">
                                    <option value="">Select</option>
                                    <option value="Yes" {{ old('court_proceedings', $user->court_proceedings) == 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('court_proceedings', $user->court_proceedings) == 'No' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="court_details_div" style="display: {{ old('court_proceedings', $user->court_proceedings) == 'Yes' ? 'block' : 'none' }};">
                                <label for="court_details" class="form-label">Please provide details below:</label>
                                <textarea name="court_details" id="court_details" class="form-control" rows="3">{{ old('court_details', $user->court_details) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                                <a href="{{ url()->previous() }}" class="btn btn-secondary btn-lg ms-3 px-4">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .card-header {
            font-weight: 600;
        }
        .text-danger {
            color: #dc3545 !important;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
        function getJobTitles() {
            const departmentId = $('#department').val();

            if (departmentId) {
                $.ajax({
                    url: '/get-job-titles/' + departmentId,
                    method: 'GET',
                    success: function(response) {
                        $('#job_title').empty().append(
                            '<option value="" disabled selected>--- Select Job Title ---</option>');
                        response.jobTitles.forEach(function(jobTitle) {
                            $('#job_title').append(
                                `<option value="${jobTitle.id}">${jobTitle.job_title}</option>`
                            );
                        });
                    },
                    error: function() {
                        alert('Error loading job titles.');
                    }
                });
            } else {
                $('#job_title').empty().append('<option value="" disabled selected>--- Select Job Title ---</option>');
            }
        }

        function toggleOfficerDetails() {
            const value = $('#conflict_officer_role').val();
            $('#officer_details_div').toggle(value === 'Yes');
        }

        function toggleFinancialDetails() {
            const value = $('#financial_interest').val();
            $('#financial_details_div').toggle(value === 'Yes');
        }

        function toggleInterestDetails() {
            const value = $('#other_interests').val();
            $('#interest_details_div').toggle(value === 'Yes');
        }

        function togglePrimaryEmployerDetails() {
            const value = $('#primary_employer_ccbrt').val();
            $('#primary_employer_details_div').toggle(value === 'No');
        }

        function toggleCourtDetails() {
            const value = $('#court_proceedings').val();
            $('#court_details_div').toggle(value === 'Yes');
        }

        function toggleInsuranceDetails() {
            const value = $('#health_insurance').val();
            $('#insurance_details_div').toggle(value === 'Yes');
            $('#insurance_no_div').toggle(value === 'Yes');
        }

        let languageIndex = {{ ($languageKnowledge && $languageKnowledge->count() > 0) ? $languageKnowledge->count() : 1 }};

        function addLanguageItem() {
            const container = document.getElementById('language-container');
            const newItem = document.createElement('div');
            newItem.className = 'language-item mb-3 p-3 border rounded';
            newItem.setAttribute('data-index', languageIndex);
            newItem.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Language</label>
                        <input type="text" name="languages[${languageIndex}][language]" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Speaking</label>
                        <select name="languages[${languageIndex}][speaking]" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reading</label>
                        <select name="languages[${languageIndex}][reading]" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Writing</label>
                        <select name="languages[${languageIndex}][writing]" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-language" onclick="removeLanguageItem(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newItem);
            languageIndex++;
        }

        function removeLanguageItem(button) {
            const container = document.getElementById('language-container');
            if (container.children.length > 1) {
                button.closest('.language-item').remove();
            } else {
                alert('You must have at least one language entry.');
            }
        }

    </script>
@endsection
