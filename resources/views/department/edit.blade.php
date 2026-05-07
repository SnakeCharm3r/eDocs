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
                        <i class="fas fa-edit me-2 text-success"></i>Edit Department
                    </h3>
                    <p class="text-muted mb-0 mt-1">{{ $department->dept_name }}</p>
                </div>
                <div class="col-auto">
                    <a href="{{ route('department.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Departments
                    </a>
                </div>
            </div>
        </div>

        {{-- Display validation errors --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><strong>Please fix the following errors:</strong>
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

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Form to update department --}}
        <form action="{{ route('department.update', $department->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2 text-success"></i>Department Information
                    </h5>
                </div>
                <div class="card-body">

                    <div class="row">
                        {{-- Department Name --}}
                        <div class="col-md-6 mb-3">
                            <label for="dept_name" class="form-label">
                                <i class="fas fa-building me-1 text-success"></i>Department Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('dept_name') is-invalid @enderror" 
                                   id="dept_name" 
                                   name="dept_name"
                                   value="{{ old('dept_name', $department->dept_name) }}" 
                                   required
                                   placeholder="Enter department name">
                            @error('dept_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Provide a unique name for the department.
                            </small>
                        </div>

                        {{-- HEC Level Selection --}}
                        <div class="col-md-6 mb-3">
                            <label for="hec_id" class="form-label">
                                <i class="fas fa-level-up-alt me-1 text-success"></i>Department HEC Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-control @error('hec_id') is-invalid @enderror" 
                                    id="hec_id" 
                                    name="hec_id" 
                                    required>
                                <option value="">Select HEC Level</option>
                                @foreach ($hecs as $hec)
                                    <option value="{{ $hec->id }}"
                                        {{ old('hec_id', $department->hec_id) == $hec->id ? 'selected' : '' }}>
                                        {{ $hec->hec_level_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('hec_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Choose the HEC level associated with this department.
                            </small>
                        </div>
                    </div>

                    <div class="row">
                        {{-- HEC Member Selection --}}
                        <div class="col-md-6 mb-3">
                            <label for="hec_member_id" class="form-label">
                                <i class="fas fa-user-shield me-1 text-success"></i>HEC Member (COO/CMS) <span class="text-muted">(Optional)</span>
                            </label>
                            <select class="form-control @error('hec_member_id') is-invalid @enderror" 
                                    id="hec_member_id" 
                                    name="hec_member_id">
                                <option value="">-- Select HEC Member --</option>
                                @foreach ($hecMembers ?? [] as $hecMember)
                                    <option value="{{ $hecMember->id }}"
                                        {{ old('hec_member_id', $department->hec_member_id) == $hecMember->id ? 'selected' : '' }}>
                                        {{ $hecMember->fname }} {{ $hecMember->lname }}
                                        @if ($hecMember->employee_id)
                                            ({{ $hecMember->employee_id }})
                                        @endif
                                        - 
                                        @foreach ($hecMember->roles as $role)
                                            {{ strtoupper($role->name) }}
                                        @endforeach
                                    </option>
                                @endforeach
                            </select>
                            @error('hec_member_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Select the HEC member (COO or CMS) who will review requisitions from this department.
                            </small>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Department Description --}}
                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-info-circle me-1 text-success"></i>Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      required
                                      rows="4"
                                      placeholder="Enter a brief description of the department">{{ old('description', $department->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Provide a detailed description of the department's functions.
                            </small>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('department.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Update Department
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .form-control:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
</style>
@endsection
