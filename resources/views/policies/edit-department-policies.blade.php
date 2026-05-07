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
    }
</style>

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Edit Department Policy</h5>
                        </div>
                        <br>
                        <div class="container">
                            <form action="{{ route('policies.update-department', $departmentPolicy->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <!-- Select Departments -->
                                <div class="form-group mb-3">
                                    <label for="department_id">Select Departments</label>
                                    <select class="form-select" name="department_id[]" id="department_id" multiple required>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}"
                                                {{ $departmentPolicy->departments->contains('id', $department->id) ? 'selected' : '' }}>
                                                {{ $department->dept_name }}
                                            </option>
                                        @endforeach
                                    </select>



                                    <small class="form-text text-muted">Select multiple departments to assign the
                                        policy.</small>
                                </div>

                                <!-- Title -->
                                <div class="form-group mb-3">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" id="title" name="title"
                                        value="{{ old('title', $departmentPolicy->title) }}" required>
                                    @error('title')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Content -->
                                <div class="form-group mb-3">
                                    <label for="content">Description</label>
                                    <textarea id="content" class="form-control" name="content" rows="6">{{ old('content', $departmentPolicy->content) }}</textarea>
                                    @error('content')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Actions -->
                                <div class="form-group mb-3">
                                    <button type="submit" class="btn btn-primary">Update Policy</button>
                                    <a href="{{ route('policies.index') }}" class="btn btn-secondary">Back</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/38.0.1/classic/ckeditor.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2
            try {
                $('#department_id').select2({
                    placeholder: "Choose Departments",
                    allowClear: true,
                    width: '100%'
                });
            } catch (e) {
                console.error('Select2 initialization failed:', e);
            }

            // Initialize CKEditor
            try {
                ClassicEditor.create(document.querySelector('#content'))
                    .catch(error => console.error('CKEditor initialization failed:', error));
            } catch (e) {
                console.error('CKEditor failed:', e);
            }
        });
    </script>
@endsection
