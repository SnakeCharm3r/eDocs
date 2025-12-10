@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-plus me-2 text-success"></i>Create New Asset
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
                        <form action="{{ route('asset-management.assets.store') }}" method="POST">
                            @csrf
                            
                            <!-- Basic Information -->
                            <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category_id') is-invalid @enderror" name="category_id" id="category_id" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" 
                                                data-prefix="{{ $cat->tag_prefix ?? 'AST' }}" 
                                                {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }} ({{ $cat->tag_prefix ?? 'N/A' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Asset Tag <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('asset_code') is-invalid @enderror" name="asset_code" id="asset_code" value="{{ $nextCode }}" required>
                                    @error('asset_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Next available tag: <strong id="next_tag_display">{{ $nextCode }}</strong></small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                                        <option value="Available" {{ old('status', 'Available') == 'Available' ? 'selected' : '' }}>Available</option>
                                        <option value="Maintenance" {{ old('status') == 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                                        <option value="Retired" {{ old('status') == 'Retired' ? 'selected' : '' }}>Retired</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Assets are created as "Available" by default. Assign them later from the Assign Assets page.</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" name="brand" value="{{ old('brand') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Model</label>
                                    <input type="text" class="form-control" name="model" value="{{ old('model') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date') }}">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Warranty Expiry</label>
                                    <input type="date" class="form-control" name="warranty_expiry" value="{{ old('warranty_expiry') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Specifications</label>
                                    <textarea class="form-control" name="specifications" rows="2">{{ old('specifications') }}</textarea>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Initial Location (Optional) -->
                            <h5 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>Initial Location (Optional)</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> You can assign this asset to a user/department later from the "Assign Assets" page. These fields are optional for initial inventory location.
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Entity</label>
                                    <select class="form-select" name="division_id" id="division_id">
                                        <option value="">Select Entity</option>
                                        @foreach($divisions as $div)
                                            <option value="{{ $div->id }}" {{ old('division_id') == $div->id ? 'selected' : '' }}>{{ $div->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="department_id" id="department_id">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->dept_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Location/Branch</label>
                                    <select class="form-select" name="location_id" id="location_id">
                                        <option value="">Select Location</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Custom Location</label>
                                    <input type="text" class="form-control" name="custom_location" value="{{ old('custom_location') }}" placeholder="e.g., Storage Room, Warehouse">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">Create Asset</button>
                                <a href="{{ route('asset-management.assets.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const assetTagInput = document.getElementById('asset_code');
    const nextTagDisplay = document.getElementById('next_tag_display');
    
    categorySelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const prefix = selectedOption.getAttribute('data-prefix');
        const categoryId = this.value;
        
        if (categoryId && prefix) {
            // Fetch next tag for this category via AJAX
            fetch(`/asset-management/assets/get-next-tag/${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.next_tag) {
                        assetTagInput.value = data.next_tag;
                        nextTagDisplay.textContent = data.next_tag;
                    }
                })
                .catch(error => {
                    console.error('Error fetching next tag:', error);
                    // Fallback: generate tag based on prefix
                    const fallbackTag = prefix + '-0001';
                    assetTagInput.value = fallbackTag;
                    nextTagDisplay.textContent = fallbackTag;
                });
                } else {
                    assetTagInput.value = 'AST-0001';
                    nextTagDisplay.textContent = 'AST-0001';
                }
    });
    
    // Trigger on page load if category is pre-selected
    if (categorySelect.value) {
        categorySelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection

