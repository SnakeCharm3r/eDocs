@extends('layouts.template_noscripts')
@include('sweetalert::alert')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    .select2-container {
        width: 100% !important;
        min-width: 300px;
    }

    .select2-selection--multiple {
        min-height: 70px !important;
        overflow-y: auto;
        border: 1px solid #ced4da !important;
        padding: 5px;
    }

    .select2-selection__choice {
        background-color: #e9ecef !important;
        border: 1px solid #ced4da !important;
        margin: 2px;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
    }

    .select2-selection__choice__remove {
        display: none !important;
        /* Hide the red 'x' */
    }

    select[multiple] {
        height: 120px;
        width: 100%;
        min-width: 300px;
    }
</style>

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Add Policy to Specific Departments</h5>
                        </div>
                        <br>
                        <div class="container">
                            <form action="{{ route('policies.store-department') }}" method="POST">
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="department_id">Select Departments</label>
                                    <select class="form-select" id="department_id" name="department_id[]" multiple required>
                                        <option value="">Choose Departments</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->dept_name }}</option>
                                        @endforeach
                                    </select>

                                    <small class="form-text text-muted">Select multiple departments to assign the policy.
                                    </small>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                    @error('title')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group mb-3">
                                    <label for="content">Description</label>
                                    <textarea id="content" class="form-control" name="content"></textarea>
                                    @error('content')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group mb-3">
                                    <button type="submit" class="btn btn-primary">Create Department Policy</button>
                                    <a href="{{ route('policies.index') }}" class="btn btn-secondary">Back</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Load jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Load Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Load CKEditor -->
    <script src="https://cdn.ckeditor.com/ckeditor5/38.0.1/classic/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 with error handling
            try {
                if (typeof jQuery !== 'undefined' && typeof $('#department_id').select2 === 'function') {
                    $('#department_id').select2({
                        placeholder: "Choose Departments",
                        allowClear: true,
                        width: '100%'
                    });
                } else {
                    console.error('Select2 or jQuery is not loaded properly.');
                }
            } catch (e) {
                console.error('Select2 initialization failed:', e);
            }

            // Initialize CKEditor with error handling
            try {
                ClassicEditor.create(document.querySelector('#content'))
                    .catch(error => console.error('CKEditor initialization failed:', error));
            } catch (e) {
                console.error('CKEditor initialization failed:', e);
            }

            // Safeguard for updateHeaderClock if it exists
            if (typeof updateHeaderClock === 'function') {
                try {
                    updateHeaderClock();
                } catch (e) {
                    console.warn('updateHeaderClock failed:', e);
                }
            }
        });
    </script>
@endsection
