@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <h3 class="page-title mb-0">
                                <i class="fas fa-user-circle me-2"></i>Employee Profile
                            </h3>
                            <a href="{{ route('employee.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Staff List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
            <div class="container">
                <div class="row flex-lg-nowrap">
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="e-profile">
                                    <div class="row">
                                        <!-- Success Message -->
                                        @if (session('success'))
                                            <div class="alert alert-success" id="successMessage">
                                                {{ session('success') }}
                                            </div>

                                            <script>
                                                setTimeout(function() {
                                                    var successMessage = document.getElementById('successMessage');
                                                    if (successMessage) {
                                                        successMessage.style.display = 'none';
                                                    }
                                                }, 3000); // 3000 milliseconds = 3 seconds
                                            </script>
                                        @endif

                                        <div class="col-12 col-sm-auto mb-3">
                                            <div class="mx-auto" style="width: 140px;">
                                                <div class="d-flex justify-content-center align-items-center rounded"
                                                    style="height: 140px; background-color: rgb(233, 236, 239); position: relative;">
                                                    @if ($user->profile_picture)
                                                        <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                            alt="Profile Picture" class="img-fluid rounded-circle"
                                                            style="max-width: 140px; height: 140px; padding: 5px; object-fit: cover;">
                                                        <a href="{{ asset('storage/' . $user->profile_picture) }}" download
                                                            class="btn btn-primary"
                                                            style="position: absolute; bottom: 10px; right: 10px; padding: 5px 10px;">
                                                            <i class="fa fa-download"></i>
                                                        </a>
                                                    @else
                                                        <img src="{{ asset('assets/img/icon.png') }}"
                                                            alt="Default User Icon" class="img-fluid rounded-circle"
                                                            style="max-width: 140px; height: 140px; padding: 1px; object-fit: cover;">
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col d-flex flex-column flex-sm-row justify-content-between mb-3">
                                            <div class="text-center text-sm-left mb-2 mb-sm-0">
                                                <h4 class="pt-sm-2 pb-1 mb-0 text-nowrap">{{ $user->fname }}
                                                    {{ $user->mname }} {{ $user->lname }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <ul class="nav nav-tabs">
                                        <li class="nav-item"><a href="#" class="active nav-link">User Info</a></li>
                                    </ul>
                                    <br>
                                    <div class="row">
                                        <div class="col">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Department</th>
                                                            <th>Job Title</th>
                                                            <th>CCBRT Code</th>
                                                            <th>Professional Reg Number</th>
                                                            <th>NSSF No</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>{{ optional($user->department)->dept_name ?? 'N/A' }}</td>
                                                            <td>{{ optional($user->jobTitle)->job_title ?? 'N/A' }}</td>
                                                            <td>{{ $user->ccbrt_code ?? 'N/A' }}</td>
                                                            <td>{{ $user->professional_reg_number ?? 'N/A' }}</td>
                                                            <td>{{ $user->nssf_no ?? 'N/A' }}</td>
                                                            <td>
                                                                <form action="{{ route('user.edit', $user->id) }}"
                                                                    method="get" id="editForm{{ $user->id }}">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                                        <i class="fas fa-edit"></i> Edit
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    
                                    {{-- Profile Completion Section for HR --}}
                                    @role('super-admin|admin|hr')
                                    @if($user->status === 'inactive' || ($user->status === 'pending' && $existingWorkflow))
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card border-primary">
                                                <div class="card-header bg-primary text-white">
                                                    <h5 class="mb-0">
                                                        <i class="fas fa-clipboard-check me-2"></i>Profile Completion Status
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6 class="mb-3">Required Profile Details:</h6>
                                                            <ul class="list-group list-group-flush">
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <span>
                                                                        <i class="fas {{ $hasPersonalDetails ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} me-2"></i>
                                                                        Personal Details
                                                                    </span>
                                                                    @if($hasPersonalDetails)
                                                                        <span class="badge bg-success">Completed</span>
                                                                    @else
                                                                        <a href="{{ route('hr.employee.personal-details', $user->id) }}" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-edit me-1"></i>Fill
                                                                        </a>
                                                                    @endif
                                                                </li>
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <span>
                                                                        <i class="fas {{ $hasFamilyDetails ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} me-2"></i>
                                                                        Family Details
                                                                    </span>
                                                                    @if($hasFamilyDetails)
                                                                        <span class="badge bg-success">Completed</span>
                                                                    @else
                                                                        <a href="{{ route('hr.employee.family-details', $user->id) }}" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-edit me-1"></i>Fill
                                                                        </a>
                                                                    @endif
                                                                </li>
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <span>
                                                                        <i class="fas {{ $hasHealthDetails ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} me-2"></i>
                                                                        Health Details
                                                                    </span>
                                                                    @if($hasHealthDetails)
                                                                        <span class="badge bg-success">Completed</span>
                                                                    @else
                                                                        <a href="{{ route('hr.employee.health-details', $user->id) }}" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-edit me-1"></i>Fill
                                                                        </a>
                                                                    @endif
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="mb-3">&nbsp;</h6>
                                                            <ul class="list-group list-group-flush">
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <span>
                                                                        <i class="fas {{ $hasLanguageKnowledge ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} me-2"></i>
                                                                        Language Knowledge
                                                                    </span>
                                                                    @if($hasLanguageKnowledge)
                                                                        <span class="badge bg-success">Completed</span>
                                                                    @else
                                                                        <a href="{{ route('hr.employee.language-knowledge', $user->id) }}" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-edit me-1"></i>Fill
                                                                        </a>
                                                                    @endif
                                                                </li>
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <span>
                                                                        <i class="fas {{ $hasCcbrtRelation ? 'fa-check-circle text-success' : 'fa-circle text-muted' }} me-2"></i>
                                                                        CCBRT Relation <span class="text-muted small">(Optional)</span>
                                                                    </span>
                                                                    @if($hasCcbrtRelation)
                                                                        <span class="badge bg-success">Completed</span>
                                                                    @else
                                                                        <a href="{{ route('hr.employee.ccbrt-relation', $user->id) }}" class="btn btn-sm btn-outline-secondary">
                                                                            <i class="fas fa-edit me-1"></i>Fill
                                                                        </a>
                                                                    @endif
                                                                </li>
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <span>
                                                                        <i class="fas {{ $hasConflictInterest ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} me-2"></i>
                                                                        Conflict of Interest
                                                                    </span>
                                                                    @if($hasConflictInterest)
                                                                        <span class="badge bg-success">Completed</span>
                                                                    @else
                                                                        <a href="{{ route('hr.employee.conflict-interest', $user->id) }}" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-edit me-1"></i>Fill
                                                                        </a>
                                                                    @endif
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mt-4">
                                                        <div class="col-12">
                                                            <div class="alert {{ $profileComplete ? 'alert-success' : 'alert-warning' }}" role="alert">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <i class="fas {{ $profileComplete ? 'fa-check-circle' : 'fa-exclamation-triangle' }} me-2"></i>
                                                                        <strong>
                                                                            @if($profileComplete)
                                                                                All profile details are completed!
                                                                            @else
                                                                                Please complete all profile details before submitting for approval.
                                                                            @endif
                                                                        </strong>
                                                                    </div>
                                                                    @if($existingWorkflow)
                                                                        <span class="badge bg-info">
                                                                            <i class="fas fa-clock me-1"></i>Pending Approval
                                                                        </span>
                                                                    @else
                                                                        <div class="alert alert-info mb-0">
                                                                            <i class="fas fa-info-circle me-2"></i>
                                                                            <strong>Note:</strong> This staff member needs to login and complete their signature to submit for HR approval.
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    @endrole
                                    
                                    <div class="row">
                                        <!-- Left Column: Account Information -->
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <th colspan="2" class="text-center">Account Information</th>
                                                    </tr>

                                                    <tr>
                                                        <th scope="row">Joined Date</th>
                                                        <td>{{ \Carbon\Carbon::parse($user->created_at)->format('d F, Y') }}
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <th scope="row">Status</th>
                                                        <td>
                                                            <div class="row">
                                                                <div class="col-8">
                                                                    @if ($user->status === 'active')
                                                                        Active
                                                                    @elseif ($user->status === 'inactive')
                                                                        Inactive
                                                                    @elseif ($user->status === 'Pending')
                                                                        Pending
                                                                    @elseif ($user->status === 'deactivated')
                                                                        Deactivated
                                                                    @else
                                                                        Unknown
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <!-- Action Column -->
                                                    <tr>
                                                        <th scope="row">Action</th>
                                                        <td>
                                                            @if ($user->status === 'active')
                                                                <!-- Deactivate User Button -->
                                                                <form action="{{ route('auth.deactivate', $user->id) }}"
                                                                    method="POST"
                                                                    id="deactivateForm{{ $user->id }}"
                                                                    style="display:inline-block;">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <button type="button"
                                                                        class="btn btn-warning btn-sm"
                                                                        onclick="confirmDeactivate({{ $user->id }})">
                                                                        <i class="fas fa-user-slash me-1"></i>Deactivate User
                                                                    </button>
                                                                </form>
                                                            @elseif ($user->status === 'inactive' || $user->status === 'deactivated')
                                                                <!-- Activate User Button -->
                                                                <form action="{{ route('auth.activate', $user->id) }}"
                                                                    method="POST"
                                                                    onsubmit="return confirm('Are you sure you want to activate this user?');"
                                                                    style="display:inline-block;">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <button type="submit"
                                                                        class="btn btn-success btn-sm">Activate
                                                                        User</button>
                                                                </form>
                                                            @endif

                                                            <!-- Delete User Button (only for inactive users) -->
                                                            @if ($user->status === 'inactive')
                                                                <form action="{{ route('auth.destroy', $user->id) }}"
                                                                    method="POST"
                                                                    onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');"
                                                                    style="display:inline-block;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                        class="btn btn-danger btn-sm">Delete User</button>
                                                                </form>
                                                            @endif
                                                        </td>


                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th colspan="2" class="text-center">User Attachments</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- CV -->
                                                    @if ($user->employee_cv)
                                                        <tr>
                                                            <th scope="row">Curriculum Vitae (CV)</th>
                                                            <td>
                                                                <a href="{{ asset('storage/' . $user->employee_cv) }}"
                                                                    target="_blank" class="btn btn-info btn-sm">View</a>

                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if ($user->marital_status == 'married' && $user->marriage_certificate)
                                                        <!-- Marriage Certificate -->
                                                        <tr>
                                                            <th scope="row">Marriage Certificate</th>
                                                            <td>
                                                                <a href="{{ asset('storage/' . $user->marriage_certificate) }}"
                                                                    target="_blank" class="btn btn-info btn-sm">View</a>

                                                            </td>
                                                        </tr>
                                                    @elseif($user->marital_status == 'divorced' && $user->divorce_certificate)
                                                        <!-- Divorce Certificate -->
                                                        <tr>
                                                            <th scope="row">Divorce Certificate</th>
                                                            <td>
                                                                <a href="{{ asset('storage/' . $user->divorce_certificate) }}"
                                                                    target="_blank" class="btn btn-info btn-sm">View</a>

                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if ($user->nida)
                                                        <tr>
                                                            <th scope="row">NIDA</th>
                                                            <td>
                                                                <a href="{{ asset('storage/' . $user->nida) }}"
                                                                    target="_blank" class="btn btn-info btn-sm">View</a>

                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if ($user->driving_license)
                                                        <tr>
                                                            <th scope="row">Driving License</th>
                                                            <td>
                                                                <a href="{{ asset('storage/' . $user->driving_license) }}"
                                                                    target="_blank" class="btn btn-info btn-sm">View</a>

                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if ($user->transport_id)
                                                        <tr>
                                                            <th scope="row">Transport ID License</th>
                                                            <td>
                                                                <a href="{{ asset('storage/' . $user->transport_id) }}"
                                                                    target="_blank" class="btn btn-info btn-sm">View</a>

                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if ($user->voting_id)
                                                        <tr>
                                                            <th scope="row">Voting ID</th>
                                                            <td>
                                                                <a href="{{ asset('storage/' . $user->voting_id) }}"
                                                                    target="_blank" class="btn btn-info btn-sm">View</a>

                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if ($user->other_document)
                                                        <tr>
                                                            <th scope="row">Other Docs</th>
                                                            <td>
                                                                <a href="{{ asset('storage/' . $user->other_document) }}"
                                                                    target="_blank" class="btn btn-info btn-sm">View</a>

                                                            </td>
                                                        </tr>
                                                    @endif

                                                </tbody>
                                            </table>
                                        </div>


                                    </div>
                                </div>
                                <table class="table mt-4" id="education-level-documents">
                                    <thead>
                                        <tr>
                                            <!-- Display Form 4 & Form 6 Column only if documents exist -->
                                            @if ($user->form_4_certificate || $user->form_6_certificate)
                                                <th>Form 4 & Form 6</th>
                                            @endif

                                            <!-- Display Diploma, Degree & Master's Column only if documents exist -->
                                            @if ($user->diploma_certificate || $user->bachelor_certificate || $user->masters_certificate)
                                                <th>Diploma, Degree & Master's</th>
                                            @endif

                                            <!-- Display PhD Column only if documents exist -->
                                            @if ($user->phd_certificate)
                                                <th>PhD</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <!-- Form 4 and Form 6 documents -->
                                            @if ($user->form_4_certificate || $user->form_6_certificate)
                                                <td>
                                                    @if ($user->form_4_certificate)
                                                        <div>
                                                            <strong>Form 4</strong><br>
                                                            <a href="{{ asset('storage/' . $user->form_4_certificate) }}"
                                                                target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye mr-2"></i>
                                                                <!-- Eye icon with right margin -->
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if ($user->form_6_certificate)
                                                        <div>
                                                            <strong>Form 6</strong><br>
                                                            <a href="{{ asset('storage/' . $user->form_6_certificate) }}"
                                                                target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye mr-2"></i>
                                                                <!-- Eye icon with right margin -->
                                                            </a>
                                                        </div>
                                                    @endif
                                                </td>
                                            @endif

                                            <!-- Diploma, Degree & Master's documents -->
                                            @if ($user->diploma_certificate || $user->bachelor_certificate || $user->masters_certificate)
                                                <td>
                                                    @if ($user->diploma_certificate)
                                                        <div>
                                                            <strong>Diploma Certificate</strong><br>
                                                            <a href="{{ asset('storage/' . $user->diploma_certificate) }}"
                                                                target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye mr-2"></i>
                                                                <!-- Eye icon with right margin -->
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if ($user->diploma_transcript)
                                                        <div>
                                                            <strong>Diploma Transcript</strong><br>
                                                            <a href="{{ asset('storage/' . $user->diploma_transcript) }}"
                                                                target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye mr-2"></i>
                                                                <!-- Eye icon with right margin -->
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if ($user->bachelor_certificate)
                                                        <div>
                                                            <strong>Bachelor's Degree</strong><br>
                                                            <a href="{{ asset('storage/' . $user->bachelor_certificate) }}"
                                                                target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye mr-2"></i>
                                                                <!-- Eye icon with right margin -->
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if ($user->bachelor_transcript)
                                                        <div>
                                                            <strong>Bachelor Transcript</strong><br>
                                                            <a href="{{ asset('storage/' . $user->bachelor_transcript) }}"
                                                                target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye mr-2"></i>
                                                                <!-- Eye icon with right margin -->
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if ($user->masters_certificate)
                                                        <div>
                                                            <strong>Master's Degree</strong><br>
                                                            <a href="{{ asset('storage/' . $user->masters_certificate) }}"
                                                                target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye mr-2"></i>
                                                                <!-- Eye icon with right margin -->
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if ($user->masters_transcript)
                                                        <div>
                                                            <strong>Master's Transcript</strong><br>
                                                            <a href="{{ asset('storage/' . $user->masters_transcript) }}"
                                                                target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye mr-2"></i>
                                                                <!-- Eye icon with right margin -->
                                                            </a>
                                                        </div>
                                                    @endif
                                                </td>
                                            @endif

                                            <!-- PhD documents -->
                                            @if ($user->phd_certificate)
                                                <td>
                                                    @if ($user->phd_certificate)
                                                        <div>
                                                            <strong>PhD Certificate</strong><br>
                                                            <a href="{{ asset('storage/' . $user->phd_certificate) }}"
                                                                target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye mr-2"></i>
                                                                <!-- Eye icon with right margin -->
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if ($user->phd_transcript)
                                                        <div>
                                                            <strong>PhD Transcript</strong><br>
                                                            <a href="{{ asset('storage/' . $user->phd_transcript) }}"
                                                                target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye mr-2"></i>
                                                                <!-- Eye icon with right margin -->
                                                            </a>
                                                        </div>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    </tbody>
                                </table>



                                {{-- CCBRT Policies Section --}}
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-file-contract me-2"></i>CCBRT Policies
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                @if ($policies->isEmpty())
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle me-2"></i>No policies available.
                                                    </div>
                                                @else
                                                    {{-- Policy Selection Interface --}}
                                                    <div class="mb-4">
                                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                                            <h6 class="mb-0">
                                                                <i class="fas fa-list-check me-2"></i>Select Policies to Download
                                                            </h6>
                                                            <div>
                                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllPolicies()">
                                                                    <i class="fas fa-check-square me-1"></i>Select All
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllPolicies()">
                                                                    <i class="fas fa-square me-1"></i>Deselect All
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <div class="row g-3" id="policy-selection">
                                                            @foreach ($policies as $policy)
                                                                <div class="col-md-6">
                                                                    <div class="card border policy-card h-100">
                                                                        <div class="card-body">
                                                                            <div class="form-check">
                                                                                <input class="form-check-input policy-checkbox" 
                                                                                    type="checkbox" 
                                                                                    value="{{ $policy->id }}" 
                                                                                    id="policy-{{ $policy->id }}"
                                                                                    data-title="{{ $policy->title }}">
                                                                                <label class="form-check-label w-100" for="policy-{{ $policy->id }}">
                                                                                    <div class="d-flex align-items-start">
                                                                                        <i class="fas fa-file-alt text-primary me-2 mt-1"></i>
                                                                                        <div class="flex-grow-1">
                                                                                            <h6 class="mb-1 fw-bold">{{ $policy->title }}</h6>
                                                                                            <small class="text-muted">
                                                                                                <i class="fas fa-calendar me-1"></i>
                                                                                                Created: {{ \Carbon\Carbon::parse($policy->created_at)->format('d M Y') }}
                                                                                            </small>
                                                                                        </div>
                                                                                    </div>
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    {{-- Download Actions --}}
                                                    <div class="border-top pt-3">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <span id="selected-count" class="badge bg-info">
                                                                    <i class="fas fa-check-circle me-1"></i>
                                                                    <span id="count-text">0</span> selected
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <button type="button" 
                                                                    class="btn btn-success" 
                                                                    id="download-selected-btn"
                                                                    onclick="downloadSelectedPolicies()"
                                                                    disabled>
                                                                    <i class="fas fa-download me-2"></i>Download Selected Policies
                                                                </button>
                                                                <button type="button" 
                                                                    class="btn btn-primary" 
                                                                    onclick="previewSelectedPolicies()"
                                                                    id="preview-btn"
                                                                    disabled>
                                                                    <i class="fas fa-eye me-2"></i>Preview Selected
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Policy Preview Modal --}}
                                                    <div class="modal fade" id="policyPreviewModal" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-primary text-white">
                                                                    <h5 class="modal-title">
                                                                        <i class="fas fa-file-alt me-2"></i>Policy Preview
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body" id="policy-preview-content" style="max-height: 70vh; overflow-y: auto;">
                                                                    <!-- Preview content will be loaded here -->
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="button" class="btn btn-success" onclick="downloadFromPreview()">
                                                                        <i class="fas fa-download me-2"></i>Download
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    const policies = @json($policies);
                                    const userId = {{ $user->id }};
                                    let selectedPolicies = [];

                                    // Update selected count
                                    function updateSelectedCount() {
                                        const checkboxes = document.querySelectorAll('.policy-checkbox:checked');
                                        selectedPolicies = Array.from(checkboxes).map(cb => ({
                                            id: cb.value,
                                            title: cb.getAttribute('data-title')
                                        }));
                                        const count = selectedPolicies.length;
                                        document.getElementById('count-text').textContent = count;
                                        document.getElementById('download-selected-btn').disabled = count === 0;
                                        document.getElementById('preview-btn').disabled = count === 0;
                                        
                                        // Update badge color
                                        const badge = document.getElementById('selected-count');
                                        if (count > 0) {
                                            badge.classList.remove('bg-info');
                                            badge.classList.add('bg-success');
                                        } else {
                                            badge.classList.remove('bg-success');
                                            badge.classList.add('bg-info');
                                        }
                                    }

                                    // Select all policies
                                    function selectAllPolicies() {
                                        document.querySelectorAll('.policy-checkbox').forEach(cb => cb.checked = true);
                                        updateSelectedCount();
                                    }

                                    // Deselect all policies
                                    function deselectAllPolicies() {
                                        document.querySelectorAll('.policy-checkbox').forEach(cb => cb.checked = false);
                                        updateSelectedCount();
                                    }

                                    // Download selected policies
                                    function downloadSelectedPolicies() {
                                        if (selectedPolicies.length === 0) {
                                            Swal.fire('Error', 'Please select at least one policy to download.', 'error');
                                            return;
                                        }

                                        const policyIds = selectedPolicies.map(p => p.id).join(',');
                                        const url = `{{ route('user.policies.download', ['id' => $user->id]) }}?policy_ids=${policyIds}`;
                                        window.location.href = url;
                                    }

                                    // Preview selected policies
                                    function previewSelectedPolicies() {
                                        if (selectedPolicies.length === 0) {
                                            Swal.fire('Error', 'Please select at least one policy to preview.', 'error');
                                            return;
                                        }

                                        const modal = new bootstrap.Modal(document.getElementById('policyPreviewModal'));
                                        const content = document.getElementById('policy-preview-content');
                                        content.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
                                        modal.show();

                                        // Load preview content
                                        const policyIds = selectedPolicies.map(p => p.id);
                                        fetch(`{{ route('user.policies.preview', ['id' => $user->id]) }}?policy_ids=${policyIds.join(',')}`)
                                            .then(response => response.text())
                                            .then(html => {
                                                content.innerHTML = html;
                                            })
                                            .catch(error => {
                                                content.innerHTML = '<div class="alert alert-danger">Failed to load preview.</div>';
                                            });
                                    }

                                    // Download from preview
                                    function downloadFromPreview() {
                                        downloadSelectedPolicies();
                                        bootstrap.Modal.getInstance(document.getElementById('policyPreviewModal')).hide();
                                    }

                                    // Event listeners
                                    document.addEventListener('DOMContentLoaded', function() {
                                        document.querySelectorAll('.policy-checkbox').forEach(checkbox => {
                                            checkbox.addEventListener('change', updateSelectedCount);
                                        });
                                        updateSelectedCount();
                                    });
                                </script>



                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        function confirmDeactivate(userId) {
            Swal.fire({
                title: 'Deactivate User?',
                html: '<div class="text-start">' +
                      '<p class="mb-3">Are you sure you want to deactivate this user?</p>' +
                      '<div class="alert alert-warning mb-0">' +
                      '<i class="fas fa-exclamation-triangle me-2"></i>' +
                      '<strong>Warning:</strong> This user will <strong>NOT be able to access the system</strong> or login after deactivation.' +
                      '</div>' +
                      '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-user-slash me-2"></i>Yes, Deactivate',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel',
                reverseButtons: true,
                focusConfirm: false,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit the form
                    document.getElementById('deactivateForm' + userId).submit();
                }
            });
        }
    </script>

@endsection
