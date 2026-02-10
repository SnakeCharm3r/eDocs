@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    
    {{-- DataTables + Buttons + Font Awesome --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.1.0/css/buttons.dataTables.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        /* Theme Color Styling */
        :root {
            --theme-color: #007A33;
        }
        
        /* Table Header with Theme Color */
        #workflowsTable thead th {
            background-color: var(--theme-color) !important;
            color: white !important;
            font-weight: 600;
            border: none;
            padding: 12px 10px;
        }
        
        /* Sorting Icons White */
        #workflowsTable thead th.sorting:before,
        #workflowsTable thead th.sorting:after,
        #workflowsTable thead th.sorting_asc:before,
        #workflowsTable thead th.sorting_asc:after,
        #workflowsTable thead th.sorting_desc:before,
        #workflowsTable thead th.sorting_desc:after {
            color: white !important;
            opacity: 1 !important;
        }
        
        /* Action Buttons with Theme Color */
        .btn-info {
            background-color: var(--theme-color) !important;
            border-color: var(--theme-color) !important;
            color: white !important;
        }
        
        .btn-info:hover {
            background-color: #005a25 !important;
            border-color: #005a25 !important;
            color: white !important;
        }
        
        .btn-outline-primary {
            border-color: var(--theme-color) !important;
            color: var(--theme-color) !important;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--theme-color) !important;
            border-color: var(--theme-color) !important;
            color: white !important;
        }
        
        .badge.bg-info {
            background-color: var(--theme-color) !important;
        }
        
        /* Error Row Highlighting */
        .table-danger {
            background-color: #f8d7da !important;
        }
        
        .table-success {
            background-color: #d1e7dd !important;
        }
        
        /* Hover Effect */
        #workflowsTable tbody tr:hover {
            background-color: #f8f9fa !important;
        }
        
        /* Badge Styling */
        .badge-sm {
            font-size: 0.7em;
            padding: 2px 6px;
        }
        
        /* Filter Card */
        .filter-card {
            background: #f8f9fa;
            border-left: 4px solid var(--theme-color);
        }
        
        /* Status Badges */
        .badge-status {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        
        /* Workflow Flow Styling */
        .workflow-flow {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .bg-light-success {
            background-color: #d1e7dd !important;
        }
        
        .bg-light-danger {
            background-color: #f8d7da !important;
        }
        
        .bg-light-warning {
            background-color: #fff3cd !important;
        }
        
        .bg-light-primary {
            background-color: #cfe2ff !important;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <br>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <h3 class="page-title mb-0"><i class="fas fa-project-diagram me-2"></i>Workflow Management</h3>
                            <div class="d-flex gap-2">
                                <a href="{{ route('workflow-management.errors') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-exclamation-triangle me-1"></i> View Errors
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4 filter-card">
                <div class="card-body">
                    <form method="GET" action="{{ route('workflow-management.index') }}" id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label"><strong>Status</strong></label>
                            <select name="status" id="status" class="form-control form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Errors</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="form_type" class="form-label"><strong>Form Type</strong></label>
                            <select name="form_type" id="form_type" class="form-control form-select">
                                <option value="">All Types</option>
                                <option value="ict" {{ request('form_type') == 'ict' ? 'selected' : '' }}>ICT Access</option>
                                <option value="hr" {{ request('form_type') == 'hr' ? 'selected' : '' }}>HR Form</option>
                                <option value="requisition" {{ request('form_type') == 'requisition' ? 'selected' : '' }}>Requisition</option>
                                <option value="locum" {{ request('form_type') == 'locum' ? 'selected' : '' }}>Locum Request</option>
                                <option value="oncall" {{ request('form_type') == 'oncall' ? 'selected' : '' }}>On-Call Request</option>
                                <option value="clearance" {{ request('form_type') == 'clearance' ? 'selected' : '' }}>Clearance</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2" style="background-color: var(--theme-color); border-color: var(--theme-color);">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <a href="{{ route('workflow-management.index') }}" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Workflows Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>All Workflows</h5>
                        <span class="badge bg-info badge-status">{{ $allWorkflows->count() }} Total</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="workflowsTable" class="display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Workflow Flow (Send By → Go To Whom)</th>
                                    <th>ID</th>
                                    <th>Form Type</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th data-priority="2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allWorkflows as $workflow)
                                    @php
                                        $formType = 'Unknown';
                                        $formTypeIcon = 'fa-question-circle';
                                        $isClearance = isset($workflow->workflow_type) && $workflow->workflow_type == 'clearance';
                                        
                                        if ($isClearance) {
                                            $formType = 'Clearance';
                                            $formTypeIcon = 'fa-clipboard-check';
                                        } elseif ($workflow->ict_request_resource_id) {
                                            $formType = 'ICT Access';
                                            $formTypeIcon = 'fa-server';
                                        } elseif ($workflow->hr_form) {
                                            $formType = 'HR Form';
                                            $formTypeIcon = 'fa-file-alt';
                                        } elseif ($workflow->requisition_id) {
                                            $formType = 'Requisition';
                                            $formTypeIcon = 'fa-shopping-cart';
                                        } elseif ($workflow->locum_request_id) {
                                            $formType = 'Locum Request';
                                            $formTypeIcon = 'fa-user-md';
                                        } elseif ($workflow->on_call_request_id) {
                                            $formType = 'On-Call Request';
                                            $formTypeIcon = 'fa-phone';
                                        }
                                        
                                        // Handle both regular and clearance workflows
                                        // Get all histories (eager loaded) and filter in PHP for better performance
                                        if ($isClearance) {
                                            $allHistoriesCollection = $workflow->histories ?? collect();
                                            $allHistories = $allHistoriesCollection->sortBy('created_at')->values();
                                            $currentHistory = $allHistories->where('status', 0)->last();
                                            $allPendingHistories = $allHistories->where('status', 0)->values();
                                        } else {
                                            $allHistoriesCollection = $workflow->workflowHistory ?? collect();
                                            $allHistories = $allHistoriesCollection->sortBy('created_at')->values();
                                            $currentHistory = $allHistories->where('status', 0)->last();
                                            $allPendingHistories = $allHistories->where('status', 0)->values();
                                        }
                                        $hasError = false;
                                        $errorMessage = '';
                                        $errorDetails = [];
                                        
                                        if ($currentHistory) {
                                            if (!$currentHistory->attendedBy) {
                                                $hasError = true;
                                                $errorMessage = 'User Not Found';
                                                $errorDetails[] = 'Assigned user (ID: ' . $currentHistory->attended_by . ') does not exist';
                                            } elseif ($currentHistory->attendedBy->status != 'active') {
                                                $hasError = true;
                                                $errorMessage = 'User Inactive';
                                                $errorDetails[] = 'Assigned user (' . $currentHistory->attendedBy->username . ') is inactive';
                                            }
                                        }
                                        
                                        // Check all pending histories for errors
                                        foreach ($allPendingHistories as $pending) {
                                            if (!$pending->attendedBy) {
                                                $hasError = true;
                                                if (!in_array('Assigned user (ID: ' . $pending->attended_by . ') does not exist', $errorDetails)) {
                                                    $errorDetails[] = 'Assigned user (ID: ' . $pending->attended_by . ') does not exist';
                                                }
                                            } elseif ($pending->attendedBy->status != 'active') {
                                                $hasError = true;
                                                if (!in_array('Assigned user (' . $pending->attendedBy->username . ') is inactive', $errorDetails)) {
                                                    $errorDetails[] = 'Assigned user (' . $pending->attendedBy->username . ') is inactive';
                                                }
                                            }
                                        }
                                        
                                        // Check for other issues (allHistories already defined above)
                                        $stuckDays = 0;
                                        if ($currentHistory && $currentHistory->created_at) {
                                            $stuckDays = \Carbon\Carbon::parse($currentHistory->created_at)->diffInDays(now());
                                            if ($stuckDays > 7) {
                                                $errorDetails[] = 'Stuck for ' . $stuckDays . ' days';
                                            }
                                        }
                                        
                                        // Check for missing steps
                                        $completedSteps = $allHistories->where('status', 1)->count();
                                        $totalSteps = $allHistories->count();
                                    @endphp
                                    <tr class="{{ $hasError ? 'table-danger' : ($workflow->work_flow_completed ? 'table-success' : '') }}">
                                        <td style="min-width: 300px;">
                                            <div class="workflow-flow">
                                                @if($currentHistory)
                                                    <div class="mb-2 p-2 border border-primary rounded bg-light-primary">
                                                        <strong><i class="fas fa-flag me-1 text-primary"></i>Current Step:</strong>
                                                        <div class="mt-1">
                                                            <strong>{{ $currentHistory->step_name ?? 'N/A' }}</strong>
                                                            @if($currentHistory->attendedBy)
                                                                <br><small class="text-muted">
                                                                    <i class="fas fa-user-tie me-1"></i>{{ $currentHistory->attendedBy->fname ?? '' }} {{ $currentHistory->attendedBy->lname ?? '' }}
                                                                    @if($currentHistory->attendedBy->roles && $currentHistory->attendedBy->roles->count() > 0)
                                                                        <span class="ms-2">
                                                                            @foreach($currentHistory->attendedBy->roles as $role)
                                                                                <span class="badge bg-secondary badge-sm me-1"><i class="fas fa-user-tag me-1"></i>{{ $role->name }}</span>
                                                                            @endforeach
                                                                        </span>
                                                                    @endif
                                                                    @if($currentHistory->attendedBy->department)
                                                                        <span class="ms-2"><i class="fas fa-building me-1"></i>{{ $currentHistory->attendedBy->department->dept_name ?? 'N/A' }}</span>
                                                                    @endif
                                                                </small>
                                                            @endif
                                                            @if($currentHistory->remark)
                                                                <br><small class="text-muted" title="{{ $currentHistory->remark }}">
                                                                    <i class="fas fa-comment me-1"></i>{{ \Illuminate\Support\Str::limit($currentHistory->remark, 50) }}
                                                                </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($allHistories->count() > 0)
                                                    @foreach($allHistories as $history)
                                                        <div class="mb-2 p-2 border rounded {{ $history->status == 1 ? 'bg-light-success' : ($history->status == 2 ? 'bg-light-danger' : 'bg-light-warning') }}">
                                                            <div class="d-flex align-items-start">
                                                                <div class="flex-grow-1">
                                                                    @if($history->forwardedBy)
                                                                        <div class="mb-1">
                                                                            <i class="fas fa-arrow-right text-primary me-1"></i>
                                                                            <strong>From:</strong> 
                                                                            {{ $history->forwardedBy->fname ?? '' }} {{ $history->forwardedBy->lname ?? '' }}
                                                                            @if($history->forwardedBy->roles && $history->forwardedBy->roles->count() > 0)
                                                                                <br><small class="ms-3">
                                                                                    @foreach($history->forwardedBy->roles as $role)
                                                                                        <span class="badge bg-secondary badge-sm me-1"><i class="fas fa-user-tag me-1"></i>{{ $role->name }}</span>
                                                                                    @endforeach
                                                                                </small>
                                                                            @endif
                                                                            @if($history->forwardedBy->department)
                                                                                <br><small class="text-muted ms-3">
                                                                                    <i class="fas fa-building me-1"></i>{{ $history->forwardedBy->department->dept_name ?? 'N/A' }}
                                                                                </small>
                                                                            @endif
                                                                        </div>
                                                                    @elseif($history->forwarded_by)
                                                                        <div class="mb-1 text-danger">
                                                                            <i class="fas fa-arrow-right me-1"></i>
                                                                            <strong>From:</strong> User Not Found (ID: {{ $history->forwarded_by }})
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    @if($history->attendedBy)
                                                                        <div class="mb-1">
                                                                            <i class="fas fa-arrow-down text-success me-1"></i>
                                                                            <strong>To:</strong> 
                                                                            {{ $history->attendedBy->fname ?? '' }} {{ $history->attendedBy->lname ?? '' }}
                                                                            @if($history->attendedBy->roles && $history->attendedBy->roles->count() > 0)
                                                                                <br><small class="ms-3">
                                                                                    @foreach($history->attendedBy->roles as $role)
                                                                                        <span class="badge bg-secondary badge-sm me-1"><i class="fas fa-user-tag me-1"></i>{{ $role->name }}</span>
                                                                                    @endforeach
                                                                                </small>
                                                                            @endif
                                                                            @if($history->attendedBy->department)
                                                                                <br><small class="text-muted ms-3">
                                                                                    <i class="fas fa-building me-1"></i>{{ $history->attendedBy->department->dept_name ?? 'N/A' }}
                                                                                </small>
                                                                            @endif
                                                                        </div>
                                                                    @elseif($history->attended_by)
                                                                        <div class="mb-1 text-danger">
                                                                            <i class="fas fa-arrow-down me-1"></i>
                                                                            <strong>To:</strong> User Not Found (ID: {{ $history->attended_by }})
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    @if($history->step_name)
                                                                        <div class="mb-1">
                                                                            <small class="badge bg-info">
                                                                                <i class="fas fa-tag me-1"></i>{{ $history->step_name }}
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    <div class="mb-1">
                                                                        @if($history->status == 1)
                                                                            <span class="badge bg-success badge-sm"><i class="fas fa-check me-1"></i>Approved</span>
                                                                        @elseif($history->status == 2)
                                                                            <span class="badge bg-danger badge-sm"><i class="fas fa-times me-1"></i>Rejected</span>
                                                                        @else
                                                                            <span class="badge bg-warning text-dark badge-sm"><i class="fas fa-clock me-1"></i>Pending</span>
                                                                        @endif
                                                                    </div>
                                                                    
                                                                    @if($history->remark)
                                                                        <div class="mt-1">
                                                                            <small class="text-muted" title="{{ $history->remark }}">
                                                                                <i class="fas fa-comment me-1"></i>{{ \Illuminate\Support\Str::limit($history->remark, 40) }}
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    <div class="mt-1">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-calendar me-1"></i>{{ \Carbon\Carbon::parse($history->created_at)->format('d M Y, H:i') }}
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="text-muted">
                                                        <i class="fas fa-info-circle me-1"></i>No workflow history found
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td><strong>#{{ $workflow->id }}</strong></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="fas {{ $formTypeIcon }} me-1"></i>{{ $formType }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($workflow->work_flow_completed)
                                                <span class="badge bg-success badge-status"><i class="fas fa-check-circle me-1"></i>Completed</span>
                                            @else
                                                <span class="badge bg-warning text-dark badge-status"><i class="fas fa-clock me-1"></i>Pending</span>
                                            @endif
                                            @if($hasError)
                                                <br><span class="badge bg-danger badge-status mt-1"><i class="fas fa-exclamation-triangle me-1"></i>Error</span>
                                            @endif
                                        </td>
                                        <td>
                                            <i class="fas fa-calendar me-1 text-muted"></i>{{ \Carbon\Carbon::parse($workflow->created_at)->format('d M Y') }}
                                            <br><small class="text-muted">{{ \Carbon\Carbon::parse($workflow->created_at)->format('H:i') }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                @if($isClearance)
                                                    <a href="{{ route('workflow-management.show-clearance', $workflow->id) }}" class="btn btn-sm" title="View Details & History" style="background-color: var(--theme-color); border-color: var(--theme-color); color: white;">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ route('workflow-management.show', $workflow->id) }}" class="btn btn-sm" title="View Details & History" style="background-color: var(--theme-color); border-color: var(--theme-color); color: white;">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                                @if($hasError && $currentHistory)
                                                    @if($isClearance)
                                                        <a href="{{ route('workflow-management.edit-clearance-history', $currentHistory->id) }}" class="btn btn-sm btn-warning" title="Fix Error">
                                                            <i class="fas fa-wrench"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('workflow-management.edit-history', $currentHistory->id) }}" class="btn btn-sm btn-warning" title="Fix Error">
                                                            <i class="fas fa-wrench"></i>
                                                        </a>
                                                    @endif
                                                @endif
                                                @if(!$workflow->work_flow_completed)
                                                    @if($isClearance)
                                                        <a href="{{ route('workflow-management.show-clearance', $workflow->id) }}#manage-flow" class="btn btn-sm btn-info" title="Manage Flow" style="background-color: #17a2b8; border-color: #17a2b8; color: white;">
                                                            <i class="fas fa-route"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('workflow-management.show', $workflow->id) }}#manage-flow" class="btn btn-sm btn-info" title="Manage Flow" style="background-color: #17a2b8; border-color: #17a2b8; color: white;">
                                                            <i class="fas fa-route"></i>
                                                        </a>
                                                    @endif
                                                @endif
                                                @if(Auth::user()->can('manage workflows') && !$isClearance)
                                                    <form action="{{ route('workflow-management.destroy', $workflow->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete workflow #{{ $workflow->id }}? This will also delete all associated workflow history. This action cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete Workflow">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No workflows found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.print.min.js"></script>

    <script>
        // ====== DataTable ======
        new DataTable('#workflowsTable', {
            responsive: true,
            order: [[4, 'desc']], // Sort by Created date (newest first) - column index 4
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            columnDefs: [{
                targets: [5], // Actions column - column index 5
                orderable: false
            }],
            language: {
                search: "Search workflows:",
                lengthMenu: "Show _MENU_ workflows per page",
                info: "Showing _START_ to _END_ of _TOTAL_ workflows",
                infoEmpty: "Showing 0 to 0 of 0 workflows",
                infoFiltered: "(filtered from _MAX_ total workflows)",
                zeroRecords: "No matching workflows found",
                emptyTable: "No workflows found"
            }
        });
    </script>
@endsection

