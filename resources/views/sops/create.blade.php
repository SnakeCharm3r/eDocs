@extends('layouts.template_noscripts')

<!-- External Styles -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-12 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-normal"> Standard Operating Procedure (SOP)</h6>
                </div>
            </div>
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
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
            <!-- Form Card -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('sops.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- SOP Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">SOP Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>

                        <!-- PDF Upload -->
                        <div class="mb-3">
                            <label for="pdf" class="form-label">Upload PDF <span class="text-danger">*</span></label>
                            <input type="file" name="pdf" id="pdf" class="form-control"
                                accept="application/pdf" required>
                            <small class="text-muted">
                                <span class="text-success">Please upload a PDF file not larger than 5 MB.</span>
                            </small>
                        </div>

                        <!-- Department Selection -->
                        <div class="mb-3">
                            <label for="departments" class="form-label">Select Departments</label>
                            <select class="form-select" id="departments" name="departments[]" multiple required>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->dept_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Global SOP Option -->
                        <div class="form-check mb-3">
                            <input type="checkbox" name="global" value="1" id="global" class="form-check-input">
                            <label for="global" class="form-check-label">This SOP applies to <strong>All
                                    Departments</strong></label>
                        </div>

                        <!-- Submit Button -->
                        <a href="{{ route('sops.index') }}" class="btn btn-secondary">← Back</a>
                        <button type="submit" class="btn btn-primary">Save SOP</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#departments').select2({
                placeholder: "Select departments",
                width: '100%'
            });

            $('#global').on('change', function() {
                $('#departments').prop('disabled', this.checked);
            });
        });
    </script>
@endsection
