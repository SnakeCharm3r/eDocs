@extends('layouts.template')

@section('content')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col">
                        <h3 class="page-title">Edit Personal Details</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('profile.index') }}">Profile</a></li>
                            <li class="breadcrumb-item active">Edit Personal Details</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-edit me-2"></i>Personal Information
                            </h5>
                        </div>
                        <div class="card-body">

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form action="{{ route('profile.update', $user->id) }}" method="POST" class="form" novalidate>
                                @csrf
                                @method('PUT')

                                <!-- Read-Only Personal Information -->
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Personal Information (Read Only)
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-user me-1"></i>Full Name
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-at me-1"></i>Username
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->username }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-calendar-alt me-1"></i>Date of Birth
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->DOB ? \Carbon\Carbon::parse($user->DOB)->format('d F Y') : 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-venus-mars me-1"></i>Gender
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->gender ?? 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-heart me-1"></i>Marital Status
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->marital_status ?? 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-map-marker-alt me-1"></i>Place of Birth
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->place_of_birth ?? 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Read-Only Employment Information -->
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-briefcase me-2"></i>Employment Information (Read Only)
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-briefcase me-1"></i>Job Title
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->jobTitle ? $user->jobTitle->job_title : 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-building me-1"></i>Department
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->department ? $user->department->dept_name : 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                <i class="fas fa-id-badge me-1"></i>Employment Type
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->employmentType ? $user->employmentType->employment_type : 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Read-Only Identification Numbers -->
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-id-card me-2"></i>Identification Numbers (Read Only)
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">
                                                <i class="fas fa-id-card me-1"></i>NIN
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->NIN ?? 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">
                                                <i class="fas fa-file-alt me-1"></i>NSSF Number
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->nssf_no ?? 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">
                                                <i class="fas fa-passport me-1"></i>Passport Number
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->passport_no ?? 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">
                                                <i class="fas fa-receipt me-1"></i>TIN Number
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->tin_no ?? 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-home me-1"></i>Domicile
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->domicile ?? 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-certificate me-1"></i>Professional Registration
                                            </label>
                                            <input class="form-control" type="text" value="{{ $user->professional_reg_number ?? 'N/A' }}" readonly style="background-color: #e9ecef;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Editable Contact Information -->
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-phone me-2"></i>Contact Information
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="mobile" class="form-label">
                                                <i class="fas fa-mobile-alt me-1"></i>Mobile Number
                                            </label>
                                            <input class="form-control @error('mobile') is-invalid @enderror" 
                                                type="text" 
                                                name="mobile"
                                                id="mobile"
                                                placeholder="e.g., 123-456-7890"
                                                value="{{ old('mobile', $user->mobile) }}">
                                            @error('mobile')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="email" class="form-label">
                                                <i class="fas fa-envelope me-1"></i>Email Address
                                            </label>
                                            <input class="form-control @error('email') is-invalid @enderror" 
                                                type="email" 
                                                name="email"
                                                id="email" 
                                                placeholder="example@domain.com"
                                                value="{{ old('email', $user->email) }}" 
                                                maxlength="255"
                                                pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                                                title="Please enter a valid email address"
                                                required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Personal Details -->
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Personal Details
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="house_no" class="form-label">
                                                <i class="fas fa-home me-1"></i>House Number
                                            </label>
                                            <input class="form-control @error('house_no') is-invalid @enderror" 
                                                type="text" 
                                                name="house_no"
                                                id="house_no"
                                                placeholder="House Number" 
                                                pattern="^[A-Za-z0-9\s]+$"
                                                maxlength="20"
                                                value="{{ old('house_no', $user->house_no ?? '') }}">
                                            @error('house_no')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="religion" class="form-label">
                                                <i class="fas fa-praying-hands me-1"></i>Religion<span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control @error('religion') is-invalid @enderror" 
                                                name="religion"
                                                id="religion-select" 
                                                required>
                                                <option value="">Select Religion</option>
                                                <option value="Christian"
                                                    {{ old('religion', $user->religion ?? '') == 'Christian' ? 'selected' : '' }}>
                                                    Christian</option>
                                                <option value="Islam"
                                                    {{ old('religion', $user->religion ?? '') == 'Islam' ? 'selected' : '' }}>
                                                    Muslim</option>
                                                <option value="Hindu"
                                                    {{ old('religion', $user->religion ?? '') == 'Hindu' ? 'selected' : '' }}>
                                                    Hindu</option>
                                                <option value="Budha"
                                                    {{ old('religion', $user->religion ?? '') == 'Budha' ? 'selected' : '' }}>
                                                    Budha</option>
                                                <option value="Other"
                                                    {{ old('religion', $user->religion ?? '') == 'Other' ? 'selected' : '' }}>
                                                    Others</option>
                                            </select>
                                            <input class="form-control mt-2 @error('religion_other') is-invalid @enderror" 
                                                type="text"
                                                name="religion_other" 
                                                id="religion-other"
                                                placeholder="Please specify your religion"
                                                style="display: none;"
                                                value="{{ old('religion_other', $user->religion_other ?? '') }}"
                                                maxlength="20">
                                            @error('religion')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            @error('religion_other')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Address Information -->
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-map-marker-alt me-2"></i>Address Information
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="region" class="form-label">
                                                <i class="fas fa-map me-1"></i>Region<span class="text-danger">*</span>
                                            </label>
                                            <input class="form-control @error('region') is-invalid @enderror" 
                                                type="text" 
                                                name="region"
                                                id="region"
                                                placeholder="Region"
                                                value="{{ old('region', $user->region ?? '') }}" 
                                                required
                                                maxlength="20">
                                            @error('region')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label for="district" class="form-label">
                                                <i class="fas fa-city me-1"></i>District<span class="text-danger">*</span>
                                            </label>
                                            <input class="form-control @error('district') is-invalid @enderror" 
                                                type="text" 
                                                name="district"
                                                id="district"
                                                placeholder="District"
                                                value="{{ old('district', $user->district ?? '') }}"
                                                required 
                                                maxlength="20">
                                            @error('district')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label for="street" class="form-label">
                                                <i class="fas fa-road me-1"></i>Street<span class="text-danger">*</span>
                                            </label>
                                            <input class="form-control @error('street') is-invalid @enderror" 
                                                type="text" 
                                                name="street"
                                                id="street"
                                                placeholder="Street"
                                                value="{{ old('street', $user->street ?? '') }}" 
                                                required
                                                maxlength="20">
                                            @error('street')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label for="box_no" class="form-label">
                                                <i class="fas fa-inbox me-1"></i>Box No
                                            </label>
                                            <input class="form-control @error('box_no') is-invalid @enderror" 
                                                type="number" 
                                                name="box_no"
                                                id="box_no"
                                                placeholder="Box No"
                                                value="{{ old('box_no', $user->box_no ?? '') }}"
                                                min="0" 
                                                max="999999">
                                            @error('box_no')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label for="plot_no" class="form-label">
                                                <i class="fas fa-th me-1"></i>Plot No
                                            </label>
                                            <input class="form-control @error('plot_no') is-invalid @enderror" 
                                                type="number" 
                                                name="plot_no"
                                                id="plot_no"
                                                placeholder="Plot No"
                                                value="{{ old('plot_no', $user->plot_no ?? '') }}"
                                                min="0" 
                                                max="999999">
                                            @error('plot_no')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label for="popular_landmark" class="form-label">
                                                <i class="fas fa-landmark me-1"></i>Popular Landmark<span class="text-danger">*</span>
                                            </label>
                                            <input class="form-control @error('popular_landmark') is-invalid @enderror" 
                                                type="text"
                                                name="popular_landmark" 
                                                id="popular_landmark"
                                                placeholder="Near well-known area"
                                                value="{{ old('popular_landmark', $user->popular_landmark ?? '') }}"
                                                required 
                                                maxlength="20">
                                            @error('popular_landmark')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="{{ route('profile.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left me-1"></i>Back to Profile
                                            </a>
                                            <button class="btn btn-secondary" type="submit">
                                                <i class="fas fa-save me-1"></i>Save Changes
                                            </button>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Show/hide other religion input
        $(document).ready(function() {
            $('#religion-select').on('change', function() {
                if ($(this).val() === 'Other') {
                    $('#religion-other').show().prop('required', true);
                } else {
                    $('#religion-other').hide().prop('required', false);
                }
            }).trigger('change');
        });
    </script>

    <style>
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: none;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .form-control:focus {
            border-color: #6c757d;
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
        }
        h6.text-muted {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
    </style>
@endsection
