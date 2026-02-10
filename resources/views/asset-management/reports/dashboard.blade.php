@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-chart-bar me-2 text-success"></i>Asset Management Dashboard
                    </h3>
                </div>
                <div class="col-auto">
                    <div class="btn-group">
                        <a href="{{ route('asset-management.reports.export', ['type' => 'all']) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-file-excel"></i> Export All
                        </a>
                        <a href="{{ route('asset-management.reports.export', ['type' => 'maintenance']) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-file-excel"></i> Export Maintenance
                        </a>
                        <a href="{{ route('asset-management.reports.export', ['type' => 'movements']) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-file-excel"></i> Export Movements
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm" style="border-left: 4px solid #3b82f6;">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Assets</h6>
                        <h3 class="mb-0">{{ number_format($totalAssets) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm" style="border-left: 4px solid #10b981;">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Available</h6>
                        <h3 class="mb-0">{{ number_format($availableAssets) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm" style="border-left: 4px solid #f59e0b;">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">In Maintenance</h6>
                        <h3 class="mb-0">{{ number_format($maintenanceAssets) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm" style="border-left: 4px solid #ef4444;">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Retired</h6>
                        <h3 class="mb-0">{{ number_format($retiredAssets) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Assets by Category -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Assets by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assetsByCategory as $category => $count)
                                        <tr>
                                            <td>{{ $category }}</td>
                                            <td class="text-end"><span class="badge bg-info">{{ $count }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">No data</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assets by Department -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Top Departments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th class="text-end">Assets</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assetsByDepartment as $dept => $count)
                                        <tr>
                                            <td>{{ $dept }}</td>
                                            <td class="text-end"><span class="badge bg-success">{{ $count }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">No data</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent Movements</h5>
                    </div>
                    <div class="card-body">
                        @if($recentMovements->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentMovements as $movement)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>{{ $movement->asset->asset_code }}</strong><br>
                                                <small class="text-muted">{{ $movement->movement_date->format('d M Y') }}</small>
                                            </div>
                                            <div class="text-end">
                                                <small>{{ $movement->movedBy->username ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">No recent movements</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent Maintenance</h5>
                    </div>
                    <div class="card-body">
                        @if($recentMaintenance->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentMaintenance as $maint)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>{{ $maint->asset->asset_code }}</strong><br>
                                                <small class="text-muted">{{ $maint->date_performed->format('d M Y') }} - {{ $maint->maintenance_type }}</small>
                                            </div>
                                            <div class="text-end">
                                                @if($maint->cost)
                                                    <small>{{ number_format($maint->cost, 0) }} TZS</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">No recent maintenance</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Warranty Expiry -->
        @if($upcomingWarrantyExpiry->count() > 0)
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm mb-4 border-warning">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Upcoming Warranty Expiry (Next 3 Months)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Asset Tag</th>
                                            <th>Category</th>
                                            <th>Warranty Expiry</th>
                                            <th>Days Remaining</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($upcomingWarrantyExpiry as $asset)
                                            <tr>
                                                <td><strong>{{ $asset->asset_code }}</strong></td>
                                                <td>{{ $asset->category->name ?? 'N/A' }}</td>
                                                <td>{{ $asset->warranty_expiry->format('d M Y') }}</td>
                                                <td>
                                                    @php
                                                        $days = now()->diffInDays($asset->warranty_expiry, false);
                                                    @endphp
                                                    @if($days < 0)
                                                        <span class="badge bg-danger">Expired {{ abs($days) }} days ago</span>
                                                    @else
                                                        <span class="badge bg-warning">{{ $days }} days</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

