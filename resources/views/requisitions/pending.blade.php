@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <style>
        /* Stats Cards */
        .stat-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            background: #fff;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: default;
        }
        .stat-card:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .stat-card .stat-icon {
            width: 34px; height: 34px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
            background: #f0f0f0; color: #495057;
        }
        .stat-card .stat-number { font-size: 1.25rem; font-weight: 700; line-height: 1; color: #212529; }
        .stat-card .stat-label { font-size: 0.7rem; color: #6c757d; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }

        .stat-pending .stat-icon { background: #fff3cd; color: #856404; }
        .stat-progress .stat-icon { background: #e9ecef; color: #6c757d; }
        .stat-approved .stat-icon { background: #d1e7dd; color: #007A33; }
        .stat-rejected .stat-icon { background: #f8d7da; color: #dc3545; }

        /* Page header */
        .page-title-area {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            color: #212529;
            margin-bottom: 1.25rem;
        }
        .page-title-area h4 { font-weight: 400; margin: 0; font-size: 1.15rem; color: #343a40; }
        .page-title-area .subtitle { color: #6c757d; font-size: 0.8rem; }

        /* Workflow tracker */
        .workflow-tracker {
            display: flex; align-items: center; gap: 0;
            padding: 0.5rem 0; flex-wrap: wrap; justify-content: center;
        }
        .wf-step {
            display: flex; align-items: center; gap: 0.4rem;
            padding: 0.35rem 0.75rem; border-radius: 20px;
            font-size: 0.75rem; font-weight: 600; white-space: nowrap;
            background: #e9ecef; color: #6c757d;
        }
        .wf-step.active { background: #007A33; color: #fff; animation: pulse-step 2s infinite; }
        .wf-step.completed { background: #d1e7dd; color: #007A33; }
        .wf-step.rejected-step { background: #f8d7da; color: #dc3545; }
        .wf-arrow { color: #adb5bd; font-size: 0.75rem; margin: 0 0.25rem; }
        @keyframes pulse-step { 0%, 100% { box-shadow: 0 0 0 0 rgba(0,122,51,0.3); } 50% { box-shadow: 0 0 0 4px rgba(0,122,51,0.1); } }

        /* Status badges */
        .status-badge {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.3rem 0.7rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
        }
        .status-badge .status-dot { width: 7px; height: 7px; border-radius: 50%; }
        .status-pending_payroll { background: #fff3cd; color: #856404; }
        .status-pending_payroll .status-dot { background: #ffc107; }
        .status-pending_hec { background: #e9ecef; color: #495057; }
        .status-pending_hec .status-dot { background: #6c757d; }
        .status-pending_cfo { background: #e9ecef; color: #495057; }
        .status-pending_cfo .status-dot { background: #6c757d; }
        .status-pending_ceo { background: #e9ecef; color: #495057; }
        .status-pending_ceo .status-dot { background: #6c757d; }
        .status-pending_hr { background: #d1e7dd; color: #007A33; }
        .status-pending_hr .status-dot { background: #007A33; }
        .status-approved { background: #d1e7dd; color: #007A33; }
        .status-approved .status-dot { background: #007A33; }
        .status-rejected, .status-rejected_for_editing { background: #f8d7da; color: #dc3545; }
        .status-rejected .status-dot, .status-rejected_for_editing .status-dot { background: #dc3545; }

        /* Filter bar */
        .filter-bar { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 10px; }
        .filter-bar .form-select, .filter-bar .form-control { border-color: #dee2e6; font-size: 0.85rem; }
        .filter-bar .form-select:focus, .filter-bar .form-control:focus { border-color: #007A33; box-shadow: 0 0 0 0.15rem rgba(0,122,51,0.15); }

        /* Table */
        .req-table thead th { font-weight: 600; white-space: nowrap; background: #f8f9fa; font-size: 0.82rem; padding: 0.65rem 0.75rem; color: #495057; text-transform: uppercase; letter-spacing: 0.3px; }
        .req-table tbody td { vertical-align: middle; font-size: 0.875rem; padding: 0.7rem 0.75rem; }
        .req-table tbody tr { transition: background 0.15s; }
        .req-table tbody tr:hover { background: #f0faf4; }
        .req-ref-link { color: #007A33; font-weight: 600; text-decoration: none; }
        .req-ref-link:hover { color: #005a25; text-decoration: underline; }

        div.dataTables_wrapper div.dataTables_length label,
        div.dataTables_wrapper div.dataTables_filter label { font-size: 0.85rem; color: #6c757d; margin-bottom: 0; }
        div.dataTables_wrapper div.dataTables_filter input { border: 1px solid #dee2e6; border-radius: 6px; padding: 0.3rem 0.65rem; font-size: 0.85rem; margin-left: 0.4rem; }
        div.dataTables_wrapper div.dataTables_info { font-size: 0.82rem; color: #6c757d; padding-top: 0.6rem; }
        div.dataTables_wrapper div.dataTables_paginate .paginate_button.current,
        div.dataTables_wrapper div.dataTables_paginate .paginate_button.current:hover { background: #007A33 !important; border-color: #007A33 !important; color: #fff !important; }

        /* Action buttons */
        .btn-review { background: #007A33; color: #fff; border: none; border-radius: 6px; font-size: 0.8rem; padding: 0.35rem 0.85rem; font-weight: 500; }
        .btn-review:hover { background: #005a25; color: #fff; }
        .btn-track { background: #6c757d; color: #fff; border: none; border-radius: 6px; font-size: 0.8rem; padding: 0.35rem 0.85rem; font-weight: 500; }
        .btn-track:hover { background: #565e64; color: #fff; }

        /* Empty state */
        .empty-state { padding: 3rem 1rem; }
        .empty-state-icon { width: 80px; height: 80px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1rem; }

        /* Role context banner */
        .role-context {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: #e9ecef; color: #495057; border-radius: 20px;
            padding: 0.3rem 0.85rem; font-size: 0.78rem; font-weight: 500;
        }
    </style>
@endpush

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    @php
        $user = auth()->user();
        $isLineManager = ($userRoleContext ?? 'line-manager') === 'line-manager';
        $roleCtx = $userRoleContext ?? 'line-manager';

        $roleLabels = [
            'line-manager' => ['label' => 'Line Manager', 'icon' => 'fa-user-tie', 'desc' => 'Track your submitted recruitment requisitions'],
            'payroll_accountant' => ['label' => 'Payroll Reviewer', 'icon' => 'fa-calculator', 'desc' => 'Review payroll details for requisitions'],
            'hec' => ['label' => 'HEC Member', 'icon' => 'fa-users-cog', 'desc' => 'Review requisitions from your assigned departments'],
            'cfo' => ['label' => 'CFO', 'icon' => 'fa-chart-line', 'desc' => 'Review financial approvals for requisitions'],
            'ceo' => ['label' => 'CEO', 'icon' => 'fa-user-shield', 'desc' => 'Final executive approval for requisitions'],
            'hr' => ['label' => 'HR Officer', 'icon' => 'fa-id-card-alt', 'desc' => 'Process approved requisitions for recruitment'],
        ];
        $currentRole = $roleLabels[$roleCtx] ?? $roleLabels['line-manager'];

        // Workflow steps
        $workflowSteps = [
            'pending_payroll' => ['label' => 'Payroll', 'icon' => 'fa-calculator'],
            'pending_hec'     => ['label' => 'HEC', 'icon' => 'fa-users-cog'],
            'pending_cfo'     => ['label' => 'CFO', 'icon' => 'fa-chart-line'],
            'pending_ceo'     => ['label' => 'CEO', 'icon' => 'fa-user-shield'],
            'pending_hr'      => ['label' => 'HR', 'icon' => 'fa-id-card-alt'],
            'approved'        => ['label' => 'Approved', 'icon' => 'fa-check-circle'],
        ];
        $statusOrder = array_keys($workflowSteps);

        // Status label mapping
        $statusLabels = [
            'pending_payroll' => 'Payroll Review',
            'pending_hec' => 'HEC Review',
            'pending_cfo' => 'CFO Review',
            'pending_ceo' => 'CEO Approval',
            'pending_hr' => 'HR Processing',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'rejected_for_editing' => 'Returned for Editing',
        ];
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Page Header --}}
            <div class="page-title-area">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h4>
                            <i class="fas {{ $currentRole['icon'] }} me-2"></i>
                            @if($isLineManager)
                                My Recruitment Requisitions
                            @else
                                Pending Requisitions
                            @endif
                        </h4>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="role-context">
                            <i class="fas {{ $currentRole['icon'] }}"></i>
                            {{ $currentRole['label'] }}
                        </span>
                        @php
                            $canSeeViewAll = $user->hasRole('hr') || $user->hasAnyRole(['line-manager', 'coo', 'cms', 'ccdro']);
                        @endphp
                        @if ($canSeeViewAll)
                            <a href="{{ route('requisitions.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-list me-1"></i> View All
                            </a>
                        @endif
                        @if($isLineManager)
                            <a href="{{ route('requisitions.create') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-plus me-1"></i> New Request
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stats Cards --}}
            @if($isLineManager)
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="stat-card stat-pending">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                                <div>
                                    <div class="stat-number">{{ $stats['pending'] ?? 0 }}</div>
                                    <div class="stat-label">Pending</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card stat-progress">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                                <div>
                                    <div class="stat-number">{{ $stats['in_progress'] ?? 0 }}</div>
                                    <div class="stat-label">In Progress</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card stat-approved">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                                <div>
                                    <div class="stat-number">{{ $stats['approved'] ?? 0 }}</div>
                                    <div class="stat-label">Approved</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card stat-rejected">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                                <div>
                                    <div class="stat-number">{{ $stats['rejected'] ?? 0 }}</div>
                                    <div class="stat-label">Rejected</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <div class="stat-card stat-pending">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                                <div>
                                    <div class="stat-number">{{ $stats['pending'] ?? 0 }}</div>
                                    <div class="stat-label">Awaiting Your Review</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Main Table Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    {{-- Filter Bar --}}
                    <div class="filter-bar d-flex flex-wrap align-items-end gap-3 mb-3 p-3">
                        <div>
                            <label class="form-label small fw-semibold mb-1">Department</label>
                            <select id="pendingFilterDept" class="form-select form-select-sm" style="min-width:180px">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                        @if($isLineManager)
                            <div>
                                <label class="form-label small fw-semibold mb-1">Status</label>
                                <select id="pendingFilterStatus" class="form-select form-select-sm" style="min-width:160px">
                                    <option value="">All Statuses</option>
                                    <option value="pending_payroll">Payroll Review</option>
                                    <option value="pending_hec">HEC Review</option>
                                    <option value="pending_cfo">CFO Review</option>
                                    <option value="pending_ceo">CEO Approval</option>
                                    <option value="pending_hr">HR Processing</option>
                                    <option value="rejected_for_editing">Returned for Editing</option>
                                </select>
                            </div>
                        @endif
                        <div>
                            <label class="form-label small fw-semibold mb-1">From Date</label>
                            <input type="date" id="pendingFilterFrom" class="form-control form-control-sm" style="min-width:150px">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">To Date</label>
                            <input type="date" id="pendingFilterTo" class="form-control form-control-sm" style="min-width:150px">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Timeframe</label>
                            <select id="pendingFilterTimeframe" class="form-select form-select-sm" style="min-width:160px">
                                <option value="" selected>All Time</option>
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 3 months</option>
                                <option value="180">Last 6 months</option>
                                <option value="365">Last 1 year</option>
                            </select>
                        </div>
                        <div>
                            <button id="pendingResetFilters" class="btn btn-sm btn-outline-secondary mt-auto">
                                <i class="fas fa-undo me-1"></i>Reset
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle req-table" id="pendingRequisitionsTable">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Department</th>
                                    <th>{{ $isLineManager ? 'Position' : 'Initiated By' }}</th>
                                    <th>Status</th>
                                    @if($isLineManager)
                                        <th>Progress</th>
                                    @else
                                        <th>Your Role</th>
                                    @endif
                                    <th>Submitted</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requisitions as $req)
                                    @php
                                        $reqStatus = $req->status ?? 'pending_payroll';
                                        $statusLabel = $statusLabels[$reqStatus] ?? ucfirst(str_replace('_', ' ', $reqStatus));

                                        // Determine current step index for progress
                                        $currentStepIdx = array_search($reqStatus, $statusOrder);
                                        $totalSteps = count($statusOrder);
                                        $progressPct = $currentStepIdx !== false ? round(($currentStepIdx / ($totalSteps - 1)) * 100) : 0;

                                        // Role badge for approvers
                                        $userRoleText = '';
                                        $roleBadgeClass = '';
                                        if (!$isLineManager) {
                                            if ($user->hasRole('payroll_accountant')) {
                                                $userRoleText = 'Payroll Review'; $roleBadgeClass = 'bg-info';
                                            } elseif ($user->hasAnyRole(['coo', 'cms', 'ccdro'])) {
                                                $userRoleText = 'HEC Review'; $roleBadgeClass = 'bg-warning';
                                            } elseif ($user->hasRole('cfo')) {
                                                $userRoleText = 'CFO Review'; $roleBadgeClass = 'bg-warning text-dark';
                                            } elseif ($user->hasRole('ceo')) {
                                                $userRoleText = 'CEO Review'; $roleBadgeClass = 'bg-danger';
                                            } elseif ($user->hasRole('hr')) {
                                                $userRoleText = 'HR Review'; $roleBadgeClass = 'bg-success';
                                            }
                                        }
                                    @endphp
                                    <tr data-created="{{ $req->created_at->toIso8601String() }}"
                                        data-dept="{{ $req->dept_name ?? '' }}"
                                        data-status="{{ $reqStatus }}">
                                        <td>
                                            <a href="{{ route('requisitions.show', $req->access_id) }}" class="req-ref-link">
                                                {{ $req->access_id }}
                                            </a>
                                        </td>
                                        <td>{{ $req->dept_name ?? 'N/A' }}</td>
                                        <td>
                                            @if($isLineManager)
                                                <div class="fw-semibold">{{ $req->position_title ?? 'N/A' }}</div>
                                                <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $req->position_type ?? '')) }}</small>
                                            @else
                                                <div>{{ $req->initiator_name }}</div>
                                                <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $req->initiator_type)) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-badge status-{{ $reqStatus }}">
                                                <span class="status-dot"></span>
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        @if($isLineManager)
                                            <td>
                                                @if(in_array($reqStatus, ['rejected', 'rejected_for_editing']))
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress flex-grow-1" style="height: 6px; border-radius: 3px;">
                                                            <div class="progress-bar bg-danger" style="width: 100%"></div>
                                                        </div>
                                                        <small class="text-danger fw-semibold">Returned</small>
                                                    </div>
                                                @else
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress flex-grow-1" style="height: 6px; border-radius: 3px;">
                                                            <div class="progress-bar" style="width: {{ $progressPct }}%; background: #007A33;"></div>
                                                        </div>
                                                        <small class="text-muted fw-semibold" style="min-width: 32px">{{ $progressPct }}%</small>
                                                    </div>
                                                @endif
                                            </td>
                                        @else
                                            <td>
                                                <span class="badge {{ $roleBadgeClass }}" style="font-weight:500">
                                                    {{ $userRoleText }}
                                                </span>
                                            </td>
                                        @endif
                                        <td>
                                            <div>{{ $req->created_at->format('d M Y') }}</div>
                                            <small class="text-muted">{{ $req->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($isLineManager)
                                                <a href="{{ route('requisitions.show', $req->access_id) }}" class="btn btn-track btn-sm">
                                                    <i class="fas fa-eye me-1"></i>Track
                                                </a>
                                                @if($reqStatus === 'rejected_for_editing' && !$req->isExpired())
                                                    <a href="{{ route('requisitions.edit', $req->access_id) }}" class="btn btn-sm btn-outline-warning ms-1" title="Edit & Resubmit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                            @else
                                                <a href="{{ route('requisitions.show', $req->access_id) }}" class="btn btn-review btn-sm">
                                                    <i class="fas fa-clipboard-check me-1"></i>Review
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($requisitions->isEmpty())
                        <div class="empty-state text-center">
                            @if($isLineManager)
                                <div class="empty-state-icon" style="background: #d1e7dd; color: #007A33;">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h5 class="text-muted mb-2">No Active Requisitions</h5>
                                <p class="text-muted mb-3">You haven't submitted any requisitions yet, or all have been processed.</p>
                                <a href="{{ route('requisitions.create') }}" class="btn btn-sm" style="background:#007A33; color:#fff;">
                                    <i class="fas fa-plus me-1"></i> Create New Requisition
                                </a>
                            @else
                                <div class="empty-state-icon" style="background: #d1e7dd; color: #007A33;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h5 class="text-muted mb-2">All Caught Up!</h5>
                                <p class="text-muted">No requisitions are waiting for your review right now.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const isLineManager = {{ $isLineManager ? 'true' : 'false' }};

            // Custom filter: timeframe + date range + department + status
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'pendingRequisitionsTable') return true;

                const row     = settings.aoData[dataIndex].nTr;
                const created = $(row).data('created');
                const dept    = ($(row).data('dept') || '').toLowerCase();
                const status  = ($(row).data('status') || '').toLowerCase();

                // Timeframe
                const days = parseInt($('#pendingFilterTimeframe').val(), 10);
                if (days && created) {
                    const cutoff = new Date();
                    cutoff.setHours(0, 0, 0, 0);
                    cutoff.setDate(cutoff.getDate() - days);
                    if (new Date(created) < cutoff) return false;
                }

                // From date
                const fromVal = $('#pendingFilterFrom').val();
                if (fromVal && created) {
                    if (new Date(created) < new Date(fromVal + 'T00:00:00')) return false;
                }

                // To date
                const toVal = $('#pendingFilterTo').val();
                if (toVal && created) {
                    if (new Date(created) > new Date(toVal + 'T23:59:59')) return false;
                }

                // Department
                const deptFilter = ($('#pendingFilterDept').val() || '').toLowerCase();
                if (deptFilter && !dept.includes(deptFilter)) return false;

                // Status (line manager only)
                const statusFilter = ($('#pendingFilterStatus').val() || '').toLowerCase();
                if (statusFilter && status !== statusFilter) return false;

                return true;
            });

            const table = $('#pendingRequisitionsTable').DataTable({
                dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6 d-flex justify-content-end"f>>rtip',
                pageLength: 25,
                lengthChange: true,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[isLineManager ? 5 : 5, 'desc']],
                language: {
                    search: '<i class="fas fa-search text-muted me-1"></i>',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ requisitions',
                    infoEmpty: 'No requisitions found',
                    infoFiltered: '(filtered from _MAX_ total)',
                    zeroRecords: 'No matching requisitions found',
                    paginate: { previous: '&laquo;', next: '&raquo;' }
                },
                columnDefs: [{ targets: [isLineManager ? 6 : 6], orderable: false }]
            });

            // Build department list from rows
            const allDepts = new Set();
            table.rows().every(function() {
                const dept = ($(this.node()).data('dept') || '').trim();
                if (dept) allDepts.add(dept);
            });
            Array.from(allDepts).sort().forEach(d =>
                $('#pendingFilterDept').append(`<option value="${d.toLowerCase()}">${d}</option>`)
            );

            // Wire filters
            $('#pendingFilterTimeframe, #pendingFilterFrom, #pendingFilterTo, #pendingFilterDept, #pendingFilterStatus')
                .on('change', () => table.draw());

            // Reset
            $('#pendingResetFilters').on('click', function() {
                $('#pendingFilterTimeframe').val('');
                $('#pendingFilterFrom').val('');
                $('#pendingFilterTo').val('');
                $('#pendingFilterDept').val('');
                $('#pendingFilterStatus').val('');
                table.draw();
            });

            // Apply default timeframe on load
            table.draw();
        });
    </script>
@endpush
