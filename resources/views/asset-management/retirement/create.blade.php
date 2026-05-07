@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-ban me-2 text-danger"></i>Retire Asset
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
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Retirement Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-4">
                            <strong>Warning:</strong> Retiring this asset will change its status to "Retired" and it will no longer be available for assignment.
                        </div>

                        <div class="alert alert-info mb-4">
                            <strong>Asset:</strong> {{ $asset->asset_code }} - {{ $asset->category->name ?? 'N/A' }}<br>
                            <strong>Brand/Model:</strong> {{ $asset->brand }} {{ $asset->model }}
                        </div>

                        <form action="{{ route('asset-management.retirement.store', $asset->id) }}" method="POST">
                            @csrf
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Retirement Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('retirement_date') is-invalid @enderror" name="retirement_date" value="{{ old('retirement_date', date('Y-m-d')) }}" required>
                                    @error('retirement_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <select class="form-select @error('reason') is-invalid @enderror" name="reason" required>
                                        <option value="Obsolete" {{ old('reason') == 'Obsolete' ? 'selected' : '' }}>Obsolete</option>
                                        <option value="Damaged" {{ old('reason') == 'Damaged' ? 'selected' : '' }}>Damaged</option>
                                        <option value="Broken" {{ old('reason') == 'Broken' ? 'selected' : '' }}>Broken</option>
                                        <option value="End of Life" {{ old('reason') == 'End of Life' ? 'selected' : '' }}>End of Life</option>
                                        <option value="Sold" {{ old('reason') == 'Sold' ? 'selected' : '' }}>Sold</option>
                                        <option value="Donated" {{ old('reason') == 'Donated' ? 'selected' : '' }}>Donated</option>
                                    </select>
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="4">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to retire this asset? This action cannot be undone.')">
                                    <i class="fas fa-ban"></i> Retire Asset
                                </button>
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

