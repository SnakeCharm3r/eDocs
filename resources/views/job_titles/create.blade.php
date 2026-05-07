@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        {{-- Page Header --}}
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-plus me-2 text-success"></i>Add New Job Title
                    </h3>
                    <p class="text-muted mb-0 mt-1">Create a new job title and assign it to a department</p>
                </div>
                <div class="col-auto">
                    <a href="{{ route('job_titles.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{!! session('error') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-briefcase me-2 text-success"></i>Job Title Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('job_titles.store') }}" method="POST" id="createJobTitleForm">
                            @csrf

                            <div class="mb-4">
                                <label for="job_title" class="form-label fw-semibold">
                                    <i class="fas fa-briefcase me-1 text-success"></i>Job Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                    class="form-control @error('job_title') is-invalid @enderror" 
                                    id="job_title" 
                                    name="job_title"
                                    value="{{ old('job_title') }}" 
                                    placeholder="e.g., Senior Software Engineer"
                                    required>
                                @error('job_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Enter the full job title name</small>
                            </div>

                            <div class="mb-4">
                                <label for="deptId" class="form-label fw-semibold">
                                    <i class="fas fa-building me-1 text-success"></i>Department <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('deptId') is-invalid @enderror" 
                                    id="deptId" 
                                    name="deptId" 
                                    required>
                                    <option value="">Select a department</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}"
                                            {{ old('deptId') == $department->id ? 'selected' : '' }}>
                                            {{ $department->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('deptId')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Select the department this job title belongs to</small>
                            </div>

                            <div class="mb-4">
                                <label for="clinical_or_non_clinical" class="form-label fw-semibold">
                                    <i class="fas fa-tag me-1 text-success"></i>Clinical or Non-Clinical <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('clinical_or_non_clinical') is-invalid @enderror" 
                                    id="clinical_or_non_clinical" 
                                    name="clinical_or_non_clinical" 
                                    required>
                                    <option value="">Select Type</option>
                                    <option value="Clinical" {{ old('clinical_or_non_clinical') == 'Clinical' ? 'selected' : '' }}>Clinical</option>
                                    <option value="Non-Clinical" {{ old('clinical_or_non_clinical') == 'Non-Clinical' ? 'selected' : '' }}>Non-Clinical</option>
                                </select>
                                @error('clinical_or_non_clinical')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Select whether this job title is clinical or non-clinical. This determines if professional registration is required.
                                </small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="{{ route('job_titles.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i> Create Job Title
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control:focus,
    .form-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
</style>
@endsection
