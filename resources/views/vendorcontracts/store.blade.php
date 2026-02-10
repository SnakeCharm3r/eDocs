@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Page Title --}}
        <div class="row mb-3">
            <div class="col-md-12">
                <h3 class="page-title text-muted">
                    <strong><i class="fas fa-file-contract"></i> Create Your Contract</strong>
                </h3>
            </div>
        </div>

        {{-- Check for required data --}}
        @if($vendors->isEmpty() || $divisions->isEmpty() || $departments->isEmpty())
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Some required data is missing:
                <ul class="mb-0">
                    @if($vendors->isEmpty()) <li>No vendors available. Please add vendors first.</li> @endif
                    @if($divisions->isEmpty()) <li>No divisions available. Please add divisions first.</li> @endif
                    @if($departments->isEmpty()) <li>No departments available. Please add departments first.</li> @endif
                </ul>
            </div>
        @endif

        {{-- Form Card --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('vendorContract.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Contract Title & Type --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Contract Title</label>
                            <input type="text" name="title" id="title" class="form-control" required {{ ($vendors->isEmpty() || $divisions->isEmpty() || $departments->isEmpty()) ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-6">
                            <label for="contract_type" class="form-label">Contract Type</label>
                            <input type="text" name="contract_type" id="contract_type" class="form-control" required {{ ($vendors->isEmpty() || $divisions->isEmpty() || $departments->isEmpty()) ? 'disabled' : '' }}>
                        </div>
                    </div>

                    {{-- Vendor, Division, Department --}}
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="vendor_id" class="form-label">Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-control" {{ $vendors->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="division_id" class="form-label">Division</label>
                            <select name="division_id" id="division_id" class="form-control" {{ $divisions->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Select Division</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="department_id" class="form-label">Department</label>
                            <select name="department_id" id="department_id" class="form-control" {{ $departments->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Cost, Duration, End Date --}}
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="cost" class="form-label">Contract Cost</label>
                            <input type="number" name="cost" id="cost" class="form-control" step="0.01" required {{ ($vendors->isEmpty() || $divisions->isEmpty() || $departments->isEmpty()) ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <label for="duration_months" class="form-label">Duration (Months)</label>
                            <input type="number" name="duration_months" id="duration_months" class="form-control" required {{ ($vendors->isEmpty() || $divisions->isEmpty() || $departments->isEmpty()) ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" required {{ ($vendors->isEmpty() || $divisions->isEmpty() || $departments->isEmpty()) ? 'disabled' : '' }}>
                        </div>
                    </div>

                    {{-- File Upload --}}
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="file_path" class="form-label">Upload Contract PDF</label>
                            <input type="file" name="file_path" id="file_path" class="form-control" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required {{ ($vendors->isEmpty() || $divisions->isEmpty() || $departments->isEmpty()) ? 'disabled' : '' }}>
                            <small class="text-muted">PDF or Word files only.</small>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="row mb-3">
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-success" {{ ($vendors->isEmpty() || $divisions->isEmpty() || $departments->isEmpty()) ? 'disabled' : '' }}>
                                <i class="fas fa-plus-circle"></i> Add Contract
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>
@endsection
