@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <br>
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title"><i class="fas fa-clipboard-check me-2"></i>Clearance Workflows</h3>
                        <a href="{{ route('workflow-management.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to All Workflows
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('workflow-management.clearance') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Errors</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <a href="{{ route('workflow-management.clearance') }}" class="btn btn-secondary">
                            <i class="fas fa-redo me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Workflows Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Clearance Workflows</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Current Step</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($workflows as $workflow)
                                @php
                                    $currentHistory = $workflow->histories()->where('status', 0)->latest()->first();
                                    $hasError = false;
                                    if ($currentHistory) {
                                        $hasError = !$currentHistory->attendedBy || $currentHistory->attendedBy->status != 'active';
                                    }
                                @endphp
                                <tr class="{{ $hasError ? 'table-danger' : '' }}">
                                    <td>#{{ $workflow->id }}</td>
                                    <td>
                                        {{ $workflow->user->fname ?? '' }} {{ $workflow->user->lname ?? '' }}
                                        <br><small class="text-muted">{{ $workflow->user->username ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        @if($workflow->work_flow_completed)
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                        @if($hasError)
                                            <span class="badge bg-danger ms-1">Error</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($currentHistory)
                                            {{ $currentHistory->step_name ?? 'N/A' }}
                                            <br>
                                            <small class="text-muted">
                                                @if($currentHistory->attendedBy)
                                                    {{ $currentHistory->attendedBy->fname ?? '' }} {{ $currentHistory->attendedBy->lname ?? '' }}
                                                @else
                                                    <span class="text-danger">User Not Found</span>
                                                @endif
                                            </small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($workflow->created_at)->format('d M Y, H:i') }}</td>
                                    <td>
                                        <a href="{{ route('workflow-management.show-clearance', $workflow->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No clearance workflows found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $workflows->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection












