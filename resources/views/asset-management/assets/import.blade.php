@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-file-import me-2 text-success"></i>Import Assets
                    </h3>
                </div>
                <div class="col-auto">
                    <a href="{{ route('asset-management.assets.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Import Assets from Excel</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('asset-management.assets.import.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="file" class="form-label">Select Excel File <span class="text-danger">*</span></label>
                                <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".xlsx,.xls,.csv" required>
                                @error('file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Accepted formats: .xlsx, .xls, .csv (Max: 10MB)</small>
                            </div>

                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Import Format:</h6>
                                <p class="mb-2">Your Excel file should have the following columns (headers):</p>
                                <ul class="mb-0">
                                    <li><strong>asset_code</strong> (optional - will auto-generate asset tag if not provided)</li>
                                    <li><strong>category</strong> (required - will create if doesn't exist)</li>
                                    <li><strong>brand</strong> (optional)</li>
                                    <li><strong>model</strong> (optional)</li>
                                    <li><strong>status</strong> (optional - defaults to "Available")</li>
                                    <li><strong>division</strong> (optional - must match existing entity name)</li>
                                    <li><strong>department</strong> (optional - must match existing department name)</li>
                                    <li><strong>location</strong> (optional - must match existing location name)</li>
                                    <li><strong>assigned_to</strong> (optional - username or CCBRT code)</li>
                                    <li><strong>line_manager</strong> (optional - username or CCBRT code)</li>
                                    <li><strong>custom_location</strong> (optional)</li>
                                    <li><strong>purchase_date</strong> (optional - YYYY-MM-DD format)</li>
                                    <li><strong>warranty_expiry</strong> (optional - YYYY-MM-DD format)</li>
                                    <li><strong>specifications</strong> (optional)</li>
                                    <li><strong>notes</strong> (optional)</li>
                                </ul>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-upload me-1"></i> Import Assets
                                </button>
                                <a href="{{ route('asset-management.assets.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

