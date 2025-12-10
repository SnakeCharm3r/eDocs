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
                        <i class="fas fa-laptop me-2 text-success"></i>Add Assets
                    </h3>
                </div>
                <div class="col-auto">
                    <div class="btn-group">
                        <a href="{{ route('asset-management.assets.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Add Asset
                        </a>
                        <a href="{{ route('asset-management.assets.import') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-file-import"></i> Import
                        </a>
                        <a href="{{ route('asset-management.assets.export') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-export"></i> Export
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('asset-management.assets.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Code, Brand, Model...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Available" {{ request('status') == 'Available' ? 'selected' : '' }}>Available</option>
                            <option value="Assigned" {{ request('status') == 'Assigned' ? 'selected' : '' }}>Assigned</option>
                            <option value="Maintenance" {{ request('status') == 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="Retired" {{ request('status') == 'Retired' ? 'selected' : '' }}>Retired</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">All</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->dept_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Entity</label>
                        <select name="division_id" class="form-select">
                            <option value="">All</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}" {{ request('division_id') == $div->id ? 'selected' : '' }}>{{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Assets Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Asset Tag</th>
                                <th>Category</th>
                                <th>Brand/Model</th>
                                <th>Status</th>
                                <th>Entity</th>
                                <th>Department</th>
                                <th>Location</th>
                                <th>Assigned To</th>
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
                                        @php
                                            $statusColors = [
                                                'Available' => 'success',
                                                'Assigned' => 'primary',
                                                'Maintenance' => 'warning',
                                                'Retired' => 'danger'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusColors[$asset->status] ?? 'secondary' }}">{{ $asset->status }}</span>
                                    </td>
                                    <td>{{ $asset->division->name ?? 'N/A' }}</td>
                                    <td>{{ $asset->department->dept_name ?? 'N/A' }}</td>
                                    <td>{{ $asset->location->name ?? ($asset->custom_location ?? 'N/A') }}</td>
                                    <td>{{ $asset->assignedTo->username ?? 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('asset-management.assets.show', $asset->id) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('asset-management.assets.edit', $asset->id) }}" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($asset->status === 'Available')
                                                <a href="{{ route('asset-management.assignments.create', $asset->id) }}" class="btn btn-sm btn-success" title="Assign">
                                                    <i class="fas fa-user-plus"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No assets found</td>
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

