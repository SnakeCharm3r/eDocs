@extends('layouts.template')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Edit Service Category</h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('service-categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('service-categories.update', $serviceCategory->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-group mb-3">
                                    <label for="name">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $serviceCategory->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="description">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $serviceCategory->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label>Payment Types</label>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">Select payment types available for this category</span>
                                        <a href="{{ route('payment-types.index') }}" class="btn btn-sm btn-outline-info" target="_blank">
                                            <i class="fas fa-cog"></i> Manage Payment Types
                                        </a>
                                    </div>
                                    <div class="row">
                                        @php
                                            $selectedTypes = old('payment_types', $serviceCategory->payment_types ?? []);
                                        @endphp
                                        @forelse($paymentTypes as $paymentType)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="payment_types[]" value="{{ $paymentType->name }}" id="payment_{{ $paymentType->id }}" {{ in_array($paymentType->name, $selectedTypes) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="payment_{{ $paymentType->id }}">
                                                        {{ $paymentType->name }}
                                                        @if($paymentType->description)
                                                            <small class="text-muted d-block">{{ $paymentType->description }}</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="col-12">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    No payment types found. <a href="{{ route('payment-types.create') }}">Create one</a> first.
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $serviceCategory->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Category
                                    </button>
                                    <a href="{{ route('service-categories.index') }}" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection















