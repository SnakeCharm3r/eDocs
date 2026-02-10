@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-exchange-alt me-2 text-success"></i>Record Asset Movement
                    </h3>
                </div>
                <div class="col-auto">
                    <a href="{{ route('asset-management.assets.show', $asset->id) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <strong>Current Location:</strong><br>
                            Entity: {{ $asset->division->name ?? 'N/A' }}<br>
                            Department: {{ $asset->department->dept_name ?? 'N/A' }}<br>
                            Location: {{ $asset->location->name ?? ($asset->custom_location ?? 'N/A') }}
                        </div>

                        <form action="{{ route('asset-management.movements.store', $asset->id) }}" method="POST">
                            @csrf
                            
                            <h5 class="mb-3">New Location</h5>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Entity</label>
                                    <select class="form-select" name="to_division_id" id="to_division_id">
                                        <option value="">Select Entity</option>
                                        @foreach($divisions as $div)
                                            <option value="{{ $div->id }}" {{ old('to_division_id') == $div->id ? 'selected' : '' }}>{{ $div->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="to_department_id" id="to_department_id">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ old('to_department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->dept_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Location/Branch</label>
                                    <select class="form-select" name="to_location_id" id="to_location_id">
                                        <option value="">Select Location</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" {{ old('to_location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Assigned To User</label>
                                    <select class="form-select" name="to_user_id" id="to_user_id">
                                        <option value="">Select User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('to_user_id') == $user->id ? 'selected' : '' }}>{{ $user->username }} ({{ $user->ccbrt_code ?? 'N/A' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Custom Location</label>
                                    <input type="text" class="form-control" name="to_custom_location" value="{{ old('to_custom_location') }}" placeholder="e.g., Room 101">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Movement Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('movement_date') is-invalid @enderror" name="movement_date" value="{{ old('movement_date', date('Y-m-d')) }}" required>
                                    @error('movement_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">Record Movement</button>
                                <a href="{{ route('asset-management.assets.show', $asset->id) }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

