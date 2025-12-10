@extends('layouts.template')

@push('styles')
    <style>
        :root {
            --brand: #198754
        }

        .page-tools .btn {
            border-radius: .5rem
        }

        .page-tools .btn-primary {
            background: var(--brand);
            border-color: var(--brand)
        }

        .page-tools .btn-primary:hover {
            filter: brightness(.95)
        }

        .status-pills .btn-filter {
            border: 1px solid var(--brand);
            color: var(--brand);
            background: #fff;
            padding: .25rem .5rem;
            font-size: .85rem;
            line-height: 1.1;
            border-radius: .4rem
        }

        .status-pills .btn-filter:hover,
        .status-pills .btn-filter.active {
            background: var(--brand);
            color: #fff
        }

        .status-pills .badge {
            font-size: .7rem;
            margin-left: .25rem
        }

        .table td,
        .table th {
            vertical-align: middle
        }

        .badge.status {
            font-size: .75rem
        }

        /* Loading button styles */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.6s linear infinite;
        }

        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }

        .btn-loading .btn-text {
            opacity: 0;
        }

        .btn-loading .btn-icon {
            opacity: 0;
        }

        /* Tab styles */
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            border-bottom-color: #dee2e6;
        }

        .nav-tabs .nav-link.active {
            color: var(--brand);
            border-bottom-color: var(--brand);
            font-weight: 600;
        }

        /* Table row hover effects */
        #pendingRequests tbody tr {
            transition: background-color 0.2s ease;
        }

        #pendingRequests tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Status badge improvements */
        .badge {
            padding: 0.4em 0.6em;
            font-weight: 500;
        }

        /* Card header styling */
        .card-header {
            border-bottom: 2px solid #dee2e6;
        }

        /* Action buttons group */
        .btn-group .btn {
            border-radius: 0.375rem;
        }

        /* Selected count styling */
        #selected-count {
            font-weight: 600;
            color: var(--brand);
        }

        /* Table cell alignment */
        #pendingRequests td {
            vertical-align: middle;
        }

        /* Status column styling */
        #pendingRequests td:nth-child(9) {
            min-width: 180px;
        }
    </style>
    {{-- DataTables CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
@endpush

@section('content')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container-fluid">
            {{-- Page Header --}}
            <div class="page-header">
                <div class="d-flex align-items-end justify-content-between gap-2 flex-wrap">
                    <div>
                        <h3 class="page-title mb-1">On-Call Claims</h3>
                    </div>

                    <div class="page-tools d-flex gap-2">
                        {{-- View Claims: visible only to HR role --}}
                        @can('view oncall reports')
                            <a href="{{ route('oncall_requests.approved') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-check me-1"></i> View Claims
                            </a>
                        @endcan

                        {{-- Action buttons: visible to requester/in-charge --}}
                        @can('create oncall requests')
                            <a href="{{ route('oncall_requests.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> New Claim
                            </a>

                        @endcan

                        {{-- Link to approver view for approvers, or my requests for regular users --}}
                        @can('approve oncall requests')
                            <a href="{{ route('oncall_requests.view') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-tasks me-1"></i> Review Requests
                            </a>
                        @elseif (auth()->user()->can('create oncall requests'))
                            <a href="{{ route('oncall_requests.view') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-list me-1"></i> My Requests
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Flash Messages --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- My Claims Section for Requesters --}}
                @can('create oncall requests')
                    <div class="card">
                        <div class="card-body">
                            {{-- Filter Pills --}}
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 status-pills" id="statusPills">
                                <button type="button" class="btn btn-filter active" data-filter="all">
                                    All <span class="badge bg-secondary">{{ $statusCounts['All'] ?? 0 }}</span>
                                </button>
                                <button type="button" class="btn btn-filter" data-filter="Pending">
                                    Pending <span class="badge bg-warning text-dark">{{ $statusCounts['Pending'] ?? 0 }}</span>
                                </button>
                                <button type="button" class="btn btn-filter" data-filter="Approved">
                                    Approved <span class="badge bg-success">{{ $statusCounts['Approved'] ?? 0 }}</span>
                                </button>
                                <button type="button" class="btn btn-filter" data-filter="Rejected">
                                    Rejected <span class="badge bg-danger">{{ $statusCounts['Rejected'] ?? 0 }}</span>
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table id="userRequests" class="table table-striped table-hover table-bordered align-middle"
                                    style="width:100%">
                                    <thead class="table-success">
                                        <tr>
                                            <th>Month</th>
                                            <th>Days</th>
                                            <th>Amount (TZS)</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>View</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($requests as $index => $request)
                                            @php
                                                $userModel = $request->user ?? null;

                                                $fullName = collect([
                                                    $userModel->fname ?? null,
                                                    $userModel->mname ?? null,
                                                    $userModel->lname ?? null,
                                                ])
                                                    ->filter()
                                                    ->implode(' ');

                                                $code = $userModel->ccbrt_code ?? '—';
                                                $deptName =
                                                    optional(optional($userModel)->department)->dept_name ??
                                                    ($userModel->dept_name ?? null);

                                                $period = trim(
                                                    collect([
                                                        $request->locum_month ?? null,
                                                        $request->locum_year ?? null,
                                                    ])
                                                        ->filter()
                                                        ->implode(' '),
                                                );
                                                if ($period === '') {
                                                    $period = '—';
                                                }

                                                $wf = $request->workflow ?? null;
                                                $histories = $wf ? $wf->histories : collect();
                                                $lastHistory = $histories->sortByDesc('updated_at')->first();

                                                $pendingLabel = function (?string $step) {
                                                    return match ($step) {
                                                        'Line Manager Approval' => 'Pending Line Manager Approval',
                                                        'HEC Approval' => 'Pending HEC Approval',
                                                        'HR Approval' => 'Pending HR Approval',
                                                        default => 'Pending',
                                                    };
                                                };

                                                // Use the same status logic as showOnCallRequest method (the modal) - this is the correct logic
                                                $label = 'Pending';
                                                $class = 'bg-warning';
                                                $tooltip = null;
                                                $filterStatus = 'Pending'; // For filtering: Pending, Approved, or Rejected

                                                // Get the last history entry (sorted by updated_at or created_at) - same as modal
                                                $last = $histories->sortByDesc(fn($h) => $h->updated_at ?? $h->created_at)->first();

                                                // First check: If workflow is completed, check work_flow_status to distinguish approved vs rejected
                                                if ($wf && (int) $wf->work_flow_completed === 1) {
                                                    // Check work_flow_status: 1 = approved, 2 = rejected
                                                    if ((int) $wf->work_flow_status === 2) {
                                                        // Rejected
                                                        $label = 'Rejected';
                                                        $class = 'bg-danger';
                                                        $filterStatus = 'Rejected';
                                                        if ($last && (string) $last->status === '2') {
                                                            $tooltip = $last->rejection_reason ?: null;
                                                        }
                                                    } else {
                                                        // Approved
                                                        $label = ($last && $last->step_name === 'HR Approval' && (string) $last->status === '1')
                                                            ? 'HR Approved' : 'Approved';
                                                        $class = 'bg-success';
                                                        $filterStatus = 'Approved';
                                                    }
                                                } elseif ($last) {
                                                    // Second check: Look at the last history entry
                                                    if ((string) $last->status === '2') {
                                                        // Last action was rejection
                                                        $label = 'Rejected';
                                                        $class = 'bg-danger';
                                                        $filterStatus = 'Rejected';
                                                        $tooltip = $last->rejection_reason ?: null;
                                                    } else {
                                                        // Find the first pending step
                                                        $nextPending = $histories->firstWhere('status', 0);
                                                        $label = $nextPending ? $pendingLabel($nextPending->step_name) : $pendingLabel($last->step_name);
                                                        $class = 'bg-warning';
                                                        $filterStatus = 'Pending';
                                                    }
                                                }
                                            @endphp

                                            <tr data-status="{{ $label }}" data-filter-status="{{ $filterStatus }}">
                                                <td>{{ $period }}</td>
                                                <td>{{ (int) ($request->number_of_days ?? 0) }}</td>
                                                <td>{{ number_format((float) ($request->total_amount_payable ?? 0), 2) }}
                                                </td>
                                                <td>
                                                    <span class="badge {{ $class }}"
                                                        @if ($tooltip) data-bs-toggle="tooltip" data-bs-title="{{ $tooltip }}" @endif>
                                                        {{ $label }}
                                                    </span>
                                                </td>
                                                <td>{{ optional(\Carbon\Carbon::parse($request->created_at))->format('d M Y H:i') }}
                                                </td>
                                                <td>
                                                    <button class="btn btn-outline-primary btn-sm view-details"
                                                        data-id="{{ $request->id }}"
                                                        data-url-template="{{ route('oncall_requests.status', ['id' => '__ID__']) }}"
                                                        data-monthlabel="{{ $period }}">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    @if ($label === 'Rejected' && $request->status === 'rejected')
                                                        <a href="{{ route('oncall_requests.edit', $request->id) }}"
                                                            class="btn btn-outline-warning btn-sm ms-1" title="Edit and resubmit">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- =======================
                     APPROVER ROLES
                     (hr only)
                 ======================== --}}
                    @can('approve oncall requests')
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-white">
                                        <h5 class="card-title mb-0 fw-semibold">
                                            <i class="fas fa-tasks me-2"></i>Pending Requests for Approval
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @if ($pendingFlat->isEmpty())
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle me-2"></i>No pending on-call requests for approval.
                                            </div>
                                        @else
                                            {{-- FORM #1: BULK APPROVE --}}
                                            <form action="{{ route('oncall_requests.bulk-approve') }}" method="POST"
                                                id="bulk-approve-form" novalidate>
                                                @csrf

                                                <table id="pendingRequests"
                                                    class="table table-striped table-hover table-bordered align-middle" style="width:100%">
                                                    <thead class="table-success">
                                                        <tr>
                                                            <th style="width:40px;">
                                                                <input type="checkbox" class="select-all" id="select-all-approver">
                                                            </th>
                                                            <th style="width:50px;">#</th>
                                                            <th>Staff</th>
                                                            <th>Month</th>
                                                            <th style="width:80px;">Days</th>
                                                            <th style="width:90px;">Hours</th>
                                                            <th>Education Level</th>
                                                            <th style="width:130px;">Amount (TZS)</th>
                                                            <th style="width:180px;">Status</th>
                                                            <th style="width:140px;">Submitted Date</th>
                                                            <th style="width:120px;">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($pendingFlat as $index => $req)
                                                            @php
                                                                $period = trim(
                                                                    collect([
                                                                        $req->locum_month ?? null,
                                                                        $req->locum_year ?? null,
                                                                    ])
                                                                        ->filter()
                                                                        ->implode(' '),
                                                                );
                                                                if ($period === '') {
                                                                    $period = '—';
                                                                }
                                                                $isOwnRequest = $req->user_id == auth()->id();

                                                                // Determine current approval stage
                                                                $wf = $req->workflow ?? null;
                                                                $histories = $wf ? $wf->histories : collect();
                                                                $currentStep = 'Pending';
                                                                $statusClass = 'bg-warning text-dark';
                                                                $statusIcon = 'fa-clock';
                                                                $pendingHistory = null;

                                                                if ($wf) {
                                                                    $pendingHistory = $histories->where('status', 0)->sortBy('id')->first();
                                                                    if ($pendingHistory) {
                                                                        $currentStep = $pendingHistory->step_name ?? 'Pending';
                                                                        // Determine status based on step
                                                                        if (str_contains($currentStep, 'Line Manager')) {
                                                                            $statusClass = 'bg-info text-white';
                                                                            $statusIcon = 'fa-user-tie';
                                                                        } elseif (str_contains($currentStep, 'HEC')) {
                                                                            $statusClass = 'bg-primary text-white';
                                                                            $statusIcon = 'fa-users';
                                                                        } elseif (str_contains($currentStep, 'HR')) {
                                                                            $statusClass = 'bg-success text-white';
                                                                            $statusIcon = 'fa-check-circle';
                                                                        }
                                                                    } else {
                                                                        // Check if approved or rejected
                                                                        if ((int) $wf->work_flow_completed === 1) {
                                                                            $currentStep = 'Approved';
                                                                            $statusClass = 'bg-success text-white';
                                                                            $statusIcon = 'fa-check-circle';
                                                                        } else {
                                                                            $rejected = $histories->where('status', 2)->sortByDesc('id')->first();
                                                                            if ($rejected) {
                                                                                $currentStep = 'Rejected';
                                                                                $statusClass = 'bg-danger text-white';
                                                                                $statusIcon = 'fa-times-circle';
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            @endphp
                                                            <tr class="align-middle">
                                                                <td class="text-center">
                                                                    <input type="checkbox" name="request_ids[]"
                                                                        value="{{ $req->workflow->id ?? '' }}">
                                                                </td>
                                                                <td class="text-center">{{ $index + 1 }}</td>

                                                                <td>
                                                                    <div class="fw-semibold">
                                                                        {{ $req->user->username ?? '—' }}
                                                                        @if ($isOwnRequest)
                                                                            <span class="badge bg-info text-white ms-1">My Request</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="small text-muted">
                                                                        <i class="fas fa-id-card me-1"></i>{{ $req->user->ccbrt_code ?? '—' }}
                                                                    </div>
                                                                    @if ($req->user->department)
                                                                        <div class="small text-muted">
                                                                            <i class="fas fa-building me-1"></i>{{ $req->user->department->dept_name ?? '—' }}
                                                                        </div>
                                                                    @endif
                                                                </td>

                                                                <td>
                                                                    <span class="fw-semibold">{{ $period }}</span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-secondary">{{ (int) ($req->number_of_days ?? 0) }}</span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="text-muted">{{ number_format((float) ($req->total_hours ?? 0), 2) }}</span>
                                                                </td>
                                                                <td>
                                                                    <small class="text-muted">{{ $req->education_level ?? '—' }}</small>
                                                                </td>
                                                                <td class="text-end">
                                                                    <span class="fw-semibold text-success">{{ number_format((float) ($req->total_amount_payable ?? 0), 2) }}</span>
                                                                </td>

                                                                <td>
                                                                    <span class="badge {{ $statusClass }} d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                                                        <i class="fas {{ $statusIcon }}"></i>
                                                                        <span>{{ $currentStep }}</span>
                                                                    </span>
                                                                    @if ($pendingHistory && $pendingHistory->attendedBy)
                                                                        <div class="small text-muted mt-1">
                                                                            <i class="fas fa-user-check me-1"></i>Awaiting: {{ $pendingHistory->attendedBy->username ?? '—' }}
                                                                        </div>
                                                                    @endif
                                                                </td>

                                                                <td>
                                                                    <div class="small">
                                                                        <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                                                        {{ \Carbon\Carbon::parse($req->created_at)->format('d M Y') }}
                                                                    </div>
                                                                    <div class="small text-muted">
                                                                        {{ \Carbon\Carbon::parse($req->created_at)->format('H:i') }}
                                                                    </div>
                                                                </td>

                                                                <td>
                                                                    <div class="btn-group" role="group">
                                                                        <a href="{{ route('oncall_requests.show', $req->id) }}"
                                                                            class="btn btn-success btn-sm" title="View Details">
                                                                            <i class="fas fa-eye"></i> View
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>

                                                <div class="mt-3 p-3 bg-light rounded border">
                                                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                                                        <div class="d-flex gap-2 align-items-center">
                                                            <span class="text-muted small">
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                <span id="selected-count">0</span> request(s) selected
                                                            </span>
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <button type="submit" class="btn btn-success btn-sm js-submit-btn" name="action"
                                                                value="approve" data-loading-label="Approving...">
                                                                <span class="btn-text">
                                                                    <i class="fas fa-check-circle me-1"></i> Approve Selected
                                                                </span>
                                                                <span class="spinner-border spinner-border-sm ms-2 d-none js-spinner"
                                                                    role="status" aria-hidden="true"></span>
                                                            </button>

                                                            <button type="button" class="btn btn-danger btn-sm" id="reject-btn-approver"
                                                                data-bs-toggle="modal" data-bs-target="#bulkRejectModal">
                                                                <i class="fas fa-times-circle me-1"></i> Reject Selected
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                            {{-- END FORM #1 --}}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- FORM #2: BULK REJECT (separate modal) --}}
                        <div class="modal fade" id="bulkRejectModal" tabindex="-1" aria-labelledby="bulkRejectModalLabel"
                            aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="{{ route('oncall_requests.bulk-reject') }}" method="POST"
                                    class="modal-content bulk-reject-form" novalidate>
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="bulkRejectModalLabel">Reject Selected Requests</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Reason for Rejection</label>
                                            <textarea class="form-control" name="rejection_reason" rows="4" required maxlength="255"></textarea>
                                        </div>
                                        {{-- request_ids[] hidden inputs will be injected by JS on modal open --}}
                                        <div class="js-selected-ids"></div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Reject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endcan

                </div>
            </div>
            </div>

            {{-- Details Modal (Workflow History) --}}
            <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                On-Call Request Details
                                <small class="text-muted ms-2" id="detailsMonth">—</small>
                            </h5>
                            <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            {{-- Summary strip --}}
                            <div class="p-2 border rounded mb-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span id="wf-badge" class="badge bg-secondary">Loading…</span>
                                    <small id="wf-tooltip" class="text-muted"></small>
                                </div>
                                <div class="small">
                                    <span class="text-muted">Pending with:</span> <span id="wf-pending-with">—</span>
                                    <span class="text-muted ms-3">Step:</span> <span id="wf-pending-step">—</span>
                                </div>
                            </div>

                            {{-- Workflow Timeline --}}
                            <h6 class="section-title mb-2">Workflow Timeline</h6>
                            <div class="card shadow-sm mb-4">
                                <div class="card-body p-0">
                                    <div class="table-responsive modal-scroll">
                                        <table class="table table-sm table-hover table-nowrap align-middle mb-0"
                                            id="wf-steps-table">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width:32%">Step</th>
                                                    <th style="width:20%">Attended By</th>
                                                    <th style="width:20%">Forwarded By</th>
                                                    <th style="width:12%">Status</th>
                                                    <th style="width:16%">Acted At</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Button loading animation function (like locum)
                window.setButtonLoading = function(button, isLoading) {
                    if (!button) return;
                    const btnText = button.querySelector('.btn-text');
                    const spinner = button.querySelector('.js-spinner');
                    const loadingLabel = button.getAttribute('data-loading-label') || 'Processing...';

                    if (isLoading) {
                        button.disabled = true;
                        button.classList.add('btn-loading');
                        if (btnText) {
                            if (!button.hasAttribute('data-original-html')) {
                                button.setAttribute('data-original-html', btnText.innerHTML);
                            }
                            btnText.innerHTML = loadingLabel;
                        }
                        if (spinner) {
                            spinner.classList.remove('d-none');
                        }
                    } else {
                        button.disabled = false;
                        button.classList.remove('btn-loading');
                        if (btnText) {
                            const originalHtml = button.getAttribute('data-original-html');
                            if (originalHtml) {
                                btnText.innerHTML = originalHtml;
                                button.removeAttribute('data-original-html');
                            }
                        }
                        if (spinner) {
                            spinner.classList.add('d-none');
                        }
                    }
                };

                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize DataTable for My Claims
                    if ($('#userRequests').length) {
                        var table = $('#userRequests').DataTable({
                            order: [
                                [4, 'desc']
                            ],
                            pageLength: 10,
                            pagingType: 'simple_numbers',
                            language: {
                                search: "Search:",
                                lengthMenu: "Show _MENU_ entries",
                                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                                infoEmpty: "No entries to show",
                                infoFiltered: "(filtered from _MAX_ total entries)",
                                zeroRecords: "No matching records found for the selected filter",
                                paginate: {
                                    previous: "Previous",
                                    next: "Next"
                                }
                            },
                            columnDefs: [{
                                    orderable: true,
                                    targets: [0, 1, 2, 3, 4]
                                },
                                {
                                    orderable: false,
                                    targets: [5]
                                }
                            ]
                        });
                    }

                    // Status filter functionality
                    $('#statusPills button').on('click', function() {
                        var filter = $(this).data('filter');
                        var pills = $('#statusPills button');

                        pills.removeClass('active');
                        $(this).addClass('active');

                        if (filter === 'all') {
                            // Clear all custom filters
                            $.fn.dataTable.ext.search = [];
                            table.draw();
                        } else {
                            // Clear existing filters
                            $.fn.dataTable.ext.search = [];

                            // Add new filter based on status
                            $.fn.dataTable.ext.search.push(
                                function(settings, data, dataIndex) {
                                    if (settings.nTable.id !== 'userRequests') {
                                        return true; // Don't filter other tables
                                    }

                                    var row = table.row(dataIndex).node();
                                    // Use the normalized filter status attribute
                                    var filterStatus = $(row).attr('data-filter-status') || '';

                                    // Compare with the selected filter
                                    return filterStatus === filter;
                                }
                            );
                            table.draw();
                        }
                    });

                    // Initialize tooltips
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });

                    // Initialize DataTable for approver table (if it exists)
                    if ($('#pendingRequests').length) {
                        var approverTable = $('#pendingRequests').DataTable({
                            order: [
                                [9, 'desc']
                            ], // Sort by Submitted Date (descending)
                            pageLength: 25,
                            pagingType: 'full_numbers',
                            language: {
                                search: "Search:",
                                lengthMenu: "Show _MENU_ entries",
                                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                                infoEmpty: "No entries to show",
                                infoFiltered: "(filtered from _MAX_ total entries)",
                                paginate: {
                                    first: "First",
                                    previous: "Previous",
                                    next: "Next",
                                    last: "Last"
                                },
                                zeroRecords: "No matching records found"
                            },
                            columnDefs: [{
                                    orderable: false,
                                    targets: [0, 10]
                                }, // Checkbox and Actions columns not sortable
                                {
                                    orderable: true,
                                    targets: '_all'
                                },
                                {
                                    className: 'text-center',
                                    targets: [0, 1, 4, 5]
                                },
                                {
                                    className: 'text-end',
                                    targets: [7]
                                }
                            ],
                            responsive: true,
                            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                            drawCallback: function() {
                                // Update selected count after table redraw
                                updateSelectedCount();
                            }
                        });
                    }

                    // Function to update selected count
                    function updateSelectedCount() {
                        var checked = $('#pendingRequests tbody input[type="checkbox"][name="request_ids[]"]:checked').length;
                        $('#selected-count').text(checked);
                    }

                    // Select all checkbox for approver table
                    $('#select-all-approver').on('change', function() {
                        var isChecked = $(this).prop('checked');
                        $('#pendingRequests tbody input[type="checkbox"][name="request_ids[]"]').prop('checked',
                            isChecked);
                        updateSelectedCount();
                    });

                    // Update select-all checkbox when individual checkboxes change
                    $('#pendingRequests tbody').on('change', 'input[type="checkbox"][name="request_ids[]"]', function() {
                        var total = $('#pendingRequests tbody input[type="checkbox"][name="request_ids[]"]').length;
                        var checked = $(
                                '#pendingRequests tbody input[type="checkbox"][name="request_ids[]"]:checked')
                            .length;
                        $('#select-all-approver').prop('checked', total === checked);
                        updateSelectedCount();
                    });

                    // Initialize selected count
                    updateSelectedCount();

                    // View details modal handler
                    $(document).on('click', '.view-details', function() {
                        var id = $(this).attr('data-id');
                        var tpl = $(this).attr('data-url-template');
                        var url = (tpl || '').replace('__ID__', id);
                        var monthLabel = $(this).attr('data-monthlabel') || '—';

                        $('#detailsMonth').text(monthLabel);
                        $('#wf-badge').removeClass('bg-success bg-warning bg-danger bg-secondary')
                            .addClass('bg-secondary').text('Loading…');
                        $('#wf-tooltip').text('');
                        $('#wf-pending-with').text('—');
                        $('#wf-pending-step').text('—');
                        $('#wf-steps-table tbody').empty();

                        var modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                        modal.show();

                        $.ajax({
                            url: url,
                            method: 'GET',
                            success: function(data) {
                                var wf = (data && data.workflow) ? data.workflow : {};
                                var kind = (wf.current_class ? wf.current_class : 'secondary').replace(
                                    'bg-', '');
                                var map = {
                                    success: 'bg-success',
                                    warning: 'bg-warning',
                                    danger: 'bg-danger',
                                    secondary: 'bg-secondary'
                                };
                                var klass = map[kind] ? map[kind] : 'bg-secondary';
                                $('#wf-badge').removeClass(
                                        'bg-success bg-warning bg-danger bg-secondary')
                                    .addClass(klass).text(wf.current_label ? wf.current_label : '—');
                                $('#wf-tooltip').text(wf.tooltip ? wf.tooltip : '');
                                $('#wf-pending-with').text(wf.pending_with ? wf.pending_with : (wf
                                    .completed ? '—' : 'Unknown'));
                                $('#wf-pending-step').text(wf.pending_step ? wf.pending_step : (wf
                                    .completed ? '—' : 'Pending'));

                                var $tb = $('#wf-steps-table tbody').empty();
                                var steps = (wf.steps && Array.isArray(wf.steps)) ? wf.steps : [];
                                steps.forEach(function(step) {
                                    var sClass = (step.status === 'Approved') ? 'bg-success' :
                                        (step.status === 'Rejected') ? 'bg-danger' :
                                        'bg-warning';
                                    var extra = '';
                                    if (step.rejection) {
                                        extra = '<div class="small text-danger mt-1">' + step
                                            .rejection + '</div>';
                                    } else if (step.status === 'Pending' && step.remark) {
                                        extra = '<div class="small text-muted mt-1">' + step
                                            .remark + '</div>';
                                    }
                                    $tb.append(
                                        '<tr>' +
                                        '<td><div class="fw-semibold">' + (step.step ? step
                                            .step : '—') + '</div>' + extra + '</td>' +
                                        '<td>' + (step.attended_by ? step.attended_by :
                                            '—') + '</td>' +
                                        '<td>' + (step.forwarded_by ? step.forwarded_by :
                                            '—') + '</td>' +
                                        '<td><span class="badge ' + sClass + '">' + step
                                        .status + '</span></td>' +
                                        '<td>' + (step.acted_at ? step.acted_at : '—') +
                                        '</td>' +
                                        '</tr>'
                                    );
                                });
                            },
                            error: function() {
                                alert('Failed to load workflow details.');
                            }
                        });
                    });

                    // Store original button text
                    $('.js-submit-btn').each(function() {
                        var $btn = $(this);
                        var originalText = $btn.find('.btn-text').text();
                        $btn.data('original-text', originalText);
                    });

                    // Bulk approve form submission
                    $('#bulk-approve-form').on('submit', function(e) {
                        var checked = $(
                                '#pendingRequests tbody input[type="checkbox"][name="request_ids[]"]:checked')
                            .length;
                        if (checked === 0) {
                            e.preventDefault();
                            alert('Please select at least one request to approve.');
                            return false;
                        }

                        // Confirm before submitting
                        if (!confirm('Are you sure you want to approve ' + checked + ' selected request(s)?')) {
                            e.preventDefault();
                            return false;
                        }

                        var submitBtn = this.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            window.setButtonLoading(submitBtn, true);
                        }
                    });

                    // Bulk reject modal functionality (for approvers)
                    const bulkRejectModal = document.getElementById('bulkRejectModal');
                    if (bulkRejectModal) {
                        function getSelectedWorkflowIds() {
                            const checkboxes = document.querySelectorAll(
                                '#pendingRequests tbody input[name="request_ids[]"]:checked');
                            return Array.from(checkboxes).map(cb => cb.value);
                        }

                        bulkRejectModal.addEventListener('show.bs.modal', function(ev) {
                            const selected = getSelectedWorkflowIds();
                            if (!selected.length) {
                                ev.preventDefault();
                                alert('Please select at least one request to reject.');
                                return;
                            }
                            const container = bulkRejectModal.querySelector('.js-selected-ids');
                            if (!container) return;
                            container.innerHTML = '';
                            selected.forEach(id => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'request_ids[]';
                                input.value = id;
                                container.appendChild(input);
                            });
                        });

                        const rejectForm = bulkRejectModal.querySelector('form.bulk-reject-form');
                        if (rejectForm) {
                            rejectForm.addEventListener('submit', function(e) {
                                const alreadyInjected = rejectForm.querySelectorAll('input[name="request_ids[]"]')
                                    .length;
                                if (!alreadyInjected) {
                                    const selected = getSelectedWorkflowIds();
                                    if (!selected.length) {
                                        e.preventDefault();
                                        alert('Please select at least one request to reject.');
                                        return;
                                    }
                                    const container = rejectForm.querySelector('.js-selected-ids');
                                    if (container) {
                                        container.innerHTML = '';
                                        selected.forEach(id => {
                                            const input = document.createElement('input');
                                            input.type = 'hidden';
                                            input.name = 'request_ids[]';
                                            input.value = id;
                                            container.appendChild(input);
                                        });
                                    }
                                }

                                // Add loading state to reject button
                                var rejectBtn = rejectForm.querySelector('button[type="submit"]');
                                if (rejectBtn) {
                                    rejectBtn.disabled = true;
                                    rejectBtn.innerHTML =
                                        '<span class="spinner-border spinner-border-sm me-2"></span>Rejecting...';
                                }
                            });
                        }

                        bulkRejectModal.addEventListener('hidden.bs.modal', function() {
                            const container = bulkRejectModal.querySelector('.js-selected-ids');
                            if (container) container.innerHTML = '';
                            const textarea = bulkRejectModal.querySelector('textarea[name="rejection_reason"]');
                            if (textarea) textarea.value = '';
                        });
                    }

                    document.querySelectorAll('.select-all').forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            const table = this.closest('table');
                            table.querySelectorAll('tbody input[type="checkbox"][name="request_ids[]"]')
                                .forEach(cb => {
                                    if (cb.closest('tr').style.display !== 'none') cb.checked = this
                                        .checked;
                                });
                        });
                    });
                });
            </script>
        @endsection
