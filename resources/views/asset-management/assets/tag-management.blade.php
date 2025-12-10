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
                        <i class="fas fa-tags me-2 text-success"></i>Asset Tag Management
                    </h3>
                </div>
                <div class="col-auto">
                    <a href="{{ route('asset-management.assets.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Assets
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Summary Cards -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4" style="border-left: 4px solid #3b82f6;">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Assets</h6>
                        <h2 class="mb-0">{{ number_format($totalAssets) }}</h2>
                        <small class="text-muted">Currently registered in system</small>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm mb-4" style="border-left: 4px solid #10b981;">
                    <div class="card-body">
                        <h6 class="text-muted mb-3">Next Asset Tags by Category</h6>
                        <div class="row">
                            @foreach($categories as $category)
                                <div class="col-md-6 mb-2">
                                    <strong>{{ $category->name }}:</strong> 
                                    <span class="badge bg-success">{{ $categoryTags[$category->id] ?? 'N/A' }}</span>
                                    <small class="text-muted">({{ $category->assets_count }} assets)</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm mb-4" style="border-left: 4px solid #f59e0b;">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Tag Format</h6>
                        <h6 class="mb-0">PREFIX-XXXX</h6>
                        <small class="text-muted">Example: LT-0206</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tag Information -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Asset Tag Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb me-2"></i>How Asset Tags Work:</h6>
                            <ul class="mb-0">
                                <li>Asset tags are automatically generated in the format: <strong>PREFIX-XXXX</strong> (e.g., LT-0206)</li>
                                <li>Each category has its own tag prefix (e.g., LT for Laptops, DESK for Desktops)</li>
                                <li>The number (XXXX) is auto-incremented based on the number of assets in that category</li>
                                <li>When you create a new asset, the next available tag for the selected category is automatically suggested</li>
                                <li>You can manually enter a custom tag if needed, but it must be unique</li>
                                <li>Tags are used to uniquely identify each asset in the system</li>
                            </ul>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>Current Statistics:</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Total Assets:</strong></td>
                                        <td>{{ number_format($totalAssets) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Asset ID:</strong></td>
                                        <td>{{ $totalAssets > 0 ? $totalAssets : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tag Format:</strong></td>
                                        <td><span class="badge bg-success">PREFIX-XXXX</span></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Quick Actions:</h6>
                                <div class="d-grid gap-2">
                                    <a href="{{ route('asset-management.assets.create') }}" class="btn btn-success">
                                        <i class="fas fa-plus me-1"></i> Create New Asset
                                    </a>
                                    <a href="{{ route('asset-management.assets.index') }}" class="btn btn-primary">
                                        <i class="fas fa-list me-1"></i> View All Assets
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Assets -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recently Added Assets</h5>
                    </div>
                    <div class="card-body">
                        @if($recentAssets->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Asset Tag</th>
                                            <th>Date Added</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentAssets as $asset)
                                            <tr>
                                                <td><strong>{{ $asset->asset_code }}</strong></td>
                                                <td>{{ $asset->created_at->format('d M Y, h:i A') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No assets have been created yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

