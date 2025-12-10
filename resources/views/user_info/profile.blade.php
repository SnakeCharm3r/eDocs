@extends('layouts.template')
@section('content')

<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header">
            <div class="row">
                <div class="col">
                    <h3 class="page-title">Profile</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profile</li>
                    </ul>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-12">
                <div class="profile-header">
                    <div class="row align-items-center">
                        <div class="col-auto profile-image">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#profilePictureModal">
                                @if($user->profile_picture)
                                    <img class="rounded-circle" alt="User Image" src="{{ asset('storage/' . $user->profile_picture) }}" style="width: 100px; height: 100px; object-fit: cover;">
                                @else
                                    <img class="rounded-circle" alt="User Image" src="{{ asset('assets/img/icon.png') }}" style="width: 100px; height: 100px; object-fit: cover;">
                                @endif
                            </a>
                        </div>
                        <div class="col ms-md-n2 profile-user-info">
                            <h4 class="user-name mb-0">{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</h4>
                            <h6 class="text-muted">
                                @if($user->jobTitle)
                                    {{ $user->jobTitle->job_title }}
                                @elseif($user->job_title)
                                    {{ is_numeric($user->job_title) ? 'N/A' : $user->job_title }}
                                @else
                                    N/A
                                @endif
                            </h6>
                            @if($user->department)
                                <div class="text-muted mb-1"><i class="fas fa-building me-1"></i>{{ $user->department->dept_name }}</div>
                            @endif
                            @if($user->home_address)
                                <div class="user-Location"><i class="fas fa-map-marker-alt"></i> {{ $user->home_address }}</div>
                            @endif
                        </div>
                        <div class="col-auto profile-btn">
                            <a href="{{ route('profile.edit', $user->id) }}" class="btn btn-light btn-lg">
                                <i class="fas fa-edit me-1"></i>Edit Personal Details
                            </a>
                        </div>
                    </div>
                </div>
                <div class="profile-menu">
                    <ul class="nav nav-tabs nav-tabs-solid">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#per_details_tab">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#password_tab">Password</a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content profile-tab-cont">

                    <div class="tab-pane fade show active" id="per_details_tab">

                        <div class="row">
                            <div class="col-lg-9">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">
                                            <span class="d-flex align-items-center">
                                                <i class="fas fa-user-circle me-2 text-muted"></i>Personal Details
                                            </span>
                                        </h5>
                                        <div class="row g-3">
                                            <!-- Basic Information -->
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-user me-1"></i>Full Name
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-at me-1"></i>Username
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->username }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-calendar-alt me-1"></i>Date of Birth
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->DOB ? \Carbon\Carbon::parse($user->DOB)->format('d F Y') : 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-venus-mars me-1"></i>Gender
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->gender ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-heart me-1"></i>Marital Status
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->marital_status ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-praying-hands me-1"></i>Religion
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->religion ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            
                                            <!-- Contact Information -->
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-envelope me-1"></i>Email
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->email }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-phone me-1"></i>Mobile
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->mobile ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-map-marker-alt me-1"></i>Place of Birth
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->place_of_birth ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            
                                            <!-- Employment Information -->
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-briefcase me-1"></i>Job Title
                                                    </label>
                                                    <p class="mb-0 fw-semibold">
                                                        @if($user->jobTitle)
                                                            {{ $user->jobTitle->job_title }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-building me-1"></i>Department
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->department ? $user->department->dept_name : 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-id-badge me-1"></i>Employment Type
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->employmentType ? $user->employmentType->employment_type : 'N/A' }}</p>
                                                </div>
                                            </div>
                                            
                                            <!-- Identification Numbers -->
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-id-card me-1"></i>NIN
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->NIN ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-file-alt me-1"></i>NSSF Number
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->nssf_no ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-passport me-1"></i>Passport Number
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->passport_no ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-receipt me-1"></i>TIN Number
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->tin_no ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-home me-1"></i>Domicile
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->domicile ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-certificate me-1"></i>Professional Registration
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->professional_reg_number ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            
                                            <!-- Address Information -->
                                            <div class="col-md-6">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-map-marker-alt me-1"></i>Home Address
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->home_address ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-map me-1"></i>Full Address
                                                    </label>
                                                    <p class="mb-0 fw-semibold">
                                                        @if($user->region || $user->district || $user->house_no || $user->street)
                                                            {{ $user->region ? $user->region . ',' : '' }}
                                                            {{ $user->district ? $user->district . ',' : '' }}
                                                            {{ $user->house_no ? $user->house_no . ',' : '' }}
                                                            {{ $user->street ? $user->street : '' }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <!-- Employment Dates -->
                                            <div class="col-md-6">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-calendar-check me-1"></i>Starting Date
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->starting_date ? \Carbon\Carbon::parse($user->starting_date)->format('d F Y') : 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item p-3 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-calendar-times me-1"></i>Ending Date
                                                    </label>
                                                    <p class="mb-0 fw-semibold">{{ $user->ending_date ? \Carbon\Carbon::parse($user->ending_date)->format('d F Y') : 'N/A' }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Display Next of Kin Details if available -->
                                        @if($nextOfKins->isNotEmpty())
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-users me-2 text-info"></i>Next of Kin
                                                </h5>
                                                @foreach($nextOfKins as $kin)
                                                    <div class="card mb-3 border-left-info" style="border-left: 4px solid #17a2b8;">
                                                        <div class="card-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-user me-1"></i>Full Name
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $kin->full_name ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-heart me-1"></i>Relationship
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $kin->relationship ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-phone me-1"></i>Mobile
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $kin->mobile ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-envelope me-1"></i>Email
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $kin->email ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-map-marker-alt me-1"></i>Address
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $kin->address ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-briefcase me-1"></i>Occupation
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $kin->occupation ?? 'N/A' }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-users me-2 text-info"></i>Next of Kin
                                                </h5>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>No next of kin information available.
                                                </div>
                                            </div>
                                        @endif


                                        <!-- Display Family Data Details if available -->
                                        @if($familyData->isNotEmpty())
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-home me-2 text-success"></i>Family Data
                                                </h5>
                                                @foreach($familyData as $fami)
                                                    <div class="card mb-3 border-left-success" style="border-left: 4px solid #28a745;">
                                                        <div class="card-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-user me-1"></i>Full Name
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $fami->full_name ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-heart me-1"></i>Relationship
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $fami->relationship ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-phone me-1"></i>Mobile
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $fami->mobile ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-envelope me-1"></i>Email
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $fami->email ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-map-marker-alt me-1"></i>Address
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $fami->address ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-briefcase me-1"></i>Occupation
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $fami->occupation ?? 'N/A' }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-home me-2 text-success"></i>Family Data
                                                </h5>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>No family data available.
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Display Health Details if available -->
                                        @if($healthDetails->isNotEmpty())
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-heartbeat me-2 text-danger"></i>Health Details
                                                </h5>
                                                @foreach($healthDetails as $health)
                                                    <div class="card mb-3 border-left-danger" style="border-left: 4px solid #dc3545;">
                                                        <div class="card-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-wheelchair me-1"></i>Physical Disability
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $health->physical_disability ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-tint me-1"></i>Blood Group
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $health->blood_group ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-history me-1"></i>Illness History
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $health->illness_history ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-shield-alt me-1"></i>Health Insurance
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $health->health_insurance ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-building me-1"></i>Insurer Name
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $health->insur_name ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-id-card me-1"></i>Insurer Number
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $health->insur_no ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-exclamation-triangle me-1"></i>Allergies
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $health->allergies ?? 'N/A' }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-heartbeat me-2 text-danger"></i>Health Details
                                                </h5>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>No health details available.
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Display CCBRT Relation Details if available -->
                                        @if($ccbrtRelation->isNotEmpty())
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-sitemap me-2 text-warning"></i>CCBRT Relations
                                                </h5>
                                                @foreach($ccbrtRelation as $relate)
                                                    <div class="card mb-3 border-left-warning" style="border-left: 4px solid #ffc107;">
                                                        <div class="card-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-user me-1"></i>Names
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $relate->names ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-heart me-1"></i>Relationship
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $relate->relation ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-briefcase me-1"></i>Position
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $relate->position ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-building me-1"></i>Department
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $relate->department_name ?? 'N/A' }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-sitemap me-2 text-warning"></i>CCBRT Relations
                                                </h5>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>No CCBRT relations available.
                                                </div>
                                            </div>
                                        @endif

                                            <!-- Display License Details for Clinical Users -->
                                        @if(isset($isClinicalDepartment) && $isClinicalDepartment)
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-id-card me-2 text-primary"></i>Professional License Details
                                                </h5>
                                                @if($licenseInfo)
                                                    <div class="card mb-3 border-left-primary" style="border-left: 4px solid #007bff;">
                                                        <div class="card-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-certificate me-1"></i>Professional Registration Number
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $user->professional_reg_number ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-building me-1"></i>License Provider
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">{{ $licenseInfo['license_provider'] ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-calendar-check me-1"></i>License Valid Until
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">
                                                                        {{ \Carbon\Carbon::parse($licenseInfo['license_valid_until'])->format('d F Y') }}
                                                                        @if($licenseInfo['is_expired'])
                                                                            <span class="badge bg-danger ms-2">Expired</span>
                                                                        @elseif($licenseInfo['expiring_soon'])
                                                                            <span class="badge bg-warning ms-2">Expiring Soon</span>
                                                                        @else
                                                                            <span class="badge bg-success ms-2">Valid</span>
                                                                        @endif
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small mb-1 d-block">
                                                                        <i class="fas fa-check-circle me-1"></i>HR Verification Status
                                                                    </label>
                                                                    <p class="mb-0 fw-semibold">
                                                                        @if($licenseInfo['professional_reg_verified'])
                                                                            <span class="badge bg-success">Verified by HR</span>
                                                                        @else
                                                                            <span class="badge bg-warning">Not Verified</span>
                                                                        @endif
                                                                    </p>
                                                                </div>
                                                                @if($licenseInfo['professional_reg_verification_notes'])
                                                                    <div class="col-md-12">
                                                                        <label class="text-muted small mb-1 d-block">
                                                                            <i class="fas fa-sticky-note me-1"></i>Verification Notes
                                                                        </label>
                                                                        <p class="mb-0 fw-semibold">{{ $licenseInfo['professional_reg_verification_notes'] }}</p>
                                                                    </div>
                                                                @endif
                                                                @if($licenseInfo['is_expired'])
                                                                    <div class="col-md-12">
                                                                        <div class="alert alert-danger mb-0">
                                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                                            <strong>License Expired:</strong> Your professional license expired on 
                                                                            {{ \Carbon\Carbon::parse($licenseInfo['license_valid_until'])->format('d F Y') }} 
                                                                            ({{ abs($licenseInfo['days_until_expiry']) }} day{{ abs($licenseInfo['days_until_expiry']) > 1 ? 's' : '' }} ago). 
                                                                            Please contact HR BP.
                                                                        </div>
                                                                    </div>
                                                                @elseif($licenseInfo['expiring_soon'])
                                                                    <div class="col-md-12">
                                                                        <div class="alert alert-warning mb-0">
                                                                            <i class="fas fa-clock me-2"></i>
                                                                            <strong>License Expiring Soon:</strong> Your professional license will expire on 
                                                                            {{ \Carbon\Carbon::parse($licenseInfo['license_valid_until'])->format('d F Y') }}
                                                                            @if($licenseInfo['days_until_expiry'] == 0)
                                                                                (today).
                                                                            @else
                                                                                (in {{ abs($licenseInfo['days_until_expiry']) }} day{{ abs($licenseInfo['days_until_expiry']) > 1 ? 's' : '' }}).
                                                                            @endif
                                                                            Please contact HR BP.
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        <strong>License Information Not Available:</strong> Your professional license information has not been recorded yet. Please contact HR BP to update your license details.
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Display Language Details if available -->
                                        @if($languageData->isNotEmpty())
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-language me-2 text-purple"></i>Language Knowledge
                                                </h5>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th><i class="fas fa-globe me-1"></i>Language</th>
                                                                <th><i class="fas fa-microphone me-1"></i>Speaking</th>
                                                                <th><i class="fas fa-book me-1"></i>Reading</th>
                                                                <th><i class="fas fa-pen me-1"></i>Writing</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($languageData as $lang)
                                                                <tr>
                                                                    <td class="fw-semibold">{{ $lang->language ?? 'N/A' }}</td>
                                                                    <td>{{ $lang->speaking ?? 'N/A' }}</td>
                                                                    <td>{{ $lang->reading ?? 'N/A' }}</td>
                                                                    <td>{{ $lang->writing ?? 'N/A' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @else
                                            <div class="mt-5 pt-4 border-top">
                                                <h5 class="card-title mb-4 d-flex align-items-center">
                                                    <i class="fas fa-language me-2 text-purple"></i>Language Knowledge
                                                </h5>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>No language information available.
                                                </div>
                                            </div>
                                        @endif




                                    </div>
                                </div>
                            </div>


                        </div>

                    </div>

                    <div id="password_tab" class="tab-pane fade">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4 d-flex align-items-center">
                                    <i class="fas fa-key me-2 text-muted"></i>Change Password
                                </h5>
                                <div class="row">
                                    <div class="col-md-10 col-lg-6">
                                        <form action="{{ route('profile.update.password') }}" method="POST">
                                            @csrf
                                            <div class="form-group mb-3">
                                                <label class="mb-2">
                                                    <i class="fas fa-lock me-1"></i>Old Password <span class="text-danger">*</span>
                                                </label>
                                                <input type="password" name="old_password" class="form-control @error('old_password') is-invalid @enderror" required>
                                                @error('old_password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="mb-2">
                                                    <i class="fas fa-key me-1"></i>New Password <span class="text-danger">*</span>
                                                </label>
                                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                                @error('password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="mb-2">
                                                    <i class="fas fa-check-circle me-1"></i>Confirm Password <span class="text-danger">*</span>
                                                </label>
                                                <input type="password" name="password_confirmation" class="form-control" required>
                                            </div>
                                            <button class="btn btn-secondary" type="submit">
                                                <i class="fas fa-save me-1"></i>Save Changes
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@include('sweetalert::alert')

<!-- Profile Picture Upload Modal -->
<div class="modal fade" id="profilePictureModal" tabindex="-1" aria-labelledby="profilePictureModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profilePictureModalLabel">Update Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('profile.update.picture') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="profile_picture">Select Profile Picture</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*" required>
                        <small class="form-text text-muted">Max size: 2MB. Allowed formats: JPEG, PNG, JPG, GIF</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-secondary">Upload Picture</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    .border-left-success {
        border-left: 4px solid #28a745 !important;
    }
    .border-left-danger {
        border-left: 4px solid #dc3545 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
    .border-left-purple {
        border-left: 4px solid #6f42c1 !important;
    }
    .border-left-primary {
        border-left: 4px solid #007bff !important;
    }
    .text-purple {
        color: #6f42c1 !important;
    }
    .info-item {
        transition: all 0.3s ease;
    }
    .info-item:hover {
        background-color: #e9ecef !important;
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .profile-header {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .profile-header .user-name {
        color: #212529;
        font-weight: 600;
    }
    .profile-header .text-muted {
        color: #6c757d !important;
    }
    .profile-header .btn-light {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        color: #212529;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    .profile-header .btn-light:hover {
        background-color: #e9ecef;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        color: #212529;
    }
    .profile-image img {
        border: 4px solid #e9ecef;
        transition: all 0.3s ease;
    }
    .profile-image:hover img {
        border-color: #dee2e6;
        transform: scale(1.05);
    }
</style>

@endsection
