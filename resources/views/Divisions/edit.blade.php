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
    }
</style>
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Page Title --}}
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-edit me-2 text-success"></i>Edit Entity
                    </h3>
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
                            <i class="fas fa-building me-2 text-success"></i>Edit Entity: {{ $division->name }}
                        </h5>
                    </div>
                    <div class="card-body">

                        {{-- Success/Error Messages --}}
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        {{-- Form --}}
                        <form action="{{ route('division.update', $division->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            {{-- Row 1 --}}
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Entity Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" 
                                           class="form-control" 
                                           value="{{ old('name', $division->name) }}"
                                           placeholder="example: Hospital" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" id="code" 
                                           class="form-control" 
                                           value="{{ old('code', $division->code) }}"
                                           placeholder="Enter Code..." required>
                                </div>
                            </div>

                            {{-- Row 2 --}}
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" 
                                           class="form-control" 
                                           placeholder="Enter Description..." rows="3">{{ old('description', $division->description) }}</textarea>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="active" {{ old('status', $division->status) == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $division->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Row 3 --}}
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Departments <span class="text-danger">*</span></label>
                                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                                        <div class="mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllDepts">
                                                <i class="fas fa-check-square"></i> Select All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllDepts">
                                                <i class="fas fa-square"></i> Deselect All
                                            </button>
                                        </div>
                                        <div class="row">
                                            @php
                                                $selectedDeptIds = old('departments', $division->departments->pluck('id')->toArray());
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
                                    <small class="form-text text-muted">Select one or more departments for this entity</small>
                                    <div id="departments-error" class="text-danger" style="display: none;">Please select at least one department.</div>
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <div class="text-end">
                                <a href="{{ route('division.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Update Entity
                                </button>
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

    // Initial validation
    validateDepartments();
});
</script>
@endsection

