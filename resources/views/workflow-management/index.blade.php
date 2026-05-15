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

        /* Custom Badge Colors for Form Types */
        .bg-purple {
            background-color: #6f42c1 !important;
            color: white !important;
        }
        .bg-teal {
            background-color: #20c997 !important;
            color: white !important;
        }
        .bg-orange {
            background-color: #fd7e14 !important;
            color: white !important;
        }
        .bg-indigo {
            background-color: #6610f2 !important;
            color: white !important;
        }
        .bg-cyan {
            background-color: #0dcaf0 !important;
            color: black !important;
        }

        /* Bulk Delete Button Styling */
        #bulkDeleteBtn {
            transition: all 0.3s ease;
        }
        
        .workflow-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        
        #selectAll {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        
        .filter-card .form-label {
            font-size: 0.75rem;
            margin-bottom: 0.25rem;
        }
        
        .filter-card .form-select-sm {
            font-size: 0.875rem;
            padding: 0.375rem 0.5rem;
        }
        
        .filter-card .card-header {
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #dee2e6;
        }
        
        .filter-card .card-body {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
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
        
        /* Pending Requests Cards */
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
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

            <!-- Pending Requests Summary (Same as Header/Sidebar) -->
            @php
                use Illuminate\Support\Facades\Auth;
                use Illuminate\Support\Facades\DB;
                use Illuminate\Support\Facades\Schema;
                
                $user = Auth::user();
                $pendingItems = [];
                $totalPending = 0;

                // Get pending form approvals - broken down by form type (SAME AS HEADER)
                if ($user->can('approve requests')) {
                    if (!$user->hasRole('finance officer')) {
                        $baseGeneralQuery = function () {
                            return DB::table('work_flow_histories')
                                ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                                ->where('work_flow_histories.attended_by', Auth::id())
                                ->where('work_flow_histories.status', 0)
                                ->where('workflows.work_flow_completed', '!=', 1)
                                ->whereNull('locum_agreement_status')
                                ->whereNull('locum_request_status')
                                ->whereNull('on_call_request_status')
                                ->whereNull('workflows.night_shift_claim_id');
                        };

                        $pendingHrForms = $baseGeneralQuery()->whereNotNull('workflows.hr_form')->count();
                        if ($pendingHrForms > 0) {
                            $pendingItems[] = [
                                'title' => 'HR Forms',
                                'count' => $pendingHrForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'file-alt',
                                'color' => 'warning'
                            ];
                            $totalPending += $pendingHrForms;
                        }

                        $pendingIctForms = $baseGeneralQuery()->whereNotNull('workflows.ict_request_resource_id')->count();
                        if ($pendingIctForms > 0) {
                            $pendingItems[] = [
                                'title' => 'ICT Access Forms',
                                'count' => $pendingIctForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'laptop',
                                'color' => 'info'
                            ];
                            $totalPending += $pendingIctForms;
                        }

                        $pendingChangeReqs = $baseGeneralQuery()->whereNotNull('workflows.change_request_id')->count();
                        if ($pendingChangeReqs > 0) {
                            $pendingItems[] = [
                                'title' => 'Change Requests',
                                'count' => $pendingChangeReqs,
                                'route' => route('requestapprove.index'),
                                'icon' => 'exchange-alt',
                                'color' => 'primary'
                            ];
                            $totalPending += $pendingChangeReqs;
                        }

                        $pendingIdForms = $baseGeneralQuery()->where('workflows.id_form', '>', 0)->count();
                        if ($pendingIdForms > 0) {
                            $pendingItems[] = [
                                'title' => 'ID Card Forms',
                                'count' => $pendingIdForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'id-card',
                                'color' => 'success'
                            ];
                            $totalPending += $pendingIdForms;
                        }

                        $pendingBankForms = $baseGeneralQuery()->where('workflows.bank_form', '>', 0)->count();
                        if ($pendingBankForms > 0) {
                            $pendingItems[] = [
                                'title' => 'Bank Forms',
                                'count' => $pendingBankForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'university',
                                'color' => 'primary'
                            ];
                            $totalPending += $pendingBankForms;
                        }

                        $pendingNhifForms = $baseGeneralQuery()->where('workflows.nhif_form', '>', 0)->count();
                        if ($pendingNhifForms > 0) {
                            $pendingItems[] = [
                                'title' => 'NHIF Forms',
                                'count' => $pendingNhifForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'heartbeat',
                                'color' => 'danger'
                            ];
                            $totalPending += $pendingNhifForms;
                        }

                        $pendingHeslbForms = $baseGeneralQuery()->where('workflows.heslb_form', '>', 0)->count();
                        if ($pendingHeslbForms > 0) {
                            $pendingItems[] = [
                                'title' => 'HESLB Forms',
                                'count' => $pendingHeslbForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'graduation-cap',
                                'color' => 'warning'
                            ];
                            $totalPending += $pendingHeslbForms;
                        }

                        $pendingJobDesc = $baseGeneralQuery()->whereNotNull('workflows.job_description_id')->count();
                        if ($pendingJobDesc > 0) {
                            $pendingItems[] = [
                                'title' => 'Job Descriptions',
                                'count' => $pendingJobDesc,
                                'route' => route('requestapprove.index'),
                                'icon' => 'briefcase',
                                'color' => 'secondary'
                            ];
                            $totalPending += $pendingJobDesc;
                        }
                    }

                    // Clearance forms
                    if ($user->can('access clearance form')) {
                        $isHR = $user->hasRole('hr');
                        $pendingClearance = 0;
                        if ($isHR) {
                            $pendingClearance = DB::table('clearance_work_flow_histories')
                                ->join('clearance_work_flows', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
                                ->join('clearance_forms', 'clearance_forms.id', '=', 'clearance_work_flows.requested_resource_id')
                                ->where('clearance_work_flow_histories.attended_by', Auth::id())
                                ->where('clearance_work_flow_histories.status', 0)
                                ->where('clearance_forms.status', '!=', 'rejected')
                                ->count();
                        } else {
                            $pendingClearance = DB::table('clearance_work_flow_histories')
                                ->join('clearance_work_flows', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
                                ->join('clearance_forms', 'clearance_forms.id', '=', 'clearance_work_flows.requested_resource_id')
                                ->where('clearance_work_flow_histories.attended_by', Auth::id())
                                ->where('clearance_work_flow_histories.status', 0)
                                ->where('clearance_forms.status', '!=', 'rejected')
                                ->whereIn('clearance_work_flow_histories.id', function ($subquery) {
                                    $subquery
                                        ->selectRaw('MAX(clearance_work_flow_histories.id)')
                                        ->from('clearance_work_flow_histories')
                                        ->where('clearance_work_flow_histories.status', 0)
                                        ->where('clearance_work_flow_histories.attended_by', Auth::id())
                                        ->groupBy('clearance_work_flow_histories.work_flow_id');
                                })
                                ->count();
                        }
                        if ($pendingClearance > 0) {
                            $pendingItems[] = [
                                'title' => 'Clearance Forms',
                                'count' => $pendingClearance,
                                'route' => route('clearance.index'),
                                'icon' => 'file-check',
                                'color' => 'info'
                            ];
                            $totalPending += $pendingClearance;
                        }
                    }
                }

                // Locum requests
                if ($user->can('approve locum requests')) {
                    $pendingLocum = DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', Auth::id())
                        ->where('work_flow_histories.status', 0)
                        ->whereNotNull('workflows.locum_request_id')
                        ->count();
                    if ($pendingLocum > 0) {
                        $pendingItems[] = [
                            'title' => 'Locum Requests',
                            'count' => $pendingLocum,
                            'route' => route('locum-requests.view'),
                            'icon' => 'money-bill-wave',
                            'color' => 'success'
                        ];
                        $totalPending += $pendingLocum;
                    }
                }

                // On-call requests
                if ($user->can('approve oncall requests')) {
                    $pendingOnCall = DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', Auth::id())
                        ->where('work_flow_histories.status', 0)
                        ->whereNotNull('workflows.on_call_request_id')
                        ->count();
                    if ($pendingOnCall > 0) {
                        $pendingItems[] = [
                            'title' => 'On-Call Requests',
                            'count' => $pendingOnCall,
                            'route' => route('oncall_requests.index'),
                            'icon' => 'phone',
                            'color' => 'primary'
                        ];
                        $totalPending += $pendingOnCall;
                    }
                }

                // Recruitment requisitions
                if ($user->hasAnyRole(['line-manager', 'payroll_accountant', 'coo', 'cms', 'cfo', 'ccdro', 'ceo', 'hr'])) {
                    if (Schema::hasTable('requisitions')) {
                        $pendingRequisition = 0;
                        if ($user->hasRole('payroll_accountant')) {
                            $pendingRequisition = \App\Models\Requisition::where('status', 'pending_payroll')->count();
                        } elseif ($user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro'])) {
                            $userHecRoles = collect($user->getRoleNames())
                                ->map(fn ($r) => strtolower((string) $r))
                                ->filter(fn ($r) => in_array($r, ['coo', 'cfo', 'cms', 'ccdro'], true))
                                ->values()->all();
                            $pendingRequisition = \App\Models\Requisition::where(function ($query) use ($user, $userHecRoles) {
                                $query->where(function ($q) use ($user, $userHecRoles) {
                                    $q->where('status', 'pending_hec')
                                        ->whereHas('department', function ($dq) use ($user, $userHecRoles) {
                                            $dq->where('hec_member_id', $user->id)
                                                ->orWhereHas('hec', function ($hecQuery) use ($userHecRoles) {
                                                    if (empty($userHecRoles)) {
                                                        $hecQuery->whereRaw('1 = 0');
                                                    } else {
                                                        $hecQuery->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(hec_level_name)'), $userHecRoles);
                                                    }
                                                });
                                        });
                                });
                                if ($user->hasRole('cfo')) {
                                    $query->orWhere('status', 'pending_cfo');
                                }
                            })->count();
                        } elseif ($user->hasRole('ceo')) {
                            $pendingRequisition = \App\Models\Requisition::where('status', 'pending_ceo')->count();
                        } elseif ($user->hasRole('hr')) {
                            $pendingRequisition = \App\Models\Requisition::where('status', 'pending_hr')->count();
                        } elseif ($user->hasRole('line-manager')) {
                            $pendingRequisition = \App\Models\Requisition::where('user_id', $user->id)
                                ->whereNotIn('status', ['approved', 'rejected'])
                                ->where(function ($q) {
                                    $q->where('status', '!=', 'rejected_for_editing')
                                      ->orWhere(function ($q2) {
                                          $q2->whereNull('can_edit_until')->orWhere('can_edit_until', '>', now());
                                      });
                                })->count();
                        }
                        if ($pendingRequisition > 0) {
                            $pendingItems[] = [
                                'title' => 'Recruitment Requests',
                                'count' => $pendingRequisition,
                                'route' => route('requisitions.pending'),
                                'icon' => 'user-tie',
                                'color' => 'danger'
                            ];
                            $totalPending += $pendingRequisition;
                        }
                    }
                }
            @endphp

            @if(count($pendingItems) > 0)
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-header bg-warning bg-opacity-10 border-warning py-2">
                    <h6 class="mb-0">
                        <i class="fas fa-bell me-2 text-warning"></i>
                        <strong>Pending Requests Requiring Your Action</strong>
                        <span class="badge bg-warning text-dark ms-2">{{ $totalPending }} Total</span>
                    </h6>
                </div>
                <div class="card-body py-2">
                    <div class="row g-2">
                        @foreach($pendingItems as $item)
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ $item['route'] }}" class="text-decoration-none">
                                <div class="card border-{{ $item['color'] }} shadow-sm h-100 hover-lift">
                                    <div class="card-body p-2 text-center">
                                        <i class="fas fa-{{ $item['icon'] }} fa-2x text-{{ $item['color'] }} mb-2"></i>
                                        <h6 class="mb-1 text-dark">{{ $item['title'] }}</h6>
                                        <span class="badge bg-{{ $item['color'] }} fs-6">{{ $item['count'] }}</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Summary Cards -->
            @php
                $allPendingCount = 0;
                $completedCount = 0;
                $errorCount = 0;
                
                foreach ($allWorkflows as $workflow) {
                    if ($workflow->work_flow_completed) {
                        $completedCount++;
                    } else {
                        $allPendingCount++;
                    }
                }
                
                // Count errors
                foreach ($allWorkflows as $workflow) {
                    $hasError = false;
                    if (isset($workflow->workflow_type) && $workflow->workflow_type == 'clearance') {
                        $allHistories = $workflow->histories ?? collect();
                    } else {
                        $allHistories = $workflow->workflowHistory ?? collect();
                    }
                    $currentHistory = $allHistories->where('status', 0)->last();
                    if ($currentHistory) {
                        if (!$currentHistory->attendedBy || ($currentHistory->attendedBy && $currentHistory->attendedBy->status != 'active')) {
                            $hasError = true;
                        }
                    }
                    if ($hasError) {
                        $errorCount++;
                    }
                }
            @endphp
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-info shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title text-info mb-1">
                                <i class="fas fa-clock me-2"></i>All Pending
                            </h5>
                            <h2 class="mb-0 text-info">{{ $allPendingCount }}</h2>
                            <small class="text-muted">Total pending workflows</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title text-success mb-1">
                                <i class="fas fa-check-circle me-2"></i>Completed
                            </h5>
                            <h2 class="mb-0 text-success">{{ $completedCount }}</h2>
                            <small class="text-muted">Finished workflows</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-danger shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title text-danger mb-1">
                                <i class="fas fa-exclamation-triangle me-2"></i>Errors
                            </h5>
                            <h2 class="mb-0 text-danger">{{ $errorCount }}</h2>
                            <small class="text-muted">Workflows with issues</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4 filter-card">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Workflows</h6>
                </div>
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('workflow-management.index') }}" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="status" class="form-label mb-1 small fw-semibold text-muted">
                                    <i class="fas fa-tasks me-1"></i>Status
                                </label>
                                <select name="status" id="status" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Errors</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="approver_role" class="form-label mb-1 small fw-semibold text-muted">
                                    <i class="fas fa-user-tag me-1"></i>Approver Role
                                </label>
                                <select name="approver_role" id="approver_role" class="form-select form-select-sm">
                                    <option value="">All Roles</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role }}" {{ request('approver_role') == $role ? 'selected' : '' }}>{{ ucfirst(str_replace('-', ' ', $role)) }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted" style="font-size: 0.7rem;">Show pending requests for selected role</small>
                            </div>
                            <div class="col-md-3">
                                <label for="form_type" class="form-label mb-1 small fw-semibold text-muted">
                                    <i class="fas fa-file-alt me-1"></i>Form Type
                                </label>
                                <select name="form_type" id="form_type" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    <option value="ict" {{ request('form_type') == 'ict' ? 'selected' : '' }}>ICT Access</option>
                                    <option value="hr" {{ request('form_type') == 'hr' ? 'selected' : '' }}>HR Form</option>
                                    <option value="requisition" {{ request('form_type') == 'requisition' ? 'selected' : '' }}>Requisition</option>
                                    <option value="locum" {{ request('form_type') == 'locum' ? 'selected' : '' }}>Locum Request</option>
                                    <option value="oncall" {{ request('form_type') == 'oncall' ? 'selected' : '' }}>On-Call Request</option>
                                    <option value="clearance" {{ request('form_type') == 'clearance' ? 'selected' : '' }}>Clearance</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100" style="background-color: var(--theme-color); border-color: var(--theme-color);">
                                    <i class="fas fa-filter me-1"></i>Apply
                                </button>
                                <a href="{{ route('workflow-management.index') }}" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Workflows Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>All Workflows</h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-info badge-status">{{ $allWorkflows->count() }} Total</span>
                            <button type="button" class="btn btn-danger btn-sm d-none" id="bulkDeleteBtn">
                                <i class="fas fa-trash me-1"></i>Delete Selected (<span id="selectedCount">0</span>)
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form id="bulkDeleteForm" action="{{ route('workflow-management.bulk-destroy') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="table-responsive">
                            <table id="workflowsTable" class="display nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="selectAll" class="form-check-input" title="Select All">
                                        </th>
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
                                        $formTypeBadgeClass = 'bg-secondary';
                                        $isClearance = isset($workflow->workflow_type) && $workflow->workflow_type == 'clearance';
                                        
                                        if ($isClearance) {
                                            $formType = 'Clearance';
                                            $formTypeIcon = 'fa-clipboard-check';
                                            $formTypeBadgeClass = 'bg-info';
                                        } elseif ($workflow->ict_request_resource_id) {
                                            $formType = 'ICT Access';
                                            $formTypeIcon = 'fa-server';
                                            $formTypeBadgeClass = 'bg-primary';
                                        } elseif ($workflow->hr_form) {
                                            $formType = 'HR Form';
                                            $formTypeIcon = 'fa-file-alt';
                                            $formTypeBadgeClass = 'bg-success';
                                        } elseif ($workflow->bank_form) {
                                            $formType = 'Bank Form';
                                            $formTypeIcon = 'fa-university';
                                            $formTypeBadgeClass = 'bg-dark';
                                        } elseif ($workflow->heslb_form) {
                                            $formType = 'HESLB Form';
                                            $formTypeIcon = 'fa-graduation-cap';
                                            $formTypeBadgeClass = 'bg-warning text-dark';
                                        } elseif ($workflow->nhif_form) {
                                            $formType = 'NHIF Form';
                                            $formTypeIcon = 'fa-hospital';
                                            $formTypeBadgeClass = 'bg-danger';
                                        } elseif ($workflow->requisition_id) {
                                            $formType = 'Requisition';
                                            $formTypeIcon = 'fa-shopping-cart';
                                            $formTypeBadgeClass = 'bg-purple';
                                        } elseif ($workflow->job_description_id) {
                                            $formType = 'Job Description';
                                            $formTypeIcon = 'fa-briefcase';
                                            $formTypeBadgeClass = 'bg-teal';
                                        } elseif ($workflow->locum_agreement_id) {
                                            $formType = 'Locum Agreement';
                                            $formTypeIcon = 'fa-handshake';
                                            $formTypeBadgeClass = 'bg-orange';
                                        } elseif ($workflow->locum_request_id) {
                                            $formType = 'Locum Request';
                                            $formTypeIcon = 'fa-user-md';
                                            $formTypeBadgeClass = 'bg-indigo';
                                        } elseif ($workflow->on_call_request_id) {
                                            $formType = 'On-Call Request';
                                            $formTypeIcon = 'fa-phone';
                                            $formTypeBadgeClass = 'bg-cyan';
                                        } elseif ($workflow->ccbrt_contract_id) {
                                            $formType = 'CCBRT Contract';
                                            $formTypeIcon = 'fa-file-contract';
                                            $formTypeBadgeClass = 'bg-success';
                                        } elseif ($workflow->contract_renewal_id) {
                                            $formType = 'Contract Renewal';
                                            $formTypeIcon = 'fa-sync-alt';
                                            $formTypeBadgeClass = 'bg-info';
                                        } elseif ($workflow->change_request_id) {
                                            $formType = 'Change Request';
                                            $formTypeIcon = 'fa-exchange-alt';
                                            $formTypeBadgeClass = 'bg-warning text-dark';
                                        }
                                        
                                        // Check if form type is unknown (orphaned workflow)
                                        $isOrphanedWorkflow = ($formType === 'Unknown');
                                        
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
                                    @php
                                        // Only mark as error if: user issue exists, OR form is unknown AND has no history
                                        $isRealError = $hasError || ($isOrphanedWorkflow && $allHistories->count() == 0);
                                    @endphp
                                    <tr class="{{ $isRealError ? 'table-danger' : ($workflow->work_flow_completed ? 'table-success' : '') }}">
                                        <td>
                                            @if(!$isClearance)
                                                <input type="checkbox" name="workflow_ids[]" value="{{ $workflow->id }}" class="form-check-input workflow-checkbox">
                                            @else
                                                <span class="text-muted" title="Clearance workflows cannot be bulk deleted">—</span>
                                            @endif
                                        </td>
                                        <td style="min-width: 300px;">
                                            <div class="workflow-flow">
                                                @if($currentHistory)
                                                    <div class="mb-2 p-2 border border-primary rounded bg-light-primary">
                                                        <strong><i class="fas fa-flag me-1 text-primary"></i>Current Pending Step:</strong>
                                                        <div class="mt-1">
                                                            <strong>{{ $currentHistory->step_name ?? 'N/A' }}</strong>
                                                            @if($currentHistory->attendedBy)
                                                                <br><small class="text-muted">
                                                                    <i class="fas fa-user-tie me-1"></i><strong>Approver:</strong> {{ $currentHistory->attendedBy->fname ?? '' }} {{ $currentHistory->attendedBy->lname ?? '' }} ({{ $currentHistory->attendedBy->username ?? 'N/A' }})
                                                                    @if($currentHistory->attendedBy->roles && $currentHistory->attendedBy->roles->count() > 0)
                                                                        <br><span class="ms-3">
                                                                            <i class="fas fa-user-tag me-1"></i><strong>Roles:</strong>
                                                                            @foreach($currentHistory->attendedBy->roles as $role)
                                                                                <span class="badge bg-info badge-sm me-1">{{ $role->name }}</span>
                                                                            @endforeach
                                                                        </span>
                                                                    @endif
                                                                    @if($currentHistory->attendedBy->department)
                                                                        <br><span class="ms-3"><i class="fas fa-building me-1"></i><strong>Department:</strong> {{ $currentHistory->attendedBy->department->dept_name ?? 'N/A' }}</span>
                                                                    @endif
                                                                    @if($currentHistory->created_at)
                                                                        <br><span class="ms-3"><i class="fas fa-calendar me-1"></i><strong>Pending Since:</strong> {{ \Carbon\Carbon::parse($currentHistory->created_at)->format('d M Y, H:i') }} ({{ \Carbon\Carbon::parse($currentHistory->created_at)->diffForHumans() }})</span>
                                                                    @endif
                                                                </small>
                                                            @endif
                                                            @if($currentHistory->remark)
                                                                <br><small class="text-muted mt-1 d-block" title="{{ $currentHistory->remark }}">
                                                                    <i class="fas fa-comment me-1"></i><strong>Remark:</strong> {{ \Illuminate\Support\Str::limit($currentHistory->remark, 100) }}
                                                                </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($isOrphanedWorkflow && $allHistories->count() == 0)
                                                    <div class="alert alert-danger mb-2">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        <strong>Error: Orphaned Workflow</strong>
                                                        <br><small>This workflow has no identifiable source form and no approval history.</small>
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
                                                @endif
                                            </div>
                                        </td>
                                        <td><strong>#{{ $workflow->id }}</strong></td>
                                        <td>
                                            <span class="badge {{ $formTypeBadgeClass }}">
                                                <i class="fas {{ $formTypeIcon }} me-1"></i>{{ $formType }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($workflow->work_flow_completed)
                                                <span class="badge bg-success badge-status"><i class="fas fa-check-circle me-1"></i>Completed</span>
                                            @else
                                                <span class="badge bg-warning text-dark badge-status"><i class="fas fa-clock me-1"></i>Pending</span>
                                            @endif
                                            @if($isRealError)
                                                <br><span class="badge bg-danger badge-status mt-1"><i class="fas fa-exclamation-triangle me-1"></i>Error</span>
                                                @if($hasError)
                                                    <br><small class="text-danger">User Issue</small>
                                                @elseif($isOrphanedWorkflow)
                                                    <br><small class="text-danger">Orphaned</small>
                                                @endif
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
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No workflows found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    </form>
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
        var workflowTable = new DataTable('#workflowsTable', {
            responsive: true,
            order: [[5, 'desc']], // Sort by Created date (newest first) - column index 5 (after adding checkbox column)
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            columnDefs: [
                {
                    targets: [0], // Checkbox column
                    orderable: false,
                    searchable: false
                },
                {
                    targets: [6], // Actions column - column index 6 (after adding checkbox column)
                    orderable: false
                }
            ],
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

        // ====== Bulk Delete Functionality ======
        function updateBulkDeleteButton() {
            var checkedCount = $('.workflow-checkbox:checked').length;
            $('#selectedCount').text(checkedCount);
            if (checkedCount > 0) {
                $('#bulkDeleteBtn').removeClass('d-none');
            } else {
                $('#bulkDeleteBtn').addClass('d-none');
            }
        }

        // Select All checkbox
        $('#selectAll').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.workflow-checkbox').prop('checked', isChecked);
            updateBulkDeleteButton();
        });

        // Individual checkbox
        $(document).on('change', '.workflow-checkbox', function() {
            var totalCheckboxes = $('.workflow-checkbox').length;
            var checkedCheckboxes = $('.workflow-checkbox:checked').length;
            $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
            updateBulkDeleteButton();
        });

        // Bulk Delete Button
        $('#bulkDeleteBtn').on('click', function() {
            var checkedCount = $('.workflow-checkbox:checked').length;
            if (checkedCount === 0) {
                alert('Please select at least one workflow to delete.');
                return;
            }

            if (confirm('Are you sure you want to delete ' + checkedCount + ' selected workflow(s)? This will also delete all associated workflow history. This action cannot be undone.')) {
                $('#bulkDeleteForm').submit();
            }
        });
    </script>
@endsection

