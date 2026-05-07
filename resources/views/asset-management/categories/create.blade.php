@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-plus me-2 text-success"></i>Create Asset Category
                    </h3>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('asset-management.asset-categories.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="tag_prefix" class="form-label">Tag Prefix <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('tag_prefix') is-invalid @enderror" id="tag_prefix" name="tag_prefix" value="{{ old('tag_prefix') }}" placeholder="e.g., LT, DESK, MON" maxlength="10" style="text-transform: uppercase;" required>
                                <small class="form-text text-muted">Prefix for asset tags (e.g., LT for Laptops, DESK for Desktops). Tags will be generated as: PREFIX-XXXX (e.g., LT-0206). Letters and numbers only, will be converted to uppercase.</small>
                                @error('tag_prefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">Create Category</button>
                                <a href="{{ route('asset-management.asset-categories.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

