@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Page Header --}}
        <div class="d-flex align-items-center mb-4">
            <h3 class="page-title mb-0">Add New Facility Asset</h3>
        </div>

        <div class="card shadow-lg">
            <div class="card-body">

                <form action="{{ route('facilityAssets.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">

                        {{-- Name --}}
                        <div class="col-md-4">
                            <label for="name" class="form-label">Asset Name</label>
                            <input type="text" name="name" id="name" 
                                   value="{{ old('name') }}" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   placeholder="Enter asset name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Type --}}
                        <div class="col-md-4">
                            <label for="type" class="form-label">Asset Type</label>
                            <select name="type" id="type" 
                                    class="form-control @error('type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                <option value="Equipment" {{ old('type')=='Equipment' ? 'selected' : '' }}>Equipment</option>
                                <option value="Furniture" {{ old('type')=='Furniture' ? 'selected' : '' }}>Furniture</option>
                                <option value="Vehicle" {{ old('type')=='Vehicle' ? 'selected' : '' }}>Vehicle</option>
                                <option value="Other" {{ old('type')=='Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Status --}}
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" 
                                    class="form-control @error('status') is-invalid @enderror" required>
                                <option value="">Select Status</option>
                                <option value="active" {{ old('status')=='active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status')=='inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="maintenance" {{ old('status')=='maintenance' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="col-md-6">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="2" 
                                      class="form-control @error('description') is-invalid @enderror" 
                                      placeholder="Brief description">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Model --}}
                        <div class="col-md-3">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" name="model" id="model" 
                                   value="{{ old('model') }}" 
                                   class="form-control @error('model') is-invalid @enderror" 
                                   placeholder="Enter model">
                            @error('model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Manufacturer --}}
                        <div class="col-md-3">
                            <label for="manufacturer" class="form-label">Manufacturer</label>
                            <input type="text" name="manufacturer" id="manufacturer" 
                                   value="{{ old('manufacturer') }}" 
                                   class="form-control @error('manufacturer') is-invalid @enderror" 
                                   placeholder="Manufacturer name">
                            @error('manufacturer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Serial Number --}}
                        <div class="col-md-4">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" id="serial_number" 
                                   value="{{ old('serial_number') }}" 
                                   class="form-control @error('serial_number') is-invalid @enderror" 
                                   placeholder="Serial number">
                            @error('serial_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Purchase Date --}}
                        <div class="col-md-4">
                            <label for="purchase_date" class="form-label">Purchase Date</label>
                            <input type="date" name="purchase_date" id="purchase_date" 
                                   value="{{ old('purchase_date') }}" 
                                   class="form-control @error('purchase_date') is-invalid @enderror">
                            @error('purchase_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Purchase Price --}}
                        <div class="col-md-4">
                            <label for="purchase_price" class="form-label">Purchase Price</label>
                            <input type="number" step="0.01" name="purchase_price" id="purchase_price" 
                                   value="{{ old('purchase_price') }}" 
                                   class="form-control @error('purchase_price') is-invalid @enderror" 
                                   placeholder="Enter price">
                            @error('purchase_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Vendor --}}
                        <div class="col-md-6">
                            <label for="vendor" class="form-label">Vendor</label>
                            <input type="text" name="vendor" id="vendor" 
                                   value="{{ old('vendor') }}" 
                                   class="form-control @error('vendor') is-invalid @enderror" 
                                   placeholder="Vendor name">
                            @error('vendor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Vendor Contact --}}
                        <div class="col-md-6">
                            <label for="vendor_contact" class="form-label">Vendor Contact</label>
                            <input type="text" name="vendor_contact" id="vendor_contact" 
                                   value="{{ old('vendor_contact') }}" 
                                   class="form-control @error('vendor_contact') is-invalid @enderror" 
                                   placeholder="Phone/email">
                            @error('vendor_contact')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Facility Location --}}
                        <div class="col-md-6">
                            <label for="facility_location_id" class="form-label">Facility Location</label>
                            <select name="facility_location_id" id="facility_location_id" 
                                    class="form-control @error('facility_location_id') is-invalid @enderror" required>
                                <option value="">Select Location</option>
                                @foreach($assets as $location)
                                    <option value="{{ $location->id }}" {{ old('facility_location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('facility_location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-4 text-end">
                        <a href="{{ route('facilityAssets.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-success">Add Asset</button>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>
@endsection
