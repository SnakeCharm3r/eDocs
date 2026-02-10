@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-laptop me-2 text-success"></i>Asset Details: {{ $asset->asset_code }}
                    </h3>
                </div>
                <div class="col-auto">
                    <div class="btn-group">
                        <a href="{{ route('asset-management.assets.edit', $asset->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('asset-management.assets.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Asset Information -->
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Asset Tag:</strong><br>
                                <span class="badge bg-primary">{{ $asset->asset_code }}</span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Category:</strong><br>
                                {{ $asset->category->name ?? 'N/A' }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Brand:</strong><br>
                                {{ $asset->brand ?? 'N/A' }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Model:</strong><br>
                                {{ $asset->model ?? 'N/A' }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Status:</strong><br>
                                @php
                                    $statusColors = [
                                        'Available' => 'success',
                                        'Assigned' => 'primary',
                                        'Maintenance' => 'warning',
                                        'Retired' => 'danger'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$asset->status] ?? 'secondary' }}">{{ $asset->status }}</span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Purchase Date:</strong><br>
                                {{ $asset->purchase_date ? $asset->purchase_date->format('d M Y') : 'N/A' }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Warranty Expiry:</strong><br>
                                @if($asset->warranty_expiry)
                                    {{ $asset->warranty_expiry->format('d M Y') }}
                                    @if($asset->warranty_expiry->isPast())
                                        <span class="badge bg-danger">Expired</span>
                                    @elseif($asset->warranty_expiry->diffInDays(now()) <= 30)
                                        <span class="badge bg-warning">Expiring Soon</span>
                                    @endif
                                @else
                                    N/A
                                @endif
                            </div>
                            @if($asset->specifications)
                                <div class="col-md-12 mb-3">
                                    <strong>Specifications:</strong><br>
                                    <p class="mb-0">{{ $asset->specifications }}</p>
                                </div>
                            @endif
                            @if($asset->notes)
                                <div class="col-md-12 mb-3">
                                    <strong>Notes:</strong><br>
                                    <p class="mb-0">{{ $asset->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Location & Assignment -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Location & Assignment</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Entity:</strong><br>
                                {{ $asset->division->name ?? 'N/A' }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Department:</strong><br>
                                {{ $asset->department->dept_name ?? 'N/A' }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Location/Branch:</strong><br>
                                {{ $asset->location->name ?? ($asset->custom_location ?? 'N/A') }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Custom Location:</strong><br>
                                {{ $asset->custom_location ?? 'N/A' }}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Assigned To:</strong><br>
                                @if($asset->assignedTo)
                                    {{ $asset->assignedTo->username }} ({{ $asset->assignedTo->ccbrt_code ?? 'N/A' }})
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Line Manager:</strong><br>
                                @if($asset->lineManager)
                                    {{ $asset->lineManager->username }} ({{ $asset->lineManager->ccbrt_code ?? 'N/A' }})
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignment Information -->
                @if($asset->status === 'Assigned' && $asset->assignedTo)
                    <div class="card shadow-sm mb-4 border-primary">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user-check me-2"></i>Current Assignment</h5>
                            <form action="{{ route('asset-management.assignments.unassign', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to unassign this asset?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light">
                                    <i class="fas fa-user-minus"></i> Unassign
                                </button>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Assigned To:</strong><br>
                                    {{ $asset->assignedTo->username }} ({{ $asset->assignedTo->ccbrt_code ?? 'N/A' }})
                                </div>
                                @if($asset->lineManager)
                                    <div class="col-md-6 mb-3">
                                        <strong>Line Manager:</strong><br>
                                        {{ $asset->lineManager->username }} ({{ $asset->lineManager->ccbrt_code ?? 'N/A' }})
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Movements History -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Movement History</h5>
                        @if($asset->status !== 'Retired')
                            <a href="{{ route('asset-management.movements.create', $asset->id) }}" class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> Record Movement
                            </a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($asset->movements->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Moved By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($asset->movements->sortByDesc('movement_date') as $movement)
                                            <tr>
                                                <td>{{ $movement->movement_date->format('d M Y') }}</td>
                                                <td>
                                                    {{ $movement->fromDivision->name ?? 'N/A' }} / 
                                                    {{ $movement->fromDepartment->dept_name ?? 'N/A' }} / 
                                                    {{ $movement->fromLocation->name ?? ($movement->from_custom_location ?? 'N/A') }}
                                                </td>
                                                <td>
                                                    {{ $movement->toDivision->name ?? 'N/A' }} / 
                                                    {{ $movement->toDepartment->dept_name ?? 'N/A' }} / 
                                                    {{ $movement->toLocation->name ?? ($movement->to_custom_location ?? 'N/A') }}
                                                </td>
                                                <td>{{ $movement->movedBy->username ?? 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No movement history recorded.</p>
                        @endif
                    </div>
                </div>

                <!-- Maintenance History -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-wrench me-2"></i>Maintenance History</h5>
                        @if($asset->status !== 'Retired')
                            <a href="{{ route('asset-management.maintenance.create', $asset->id) }}" class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> Add Maintenance
                            </a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($asset->maintenance->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Performed By</th>
                                            <th>Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($asset->maintenance->sortByDesc('date_performed') as $maintenance)
                                            <tr>
                                                <td>{{ $maintenance->date_performed->format('d M Y') }}</td>
                                                <td><span class="badge bg-info">{{ $maintenance->maintenance_type }}</span></td>
                                                <td>{{ Str::limit($maintenance->description, 50) }}</td>
                                                <td>{{ $maintenance->performed_by ?? ($maintenance->performedByUser->username ?? 'N/A') }}</td>
                                                <td>{{ $maintenance->cost ? number_format($maintenance->cost, 2) . ' TZS' : 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No maintenance records found.</p>
                        @endif
                    </div>
                </div>

                <!-- Retirement Information -->
                @if($asset->retirement)
                    <div class="card shadow-sm mb-4 border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-ban me-2"></i>Retirement Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Retirement Date:</strong><br>
                                    {{ $asset->retirement->retirement_date->format('d M Y') }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Reason:</strong><br>
                                    <span class="badge bg-danger">{{ $asset->retirement->reason }}</span>
                                </div>
                                @if($asset->retirement->notes)
                                    <div class="col-md-12 mt-3">
                                        <strong>Notes:</strong><br>
                                        {{ $asset->retirement->notes }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif($asset->status !== 'Retired')
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-ban me-2"></i>Retire Asset</h5>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('asset-management.retirement.create', $asset->id) }}" class="btn btn-danger">
                                <i class="fas fa-ban"></i> Retire This Asset
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Quick Actions Sidebar -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        @if($asset->status !== 'Retired')
                            <div class="d-grid gap-2">
                                @if($asset->status === 'Available')
                                    <a href="{{ route('asset-management.assignments.create', $asset->id) }}" class="btn btn-success">
                                        <i class="fas fa-user-plus me-1"></i> Assign Asset
                                    </a>
                                @elseif($asset->status === 'Assigned')
                                    <form action="{{ route('asset-management.assignments.unassign', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to unassign this asset?')">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-user-minus me-1"></i> Unassign Asset
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('asset-management.movements.create', $asset->id) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-exchange-alt me-1"></i> Record Movement
                                </a>
                                <a href="{{ route('asset-management.maintenance.create', $asset->id) }}" class="btn btn-outline-warning">
                                    <i class="fas fa-wrench me-1"></i> Add Maintenance
                                </a>
                                <a href="{{ route('asset-management.retirement.create', $asset->id) }}" class="btn btn-outline-danger">
                                    <i class="fas fa-ban me-1"></i> Retire Asset
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

