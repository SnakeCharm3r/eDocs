@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-wrench me-2 text-success"></i>Add Maintenance Record
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
                            <strong>Asset:</strong> {{ $asset->asset_code }} - {{ $asset->category->name ?? 'N/A' }}<br>
                            <strong>Brand/Model:</strong> {{ $asset->brand }} {{ $asset->model }}
                        </div>

                        <form action="{{ route('asset-management.maintenance.store', $asset->id) }}" method="POST">
                            @csrf
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('maintenance_type') is-invalid @enderror" name="maintenance_type" required>
                                        <option value="Routine" {{ old('maintenance_type') == 'Routine' ? 'selected' : '' }}>Routine</option>
                                        <option value="Repair" {{ old('maintenance_type') == 'Repair' ? 'selected' : '' }}>Repair</option>
                                        <option value="Upgrade" {{ old('maintenance_type') == 'Upgrade' ? 'selected' : '' }}>Upgrade</option>
                                        <option value="Inspection" {{ old('maintenance_type') == 'Inspection' ? 'selected' : '' }}>Inspection</option>
                                    </select>
                                    @error('maintenance_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date Performed <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('date_performed') is-invalid @enderror" name="date_performed" value="{{ old('date_performed', date('Y-m-d')) }}" required>
                                    @error('date_performed')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4" required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Performed By (Name)</label>
                                    <input type="text" class="form-control" name="performed_by" value="{{ old('performed_by') }}" placeholder="External technician name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Performed By (User)</label>
                                    <select class="form-select" name="performed_by_user_id">
                                        <option value="">Select User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('performed_by_user_id') == $user->id ? 'selected' : '' }}>{{ $user->username }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Cost (TZS)</label>
                                    <input type="number" step="0.01" class="form-control" name="cost" value="{{ old('cost') }}" placeholder="0.00">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">Save Maintenance Record</button>
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

