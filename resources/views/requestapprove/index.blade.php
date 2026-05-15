@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    @if (session('error'))
        <div class="alert alert-danger">
            {!! session('error') !!}
        </div>
    @endif

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" />

    <div class="page-wrapper" style="visibility:hidden">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <h3 class="page-title mb-0">
                                <span id="page-title-text">Requests To Approve</span>
                            </h3>
                            <div class="d-flex gap-2 align-items-center">
                                @can('ict_acces_report')
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnPendingMyReview" onclick="toggleMyReview()">
                                        <i class="fas fa-user-clock me-1"></i> Pending My Review
                                        @php
                                            $myReviewCount = count($myPendingWorkflowIds ?? []);
                                        @endphp
                                        @if($myReviewCount > 0)
                                            <span class="badge bg-danger ms-1">{{ $myReviewCount }}</span>
                                        @endif
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#exportModal">
                                        <i class="fas fa-file-excel me-1"></i> Export Report
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Approver Statistics Chat Style (Reports Mode Only) --}}
            @if (($viewMode ?? 'requests') === 'reports' && isset($approverStats))
                @php
                    if ($approverStats->isNotEmpty()) {
                        // Filter out 'requester' role from approver statistics
                        $filteredStats = $approverStats->reject(function ($value, $key) {
                            return strtolower($key) === 'requester';
                        });

                        // Show all roles (no role filter)
                        $rolesToShow = $filteredStats->keys();
                    } else {
                        $rolesToShow = collect();
                    }

                    // Format form type name
                    $formTypeNames = [
                        'all' => 'All Form Types',
                        'ict' => 'ICT Access Form',
                        'hr' => 'HR Form',
                        'bank' => 'Bank Details Form',
                        'heslb' => 'Loan Board Form (HESLB)',
                        'nhif' => 'NHIF Registration',
                        'id' => 'ID Card Request',
                        'change' => 'Change Request',
                    ];
                    $displayFormType = $formTypeNames[$formTypeFilter ?? 'all'] ?? 'All Form Types';

                    // Format month name
                    $monthNames = [
                        'all' => 'All Months',
                        '1' => 'January',
                        '2' => 'February',
                        '3' => 'March',
                        '4' => 'April',
                        '5' => 'May',
                        '6' => 'June',
                        '7' => 'July',
                        '8' => 'August',
                        '9' => 'September',
                        '10' => 'October',
                        '11' => 'November',
                        '12' => 'December',
                    ];
                    $displayMonth = $monthNames[$monthFilter ?? 'all'] ?? 'All Months';

                    // Role name mapping
                    $roleDisplayMap = [
                        'it' => 'IT',
                        'hr' => 'HR',
                        'ceo' => 'CEO',
                        'coo' => 'COO',
                        'cfo' => 'CFO',
                        'cms' => 'CMS',
                        'ccdro' => 'CCDRO',
                        'line-manager' => 'Line Manager',
                        'line_manager' => 'Line Manager',
                        'quality_assurance' => 'Quality Assurance',
                        'quality-assurance' => 'Quality Assurance',
                    ];
                @endphp
                @if ($rolesToShow->isNotEmpty())
                    <div class="card shadow-sm mb-3 border-0">
                        <div class="card-body p-0">
                            <div class="chat-container" style="max-height: 600px; overflow-y: auto;">
                                {{-- System Header Message --}}
                                <div class="chat-message chat-message-system">
                                    <div class="chat-avatar chat-avatar-system">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="chat-bubble chat-bubble-system">
                                        <div class="chat-message-header">
                                            <strong>Report Summary</strong>
                                            <span class="chat-time">{{ now()->format('H:i') }}</span>
                                        </div>
                                        <div class="chat-message-body mt-2">
                                            <div class="summary-item">
                                                <i class="fas fa-file-alt text-primary"></i>
                                                <span><strong>Form Type:</strong> {{ $displayFormType }}</span>
                                            </div>
                                            <div class="summary-item">
                                                <i class="fas fa-calendar-alt text-success"></i>
                                                <span><strong>Year:</strong> {{ $yearFilter ?? date('Y') }}</span>
                                            </div>
                                            <div class="summary-item">
                                                <i class="fas fa-calendar text-warning"></i>
                                                <span><strong>Month:</strong> {{ $displayMonth }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Approver Statistics by Role --}}
                                @foreach ($rolesToShow as $roleName)
                                    @if ($approverStats->has($roleName) && strtolower($roleName) !== 'requester')
                                        @php
                                            $approvers = $approverStats->get($roleName);
                                            $roleLower = strtolower($roleName);
                                            $displayRole = isset($roleDisplayMap[$roleLower])
                                                ? $roleDisplayMap[$roleLower]
                                                : ucwords(str_replace(['_', '-'], ' ', $roleName));

                                            // Get role icon
                                            $roleIcons = [
                                                'it' => 'fa-laptop-code',
                                                'hr' => 'fa-user-tie',
                                                'ceo' => 'fa-crown',
                                                'coo' => 'fa-briefcase',
                                                'cfo' => 'fa-dollar-sign',
                                                'cms' => 'fa-stethoscope',
                                                'ccdro' => 'fa-user-shield',
                                                'line-manager' => 'fa-users-cog',
                                                'line_manager' => 'fa-users-cog',
                                                'quality_assurance' => 'fa-clipboard-check',
                                                'quality-assurance' => 'fa-clipboard-check',
                                            ];
                                            $roleIcon = $roleIcons[$roleLower] ?? 'fa-users';
                                        @endphp
                                        @if ($approvers && $approvers->isNotEmpty())
                                            <div class="chat-message chat-message-role">
                                                <div class="chat-avatar chat-avatar-role">
                                                    <i class="fas {{ $roleIcon }}"></i>
                                                </div>
                                                <div class="chat-bubble chat-bubble-role">
                                                    <div class="chat-message-header">
                                                        <strong>Top {{ $displayRole }} Approvers</strong>
                                                        <span class="badge bg-primary ms-2">{{ $approvers->count() }}
                                                            staff</span>
                                                    </div>
                                                    <div class="chat-message-body mt-2">
                                                        <div class="approvers-list">
                                                            @foreach ($approvers->take(10) as $index => $approver)
                                                                <div class="approver-item">
                                                                    <div class="approver-rank">
                                                                        <span
                                                                            class="rank-badge rank-{{ $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : 'default')) }}">
                                                                            {{ $index + 1 }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="approver-info">
                                                                        <div class="approver-name">
                                                                            {{ trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) ?: $approver->username }}
                                                                        </div>
                                                                        <div class="approver-count">
                                                                            <i
                                                                                class="fas fa-check-circle text-success me-1"></i>
                                                                            {{ $approver->approval_count }}
                                                                            {{ Str::plural('approval', $approver->approval_count) }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Filter Section --}}
            <div class="card mb-3 shadow-sm">
                <div class="card-body py-3">
                    @if (($viewMode ?? 'requests') === 'reports')
                        {{-- Reports Mode Filters --}}
                        <form method="GET" action="{{ route('requestapprove.index') }}" id="reportsFilterForm">
                            <input type="hidden" name="mode" value="reports">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">
                                        <i class="fas fa-file-alt me-1 text-primary"></i>Form Type
                                    </label>
                                    <select class="form-select form-select-sm" name="form_type" id="formTypeFilter">
                                        <option value="all"
                                            {{ ($formTypeFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Form Types
                                        </option>
                                        @foreach ($availableFormTypes ?? [] as $ftKey => $ftLabel)
                                            <option value="{{ $ftKey }}"
                                                {{ ($formTypeFilter ?? '') === $ftKey ? 'selected' : '' }}>
                                                {{ $ftLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">
                                        <i class="fas fa-calendar-alt me-1 text-primary"></i>Year
                                    </label>
                                    <select class="form-select form-select-sm" name="year" id="yearFilter">
                                        @foreach ($availableYears ?? [] as $yr)
                                            <option value="{{ $yr }}"
                                                {{ ($yearFilter ?? '') == $yr ? 'selected' : '' }}>{{ $yr }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">
                                        <i class="fas fa-calendar me-1 text-primary"></i>Month
                                    </label>
                                    <select class="form-select form-select-sm" name="month" id="monthFilter">
                                        <option value="all" {{ ($monthFilter ?? 'all') === 'all' ? 'selected' : '' }}>
                                            All Months</option>
                                        @foreach ($availableMonths ?? [] as $mo)
                                            <option value="{{ $mo }}"
                                                {{ ($monthFilter ?? '') == $mo ? 'selected' : '' }}>
                                                {{ $monthNames[$mo] ?? $mo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-search me-1"></i>Apply
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        {{-- Requests Mode Filters --}}
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label mb-1 small fw-semibold text-muted">
                                    <i class="fas fa-file-alt me-1"></i>Form Type:
                                </label>
                                <select class="form-select form-select-sm" id="formTypeFilter">
                                    <option value="all" selected>All Form Types</option>
                                    <option value="ict">ICT Access Form</option>
                                    <option value="hr">HR Form</option>
                                    <option value="bank">Bank Details Form</option>
                                    <option value="heslb">Loan Board Form (HESLB)</option>
                                    <option value="nhif">NHIF Registration</option>
                                    <option value="id">ID Card Request</option>
                                    <option value="change">Change Request</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-7">
                                <label class="form-label mb-1 small fw-semibold text-muted">
                                    <i class="fas fa-filter me-1"></i>Status:
                                </label>
                                <div class="status-filter-group">
                                    <button type="button" class="status-btn status-btn-all active" data-filter="all"
                                        id="filter-all">
                                        <i class="fas fa-list me-1"></i>All
                                    </button>
                                    <button type="button" class="status-btn status-btn-pending" data-filter="pending"
                                        id="filter-pending">
                                        <i class="fas fa-clock me-1"></i>Pending
                                    </button>
                                    <button type="button" class="status-btn status-btn-approved" data-filter="approved"
                                        id="filter-approved">
                                        <i class="fas fa-check-circle me-1"></i>Approved
                                    </button>
                                    <button type="button" class="status-btn status-btn-rejected" data-filter="rejected"
                                        id="filter-rejected">
                                        <i class="fas fa-times-circle me-1"></i>Rejected
                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- Active filter tags + clear --}}
                        <div id="activeFiltersDisplay" class="mt-2 d-flex align-items-center gap-2 flex-wrap"
                            style="display:none!important;">
                            <small class="text-muted">Active filters:</small>
                            <span class="badge bg-primary d-none" id="activeFormType"></span>
                            <span class="badge d-none" id="activeStatus"></span>
                            <button type="button" class="btn btn-outline-secondary btn-xs px-2 py-0" id="clearFilters"
                                style="font-size:0.75rem;display:none;">
                                <i class="fas fa-times me-1"></i>Clear
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Summary Report (Requests mode only) --}}
            @if (($viewMode ?? 'requests') !== 'reports')
                <div class="card mb-3 border-0 shadow-sm" id="summaryReport">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-1">
                                <i class="fas fa-chart-bar text-muted me-1" style="font-size:0.85rem;"></i>
                                <small class="text-muted fw-semibold" id="summaryLabel">All Requests</small>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <div class="summary-stat summary-stat-total" onclick="resetFilters()">
                                    <span class="summary-stat-count" id="count-total">—</span>
                                    <span class="summary-stat-label"><i class="fas fa-layer-group me-1"></i>Total</span>
                                </div>
                                <div class="summary-stat summary-stat-pending" onclick="setStatusFilter('pending')">
                                    <span class="summary-stat-count" id="count-pending">—</span>
                                    <span class="summary-stat-label"><i class="fas fa-clock me-1"></i>Pending</span>
                                </div>
                                <div class="summary-stat summary-stat-approved" onclick="setStatusFilter('approved')">
                                    <span class="summary-stat-count" id="count-approved">—</span>
                                    <span class="summary-stat-label"><i
                                            class="fas fa-check-circle me-1"></i>Approved</span>
                                </div>
                                <div class="summary-stat summary-stat-rejected" onclick="setStatusFilter('rejected')">
                                    <span class="summary-stat-count" id="count-rejected">—</span>
                                    <span class="summary-stat-label"><i
                                            class="fas fa-times-circle me-1"></i>Rejected</span>
                                </div>
                            </div>
                        </div>
                        {{-- Entity breakdown (shown only when entity data exists) --}}
                        <div id="entityBreakdown" class="mt-2 pt-2" style="border-top:1px solid #f0f0f0; display:none;">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <small class="text-muted fw-semibold" style="font-size:0.75rem; min-width:fit-content;">
                                    <i class="fas fa-building me-1"></i>By Entity:
                                </small>
                                <div id="entityBreakdownItems" class="d-flex gap-2 flex-wrap"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table id="example" class="table table-hover table-striped align-middle" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th class="no-sort" style="width:2rem">#</th>
                                    <th>Request Type</th>
                                    <th>Requester Name</th>
                                    <th>Submitted Date</th>
                                    <th>Status</th>
                                    @if (($viewMode ?? 'requests') === 'reports')
                                        <th class="no-sort">Approval Flow</th>
                                        <th>Decision By</th>
                                    @endif
                                    <th>Decision Date</th>
                                    <th class="no-sort text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 1;

                                    // Default mapping for all other forms
                                    $defaultStatusMap = [
                                        0 => ['class' => 'warning', 'text' => 'Pending'],
                                        1 => ['class' => 'success', 'text' => 'Approved'],
                                        -1 => ['class' => 'danger', 'text' => 'Rejected'],
                                        2 => ['class' => 'danger', 'text' => 'Rejected'],
                                    ];

                                    // Requisition mapping
                                    $requisitionStatusMap = [
                                        -1 => ['class' => 'info', 'text' => 'Approved'],
                                        0 => ['class' => 'warning', 'text' => 'Pending'],
                                        1 => ['class' => 'secondary', 'text' => 'Under Review'],
                                        2 => ['class' => 'danger', 'text' => 'Rejected'],
                                        3 => ['class' => 'success', 'text' => 'Completed'],
                                    ];
                                @endphp

                                {{-- PENDING REQUESTS --}}
                                @foreach ($pending as $pendingRequest)
                                    @php
                                        // Determine status for filtering
                                        $rowStatus = 'pending';
                                        $isRejected = false;
                                        $rejectionReason = $pendingRequest->rejection_reason ?? null;

                                        if ($pendingRequest->requisition_id) {
                                            if (
                                                $pendingRequest->requisition_status == -1 ||
                                                $pendingRequest->requisition_status == 3
                                            ) {
                                                $rowStatus = 'approved';
                                            } elseif ($pendingRequest->requisition_status == 2) {
                                                $rowStatus = 'rejected';
                                                $isRejected = true;
                                            } elseif ($pendingRequest->requisition_status == 0) {
                                                $rowStatus = 'pending';
                                            } else {
                                                $rowStatus = 'pending';
                                            }
                                        } else {
                                            // For report mode, check workflow completion status
                                            if (
                                                ($viewMode ?? 'requests') === 'reports' &&
                                                isset($pendingRequest->work_flow_completed)
                                            ) {
                                                if ($pendingRequest->work_flow_completed == 1) {
                                                    $rowStatus = 'approved';
                                                } elseif (str_contains(strtolower($pendingRequest->work_flow_status ?? ''), 'rejected')) {
                                                    $rowStatus = 'rejected';
                                                    $isRejected = true;
                                                } else {
                                                    $rowStatus = 'pending';
                                                }
                                            } else {
                                                // Normal requests mode
                                                if ($pendingRequest->status == 1) {
                                                    $rowStatus = 'approved';
                                                } elseif ($pendingRequest->status == -1 || $pendingRequest->status == 2) {
                                                    $rowStatus = 'rejected';
                                                    $isRejected = true;
                                                    $rejectionReason = $pendingRequest->rejection_reason ?? $rejectionReason;
                                                } else {
                                                    $rowStatus = 'pending';
                                                }
                                            }
                                        }

                                        // Get requester full name
                                        $requesterFullName = trim(
                                            ($pendingRequest->requester_fname ?? '') .
                                                ' ' .
                                                ($pendingRequest->requester_lname ?? ''),
                                        );
                                        if (empty($requesterFullName)) {
                                            $requesterFullName = $pendingRequest->requester_name;
                                        }

                                        // Determine form type for filtering
                                        $formType = 'other';
                                        $formTypeName = 'No Form';
                                        if ($pendingRequest->ict_request_resource_id) {
                                            $formType = 'ict';
                                            $formTypeName = 'ICT Access Form';
                                        } elseif ($pendingRequest->hr_form) {
                                            $formType = 'hr';
                                            $formTypeName = 'HR Form';
                                        } elseif ($pendingRequest->bank_form) {
                                            $formType = 'bank';
                                            $formTypeName = 'Bank Details Form';
                                        } elseif ($pendingRequest->heslb_form) {
                                            $formType = 'heslb';
                                            $formTypeName = 'Loan Board Form';
                                        } elseif ($pendingRequest->nhif_form) {
                                            $formType = 'nhif';
                                            $formTypeName = 'NHIF Form';
                                        } elseif ($pendingRequest->id_form) {
                                            $formType = 'id';
                                            $formTypeName = 'ID Form';
                                        } elseif ($pendingRequest->change_request_id) {
                                            $formType = 'change';
                                            $formTypeName = 'Change Request';
                                        } elseif ($pendingRequest->requisition_id) {
                                            $formType = 'rrf';
                                            $formTypeName = 'RRF Request';
                                        }
                                        // Decision By (reports mode only)
                                        $decisionByUser = null;
                                        if (
                                            ($viewMode ?? 'requests') === 'reports' &&
                                            ($pendingRequest->who_approve ?? null)
                                        ) {
                                            $decisionByUser = \App\Models\User::find($pendingRequest->who_approve);
                                        }
                                    @endphp
                                    <tr data-status="{{ $rowStatus }}" data-form-type="{{ $formType }}"
                                        data-entity="{{ $pendingRequest->entity_name ?? '' }}"
                                        data-my-review="{{ in_array($pendingRequest->id, $myPendingWorkflowIds ?? []) ? '1' : '0' }}"
                                        class="{{ $isRejected ? 'table-danger bg-danger-subtle' : '' }}">
                                        <td>{{ $i++ }}</td>
                                        <td>{{ $formTypeName }}</td>

                                        <td>{{ $requesterFullName }}</td>

                                        <td>
                                            {{ \Carbon\Carbon::parse($pendingRequest->attend_date ?? $pendingRequest->created_at)->format('d F Y') }}
                                        </td>

                                        {{-- STATUS --}}
                                        <td>
                                            @if ($pendingRequest->requisition_id)
                                                @php
                                                    $statusInfo = $requisitionStatusMap[
                                                        $pendingRequest->requisition_status
                                                    ] ?? ['class' => 'secondary', 'text' => 'Unknown'];
                                                @endphp
                                                <span
                                                    class="badge bg-{{ $statusInfo['class'] }} {{ $statusInfo['class'] == 'danger' ? 'text-white' : 'text-dark' }} font-size-11">
                                                    {{ $statusInfo['text'] }}
                                                </span>
                                            @else
                                                @php
                                                    // For report mode, use workflow status
                                                    if (
                                                        ($viewMode ?? 'requests') === 'reports' &&
                                                        isset($pendingRequest->work_flow_completed)
                                                    ) {
                                                        if ($pendingRequest->work_flow_completed == 1) {
                                                            $statusInfo = [
                                                                'class' => 'success',
                                                                'text' => 'Approved',
                                                            ];
                                                        } elseif (str_contains(strtolower($pendingRequest->work_flow_status ?? ''), 'rejected')) {
                                                            $statusInfo = [
                                                                'class' => 'danger',
                                                                'text' => 'Rejected',
                                                            ];
                                                        } else {
                                                            $statusInfo = [
                                                                'class' => 'warning',
                                                                'text' => 'Pending',
                                                            ];
                                                        }
                                                    } else {
                                                        $statusInfo = $defaultStatusMap[$pendingRequest->status] ?? [
                                                            'class' => 'secondary',
                                                            'text' => 'Unknown',
                                                        ];
                                                    }

                                                    // For pending items, find who it's currently pending with
                                                    $pendingAtRole = null;
                                                    $pendingAtUser = null;
                                                    if ($statusInfo['text'] === 'Pending') {
                                                        $pendingHistories = \App\Models\WorkFlowHistory::where('work_flow_id', $pendingRequest->id)
                                                            ->where('status', 0)
                                                            ->orderBy('id', 'desc')
                                                            ->get();
                                                        foreach ($pendingHistories as $pendingHistory) {
                                                            if ($pendingHistory->step_name) {
                                                                $pendingAtRole = $pendingHistory->step_name;
                                                                break;
                                                            } elseif ($pendingHistory->attended_by) {
                                                                $pendingUser = \App\Models\User::find($pendingHistory->attended_by);
                                                                if ($pendingUser) {
                                                                    $roleName = $pendingUser->getRoleNames()->reject(fn($r) => $r === 'requester')->first();
                                                                    if ($roleName) {
                                                                        $pendingAtRole = ucwords(str_replace(['-', '_'], ' ', $roleName));
                                                                        $pendingAtRole = preg_replace_callback('/\b(it|hr|cms|cfo|coo|ict)\b/i', fn($m) => strtoupper($m[1]), $pendingAtRole);
                                                                        $pendingAtUser = trim($pendingUser->fname . ' ' . $pendingUser->lname);
                                                                        break;
                                                                    } else {
                                                                        // User only has 'requester' role - likely a line manager without the role assigned
                                                                        $pendingAtRole = 'Line Manager';
                                                                        $pendingAtUser = trim($pendingUser->fname . ' ' . $pendingUser->lname);
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                <span
                                                    class="badge bg-{{ $statusInfo['class'] }} {{ $statusInfo['class'] == 'danger' ? 'text-white' : 'text-dark' }} font-size-11">
                                                    {{ $statusInfo['text'] }}@if ($statusInfo['text'] === 'Pending' && $pendingAtRole) — {{ $pendingAtRole }}@endif
                                                </span>
                                                @if ($statusInfo['text'] === 'Unknown')
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>No valid form data
                                                    </small>
                                                @endif
                                                @if ($isRejected && $rejectionReason)
                                                    <br>
                                                    <small class="text-danger" title="{{ $rejectionReason }}">
                                                        <i class="fas fa-info-circle"></i>
                                                        {{ \Illuminate\Support\Str::limit($rejectionReason, 30) }}
                                                    </small>
                                                @endif
                                            @endif
                                        </td>

                                        {{-- APPROVAL FLOW (Reports Mode Only) --}}
                                        @if (($viewMode ?? 'requests') === 'reports')
                                            <td>
                                                @php
                                                    $workflow = \App\Models\Workflow::with([
                                                        'histories' => function ($q) {
                                                            $q->with(['attendedBy', 'forwardedBy'])->orderBy(
                                                                'id',
                                                                'asc',
                                                            );
                                                        },
                                                    ])->find($pendingRequest->id);

                                                    if ($workflow && $workflow->histories) {
                                                        // Role name mapping
                                                        $roleMap = [
                                                            'it' => 'IT',
                                                            'hr' => 'HR',
                                                            'ceo' => 'CEO',
                                                            'coo' => 'COO',
                                                            'cfo' => 'CFO',
                                                            'cms' => 'CMS',
                                                            'ccdro' => 'CCDRO',
                                                            'line-manager' => 'Line Manager',
                                                            'line_manager' => 'Line Manager',
                                                            'quality_assurance' => 'Quality Assurance',
                                                            'quality-assurance' => 'Quality Assurance',
                                                            'requester' => 'Requester',
                                                        ];

                                                        $steps = [];
                                                        foreach ($workflow->histories as $history) {
                                                            $approverRole =
                                                                $history->attendedBy->getRoleNames()->first() ?? 'N/A';

                                                            // Format role name
                                                            if (empty($approverRole) || $approverRole === 'N/A') {
                                                                $formattedRole = 'N/A';
                                                            } else {
                                                                $roleLower = strtolower($approverRole);
                                                                if (isset($roleMap[$roleLower])) {
                                                                    $formattedRole = $roleMap[$roleLower];
                                                                } else {
                                                                    $formatted = str_replace(
                                                                        ['_', '-'],
                                                                        ' ',
                                                                        $approverRole,
                                                                    );
                                                                    $formatted = ucwords(strtolower($formatted));
                                                                    if (
                                                                        strlen($formatted) <= 4 &&
                                                                        !str_contains($formatted, ' ')
                                                                    ) {
                                                                        $formattedRole = strtoupper($formatted);
                                                                    } else {
                                                                        $formattedRole = $formatted;
                                                                    }
                                                                }
                                                            }

                                                            if ($history->status == 1) {
                                                                $steps[] =
                                                                    '<span class="badge bg-success text-white">' .
                                                                    htmlspecialchars($formattedRole) .
                                                                    '</span>';
                                                            } elseif ($history->status == 2 || $history->status == -1) {
                                                                $steps[] =
                                                                    '<span class="badge bg-danger text-white">' .
                                                                    htmlspecialchars($formattedRole) .
                                                                    ' <small>(Rejected)</small></span>';
                                                            } else {
                                                                $steps[] =
                                                                    '<span class="badge bg-warning text-dark">' .
                                                                    htmlspecialchars($formattedRole) .
                                                                    ' <small>(Pending)</small></span>';
                                                            }
                                                        }
                                                        echo '<div class="d-flex flex-wrap align-items-center gap-1">' .
                                                            implode(
                                                                '<i class="fas fa-arrow-right text-muted mx-1"></i>',
                                                                $steps,
                                                            ) .
                                                            '</div>';
                                                    } else {
                                                        echo '<span class="text-muted">—</span>';
                                                    }
                                                @endphp
                                            </td>
                                        @endif

                                        {{-- DECISION BY (Reports Mode Only) --}}
                                        @if (($viewMode ?? 'requests') === 'reports')
                                            <td>
                                                @if ($decisionByUser)
                                                    <strong
                                                        class="d-block">{{ trim(($decisionByUser->fname ?? '') . ' ' . ($decisionByUser->lname ?? '')) ?: $decisionByUser->username }}</strong>
                                                    <small
                                                        class="text-muted">{{ $decisionByUser->getRoleNames()->first() ? ucwords(str_replace(['-', '_'], ' ', $decisionByUser->getRoleNames()->first())) : '' }}</small>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        @endif

                                        {{-- APPROVED / REJECTED DATE --}}
                                        <td>
                                            @if ($pendingRequest->requisition_id && in_array($pendingRequest->requisition_status, [-1, 1, 2, 3]))
                                                {{ \Carbon\Carbon::parse($pendingRequest->decision_date ?? $pendingRequest->updated_at)->format('d F Y') }}
                                            @elseif (in_array($pendingRequest->status, [1, -1]))
                                                {{ \Carbon\Carbon::parse($pendingRequest->decision_date ?? $pendingRequest->updated_at)->format('d F Y') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        {{-- ACTIONS --}}
                                        <td>
                                            @if ($pendingRequest->ict_request_resource_id)
                                                <button type="button"
                                                    class="btn btn-{{ $isRejected ? 'outline-danger' : 'primary' }} btn-sm"
                                                    title="View"
                                                    onclick="showForm('ict', {{ $pendingRequest->ict_request_resource_id }})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @elseif ($pendingRequest->hr_form)
                                                <a href="{{ route('hr_form', ['id' => $pendingRequest->hr_form]) }}"
                                                    class="btn btn-{{ $isRejected ? 'outline-danger' : 'primary' }} btn-sm"
                                                    title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @elseif ($pendingRequest->bank_form)
                                                <a href="{{ route('bank_form', ['id' => $pendingRequest->bank_form]) }}"
                                                    class="btn btn-{{ $isRejected ? 'outline-danger' : 'primary' }} btn-sm"
                                                    title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @elseif ($pendingRequest->heslb_form)
                                                <a href="{{ route('heslb_form', ['id' => $pendingRequest->heslb_form]) }}"
                                                    class="btn btn-{{ $isRejected ? 'outline-danger' : 'primary' }} btn-sm"
                                                    title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @elseif ($pendingRequest->nhif_form)
                                                <a href="{{ route('nhif_form', ['id' => $pendingRequest->nhif_form]) }}"
                                                    class="btn btn-{{ $isRejected ? 'outline-danger' : 'primary' }} btn-sm"
                                                    title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @elseif ($pendingRequest->id_form)
                                                <a href="{{ route('id_form', ['id' => $pendingRequest->id_form]) }}"
                                                    class="btn btn-{{ $isRejected ? 'outline-danger' : 'primary' }} btn-sm"
                                                    title="View ID Form">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @elseif ($pendingRequest->change_request_id)
                                                <a href="{{ route('change_request.show', ['id' => $pendingRequest->change_request_id]) }}"
                                                    class="btn btn-{{ $isRejected ? 'outline-danger' : 'primary' }} btn-sm"
                                                    title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @endif

                                            {{-- Delete button: super-admin can delete any pending/unknown form; admin/HR/IT can delete unknown/broken forms --}}
                                            @php
                                                $isSuperAdmin = Auth::user()->hasAnyRole(['super-admin', 'Super-Admin']);
                                                $canDeleteThis = ($formType === 'other' || $statusInfo['text'] === 'Unknown')
                                                    ? Auth::user()->hasAnyRole(['super-admin', 'Super-Admin', 'admin', 'hr', 'it'])
                                                    : ($isSuperAdmin && $rowStatus === 'pending');
                                            @endphp
                                            @if ($canDeleteThis)
                                                <button type="button" class="btn btn-outline-danger btn-sm ms-1"
                                                    title="Delete request"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteWorkflowModal{{ $pendingRequest->id }}">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>

                                    {{-- Delete Modal --}}
                                    @if ($canDeleteThis)
                                        <div class="modal fade" id="deleteWorkflowModal{{ $pendingRequest->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>Delete Request
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete this request?</p>
                                                        <div class="alert alert-warning">
                                                            <strong>Request #{{ $pendingRequest->id }}</strong> — {{ $formTypeName }}
                                                            <br>
                                                            <small>Requester: {{ $requesterFullName }}</small>
                                                            <br>
                                                            <small>Status: {{ $statusInfo['text'] ?? 'Unknown' }}</small>
                                                        </div>
                                                        <p class="text-danger small"><i class="fas fa-exclamation-circle me-1"></i>This will permanently delete the workflow and all its history. This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="{{ route('requestapprove.destroy', $pendingRequest->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-trash-alt me-1"></i>Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach

                                {{-- CLEARANCE FORMS REMOVED - Now shown in separate "Clearance Forms" navigation --}}
                                {{-- Clearance forms should only appear in clearance.index view, not in General Requests --}}
                                @if (false)
                                    @foreach ($clear as $clearRequest)
                                        @php
                                            $workflow = \App\Models\Clearance_work_flow::find(
                                                $clearRequest->work_flow_id,
                                            );
                                            $workflowStatus = $workflow ? $workflow->work_flow_status : 'Unknown';
                                            $stepName = $clearRequest->step_name ?? 'Pending';
                                            $isHR = Auth::user()->hasRole('hr');

                                            // For non-HR users: Skip approved forms (they should have moved to next level)
                                            // For HR: Show all forms
                                            if (!$isHR && $clearRequest->status == 1) {
                                                continue; // Skip approved forms for non-HR
                                            }

                                            // Determine status badge
                                            if ($clearRequest->status == 1) {
                                                $statusClass = 'success';
                                                $statusText = 'Approved';
                                                $rowStatus = 'approved';
                                            } elseif ($clearRequest->status == 2) {
                                                $statusClass = 'danger';
                                                $statusText = 'Rejected';
                                                $rowStatus = 'rejected';
                                            } else {
                                                $statusClass = 'warning';
                                                $statusText = $workflowStatus;
                                                $rowStatus = 'pending';
                                            }
                                        @endphp
                                        <tr data-status="{{ $rowStatus }}" data-request-type="clearance">
                                            <td>{{ $i++ }}</td>
                                            <td>
                                                <strong>Clearance Form</strong>
                                                @if ($stepName && $stepName !== 'Pending')
                                                    <br><small class="text-muted">Step: {{ $stepName }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if (isset($clearRequest->fname) && isset($clearRequest->lname))
                                                    {{ trim($clearRequest->fname . ' ' . $clearRequest->lname) }}
                                                @else
                                                    {{ $clearRequest->requester_name }}
                                                @endif
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($clearRequest->attend_date ?? $clearRequest->created_at)->format('d F Y, h:i A') }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $statusClass }} text-white font-size-11">
                                                    {{ $statusText }}
                                                </span>
                                                @if ($workflow && $workflow->work_flow_completed == 1)
                                                    <br><small class="text-success"><i class="fas fa-check-circle"></i>
                                                        Completed</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if (in_array($clearRequest->status, [1, 2]))
                                                    {{ \Carbon\Carbon::parse($clearRequest->updated_at)->format('d F Y, h:i A') }}
                                                @else
                                                    <span class="text-muted">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button class="btn btn-primary btn-sm" title="View & Review"
                                                    onclick="showForm('clearance', {{ $clearRequest->requested_resource_id }})">
                                                    <i class="fas fa-eye me-1"></i> Review
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- JS Scripts --}}
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

        <script>
            @php $isReportsModeJs = ($viewMode ?? 'requests') === 'reports'; @endphp
            var isReportsMode = {{ $isReportsModeJs ? 'true' : 'false' }};
            // col indices: reports=[0,5,8] no-sort, requests=[0,6] no-sort
            var noSortCols = isReportsMode ? [0, 5, 8] : [0, 6];
            var table = $('#example').DataTable({
                dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6 d-flex justify-content-end"f>>rtip',
                pageLength: 25,
                order: [
                    [3, 'desc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: noSortCols
                }],
                initComplete: function() {
                    // Fix double arrow on page size select
                    $('select[name="example_length"]').css({
                        '-webkit-appearance': 'none',
                        '-moz-appearance': 'none',
                        'appearance': 'none',
                        'background': 'white url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 12 12\'%3E%3Cpath d=\'M6 8L1 3h10z\' fill=\'%23333\'/%3E%3C/svg%3E") no-repeat right 8px center',
                        'padding-right': '28px'
                    });
                },
                language: {
                    search: 'Search:',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries to show',
                    infoFiltered: '(filtered from _MAX_ total)',
                    zeroRecords: 'No matching records found',
                    lengthMenu: 'Show _MENU_ entries',
                    paginate: {
                        first: 'First',
                        last: 'Last',
                        next: 'Next',
                        previous: 'Previous'
                    }
                }
            });

            // Current filter state
            let currentStatusFilter = 'all';
            let currentFormTypeFilter = 'all';
            let myReviewFilterActive = false;

            // Form type names for display
            const formTypeNames = {
                'all': 'All Form Types',
                'ict': 'ICT Access Form',
                'hr': 'HR Form',
                'bank': 'Bank Details Form',
                'heslb': 'Loan Board Form',
                'nhif': 'NHIF Form',
                'id': 'ID Form',
                'change': 'Change Request'
            };

            const statusNames = {
                'all': 'All Status',
                'pending': 'Pending',
                'approved': 'Approved',
                'rejected': 'Rejected'
            };

            // Register DataTables custom search
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'example') return true;
                var rowNode = table.row(dataIndex).node();
                if (!rowNode) return true;
                var rowStatus = $(rowNode).data('status') || '';
                var rowFormType = $(rowNode).data('form-type') || '';
                var rowMyReview = $(rowNode).data('my-review');
                var statusMatch = currentStatusFilter === 'all' || rowStatus === currentStatusFilter;
                var formTypeMatch = currentFormTypeFilter === 'all' || rowFormType === currentFormTypeFilter;
                var myReviewMatch = !myReviewFilterActive || rowMyReview == 1;
                return statusMatch && formTypeMatch && myReviewMatch;
            });

            // Update summary cards from visible (filtered) rows
            function updateSummaryCards() {
                var total = 0,
                    pending = 0,
                    approved = 0,
                    rejected = 0;
                var entityCounts = {};
                table.rows({
                    search: 'applied'
                }).nodes().each(function(row) {
                    total++;
                    var s = $(row).data('status');
                    if (s === 'pending') pending++;
                    else if (s === 'approved') approved++;
                    else if (s === 'rejected') rejected++;
                    var entity = $(row).data('entity');
                    if (entity) {
                        entityCounts[entity] = (entityCounts[entity] || 0) + 1;
                    }
                });
                $('#count-total').text(total);
                $('#count-pending').text(pending);
                $('#count-approved').text(approved);
                $('#count-rejected').text(rejected);

                // Update entity breakdown
                var entityKeys = Object.keys(entityCounts).sort(function(a, b) {
                    return entityCounts[b] - entityCounts[a];
                });
                if (entityKeys.length > 0) {
                    var html = '';
                    entityKeys.forEach(function(name) {
                        html += '<span class="entity-badge"><span class="entity-badge-name">' + name + '</span>' +
                            '<span class="entity-badge-count">' + entityCounts[name] + '</span></span>';
                    });
                    $('#entityBreakdownItems').html(html);
                    $('#entityBreakdown').show();
                } else {
                    $('#entityBreakdown').hide();
                }

                // Update summary label
                var parts = [];
                if (currentFormTypeFilter !== 'all') parts.push(formTypeNames[currentFormTypeFilter]);
                if (currentStatusFilter !== 'all') parts.push(statusNames[currentStatusFilter]);
                $('#summaryLabel').text(parts.length ? parts.join(' — ') : 'All Requests');
            }

            // Apply filters via DataTables redraw
            function applyFilters() {
                table.draw();
                updateSummaryCards();
                updateActiveFiltersDisplay();
            }

            function setStatusFilter(status) {
                currentStatusFilter = status;
                document.querySelectorAll('[data-filter]').forEach(function(btn) {
                    btn.classList.toggle('active', btn.getAttribute('data-filter') === status);
                });
                applyFilters();
            }

            function resetFilters() {
                currentStatusFilter = 'all';
                currentFormTypeFilter = 'all';
                myReviewFilterActive = false;
                document.querySelectorAll('[data-filter]').forEach(function(btn) {
                    btn.classList.toggle('active', btn.getAttribute('data-filter') === 'all');
                });
                var ft = document.getElementById('formTypeFilter');
                if (ft) ft.value = 'all';
                var mrBtn = document.getElementById('btnPendingMyReview');
                if (mrBtn) {
                    mrBtn.classList.remove('btn-primary');
                    mrBtn.classList.add('btn-outline-primary');
                }
                applyFilters();
            }

            function toggleMyReview() {
                myReviewFilterActive = !myReviewFilterActive;
                var btn = document.getElementById('btnPendingMyReview');
                if (myReviewFilterActive) {
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-primary');
                    // Reset status filter to 'all' so user sees all their pending items
                    currentStatusFilter = 'all';
                    document.querySelectorAll('[data-filter]').forEach(function(b) {
                        b.classList.toggle('active', b.getAttribute('data-filter') === 'all');
                    });
                } else {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                }
                applyFilters();
            }

            // Update active filter tags display
            function updateActiveFiltersDisplay() {
                var el = document.getElementById('activeFiltersDisplay');
                var elFt = document.getElementById('activeFormType');
                var elSt = document.getElementById('activeStatus');
                var elCl = document.getElementById('clearFilters');
                if (!el) return;

                var hasFilters = currentStatusFilter !== 'all' || currentFormTypeFilter !== 'all';
                el.style.display = hasFilters ? 'flex' : 'none';
                if (elCl) elCl.style.display = hasFilters ? 'inline' : 'none';

                if (elFt) {
                    if (currentFormTypeFilter !== 'all') {
                        elFt.textContent = formTypeNames[currentFormTypeFilter];
                        elFt.classList.remove('d-none');
                    } else {
                        elFt.classList.add('d-none');
                    }
                }
                if (elSt) {
                    if (currentStatusFilter !== 'all') {
                        elSt.textContent = statusNames[currentStatusFilter];
                        elSt.className = 'badge';
                        if (currentStatusFilter === 'pending') elSt.classList.add('bg-warning', 'text-dark');
                        else if (currentStatusFilter === 'approved') elSt.classList.add('bg-success', 'text-white');
                        else if (currentStatusFilter === 'rejected') elSt.classList.add('bg-danger', 'text-white');
                        elSt.classList.remove('d-none');
                    } else {
                        elSt.classList.add('d-none');
                    }
                }
            }

            // Wire up filters after DOM ready
            document.addEventListener('DOMContentLoaded', function() {
                var filterButtons = document.querySelectorAll('[data-filter]');
                var formTypeSelect = document.getElementById('formTypeFilter');
                var clearFiltersBtn = document.getElementById('clearFilters');

                filterButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        currentStatusFilter = this.getAttribute('data-filter');
                        filterButtons.forEach(function(b) {
                            b.classList.remove('active');
                        });
                        this.classList.add('active');
                        applyFilters();
                    });
                });

                if (formTypeSelect) {
                    formTypeSelect.addEventListener('change', function() {
                        currentFormTypeFilter = this.value;
                        applyFilters();
                    });
                }

                if (clearFiltersBtn) {
                    clearFiltersBtn.addEventListener('click', resetFilters);
                }

                // Initialize counts on load
                updateSummaryCards();
            });

            function showForm(type, id) {
                let routes = {
                    ict: '/show_form/',
                    clearance: '/clearance_forms/',
                    hr_form: '/hr_form/',
                    requisition: '/requisitions/'
                };

                if (routes[type]) {
                    window.location.href = routes[type] + id;
                }
            }
        </script>

        <style>
            /* Reveal the page only after this stylesheet is parsed — prevents FOUC */
            .page-wrapper {
                visibility: visible !important;
            }

            table.dataTable {
                border-collapse: collapse !important;
            }

            table.dataTable thead th {
                font-weight: 600;
                white-space: nowrap;
            }

            table.dataTable tbody td {
                vertical-align: middle;
            }

            .dt-buttons {
                margin-bottom: 0.5rem;
            }

            .dt-buttons .btn {
                border-radius: 4px !important;
            }

            .btn-group-sm .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
                border-radius: 0;
            }

            .btn-group-sm .btn:first-child {
                border-top-left-radius: 0.25rem;
                border-bottom-left-radius: 0.25rem;
            }

            .btn-group-sm .btn:last-child {
                border-top-right-radius: 0.25rem;
                border-bottom-right-radius: 0.25rem;
            }

            .btn-group-sm .btn.active {
                box-shadow: 0 0 0 0.15rem rgba(0, 123, 255, 0.25);
            }

            /* Rejected row styling */
            tr.table-danger,
            tr.bg-danger-subtle {
                background-color: rgba(220, 53, 69, 0.1) !important;
            }

            tr.table-danger td,
            tr.bg-danger-subtle td {
                border-left: 3px solid #dc3545;
            }

            tr.table-danger td:first-child,
            tr.bg-danger-subtle td:first-child {
                border-left: 3px solid #dc3545;
            }

            tr.table-danger:hover,
            tr.bg-danger-subtle:hover {
                background-color: rgba(220, 53, 69, 0.15) !important;
            }

            .badge.bg-danger {
                background-color: #dc3545 !important;
            }

            /* Status badge improvements */
            .badge {
                font-weight: 500;
                padding: 0.35em 0.65em;
            }

            /* Filter section styling */
            #formTypeFilter {
                border-color: #dee2e6;
            }

            #formTypeFilter:focus {
                border-color: #007A33;
                box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.25);
            }

            /* ── Status filter button group ── */
            .status-filter-group {
                display: flex;
                border: 1px solid #dee2e6;
                border-radius: 6px;
                overflow: hidden;
                width: 100%;
            }

            .status-btn {
                flex: 1;
                padding: 0.38rem 0.5rem;
                border: none;
                border-right: 1px solid #dee2e6;
                background: #fff;
                font-size: 0.8rem;
                font-weight: 500;
                cursor: pointer;
                transition: background 0.15s, color 0.15s;
                white-space: nowrap;
                text-align: center;
            }

            .status-btn:last-child {
                border-right: none;
            }

            /* default (inactive) colours */
            .status-btn-all {
                color: #6c757d;
            }

            .status-btn-pending {
                color: #fd7e14;
            }

            .status-btn-approved {
                color: #198754;
            }

            .status-btn-rejected {
                color: #dc3545;
            }

            /* active (selected) state */
            .status-btn-all.active {
                background: #198754;
                color: #fff;
            }

            .status-btn-pending.active {
                background: #fd7e14;
                color: #fff;
            }

            .status-btn-approved.active {
                background: #198754;
                color: #fff;
            }

            .status-btn-rejected.active {
                background: #dc3545;
                color: #fff;
            }

            .status-btn:hover:not(.active) {
                background: #f0f0f0;
            }

            #activeFiltersDisplay {
                min-height: 20px;
            }

            #activeFiltersDisplay .badge {
                font-size: 0.7rem;
            }

            .btn-xs {
                font-size: 0.72rem;
                line-height: 1.4;
            }

            /* ── Inline summary stat bar ── */
            .summary-stat {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 4px 12px;
                border-radius: 20px;
                cursor: pointer;
                transition: background 0.15s;
                user-select: none;
            }

            .summary-stat:hover {
                background: #f0f0f0;
            }

            .summary-stat-count {
                font-size: 1.1rem;
                font-weight: 700;
                line-height: 1;
            }

            .summary-stat-label {
                font-size: 0.75rem;
                color: #6c757d;
                white-space: nowrap;
            }

            .summary-stat-total .summary-stat-count {
                color: #0d6efd;
            }

            .summary-stat-pending .summary-stat-count {
                color: #fd7e14;
            }

            .summary-stat-approved .summary-stat-count {
                color: #198754;
            }

            .summary-stat-rejected .summary-stat-count {
                color: #dc3545;
            }

            /* ── Entity breakdown badges ── */
            .entity-badge {
                display: inline-flex;
                align-items: center;
                gap: 0;
                border: 1px solid #dee2e6;
                border-radius: 12px;
                overflow: hidden;
                font-size: 0.72rem;
            }

            .entity-badge-name {
                padding: 2px 8px;
                background: #f8f9fa;
                color: #495057;
                white-space: nowrap;
            }

            .entity-badge-count {
                padding: 2px 7px;
                background: #007A33;
                color: #fff;
                font-weight: 700;
                white-space: nowrap;
            }

            /* Compact filter card */
            .card.mb-2 .card-body {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
            }

            .no-sort {
                cursor: default !important;
            }

            .no-sort::after,
            .no-sort::before {
                display: none !important;
            }

            .btn-group-sm .btn i {
                font-size: 0.75rem;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .btn-group-sm.w-100 .btn {
                    font-size: 0.75rem;
                    padding: 0.2rem 0.4rem;
                }

                .btn-group-sm.w-100 .btn i {
                    display: none;
                }
            }

            /* Gradient cards for statistics */
            .bg-gradient-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }

            .bg-gradient-success {
                background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            }

            .bg-gradient-warning {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            }

            .bg-gradient-danger {
                background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            }

            .card.shadow-sm {
                transition: transform 0.2s;
            }

            .card.shadow-sm:hover {
                transform: translateY(-2px);
            }

            /* Chat Diagram Style for Approver Statistics */
            .chat-container {
                padding: 1.5rem;
                background: linear-gradient(to bottom, #f0f4f8 0%, #e8f0f5 100%);
                min-height: 400px;
            }

            .chat-message {
                display: flex;
                margin-bottom: 1.5rem;
                align-items: flex-start;
                animation: slideIn 0.3s ease-out;
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .chat-message-system {
                justify-content: center;
            }

            .chat-message-role {
                justify-content: flex-start;
            }

            .chat-avatar {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                margin-right: 12px;
                font-size: 1.2rem;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }

            .chat-avatar-system {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }

            .chat-avatar-role {
                background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
                color: white;
            }

            .chat-bubble {
                background: white;
                border-radius: 18px;
                padding: 1rem 1.25rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                max-width: 85%;
                position: relative;
                border: 1px solid rgba(0, 0, 0, 0.05);
            }

            .chat-bubble-system {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                max-width: 70%;
                border: none;
            }

            .chat-bubble-role {
                border-left: 4px solid #0d6efd;
            }

            .chat-message-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 0.5rem;
                font-size: 0.95rem;
            }

            .chat-time {
                font-size: 0.75rem;
                opacity: 0.7;
                margin-left: auto;
            }

            .chat-message-body {
                font-size: 0.9rem;
            }

            .summary-item {
                display: flex;
                align-items: center;
                margin-bottom: 0.5rem;
                gap: 0.5rem;
            }

            .summary-item:last-child {
                margin-bottom: 0;
            }

            .summary-item i {
                width: 20px;
                text-align: center;
            }

            .chat-bubble-system .summary-item {
                color: rgba(255, 255, 255, 0.95);
            }

            .approvers-list {
                margin-top: 0.5rem;
            }

            .approver-item {
                display: flex;
                align-items: center;
                padding: 0.75rem;
                margin-bottom: 0.5rem;
                background: #f8f9fa;
                border-radius: 10px;
                transition: all 0.2s ease;
                border: 1px solid transparent;
            }

            .approver-item:last-child {
                margin-bottom: 0;
            }

            .approver-item:hover {
                background: #e9ecef;
                border-color: #0d6efd;
                transform: translateX(5px);
                box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
            }

            .approver-rank {
                margin-right: 12px;
            }

            .rank-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                font-weight: bold;
                font-size: 0.85rem;
                color: white;
            }

            .rank-badge.rank-gold {
                background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
                box-shadow: 0 2px 8px rgba(253, 160, 133, 0.4);
            }

            .rank-badge.rank-silver {
                background: linear-gradient(135deg, #c0c0c0 0%, #808080 100%);
                box-shadow: 0 2px 8px rgba(128, 128, 128, 0.4);
            }

            .rank-badge.rank-bronze {
                background: linear-gradient(135deg, #cd7f32 0%, #8b4513 100%);
                box-shadow: 0 2px 8px rgba(205, 127, 50, 0.4);
            }

            .rank-badge.rank-default {
                background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            }

            .approver-info {
                flex: 1;
            }

            .approver-name {
                font-weight: 600;
                color: #212529;
                margin-bottom: 0.25rem;
                font-size: 0.9rem;
            }

            .approver-count {
                font-size: 0.8rem;
                color: #6c757d;
                display: flex;
                align-items: center;
            }

            .chat-bubble .badge {
                font-size: 0.75rem;
                padding: 0.35em 0.65em;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .chat-bubble {
                    max-width: 90%;
                }

                .chat-bubble-system {
                    max-width: 85%;
                }

                .chat-avatar {
                    width: 40px;
                    height: 40px;
                    font-size: 1rem;
                }
            }
        </style>

        {{-- ── Export Report Modal ──────────────────────────────────────── --}}
        <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true"
            data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="GET" action="{{ route('requestapprove.export') }}" id="exportForm"
                        target="exportFrame">
                        {{-- Header: plain grey --}}
                        <div class="modal-header bg-light border-bottom">
                            <h5 class="modal-title text-dark fw-semibold" id="exportModalLabel">
                                <i class="fas fa-file-excel me-2 text-success"></i>Export Requests Report
                            </h5>
                            <button type="button" class="btn-close" id="exportCloseBtn"
                                data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3" id="exportFields">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">
                                        <i class="fas fa-calendar-alt me-1 text-success"></i>Date From
                                    </label>
                                    <input type="date" name="date_from" id="exp_date_from"
                                        class="form-control form-control-sm">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">
                                        <i class="fas fa-calendar-alt me-1 text-success"></i>Date To
                                    </label>
                                    <input type="date" name="date_to" id="exp_date_to"
                                        class="form-control form-control-sm">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">
                                        <i class="fas fa-file-alt me-1 text-success"></i>Form Type
                                    </label>
                                    <select name="form_type" class="form-select form-select-sm">
                                        <option value="all">All Form Types</option>
                                        <option value="ict">ICT Access Form</option>
                                        <option value="hr">HR Form</option>
                                        <option value="bank">Bank Details Form</option>
                                        <option value="heslb">Loan Board Form (HESLB)</option>
                                        <option value="nhif">NHIF Registration</option>
                                        <option value="id">ID Card Request</option>
                                        <option value="change">Change Request</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">
                                        <i class="fas fa-building me-1 text-success"></i>Department
                                    </label>
                                    <select name="department_id" class="form-select form-select-sm">
                                        <option value="all">All Departments</option>
                                        @foreach ($departments ?? [] as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->dept_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Progress area (hidden until export starts) --}}
                            <div id="exportProgress" style="display:none;" class="mt-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                                    <span class="small fw-semibold text-muted" id="exportProgressLabel">Preparing your
                                        report…</span>
                                </div>
                                <div class="progress" style="height:6px; border-radius:3px;">
                                    <div id="exportProgressBar"
                                        class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                        role="progressbar" style="width:0%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" id="exportCancelBtn"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm" id="exportSubmitBtn">
                                <i class="fas fa-download me-1"></i> Download Excel
                            </button>
                        </div>
                    </form>
                    {{-- Hidden iframe receives the file download so the page doesn't navigate --}}
                    <iframe name="exportFrame" style="display:none;"></iframe>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var exportModal = document.getElementById('exportModal');
                if (!exportModal) return;

                var form = document.getElementById('exportForm');
                var submitBtn = document.getElementById('exportSubmitBtn');
                var cancelBtn = document.getElementById('exportCancelBtn');
                var closeBtn = document.getElementById('exportCloseBtn');
                var fields = document.getElementById('exportFields');
                var progress = document.getElementById('exportProgress');
                var progressBar = document.getElementById('exportProgressBar');
                var progressLbl = document.getElementById('exportProgressLabel');

                var exporting = false;
                var progressTimer = null;
                var pollTimer = null;
                var downloadToken = null;

                // Pre-fill dates (last 30 days) when modal opens
                exportModal.addEventListener('show.bs.modal', function() {
                    var df = document.getElementById('exp_date_from');
                    var dt = document.getElementById('exp_date_to');
                    if (!df.value) {
                        var d = new Date();
                        dt.value = d.toISOString().split('T')[0];
                        d.setDate(d.getDate() - 30);
                        df.value = d.toISOString().split('T')[0];
                    }
                });

                // Reset modal state when it closes
                exportModal.addEventListener('hidden.bs.modal', function() {
                    resetExportState();
                });

                // Prevent closing while export is in progress
                exportModal.addEventListener('hide.bs.modal', function(e) {
                    if (exporting) e.preventDefault();
                });

                function getCookie(name) {
                    var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return match ? decodeURIComponent(match[1]) : null;
                }

                function resetExportState() {
                    exporting = false;
                    clearInterval(progressTimer);
                    clearInterval(pollTimer);
                    fields.style.display = '';
                    progress.style.display = 'none';
                    progressBar.style.width = '0%';
                    progressLbl.textContent = 'Preparing your report…';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-download me-1"></i> Download Excel';
                    cancelBtn.disabled = false;
                    closeBtn.disabled = false;
                    // Remove the token input so it isn't submitted twice
                    var old = form.querySelector('input[name="download_token"]');
                    if (old) old.remove();
                }

                function onDownloadComplete() {
                    clearInterval(progressTimer);
                    clearInterval(pollTimer);
                    // Clear the cookie
                    document.cookie = 'download_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                    progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                    progressBar.style.width = '100%';
                    progressLbl.textContent = 'Download complete!';
                    // Auto-close after short pause
                    setTimeout(function() {
                        exporting = false; // allow close
                        var bsModal = bootstrap.Modal.getInstance(exportModal);
                        if (bsModal) bsModal.hide();
                    }, 1000);
                }

                function startProgress() {
                    exporting = true;
                    fields.style.display = 'none';
                    progress.style.display = 'block';
                    submitBtn.disabled = true;
                    cancelBtn.disabled = true;
                    closeBtn.disabled = true;

                    var pct = 5;
                    var steps = [
                        [20, 'Querying records…'],
                        [45, 'Processing data…'],
                        [70, 'Building spreadsheet…'],
                        [88, 'Finalising file…'],
                        [96, 'Almost done…'],
                    ];
                    var stepIdx = 0;

                    progressTimer = setInterval(function() {
                        if (stepIdx < steps.length) {
                            pct = steps[stepIdx][0];
                            progressLbl.textContent = steps[stepIdx][1];
                            stepIdx++;
                        } else {
                            pct = Math.min(pct + 1, 98);
                        }
                        progressBar.style.width = pct + '%';
                    }, 700);

                    // Poll for the server-set download_token cookie every 500 ms
                    pollTimer = setInterval(function() {
                        if (getCookie('download_token') === downloadToken) {
                            onDownloadComplete();
                        }
                    }, 500);
                }

                // On submit: inject token, start progress
                form.addEventListener('submit', function() {
                    downloadToken = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                    var old = form.querySelector('input[name="download_token"]');
                    if (old) old.remove();
                    var inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'download_token';
                    inp.value = downloadToken;
                    form.appendChild(inp);
                    startProgress();
                });
            });
        </script>
    @endsection
