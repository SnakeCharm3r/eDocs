@extends('layouts.template_noscripts')

@section('content')
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row mb-4">
                <div class="col-md-12 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Edit SOP</h4>
                    <a href="{{ route('sops.index') }}" class="btn btn-secondary">← Back to SOP List</a>
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
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit SOP Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('sops.update', $sop->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- SOP Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">SOP Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control"
                                value="{{ old('title', $sop->title) }}" required>
                        </div>

                        <!-- PDF Upload -->
                        <div class="mb-3">
                            <label for="pdf" class="form-label">Upload New PDF (optional) <small class="text-muted">Max
                                    file size: 5 MB.</small>
                            </label>
                            <input type="file" name="pdf" id="pdf" class="form-control"
                                accept="application/pdf">
                            @if ($sop->pdf_path)
                                <small class="text-muted d-block mt-1">
                                    Current File: <a href="{{ asset('storage/' . $sop->pdf_path) }}" target="_blank">View
                                        PDF</a>
                                </small>
                            @endif
                        </div>

                        <!-- Department Selection -->
                        <div class="mb-3">
                            <label for="departments" class="form-label">Select Departments</label>
                            <select class="form-select select2" id="departments" name="departments[]" multiple>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @if ($sop->departments->contains($department->id)) selected @endif>
                                        {{ $department->dept_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Global SOP Checkbox -->
                        <div class="form-check mb-3">
                            <input type="checkbox" name="global" value="1" id="global" class="form-check-input"
                                {{ $sop->is_global ? 'checked' : '' }}>
                            <label for="global" class="form-check-label">This SOP applies to <strong>All
                                    Departments</strong></label>
                        </div>

                        <!-- Submit -->
                        <div class="d-flex">
                            <a href="{{ route('sops.index') }}" class="btn btn-secondary me-2">Back</a>
                            <button type="submit" class="btn btn-primary">Update SOP</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Include jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Initialize Select2 and Handle Global Checkbox -->
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Select departments",
                width: '100%'
            });

            // Disable department select if global is checked
            function toggleDepartments() {
                $('#departments').prop('disabled', $('#global').is(':checked'));
            }

            $('#global').on('change', toggleDepartments);
            toggleDepartments(); // Run on page load
        });
    </script>
@endsection
