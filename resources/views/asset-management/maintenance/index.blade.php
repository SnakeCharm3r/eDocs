@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-wrench me-2 text-success"></i>Maintenance Records
                    </h3>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="mb-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Filter by Asset</label>
                                    <select name="asset_id" class="form-select">
                                        <option value="">All Assets</option>
                                        @foreach($assets as $ast)
                                            <option value="{{ $ast->id }}" {{ request('asset_id') == $ast->id ? 'selected' : '' }}>{{ $ast->asset_code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Maintenance Type</label>
                                    <select name="maintenance_type" class="form-select">
                                        <option value="">All Types</option>
                                        <option value="Routine" {{ request('maintenance_type') == 'Routine' ? 'selected' : '' }}>Routine</option>
                                        <option value="Repair" {{ request('maintenance_type') == 'Repair' ? 'selected' : '' }}>Repair</option>
                                        <option value="Upgrade" {{ request('maintenance_type') == 'Upgrade' ? 'selected' : '' }}>Upgrade</option>
                                        <option value="Inspection" {{ request('maintenance_type') == 'Inspection' ? 'selected' : '' }}>Inspection</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Asset</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Performed By</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($maintenance as $maint)
                                        <tr>
                                            <td>{{ $maint->date_performed->format('d M Y') }}</td>
                                            <td><strong>{{ $maint->asset->asset_code }}</strong></td>
                                            <td><span class="badge bg-info">{{ $maint->maintenance_type }}</span></td>
                                            <td>{{ Str::limit($maint->description, 50) }}</td>
                                            <td>{{ $maint->performed_by ?? ($maint->performedByUser->username ?? 'N/A') }}</td>
                                            <td>{{ $maint->cost ? number_format($maint->cost, 2) . ' TZS' : 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No maintenance records found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $maintenance->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

