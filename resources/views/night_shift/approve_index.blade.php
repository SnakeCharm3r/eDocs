@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<style>
    .badge.status { font-size: .75rem; }
    .emp-mini { font-size: 0.75rem; color: #6c757d; margin-top: 2px; }
    .bt-match  { color: #198754; font-weight: 600; }
    .bt-miss   { color: #dc3545; font-weight: 600; }
    .bt-na     { color: #6c757d; }
    #bulk-bar  { transition: opacity .2s; }

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
    .summary-stat:hover { background: #f0f0f0; }
    .summary-stat-count { font-size: 1.1rem; font-weight: 700; line-height: 1; }
    .summary-stat-label { font-size: 0.75rem; color: #6c757d; white-space: nowrap; }
    .summary-stat-total    .summary-stat-count { color: #0d6efd; }
    .summary-stat-pending  .summary-stat-count { color: #fd7e14; }
    .summary-stat-amount   .summary-stat-count { color: #198754; }
    .summary-stat-dept     .summary-stat-count { color: #6f42c1; }

    /* ── Step badges in table ── */
    .step-badge { font-size: 0.72rem; padding: 4px 10px; border-radius: 20px; display: inline-block; }

    /* ── Cards ── */
    .card { border-radius: 10px; border: 1px solid #e9ecef; }
    .card-header { font-weight: 600; border-bottom: 1px solid #e9ecef; background-color: #f8f9fa !important; }
    .card.shadow-sm { box-shadow: 0 1px 4px rgba(0,0,0,.07) !important; }

    /* ── Table ── */
    table.dataTable tbody tr:hover { background-color: #f0f7f8 !important; }

    /* ── Action buttons ── */
    .action-btn { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }

    /* ── Page header ── */
    .page-title { font-size: 1.2rem; font-weight: 700; color: #343a40; }
    .page-header { padding-bottom: 0.5rem; border-bottom: 1px solid #f0f0f0; margin-bottom: 1.25rem !important; }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="page-wrapper">
<div class="content container-fluid">

    @php
        $ratePerDay   = (float) \App\Http\Controllers\SettingsController::getSetting('night_allowance_amount_per_day', 0);
        $totalClaims  = $pendingClaims->count();
        $totalDays    = $pendingClaims->sum('total_days_worked');
        $totalAmount  = $totalDays * $ratePerDay;
        $departments  = $pendingClaims->pluck('department.dept_name')->filter()->unique()->count();
    @endphp

    {{-- Header --}}
    <div class="page-header mb-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="page-title mb-0">
                <i class="fas fa-moon text-info me-2"></i>Night Allowances Approvals
            </h3>
            <div class="d-flex gap-2">
                <a href="{{ route('night-shift.report') }}" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-chart-bar me-1"></i> Report
                </a>
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Summary stat bar (inline like clearance) --}}
    @if($totalClaims > 0)
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-1">
                    <i class="fas fa-chart-bar text-muted me-1" style="font-size:0.85rem;"></i>
                    <small class="text-muted fw-semibold">Summary</small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <div class="summary-stat summary-stat-total">
                        <span class="summary-stat-count">{{ $totalClaims }}</span>
                        <span class="summary-stat-label"><i class="fas fa-file-alt me-1"></i>Claims</span>
                    </div>
                    <div class="summary-stat summary-stat-pending">
                        <span class="summary-stat-count">{{ $totalDays }}</span>
                        <span class="summary-stat-label"><i class="fas fa-calendar-day me-1"></i>Days</span>
                    </div>
                    <div class="summary-stat summary-stat-amount">
                        <span class="summary-stat-count">{{ number_format($totalAmount, 0) }}</span>
                        <span class="summary-stat-label"><i class="fas fa-money-bill-wave me-1"></i>TZS</span>
                    </div>
                    <div class="summary-stat summary-stat-dept">
                        <span class="summary-stat-count">{{ $departments }}</span>
                        <span class="summary-stat-label"><i class="fas fa-building me-1"></i>Depts</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Bulk action bar --}}
    <div id="bulk-bar" class="alert alert-light border d-flex align-items-center gap-3 flex-wrap mb-3" style="display:none!important;">
        <span class="fw-semibold small"><i class="fas fa-check-square text-primary me-1"></i><span id="bulk-count">0</span> selected</span>
        <button type="button" class="btn btn-success btn-sm" onclick="submitBulkAction('approve')">
            <i class="fas fa-check me-1"></i> Approve Selected
        </button>
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#bulkRejectModal">
            <i class="fas fa-times me-1"></i> Reject Selected
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">Clear</button>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0"><i class="fas fa-table me-2 text-muted"></i>Pending Approvals</h6>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <select id="filterYear" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All Years</option>
                    @foreach($pendingClaims->pluck('year')->unique()->sortDesc() as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
                <select id="filterMonth" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All Months</option>
                    @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $mn)
                        <option value="{{ $mn }}">{{ $mn }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body p-3">
            @if($pendingClaims->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <p class="text-muted mb-0 fw-semibold">No night allowances claims pending your approval</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle" id="claims-table" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="no-sort" style="width:2rem">
                                <input type="checkbox" id="select-all" class="form-check-input" title="Select all">
                            </th>
                            <th class="no-sort" style="width:2rem">#</th>
                            <th>Submitted By</th>
                            <th>Department</th>
                            <th>Month / Year</th>
                            <th class="text-center">Days</th>
                            <th class="text-end">Amount (TZS)</th>
                            <th>Current Step</th>
                            <th>Submitted</th>
                            <th class="text-center no-sort" style="width:130px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingClaims as $i => $claim)
                        @php
                            $amount      = $claim->total_days_worked * $ratePerDay;
                            $pendingStep = $claim->workflow?->histories->firstWhere('status', 0);
                            $stepName    = $pendingStep?->step_name ?? 'Pending';
                            $stepColors  = [
                                'Incharge Approval'          => ['bg' => '#fff3cd', 'text' => '#856404', 'icon' => 'user-shield'],
                                'Platform Manager Approval'  => ['bg' => '#cfe2ff', 'text' => '#084298', 'icon' => 'sitemap'],
                                'Line Manager Approval'      => ['bg' => '#d1e7dd', 'text' => '#0f5132', 'icon' => 'user-tie'],
                                'HEC Approval'               => ['bg' => '#e2d9f3', 'text' => '#4a1d91', 'icon' => 'landmark'],
                                'HR Approval'                => ['bg' => '#f8d7da', 'text' => '#842029', 'icon' => 'users'],
                            ];
                            $sc = $stepColors[$stepName] ?? ['bg' => '#e2e3e5', 'text' => '#41464b', 'icon' => 'clock'];
                        @endphp
                        <tr data-year="{{ $claim->year }}" data-month="{{ $claim->month }}">
                            <td>
                                <input type="checkbox" class="row-check form-check-input" value="{{ $claim->id }}">
                            </td>
                            <td class="text-muted small">{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ trim($claim->submitter->fname.' '.($claim->submitter->mname??'').' '.$claim->submitter->lname) }}</strong>
                                @if($claim->submitter->ccbrt_code)
                                <br><small class="text-muted">{{ $claim->submitter->ccbrt_code }}</small>
                                @endif
                            </td>
                            <td>{{ $claim->department?->dept_name ?? '—' }}</td>
                            <td><strong>{{ $claim->month }} {{ $claim->year }}</strong></td>
                            <td class="text-center">{{ $claim->total_days_worked }}</td>
                            <td class="text-end fw-semibold">{{ number_format($amount, 2) }}</td>
                            <td>
                                <span class="step-badge" style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }};">
                                    <i class="fas fa-{{ $sc['icon'] }} me-1" style="font-size: 0.65rem;"></i>{{ $stepName }}
                                </span>
                            </td>
                            <td class="small text-muted" data-order="{{ $claim->created_at->format('Y-m-d') }}">
                                {{ $claim->created_at->format('d M Y') }}
                                <br><small>{{ $claim->created_at->diffForHumans() }}</small>
                            </td>
                            <td class="text-center" style="white-space:nowrap;">
                                <button type="button"
                                        class="btn btn-sm action-btn btn-outline-primary view-details"
                                        title="View Details + BioTime"
                                        data-id="{{ $claim->id }}"
                                        data-url="{{ route('night-shift.details', $claim) }}"
                                        data-biotime-url="{{ route('night-shift.biotime', $claim) }}"
                                        data-month="{{ $claim->month }} / {{ $claim->year }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-sm action-btn btn-success"
                                        title="Approve"
                                        onclick="openApprove({{ $claim->id }}, '{{ $claim->month }} {{ $claim->year }}')">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-sm action-btn btn-outline-danger"
                                        title="Reject"
                                        onclick="openReject({{ $claim->id }})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
</div>

{{-- ════════════════  DETAIL MODAL  ════════════════ --}}
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Night Allowances Details
                    <small class="text-muted ms-2" id="modal-month">—</small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- Meta --}}
                <div class="d-flex flex-wrap gap-3 mb-2 small">
                    <span class="text-muted">Submitted at:</span> <span id="m-submitted-at">—</span>
                    <span class="text-muted">Approved at:</span>  <span id="m-approved-at">—</span>
                </div>
                <div class="p-2 border rounded mb-4">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span id="m-status-badge" class="badge bg-secondary">—</span>
                    </div>
                    <div class="small">
                        <span class="text-muted">Pending with:</span> <span id="m-pending-with">—</span>
                        <span class="text-muted ms-3">Step:</span> <span id="m-pending-step">—</span>
                    </div>
                </div>

                {{-- Workflow Timeline --}}
                <h6 class="fw-bold mb-2">Workflow Timeline</h6>
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:28%">Step</th>
                                        <th style="width:20%">Attended By</th>
                                        <th style="width:20%">Forwarded By</th>
                                        <th style="width:12%">Status</th>
                                        <th style="width:20%">Acted At</th>
                                    </tr>
                                </thead>
                                <tbody id="m-steps-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Claimed Days vs BioTime Comparison --}}
                <h6 class="fw-bold mb-1">Claimed Days vs BioTime</h6>

                {{-- BioTime summary bar --}}
                <div class="alert alert-secondary py-2 small mb-2" id="bt-summary" style="display:none;">
                    <span class="me-3">BioTime worked days: <strong id="bt-days">—</strong></span>
                    <span class="me-3">BioTime total hours: <strong id="bt-hours">—</strong></span>
                    <span>Claimed nights: <strong id="bt-claimed">—</strong></span>
                </div>
                <div id="bt-loading" class="text-muted small mb-2" style="display:none;">
                    <i class="fas fa-spinner fa-spin me-1"></i> Loading BioTime data…
                </div>
                <div id="bt-error" class="alert alert-warning small py-2 mb-2" style="display:none;">
                    BioTime data unavailable (no connection or employee code not found).
                </div>

                <div class="card shadow-sm mb-2">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Platform</th>
                                        <th>Unit</th>
                                        <th class="text-center">Claimed Hrs</th>
                                        <th class="text-center">BioTime In</th>
                                        <th class="text-center">BioTime Out</th>
                                        <th class="text-center">BioTime Hrs</th>
                                        <th class="text-end">Amount (TZS)</th>
                                    </tr>
                                </thead>
                                <tbody id="m-days-body"></tbody>
                                <tfoot id="m-days-foot" style="background:#f8f9fa; font-weight:600; font-size:0.82rem;"></tfoot>
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

{{-- ════════════════  APPROVE MODAL  ════════════════ --}}
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-check-circle text-success me-2"></i> Confirm Approval
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Are you sure you want to approve the claim for:</p>
                <p class="fw-bold mb-0" id="approve-claim-label">—</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="approveForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-check me-1"></i> Yes, Approve
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════  REJECT MODAL  ════════════════ --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-times-circle text-danger me-2"></i> Reject Claim
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <label class="form-label fw-semibold small">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control form-control-sm" rows="3"
                              placeholder="Please state the reason for rejection…" required minlength="5"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-times me-1"></i> Confirm Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ════════════════  BULK REJECT MODAL  ════════════════ --}}
<div class="modal fade" id="bulkRejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-times-circle text-danger me-2"></i> Bulk Reject
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkRejectForm" method="POST" action="{{ route('night-shift.bulk-reject') }}">
                @csrf
                <div id="bulk-reject-ids"></div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">Rejection reason will apply to all <strong id="bulk-reject-count">0</strong> selected claim(s).</p>
                    <label class="form-label fw-semibold small">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control form-control-sm" rows="3"
                              placeholder="Please state the reason for rejection…" required minlength="5"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-times me-1"></i> Reject All Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Hidden bulk-approve form --}}
<form id="bulkApproveForm" method="POST" action="{{ route('night-shift.bulk-approve') }}" style="display:none;">
    @csrf
    <div id="bulk-approve-ids"></div>
</form>

@endsection

@push('scripts')
<script>
$(function () {
    // Register custom filter BEFORE DataTable init so first draw applies it
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'claims-table') return true;
        var row = settings.aoData[dataIndex].nTr;
        if (!row) return true;
        var yr = document.getElementById('filterYear').value;
        var mo = document.getElementById('filterMonth').value;
        var matchYr = !yr || String(row.dataset.year) === yr;
        var matchMo = !mo || row.dataset.month === mo;
        return matchYr && matchMo;
    });

    var dt = $('#claims-table').DataTable({
        order: [[8, 'desc']],
        columnDefs: [
            { orderable: false, targets: [0, 1, 9] }
        ],
        pageLength: 25,
        language: {
            search: '',
            searchPlaceholder: 'Search…',
            info: 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty: 'No entries',
            infoFiltered: '(filtered from _MAX_)',
            zeroRecords: 'No matching records found',
            lengthMenu: 'Show _MENU_',
            paginate: { first: '«', last: '»', next: '›', previous: '‹' }
        }
    });

    // Wire up filter dropdowns
    $('#filterYear, #filterMonth').on('change', function() { dt.draw(); });

    /* ─── Checkbox selection ─── */
    $('#select-all').on('change', function () {
        $('.row-check').prop('checked', this.checked);
        updateBulkBar();
    });
    $(document).on('change', '.row-check', updateBulkBar);

    function updateBulkBar() {
        var n = $('.row-check:checked').length;
        $('#bulk-count').text(n);
        if (n > 0) {
            $('#bulk-bar').css('display', 'flex');
        } else {
            $('#bulk-bar').css('display', 'none');
        }
        $('#bulk-reject-count').text(n);
    }

    /* ─── Detail modal with BioTime ─── */
    $(document).on('click', '.view-details', function () {
        var url        = $(this).data('url');
        var btUrl      = $(this).data('biotime-url');
        var month      = $(this).data('month');

        $('#modal-month').text(month);
        $('#m-steps-body').html('<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-1"></i> Loading…</td></tr>');
        $('#m-days-body,#m-days-foot').html('');
        $('#m-status-badge').removeClass().addClass('badge bg-secondary').text('Loading…');
        $('#m-pending-with,#m-pending-step,#m-submitted-at,#m-approved-at').text('—');
        $('#bt-summary,#bt-error').hide();
        $('#bt-loading').show();

        new bootstrap.Modal(document.getElementById('detailModal')).show();

        // Load claim details + BioTime in parallel
        var claimData  = null;
        var btData     = null;
        var detailDone = false;
        var btDone     = false;

        function tryRender() {
            if (!detailDone || !btDone) return;
            renderModal(claimData, btData);
        }

        $.getJSON(url, function (d) {
            claimData  = d;
            detailDone = true;
            tryRender();
        }).fail(function () {
            $('#m-steps-body').html('<tr><td colspan="5" class="text-danger text-center py-2">Failed to load claim details.</td></tr>');
            detailDone = true;
            tryRender();
        });

        $.getJSON(btUrl, function (bt) {
            btData = bt.worked_days || {};
            btDone = true;
            tryRender();
        }).fail(function () {
            btData = null;
            btDone = true;
            $('#bt-loading').hide();
            $('#bt-error').show();
            tryRender();
        });
    });

    function renderModal(d, btMap) {
        if (!d) return;

        // Meta
        $('#m-submitted-at').text(d.submitted_at || '—');
        $('#m-approved-at').text(d.approved_at   || '—');
        var bc = d.status === 'approved' ? 'bg-success' : d.status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark';
        var bl = d.status === 'approved' ? 'Approved'   : d.status === 'rejected' ? 'Rejected'  : 'Pending';
        $('#m-status-badge').removeClass().addClass('badge ' + bc).text(bl);
        $('#m-pending-with').text(d.pending_with || '—');
        $('#m-pending-step').text(d.pending_step || '—');

        // Workflow steps
        var sh = '';
        (d.steps || []).forEach(function (s) {
            var sc = s.status === 1 ? 'bg-success' : s.status === 2 ? 'bg-danger' : 'bg-warning text-dark';
            var sl = s.status === 1 ? 'Approved'   : s.status === 2 ? 'Rejected'  : 'Pending';
            var bg = s.status === 0 ? 'style="background:#fffbeb;"' : '';
            sh += '<tr ' + bg + '><td class="fw-semibold">'
                + (s.status === 0 ? '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;margin-right:5px;"></span>' : '')
                + s.step_name + '</td><td>' + s.attended_by + '</td><td>' + s.forwarded_by
                + '</td><td><span class="badge ' + sc + '">' + sl + '</span></td><td class="text-muted small">' + s.acted_at + '</td></tr>';
        });
        $('#m-steps-body').html(sh || '<tr><td colspan="5" class="text-center text-muted py-2">No steps yet.</td></tr>');

        // Days + BioTime comparison
        $('#bt-loading').hide();
        var dh = '', totalClaimedMin = 0, totalBtMin = 0, btWorkedDays = 0;

        function minsToHHMM(mins) {
            mins = Math.max(0, Math.round(mins));
            var h = Math.floor(mins / 60), m = mins % 60;
            return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0');
        }

        // Render claimed rows with BioTime overlay
        (d.days || []).forEach(function (day) {
            var iso     = day.iso || day.date;
            var bt      = btMap ? (btMap[iso] || null) : null;
            var clMin   = Math.round((parseFloat(day.hours) || 0) * 60);
            var btMin   = (bt && bt.worked) ? (bt.total_min || Math.round((parseFloat(bt.hours || '0').toString().indexOf(':') >= 0 ? 0 : parseFloat(bt.hours || 0)) * 60)) : 0;
            totalClaimedMin += clMin;
            if (bt && bt.worked) { totalBtMin += (bt.total_min || 0); btWorkedDays++; }

            var rowClass  = '';
            var btInCell  = '<span class="bt-na">—</span>';
            var btOutCell = '<span class="bt-na">—</span>';
            var btHrsCell = '<span class="bt-na">—</span>';

            if (btMap === null) {
                btInCell = btOutCell = btHrsCell = '<span class="bt-na small">N/A</span>';
            } else if (bt && bt.worked) {
                btInCell  = '<span class="bt-match">' + (bt.time_in  || '—') + '</span>';
                btOutCell = '<span class="' + (bt.time_out ? 'bt-match' : 'bt-miss') + '">' + (bt.time_out || 'Open') + '</span>';
                btHrsCell = '<span class="bt-match">' + bt.hours + '</span>';
                rowClass  = 'table-success';
            } else {
                btHrsCell = '<span class="bt-miss">No punch</span>';
                rowClass  = 'table-danger';
            }

            dh += '<tr class="' + rowClass + '"><td>' + day.date + '</td>'
                + '<td class="text-muted small">' + (day.weekday || '') + '</td>'
                + '<td>' + day.platform + '</td>'
                + '<td>' + day.unit + '</td>'
                + '<td class="text-center">' + (clMin ? minsToHHMM(clMin) : '—') + '</td>'
                + '<td class="text-center">' + btInCell  + '</td>'
                + '<td class="text-center">' + btOutCell + '</td>'
                + '<td class="text-center">' + btHrsCell + '</td>'
                + '<td class="text-end">' + fmt(d.rate_per_day) + '</td></tr>';
        });

        $('#m-days-body').html(dh || '<tr><td colspan="9" class="text-center text-muted py-2">No days.</td></tr>');

        var ta = (d.days ? d.days.length : 0) * (d.rate_per_day || 0);
        $('#m-days-foot').html('<tr>'
            + '<td colspan="4" class="text-end pe-3">Total</td>'
            + '<td class="text-center">' + minsToHHMM(totalClaimedMin) + ' (claimed)</td>'
            + '<td colspan="2"></td>'
            + '<td class="text-center">' + (btMap ? minsToHHMM(totalBtMin) + ' (BioTime)' : 'N/A') + '</td>'
            + '<td class="text-end">' + fmt(ta) + '</td></tr>');

        // BioTime summary bar
        if (btMap !== null) {
            $('#bt-days').text(btWorkedDays);
            $('#bt-hours').text(minsToHHMM(totalBtMin));
            $('#bt-claimed').text(d.days ? d.days.length : 0);
            $('#bt-summary').show();
        }
    }

    function fmt(v) {
        return parseFloat(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
});

/* ─── Bulk actions ─── */
function getSelectedIds() {
    return $('.row-check:checked').map(function () { return $(this).val(); }).get();
}

function clearSelection() {
    $('.row-check, #select-all').prop('checked', false);
    $('#bulk-bar').css('display', 'none');
}

function submitBulkAction(action) {
    var ids = getSelectedIds();
    if (!ids.length) return;
    var container = $('#bulk-approve-ids');
    container.html('');
    ids.forEach(function (id) {
        container.append('<input type="hidden" name="claim_ids[]" value="' + id + '">');
    });
    $('#bulkApproveForm').submit();
}

$(document).on('show.bs.modal', '#bulkRejectModal', function () {
    var ids = getSelectedIds();
    var container = $('#bulk-reject-ids');
    container.html('');
    ids.forEach(function (id) {
        container.append('<input type="hidden" name="claim_ids[]" value="' + id + '">');
    });
    $('#bulk-reject-count').text(ids.length);
});

/* ─── Single approve / reject ─── */
function openApprove(id, label) {
    document.getElementById('approveForm').action = '/night-shift/' + id + '/approve';
    document.getElementById('approve-claim-label').textContent = label;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
function openReject(id) {
    document.getElementById('rejectForm').action = '/night-shift/' + id + '/reject';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush
