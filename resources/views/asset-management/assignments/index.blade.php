@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-user-check me-2 text-success"></i>Assign Assets
                    </h3>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('asset-management.assignments.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Tag, Brand, Model...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Available Assets Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Available Assets for Assignment</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Asset Tag</th>
                                <th>Category</th>
                                <th>Brand/Model</th>
                                <th>Current Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assets as $asset)
                                <tr>
                                    <td><strong>{{ $asset->asset_code }}</strong></td>
                                    <td>{{ $asset->category->name ?? 'N/A' }}</td>
                                    <td>{{ $asset->brand }} {{ $asset->model }}</td>
                                    <td>
                                        @if($asset->location)
                                            {{ $asset->location->name }}
                                        @elseif($asset->custom_location)
                                            {{ $asset->custom_location }}
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('asset-management.assignments.create', $asset->id) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-user-plus"></i> Assign
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="py-4">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <p class="text-muted">No available assets found. All assets are currently assigned or in maintenance.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $assets->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

