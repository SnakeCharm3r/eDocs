@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<style>
    .department-checkbox {
        cursor: pointer;
    }
    .form-check-label {
        cursor: pointer;
        user-select: none;
    }
    .form-check:hover {
        background-color: #e9ecef;
        border-radius: 4px;
        padding: 2px 4px;
        transition: background-color 0.2s;
    }
    .form-control:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    .card {
        border: none;
    }
    .card-header {
        background-color: #f8f9fa !important;
    }
</style>
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Page Title --}}
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-plus-circle me-2 text-success"></i>Add New Entity
                    </h3>
                    <p class="text-muted mb-0 mt-1">Create a new CCBRT entity and assign departments</p>
                </div>
                <div class="col-auto">
                    <a href="{{ route('division.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Entities
                    </a>
                </div>
            </div>
        </div>

        {{-- Card Section --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-building me-2 text-success"></i>Entity Information
                        </h5>
                    </div>
                    <div class="card-body">

                        {{-- Success/Error Messages --}}
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

                        {{-- Form --}}
                        <form action="{{ route('division.store') }}" method="POST">
                            @csrf

                            {{-- Row 1: Entity Name and Code --}}
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-building me-1 text-success"></i>Entity Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           name="name" 
                                           id="name" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}"
                                           placeholder="e.g., Hospital, NGO, Academy" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="code" class="form-label">
                                        <i class="fas fa-code me-1 text-success"></i>Code <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           name="code" 
                                           id="code" 
                                           class="form-control @error('code') is-invalid @enderror" 
                                           value="{{ old('code') }}"
                                           placeholder="e.g., HOSP, NGO, ACAD" 
                                           required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Unique code for this entity (e.g., HOSP, NGO, ACAD)</small>
                                </div>
                            </div>

                            {{-- Row 2: Description and Status --}}
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-info-circle me-1 text-success"></i>Description
                                    </label>
                                    <textarea name="description" 
                                              id="description" 
                                              class="form-control @error('description') is-invalid @enderror" 
                                              placeholder="Enter a brief description of this entity..." 
                                              rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-toggle-on me-1 text-success"></i>Status <span class="text-danger">*</span>
                                    </label>
                                    <select name="status" 
                                            id="status" 
                                            class="form-control @error('status') is-invalid @enderror" 
                                            required>
                                        <option value="" disabled {{ old('status') == '' ? 'selected' : '' }}>-- Select Status --</option>
                                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Row 3: Departments --}}
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-sitemap me-1 text-success"></i>Departments <span class="text-danger">*</span>
                                    </label>
                                    <div class="border rounded p-3" style="max-height: 350px; overflow-y: auto; background-color: #f8f9fa;">
                                        <div class="mb-3 d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-success" id="selectAllDepts">
                                                <i class="fas fa-check-square"></i> Select All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllDepts">
                                                <i class="fas fa-square"></i> Deselect All
                                            </button>
                                            <span class="ms-auto align-self-center text-muted">
                                                <span id="selected-count">0</span> department(s) selected
                                            </span>
                                        </div>
                                        <div class="row">
                                            @php
                                                $selectedDeptIds = old('departments', []);
                                            @endphp
                                            @foreach($departments as $dept)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input department-checkbox" 
                                                               type="checkbox" 
                                                               name="departments[]" 
                                                               value="{{ $dept->id }}" 
                                                               id="dept_{{ $dept->id }}"
                                                               {{ in_array($dept->id, $selectedDeptIds) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="dept_{{ $dept->id }}">
                                                            {{ $dept->dept_name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Select one or more departments to assign to this entity
                                    </small>
                                    <div id="departments-error" class="text-danger mt-1" style="display: none;">
                                        <i class="fas fa-exclamation-circle"></i> Please select at least one department.
                                    </div>
                                    @error('departments')
                                        <div class="text-danger mt-1">
                                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Submit Buttons --}}
                            <div class="row mt-4">
                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="{{ route('division.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Create Entity
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
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Select All functionality
    $('#selectAllDepts').on('click', function() {
        $('.department-checkbox').prop('checked', true);
        validateDepartments();
    });

    // Deselect All functionality
    $('#deselectAllDepts').on('click', function() {
        $('.department-checkbox').prop('checked', false);
        validateDepartments();
    });

    // Validate departments on checkbox change
    $('.department-checkbox').on('change', function() {
        validateDepartments();
    });

    function validateDepartments() {
        var checkedCount = $('.department-checkbox:checked').length;
        $('#selected-count').text(checkedCount);
        if (checkedCount === 0) {
            $('#departments-error').show();
        } else {
            $('#departments-error').hide();
        }
    }

    // Form submission validation
    $('form').on('submit', function(e) {
        var checkedCount = $('.department-checkbox:checked').length;
        if (checkedCount === 0) {
            e.preventDefault();
            $('#departments-error').show();
            $('html, body').animate({
                scrollTop: $('#departments-error').offset().top - 100
            }, 500);
            return false;
        }
    });

    // Update selected count on page load
    function updateSelectedCount() {
        var checkedCount = $('.department-checkbox:checked').length;
        $('#selected-count').text(checkedCount);
    }
    
    // Initial validation and count update
    validateDepartments();
    updateSelectedCount();
});
</script>
@endsection
