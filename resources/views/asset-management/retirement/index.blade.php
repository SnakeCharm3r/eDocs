@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-ban me-2 text-danger"></i>Retired Assets
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
                                    <label class="form-label">Filter by Reason</label>
                                    <select name="reason" class="form-select">
                                        <option value="">All Reasons</option>
                                        <option value="Obsolete" {{ request('reason') == 'Obsolete' ? 'selected' : '' }}>Obsolete</option>
                                        <option value="Damaged" {{ request('reason') == 'Damaged' ? 'selected' : '' }}>Damaged</option>
                                        <option value="Broken" {{ request('reason') == 'Broken' ? 'selected' : '' }}>Broken</option>
                                        <option value="End of Life" {{ request('reason') == 'End of Life' ? 'selected' : '' }}>End of Life</option>
                                        <option value="Sold" {{ request('reason') == 'Sold' ? 'selected' : '' }}>Sold</option>
                                        <option value="Donated" {{ request('reason') == 'Donated' ? 'selected' : '' }}>Donated</option>
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
                                        <th>Retirement Date</th>
                                        <th>Asset Tag</th>
                                        <th>Category</th>
                                        <th>Reason</th>
                                        <th>Retired By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($retirements as $retirement)
                                        <tr>
                                            <td>{{ $retirement->retirement_date->format('d M Y') }}</td>
                                            <td><strong>{{ $retirement->asset->asset_code }}</strong></td>
                                            <td>{{ $retirement->asset->category->name ?? 'N/A' }}</td>
                                            <td><span class="badge bg-danger">{{ $retirement->reason }}</span></td>
                                            <td>{{ $retirement->retiredBy->username ?? 'N/A' }}</td>
                                            <td>
                                                <a href="{{ route('asset-management.assets.show', $retirement->asset->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No retired assets found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $retirements->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

