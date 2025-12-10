@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-exchange-alt me-2 text-success"></i>Asset Movements
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
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Moved By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($movements as $movement)
                                        <tr>
                                            <td>{{ $movement->movement_date->format('d M Y') }}</td>
                                            <td><strong>{{ $movement->asset->asset_code }}</strong></td>
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
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No movements found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $movements->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

