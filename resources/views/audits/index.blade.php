@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.1.0/css/buttons.bootstrap5.min.css">
<style>
    .badge.bg-purple {
        background-color: #6f42c1 !important;
        color: white;
    }
    
    #auditTable_wrapper .dt-buttons {
        margin-bottom: 1rem;
    }
    
    #auditTable_wrapper .dt-buttons .btn-link {
        border: none;
        background: transparent;
        text-decoration: none;
        font-size: 1.2rem;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }
    
    #auditTable_wrapper .dt-buttons .btn-link:hover {
        transform: scale(1.1);
        opacity: 0.8;
        text-decoration: none;
    }
    
    #auditTable_wrapper .dt-buttons .btn-link:focus {
        box-shadow: none;
        outline: none;
    }
    
    #auditTable_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        border-radius: 0.375rem;
        overflow: hidden;
    }
    
    .audit-user-info {
        display: flex;
        flex-direction: column;
    }
    
    .audit-user-info strong {
        font-size: 0.9rem;
    }
    
    .audit-user-info small {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    .audit-description {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .audit-ip {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
    }
    
    .audit-browser {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .modal-body pre {
        font-size: 0.85rem;
        line-height: 1.5;
    }
    
    .filter-card {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    /* Pagination Styles */
    .pagination-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1rem 0;
        border-top: 1px solid #e6e6e6;
        margin-top: 1.5rem;
    }
    
    .pagination-info {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .pagination {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .pagination .page-link {
        color: #61ce70;
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease;
        text-decoration: none;
        background-color: #fff;
        min-width: 38px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .pagination .page-link:hover {
        color: #fff;
        background-color: #61ce70;
        border-color: #61ce70;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(97, 206, 112, 0.2);
    }
    
    .pagination .page-item.active .page-link {
        background-color: #61ce70;
        border-color: #61ce70;
        color: #fff;
        font-weight: 600;
    }
    
    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .pagination .page-item.disabled .page-link:hover {
        transform: none;
        box-shadow: none;
        background-color: #fff;
        border-color: #dee2e6;
        color: #6c757d;
    }
    
    .pagination .page-link:focus {
        box-shadow: 0 0 0 0.2rem rgba(97, 206, 112, 0.25);
        outline: none;
    }
    
    /* Large arrow icons for first/last */
    .pagination .page-link i {
        font-size: 1.1rem;
    }
</style>
@endpush

@section('content')
    {{-- CSRF Token for AJAX requests --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">System Activity Monitor</h3>
                        <p class="text-muted">Track all user actions, IP addresses, and system changes for security and compliance purposes.</p>
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Cleanup Job Status Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-broom me-2"></i>Automatic Cleanup Job
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="mb-0 me-3">Status:</h6>
                                        @if($cleanupStatus)
                                            <span class="badge bg-success fs-6">
                                                <i class="fas fa-check-circle me-1"></i>Active
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark fs-6">
                                                <i class="fas fa-pause-circle me-1"></i>Paused
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-muted mb-2 small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Automatically deletes audit records older than 60 days (2 months) on the 1st of each month at 2:00 AM.
                                    </p>
                                    @if($lastRun)
                                        <p class="text-muted mb-0 small">
                                            <i class="fas fa-clock me-1"></i>
                                            Last run: {{ \Carbon\Carbon::parse($lastRun)->format('F d, Y H:i:s') }}
                                        </p>
                                    @else
                                        <p class="text-muted mb-0 small">
                                            <i class="fas fa-clock me-1"></i>
                                            Last run: Never
                                        </p>
                                    @endif
                                </div>
                                <div class="col-md-4 text-end">
                                    @if($cleanupStatus)
                                        <button type="button" class="btn btn-warning" id="pauseCleanupBtn">
                                            <i class="fas fa-pause me-1"></i>Pause Job
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-success" id="activateCleanupBtn">
                                            <i class="fas fa-play me-1"></i>Activate Job
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-filter me-2"></i>Filter Options
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Filter Form -->
                            <form method="GET" action="{{ route('audits.index') }}" id="filterForm">
                                <div class="row g-3">
                                    <div class="col-md-2 col-12">
                                        <label class="form-label small">Search</label>
                                        <input type="text" name="search" class="form-control" placeholder="Search..."
                                            value="{{ request('search') }}">
                                    </div>
                                    <div class="col-md-2 col-12">
                                        <label class="form-label small">User</label>
                                        <select name="user_id" class="form-control">
                                            <option value="">All Users</option>
                                            @foreach($users as $u)
                                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                                    {{ $u->username ?? $u->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-12">
                                        <label class="form-label small">Event</label>
                                        <select name="event" class="form-control">
                                            <option value="">All Events</option>
                                            <option value="created" {{ request('event') == 'created' ? 'selected' : '' }}>Created</option>
                                            <option value="updated" {{ request('event') == 'updated' ? 'selected' : '' }}>Updated</option>
                                            <option value="deleted" {{ request('event') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                                            <option value="role_changed" {{ request('event') == 'role_changed' ? 'selected' : '' }}>Role Changed</option>
                                            <option value="permissions_changed" {{ request('event') == 'permissions_changed' ? 'selected' : '' }}>Permissions Changed</option>
                                            <option value="password_reset" {{ request('event') == 'password_reset' ? 'selected' : '' }}>Password Reset</option>
                                            <option value="password_changed" {{ request('event') == 'password_changed' ? 'selected' : '' }}>Password Changed</option>
                                            <option value="user_activated" {{ request('event') == 'user_activated' ? 'selected' : '' }}>User Activated</option>
                                            <option value="user_deactivated" {{ request('event') == 'user_deactivated' ? 'selected' : '' }}>User Deactivated</option>
                                            <option value="oncall_claimed" {{ request('event') == 'oncall_claimed' ? 'selected' : '' }}>On-Call Claimed</option>
                                            <option value="oncall_claimed_for_staff" {{ request('event') == 'oncall_claimed_for_staff' ? 'selected' : '' }}>On-Call Claimed for Staff</option>
                                            <option value="locum_claimed" {{ request('event') == 'locum_claimed' ? 'selected' : '' }}>Locum Claimed</option>
                                            <option value="locum_claimed_for_staff" {{ request('event') == 'locum_claimed_for_staff' ? 'selected' : '' }}>Locum Claimed for Staff</option>
                                            <option value="http_post" {{ request('event') == 'http_post' ? 'selected' : '' }}>HTTP POST</option>
                                            <option value="http_update" {{ request('event') == 'http_update' ? 'selected' : '' }}>HTTP Update</option>
                                            <option value="http_delete" {{ request('event') == 'http_delete' ? 'selected' : '' }}>HTTP Delete</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-12">
                                        <label class="form-label small">Model</label>
                                        <select name="model" class="form-control">
                                            <option value="">All Models</option>
                                            <option value="App\Models\User"
                                                {{ request('model') == 'App\Models\User' ? 'selected' : '' }}>User</option>
                                            <option value="App\Models\Role"
                                                {{ request('model') == 'App\Models\Role' ? 'selected' : '' }}>Role</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-12">
                                        <label class="form-label small">Start Date</label>
                                        <input type="date" name="start_date" class="form-control"
                                            value="{{ request('start_date') }}">
                                    </div>
                                    <div class="col-md-2 col-12">
                                        <label class="form-label small">End Date</label>
                                        <input type="date" name="end_date" class="form-control"
                                            value="{{ request('end_date') }}">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('audits.index') }}" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-redo me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>

                    <!-- Audit Table -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Activity Logs
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="auditTable" class="table table-bordered table-striped table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 60px;">ID</th>
                                            <th style="width: 150px;">Date & Time</th>
                                            <th style="width: 150px;">User</th>
                                            <th style="width: 150px;">Action</th>
                                            <th>Description</th>
                                            <th style="width: 130px;">IP Address</th>
                                            <th style="width: 200px;">Browser/Device</th>
                                            <th style="width: 100px;" class="text-center">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($audits as $audit)
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary">#{{ $audit->id }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold">{{ $audit->created_at->format('M d, Y') }}</span>
                                                        <small class="text-muted">{{ $audit->created_at->format('H:i:s') }}</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="audit-user-info">
                                                        @if ($audit->user)
                                                            <strong>{{ $audit->user->username ?? $audit->user->email }}</strong>
                                                            @if($audit->user->email && $audit->user->username)
                                                                <small>{{ $audit->user->email }}</small>
                                                            @endif
                                                        @else
                                                            <span class="text-muted fst-italic">System</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($audit->event == 'created')
                                                        <span class="badge bg-success">{{ ucfirst($audit->event) }}</span>
                                                    @elseif($audit->event == 'updated')
                                                        <span class="badge bg-warning text-dark">{{ ucfirst($audit->event) }}</span>
                                                    @elseif($audit->event == 'deleted')
                                                        <span class="badge bg-danger">{{ ucfirst($audit->event) }}</span>
                                                    @elseif($audit->event == 'role_changed')
                                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                    @elseif($audit->event == 'permissions_changed')
                                                        <span class="badge bg-purple">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                    @elseif($audit->event == 'password_reset' || $audit->event == 'password_changed')
                                                        <span class="badge bg-danger">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                    @elseif($audit->event == 'user_activated')
                                                        <span class="badge bg-success">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                    @elseif($audit->event == 'user_deactivated')
                                                        <span class="badge bg-dark">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                    @elseif($audit->event == 'oncall_claimed' || $audit->event == 'oncall_claimed_for_staff')
                                                        <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                    @elseif($audit->event == 'locum_claimed' || $audit->event == 'locum_claimed_for_staff')
                                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                    @elseif($audit->event == 'http_post' || $audit->event == 'http_update' || $audit->event == 'http_delete')
                                                        <span class="badge bg-warning text-dark">{{ strtoupper(str_replace('http_', '', $audit->event)) }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ ucfirst($audit->event) }}</span>
                                                    @endif
                                                    <br>
                                                    <small class="text-muted">{{ class_basename($audit->auditable_type ?? 'N/A') }}</small>
                                                </td>
                                                <td>
                                                    <div class="audit-description" title="{{ $audit->description ?? 'N/A' }}">
                                                        {{ $audit->description ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <code class="audit-ip">{{ $audit->ip_address ?? 'N/A' }}</code>
                                                </td>
                                                <td>
                                                    <div class="audit-browser" title="{{ $audit->user_agent ?? 'N/A' }}">
                                                        {{ Str::limit($audit->user_agent ?? 'N/A', 50) }}
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#auditModal{{ $audit->id }}" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Modal for Details -->
                                            <div class="modal fade" id="auditModal{{ $audit->id }}" tabindex="-1" aria-labelledby="auditModalLabel{{ $audit->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-xl">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title" id="auditModalLabel{{ $audit->id }}">
                                                                <i class="fas fa-info-circle me-2"></i>Activity Details #{{ $audit->id }}
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <div class="card border-0 bg-light">
                                                                        <div class="card-body">
                                                                            <h6 class="text-muted mb-2"><i class="fas fa-user me-2"></i>User Information</h6>
                                                                            <p class="mb-0">
                                                                                <strong>{{ $audit->user ? ($audit->user->username ?? $audit->user->email) : 'System' }}</strong>
                                                                                @if($audit->user && $audit->user->email && $audit->user->username)
                                                                                    <br><small class="text-muted">{{ $audit->user->email }}</small>
                                                                                @endif
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="card border-0 bg-light">
                                                                        <div class="card-body">
                                                                            <h6 class="text-muted mb-2"><i class="fas fa-clock me-2"></i>Date & Time</h6>
                                                                            <p class="mb-0">
                                                                                <strong>{{ $audit->created_at->format('F d, Y') }}</strong>
                                                                                <br><small class="text-muted">{{ $audit->created_at->format('H:i:s') }}</small>
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <div class="card border-0 bg-light">
                                                                        <div class="card-body">
                                                                            <h6 class="text-muted mb-2"><i class="fas fa-network-wired me-2"></i>IP Address</h6>
                                                                            <p class="mb-0"><code>{{ $audit->ip_address ?? 'N/A' }}</code></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="card border-0 bg-light">
                                                                        <div class="card-body">
                                                                            <h6 class="text-muted mb-2"><i class="fas fa-tag me-2"></i>Event Type</h6>
                                                                            <p class="mb-0">
                                                                                @if($audit->event == 'created')
                                                                                    <span class="badge bg-success">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                                                @elseif($audit->event == 'updated')
                                                                                    <span class="badge bg-warning text-dark">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                                                @elseif($audit->event == 'deleted')
                                                                                    <span class="badge bg-danger">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                                                @else
                                                                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</span>
                                                                                @endif
                                                                                <br><small class="text-muted mt-1 d-block">{{ class_basename($audit->auditable_type ?? 'N/A') }}</small>
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-4">
                                                                <h6 class="text-muted mb-2"><i class="fas fa-align-left me-2"></i>Description</h6>
                                                                <div class="card border-0 bg-light">
                                                                    <div class="card-body">
                                                                        <p class="mb-0">{{ $audit->description ?? 'N/A' }}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-4">
                                                                <h6 class="text-muted mb-2"><i class="fas fa-globe me-2"></i>URL</h6>
                                                                <div class="card border-0 bg-light">
                                                                    <div class="card-body">
                                                                        <p class="mb-0 small text-break">{{ $audit->url ?? 'N/A' }}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-4">
                                                                <h6 class="text-muted mb-2"><i class="fas fa-desktop me-2"></i>Browser/Device</h6>
                                                                <div class="card border-0 bg-light">
                                                                    <div class="card-body">
                                                                        <p class="mb-0 small">{{ $audit->user_agent ?? 'N/A' }}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            @if($audit->old_values || $audit->new_values)
                                                                <div class="row">
                                                                    @if($audit->old_values)
                                                                        <div class="col-md-6 mb-3">
                                                                            <h6 class="text-muted mb-2"><i class="fas fa-arrow-left me-2"></i>Old Values</h6>
                                                                            <div class="card border-0 bg-light">
                                                                                <div class="card-body">
                                                                                    <pre class="mb-0" style="max-height:300px; overflow:auto; background: white; padding: 1rem; border-radius: 0.25rem;">{{ json_encode($audit->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                    @if($audit->new_values)
                                                                        <div class="col-md-6 mb-3">
                                                                            <h6 class="text-muted mb-2"><i class="fas fa-arrow-right me-2"></i>New Values</h6>
                                                                            <div class="card border-0 bg-light">
                                                                                <div class="card-body">
                                                                                    <pre class="mb-0" style="max-height:300px; overflow:auto; background: white; padding: 1rem; border-radius: 0.25rem;">{{ json_encode($audit->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i>Close
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted fs-5">No activity logs found.</p>
                                                    <p class="text-muted">Try adjusting your filters or check back later.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            @if($audits->hasPages())
                                <div class="pagination-wrapper">
                                    <div class="pagination-info">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Showing <strong>{{ $audits->firstItem() }}</strong> to <strong>{{ $audits->lastItem() }}</strong> of <strong>{{ number_format($audits->total()) }}</strong> {{ $audits->total() == 1 ? 'entry' : 'entries' }}
                                    </div>
                                    <div>
                                        {{ $audits->withQueryString()->links('pagination::bootstrap-4') }}
                                    </div>
                                </div>
                            @elseif($audits->total() > 0)
                                <div class="pagination-wrapper">
                                    <div class="pagination-info">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Showing <strong>{{ $audits->count() }}</strong> {{ $audits->count() == 1 ? 'entry' : 'entries' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.2/js/dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.2/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/3.0.2/js/responsive.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.print.min.js"></script>

        <script>
            $(document).ready(function() {
                // Initialize DataTable
                var table = $('#auditTable').DataTable({
                    responsive: true,
                    paging: false, // Disable DataTables pagination since we're using Laravel pagination
                    searching: true,
                    ordering: true,
                    info: true,
                    order: [[0, 'desc']], // Sort by ID descending (newest first)
                    columnDefs: [
                        {
                            orderable: false,
                            targets: [7] // Details column
                        },
                        {
                            type: 'date',
                            targets: [1] // Date column
                        }
                    ],
                    dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rt',
                    buttons: [
                        {
                            extend: 'csv',
                            text: '<i class="fas fa-file-csv"></i>',
                            className: 'btn btn-sm btn-link text-primary p-2',
                            titleAttr: 'Export CSV',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            },
                            title: 'Activity_Logs_' + new Date().toISOString().split('T')[0]
                        },
                        {
                            extend: 'excel',
                            text: '<i class="fas fa-file-excel"></i>',
                            className: 'btn btn-sm btn-link text-success p-2',
                            titleAttr: 'Export Excel',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            },
                            title: 'Activity_Logs_' + new Date().toISOString().split('T')[0]
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fas fa-file-pdf"></i>',
                            className: 'btn btn-sm btn-link text-danger p-2',
                            titleAttr: 'Export PDF',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            },
                            title: 'Activity Logs - ' + new Date().toLocaleDateString(),
                            orientation: 'landscape',
                            pageSize: 'A4'
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print"></i>',
                            className: 'btn btn-sm btn-link text-secondary p-2',
                            titleAttr: 'Print',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            },
                            title: 'Activity Logs - ' + new Date().toLocaleDateString()
                        }
                    ],
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search activities...",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "No entries to show",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        zeroRecords: "No matching records found",
                        emptyTable: "No activity logs available"
                    }
                });

                // Custom search that works with Laravel pagination
                // Note: DataTables search will only work on current page
                // For full search, users should use the filter form
                
                // Style the search box
                $('.dataTables_filter input').addClass('form-control form-control-sm');
                $('.dataTables_filter').addClass('mb-3');
                
                // Style the buttons
                $('.dt-buttons').addClass('btn-group');
                $('.dt-buttons .btn').removeClass('btn-sm');
            });
            
            // Cleanup job toggle handlers
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
            
            document.getElementById('pauseCleanupBtn')?.addEventListener('click', function() {
                toggleCleanupStatus('paused');
            });
            
            document.getElementById('activateCleanupBtn')?.addEventListener('click', function() {
                toggleCleanupStatus('active');
            });
            
            function toggleCleanupStatus(status) {
                const btn = status === 'active' ? document.getElementById('activateCleanupBtn') : document.getElementById('pauseCleanupBtn');
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
                
                fetch('{{ route('audits.cleanup.toggle') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            alert(data.message);
                            window.location.reload();
                        }
                    } else {
                        throw new Error(data.message || 'Failed to update status');
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Failed to update cleanup status'
                        });
                    } else {
                        alert('Error: ' + (error.message || 'Failed to update cleanup status'));
                    }
                });
            }
        </script>
    @endpush
@endsection
