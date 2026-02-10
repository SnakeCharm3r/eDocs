@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-edit me-2 text-success"></i>Edit Asset
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
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('asset-management.assets.update', $asset->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <!-- Basic Information -->
                            <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Asset Tag <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('asset_code') is-invalid @enderror" name="asset_code" value="{{ old('asset_code', $asset->asset_code) }}" required>
                                    @error('asset_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category_id') is-invalid @enderror" name="category_id" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ old('category_id', $asset->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                                        <option value="Available" {{ old('status', $asset->status) == 'Available' ? 'selected' : '' }}>Available</option>
                                        <option value="Assigned" {{ old('status', $asset->status) == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                                        <option value="Maintenance" {{ old('status', $asset->status) == 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                                        <option value="Retired" {{ old('status', $asset->status) == 'Retired' ? 'selected' : '' }}>Retired</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" name="brand" value="{{ old('brand', $asset->brand) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Model</label>
                                    <input type="text" class="form-control" name="model" value="{{ old('model', $asset->model) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Warranty Expiry</label>
                                    <input type="date" class="form-control" name="warranty_expiry" value="{{ old('warranty_expiry', $asset->warranty_expiry?->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Specifications</label>
                                    <textarea class="form-control" name="specifications" rows="2">{{ old('specifications', $asset->specifications) }}</textarea>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Location & Assignment -->
                            <h5 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>Location & Assignment</h5>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Entity</label>
                                    <select class="form-select" name="division_id" id="division_id">
                                        <option value="">Select Entity</option>
                                        @foreach($divisions as $div)
                                            <option value="{{ $div->id }}" {{ old('division_id', $asset->division_id) == $div->id ? 'selected' : '' }}>{{ $div->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="department_id" id="department_id">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ old('department_id', $asset->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->dept_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Location/Branch</label>
                                    <select class="form-select" name="location_id" id="location_id">
                                        <option value="">Select Location</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" {{ old('location_id', $asset->location_id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Assigned To</label>
                                    <select class="form-select" name="assigned_to_user_id" id="assigned_to_user_id">
                                        <option value="">Select User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to_user_id', $asset->assigned_to_user_id) == $user->id ? 'selected' : '' }}>{{ $user->username }} ({{ $user->ccbrt_code ?? 'N/A' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Line Manager</label>
                                    <select class="form-select" name="line_manager_id" id="line_manager_id">
                                        <option value="">Select Line Manager</option>
                                        @foreach($users->filter(fn($u) => $u->hasRole('line-manager')) as $lm)
                                            <option value="{{ $lm->id }}" {{ old('line_manager_id', $asset->line_manager_id) == $lm->id ? 'selected' : '' }}>{{ $lm->username }} ({{ $lm->ccbrt_code ?? 'N/A' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Custom Location</label>
                                    <input type="text" class="form-control" name="custom_location" value="{{ old('custom_location', $asset->custom_location) }}" placeholder="e.g., Room 101, Building A">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" rows="3">{{ old('notes', $asset->notes) }}</textarea>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">Update Asset</button>
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

