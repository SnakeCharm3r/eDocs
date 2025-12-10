@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

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
    </style>
    {{-- DataTables CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
@endpush

@section('content')
    @php $user = auth()->user(); @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Header --}}
            <div class="page-header">
                <div class="d-flex align-items-end justify-content-between gap-2 flex-wrap">
                    <div>
                        <h3 class="page-title mb-1">Locum Claims</h3>

                    </div>
                    <div class="page-tools d-flex gap-2">
                        @if (!empty($agreement))
                            <a href="{{ route('locum-agreements.view') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-file-contract me-1"></i> Agreement
                            </a>
                        @endif
                        {{-- @if ($user && ($user->hasRole('incharge') || $user->hasRole('in-charge')))
                            <a href="{{ route('locum-requests.create-for-staff') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-user-plus me-1"></i> Staff
                            </a>
                        @endif --}}
                        @if (!empty($canCreate))
                            <a href="{{ route('locum-requests.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Claim Locum
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Agreement/account gates --}}
                @if (empty($agreement) || (isset($agreement->has_contract) && !$agreement->has_contract))
                    <div class="alert alert-warning mt-3">
                        <div class="fw-semibold">No active locum agreement found.</div>
                        <div class="small">Create an agreement before submitting claims.</div>
                        <a href="{{ route('locum-agreements.index') }}" class="btn btn-sm btn-primary mt-2">Create
                            Agreement</a>
                    </div>
                @elseif (
                    !empty($agreement->end_date) &&
                        now()->startOfDay()->gt(\Carbon\Carbon::parse($agreement->end_date)->endOfDay()))
                    <div class="alert alert-warning mt-3">
                        <div class="fw-semibold">Your locum agreement has expired.</div>
                        <div class="small">Expired on {{ \Carbon\Carbon::parse($agreement->end_date)->format('F j, Y') }}.
                            Contact HR.</div>
                    </div>
                @elseif (strtolower((string) ($user->status ?? '')) !== 'active')
                    <div class="alert alert-warning mt-3">
                        <div class="fw-semibold">Your account is not active ({{ $user->status ?? '' }}).</div>
                        <div class="small">Please contact HR to activate your account.</div>
                    </div>
                @endif
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Approver queue --}}
            @if (!empty($pendingGroups) && $pendingGroups->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header bg-light"><strong>Awaiting Your Action</strong></div>
                    <div class="card-body">
                        @foreach ($pendingGroups as $monthKey => $items)
                            <div class="mb-3">
                                <div class="fw-semibold mb-2">{{ $monthKey }}</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Dept</th>
                                                <th>Days</th>
                                                <th>Amount (TZS)</th>
                                                <th>Status</th>
                                                <th>Open</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($items as $it)
                                                <tr>
                                                    <td>{{ $it['employee'] }}</td>
                                                    <td>{{ $it['dept'] }}</td>
                                                    <td>{{ $it['days'] }}</td>
                                                    <td>{{ $it['amount_fmt'] }}</td>
                                                    <td><span class="badge {{ $it['badge'] }}">{{ $it['status'] }}</span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('locum-requests.show', $it['id']) }}"
                                                            class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Filter pills --}}
            @php
                $tot = ($pillCounts['Pending'] ?? 0) + ($pillCounts['Approved'] ?? 0) + ($pillCounts['Rejected'] ?? 0);
                // Default: show all except rejected
                $defaultFilter = 'active';
            @endphp
            <div class="d-flex flex-wrap align-items-center gap-2 status-pills mb-3" id="statusPills">
                <button type="button" class="btn btn-filter active" data-filter="all">
                    All <span class="badge bg-secondary">{{ $tot }}</span>
                </button>
                <button type="button" class="btn btn-filter" data-filter="Pending">
                    Pending <span class="badge bg-warning text-dark">{{ $pillCounts['Pending'] ?? 0 }}</span>
                </button>
                <button type="button" class="btn btn-filter" data-filter="Approved">
                    Approved <span class="badge bg-success">{{ $pillCounts['Approved'] ?? 0 }}</span>
                </button>
                <button type="button" class="btn btn-filter" data-filter="Rejected">
                    Rejected <span class="badge bg-danger">{{ $pillCounts['Rejected'] ?? 0 }}</span>
                </button>
            </div>

            {{-- Main table --}}
            <div class="card">
                <div class="card-body">
                    @if (empty($rows))
                        <div class="text-muted">No locum requests found.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped align-middle" id="claimsTable">
                                <thead class="table-success">
                                    <tr>
                                        <th>Month</th>
                                        <th>Days</th>
                                        <th>Amount (TZS)</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>View</th>
                                        <th style="display:none;">CreatedISO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $row)
                                        @php
                                            $isRejected = strtolower($row['status_label']) === 'rejected';
                                            $canEdit = false;
                                            if ($isRejected && !empty($row['rejected_at'])) {
                                                $rejectedDate = \Carbon\Carbon::parse($row['rejected_at']);
                                                $twoMonthsAgo = now()->subMonths(2);
                                                $canEdit = $rejectedDate->gte($twoMonthsAgo);
                                            }
                                        @endphp
                                        <tr data-status="{{ $row['status_label'] }}"
                                            @if ($isRejected && !empty($row['rejected_at']))
                                                data-rejected-at="{{ $row['rejected_at'] }}"
                                            @endif>
                                            <td>{{ $row['month_label'] }}</td>
                                            <td>{{ $row['days'] }}</td>
                                            <td>{{ $row['amount_fmt'] }}</td>
                                            <td>
                                                <span class="badge status {{ $row['status_class'] }}"
                                                    @if (!empty($row['status_tip'])) title="{{ $row['status_tip'] }}" @endif>
                                                    {{ $row['status_label'] }}
                                                </span>
                                                @if ($isRejected && !empty($row['rejection_reason']))
                                                    <button type="button" class="btn btn-outline-danger btn-sm ms-2"
                                                        data-bs-toggle="modal" data-bs-target="#rejectionModal"
                                                        data-employee="{{ $row['employee'] }}"
                                                        data-dept="{{ $row['dept'] }}"
                                                        data-month="{{ $row['month_text'] }}"
                                                        data-step="{{ $row['last_step_name'] }}"
                                                        data-reason="{{ e($row['rejection_reason']) }}"
                                                        data-by="{{ $row['acted_by'] }}"
                                                        data-when="{{ $row['acted_at'] }}">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                @endif
                                            </td>
                                            <td>{{ $row['created_at'] }}</td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <button class="btn btn-outline-primary btn-sm view-details"
                                                        data-id="{{ $row['id'] }}"
                                                        data-url-template="{{ $detailsUrlTemplate }}"
                                                        data-monthlabel="{{ $row['month_label'] }}"
                                                        data-worked='@json($row['worked_days'])'>
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    @if ($isRejected && $canEdit)
                                                        <a href="{{ route('locum-requests.edit', $row['id']) }}"
                                                            class="btn btn-outline-warning btn-sm"
                                                            title="Edit and resubmit this rejected request">
                                                            <i class="fas fa-edit"></i> Edit & Resubmit
                                                        </a>
                                                    @elseif ($isRejected && !$canEdit)
                                                        <span class="btn btn-outline-secondary btn-sm disabled"
                                                            title="This request was rejected more than 2 months ago and can no longer be edited">
                                                            <i class="fas fa-lock"></i> Expired
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td style="display:none;">{{ $row['created_iso'] }}</td>
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

    {{-- Rejection Modal --}}
    <div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="rejectionModalLabel">Rejection Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-1 small text-muted">Employee</div>
                    <div class="fw-semibold mb-3" id="rejEmployee">—</div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="small text-muted">Department</div>
                            <div class="fw-semibold" id="rejDept">—</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Month</div>
                            <div class="fw-semibold" id="rejMonth">—</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Step</div>
                            <div class="fw-semibold" id="rejStep">—</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">By</div>
                            <div class="fw-semibold" id="rejBy">—</div>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="mb-2">
                        <div class="small text-muted mb-2 fw-semibold">Reason for Rejection</div>
                        <div id="rejReason" class="p-3 bg-light border rounded" style="min-height: 80px; white-space: pre-wrap; word-wrap: break-word; font-size: 0.95rem; line-height: 1.6; color: #333;">—</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="text-muted small me-auto" id="rejWhen">—</span>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Details Modal (Professional layout: Workflow first, then Worked Days) --}}
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Locum Request Details
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

                    {{-- 1) Workflow Timeline --}}
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

                    {{-- 2) Worked Days --}}
                    <h6 class="section-title mb-2">Worked Days</h6>
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive modal-scroll">
                                <table class="table table-sm table-hover table-nowrap align-middle mb-0"
                                    id="workedDaysTable">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width:16%">Date</th>
                                            <th style="width:10%">Worked</th>
                                            <th>Entries (Shift / Hours / Platform / Unit)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="wd-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <a id="edit-resubmit-btn" href="#" class="btn btn-warning me-auto" style="display: none;">
                        <i class="fas fa-edit"></i> Edit & Resubmit
                    </a>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>


    {{-- JS (ES5 only, no Moment.js) --}}
    <script>
        (function() {
            // Simple "DD MMM" formatter from YYYY-MM-DD or Date-like
            function formatDayMonth(dateStr) {
                var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                if (typeof dateStr !== 'string') return '—';
                var p = dateStr.split('-');
                if (p.length === 3) {
                    var y = parseInt(p[0], 10),
                        m = parseInt(p[1], 10) - 1,
                        d = parseInt(p[2], 10);
                    if (!isNaN(y) && !isNaN(m) && !isNaN(d) && m >= 0 && m <= 11) {
                        var dd = (d < 10 ? '0' + d : '' + d);
                        return dd + ' ' + months[m];
                    }
                }
                try {
                    var dt = new Date(dateStr);
                    if (!isNaN(dt.getTime())) {
                        var dd2 = (dt.getDate() < 10 ? '0' + dt.getDate() : '' + dt.getDate());
                        return dd2 + ' ' + months[dt.getMonth()];
                    }
                } catch (_) {}
                return dateStr;
            }

            function waitForJQuery(callback) {
                if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
                    callback();
                } else {
                    setTimeout(function() { waitForJQuery(callback); }, 100);
                }
            }

            waitForJQuery(function() {
                $(document).ready(function() {
                    // Initialize DataTables
                    var dataTable = null;
                    if ($.fn.DataTable && $('#claimsTable').length && !$.fn.DataTable.isDataTable('#claimsTable')) {
                        dataTable = $('#claimsTable').DataTable({
                            paging: true,
                            pageLength: 10,
                            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                            searching: true,
                            ordering: true,
                            info: true,
                            autoWidth: false,
                            responsive: true,
                            pagingType: 'simple_numbers',
                            language: {
                                paginate: {
                                    previous: 'Previous',
                                    next: 'Next'
                                }
                            },
                            order: [[4, 'desc']], // Sort by Created column descending
                            columnDefs: [
                                { orderable: false, targets: [5] }, // Disable sorting on View column
                                { visible: false, targets: [6] } // Hide CreatedISO column
                            ]
                        });
                    }

                    // Filter pills - work with DataTables
                    var pills = document.querySelectorAll('#statusPills .btn-filter');
                    Array.prototype.forEach.call(pills, function(btn) {
                        btn.addEventListener('click', function() {
                            Array.prototype.forEach.call(pills, function(b) {
                                b.classList.remove('active');
                            });
                            this.classList.add('active');
                            var want = this.getAttribute('data-filter');

                            if (dataTable) {
                                // Clear all existing custom filters
                                $.fn.dataTable.ext.search = [];

                                // Use DataTables search/filter
                                if (want === 'all') {
                                    // Show all requests (including rejected)
                                    dataTable.search('').draw();
                                } else {
                                    // Filter by specific status
                                    $.fn.dataTable.ext.search.push(
                                        function(settings, data, dataIndex) {
                                            var row = dataTable.row(dataIndex).node();
                                            var status = $(row).attr('data-status') || '';
                                            return status === want;
                                        }
                                    );
                                    dataTable.draw();
                                }
                            } else {
                                // Fallback for non-DataTables
                                var table = document.getElementById('claimsTable');
                                var rows = table ? table.querySelectorAll('tbody tr') : [];
                                Array.prototype.forEach.call(rows, function(tr) {
                                    if (want === 'all') {
                                        tr.style.display = '';
                                        return;
                                    }
                                    var s = tr.getAttribute('data-status') || '';
                                    tr.style.display = (s === want) ? '' : 'none';
                                });
                            }
                        });
                    });

                // View (open modal + load both workflow & worked-days) - using event delegation for DataTables
                $(document).on('click', '.view-details', function() {
                        var id = $(this).attr('data-id');
                        var tpl = $(this).attr('data-url-template'); // /locum-requests/__ID__/status
                        var url = (tpl || '').replace('__ID__', id);
                        var requestId = id; // Store for edit button

                        document.getElementById('detailsMonth').textContent = $(this).attr('data-monthlabel') || '—';

                        // Set edit button URL and hide it initially
                        var editUrl = '{{ route("locum-requests.edit", ":id") }}'.replace(':id', requestId);
                        $('#edit-resubmit-btn').attr('href', editUrl).hide();

                        // reset UI
                        var $badge = $('#wf-badge');
                        $badge.removeClass('bg-success bg-warning bg-danger bg-secondary')
                            .addClass('bg-secondary').text('Loading…');
                        $('#wf-tooltip').text('');
                        $('#wf-pending-with').text('—');
                        $('#wf-pending-step').text('—');
                        $('#wf-steps-table tbody').empty();

                        // worked-days from data
                        var wd = $(this).attr('data-worked');
                        var worked = [];
                        try {
                            worked = wd ? JSON.parse(wd) : [];
                        } catch (e) {
                            worked = [];
                        }
                        renderWorkedDays(worked);

                        // show modal
                        var modal = new bootstrap.Modal(document.getElementById(
                            'detailsModal'));
                        modal.show();

                        // fetch workflow JSON
                        $.ajax({
                            url: url,
                            method: 'GET',
                            success: function(data) {
                                var wf = (data && data.workflow) ? data.workflow :
                                    {};
                                var kind = (wf.current_class ? wf.current_class :
                                    'secondary').replace('bg-', '');
                                var map = {
                                    success: 'bg-success',
                                    warning: 'bg-warning',
                                    danger: 'bg-danger',
                                    secondary: 'bg-secondary'
                                };
                                var klass = map[kind] ? map[kind] : 'bg-secondary';
                                $badge.removeClass(
                                    'bg-success bg-warning bg-danger bg-secondary'
                                ).addClass(klass).text(wf.current_label ? wf
                                    .current_label : '—');

                                $('#wf-tooltip').text(wf.tooltip ? wf.tooltip : '');
                                $('#wf-pending-with').text(wf.pending_with ? wf
                                    .pending_with : (wf.completed ? '—' :
                                        'Unknown'));
                                $('#wf-pending-step').text(wf.pending_step ? wf
                                    .pending_step : (wf.completed ? '—' :
                                        'Pending'));

                                // Show/hide Edit & Resubmit button based on rejection status and time limit
                                var isRejected = (wf.current_class === 'bg-danger' || wf.current_label === 'Rejected');
                                if (isRejected) {
                                    // Check if 2 months have passed
                                    var row = $('.view-details[data-id="' + requestId + '"]').closest('tr');
                                    var rejectedAt = row.attr('data-rejected-at');
                                    var canEdit = false;

                                    if (rejectedAt) {
                                        var rejectedDate = new Date(rejectedAt);
                                        var twoMonthsAgo = new Date();
                                        twoMonthsAgo.setMonth(twoMonthsAgo.getMonth() - 2);
                                        canEdit = rejectedDate >= twoMonthsAgo;
                                    } else {
                                        // If no rejected_at, allow edit (fallback)
                                        canEdit = true;
                                    }

                                    if (canEdit) {
                                        $('#edit-resubmit-btn').show();
                                    } else {
                                        $('#edit-resubmit-btn').hide();
                                    }
                                } else {
                                    $('#edit-resubmit-btn').hide();
                                }

                                var $tb = $('#wf-steps-table tbody').empty();
                                var steps = (wf.steps && Array.isArray(wf.steps)) ?
                                    wf.steps : [];
                                steps.forEach(function(step) {
                                    var sClass = (step.status ===
                                            'Approved') ? 'bg-success' : (
                                            step.status === 'Rejected') ?
                                        'bg-danger' : 'bg-warning';
                                    var extra = '';
                                    if (step.rejection) {
                                        extra =
                                            '<div class="small text-danger mt-1">' +
                                            step.rejection + '</div>';
                                    } else if (step.status === 'Pending' &&
                                        step.remark) {
                                        extra =
                                            '<div class="small text-muted mt-1">' +
                                            step.remark + '</div>';
                                    }
                                    $tb.append(
                                        '<tr>' +
                                        '<td><div class="fw-semibold">' +
                                        (step.step ? step.step : '—') +
                                        '</div>' + extra + '</td>' +
                                        '<td>' + (step.attended_by ?
                                            step.attended_by : '—') +
                                        '</td>' +
                                        '<td>' + (step.forwarded_by ?
                                            step.forwarded_by : '—') +
                                        '</td>' +
                                        '<td><span class="badge ' +
                                        sClass + '">' + step.status +
                                        '</span></td>' +
                                        '<td>' + (step.acted_at ? step
                                            .acted_at : '—') + '</td>' +
                                        '</tr>'
                                    );
                                });
                            },
                            error: function() {
                                alert('Failed to load workflow details.');
                            }
                        });
                    });
                });

                // Rejection modal fill
                var rejModal = document.getElementById('rejectionModal');
                if (rejModal) {
                    rejModal.addEventListener('show.bs.modal', function(e) {
                        var btn = e.relatedTarget;
                        if (!btn) return;

                        function attr(n) {
                            return btn.getAttribute('data-' + n) || '—';
                        }
                        document.getElementById('rejEmployee').textContent = attr('employee');
                        document.getElementById('rejDept').textContent = attr('dept');
                        document.getElementById('rejMonth').textContent = attr('month');
                        document.getElementById('rejStep').textContent = attr('step');
                        document.getElementById('rejBy').textContent = attr('by');
                        document.getElementById('rejWhen').textContent = attr('when');

                        // Format rejection reason - data is already HTML escaped from server
                        var reason = attr('reason');
                        var reasonEl = document.getElementById('rejReason');
                        if (reason && reason !== '—') {
                            // Use textContent to safely display (preserves line breaks with white-space: pre-wrap)
                            reasonEl.textContent = reason;
                            reasonEl.style.color = '#333';
                            reasonEl.style.fontWeight = '400';
                        } else {
                            reasonEl.textContent = '—';
                            reasonEl.style.color = '#999';
                            reasonEl.style.fontStyle = 'italic';
                        }
                    });
                }
            });

            // Render worked-days into the right-hand table inside the modal
            function renderWorkedDays(worked) {
                var body = document.getElementById('wd-body');
                if (!body) return;
                body.innerHTML = '';

                if (!worked || typeof worked !== 'object' || Object.keys(worked).length === 0) {
                    body.innerHTML = '<tr><td colspan="3" class="text-muted">No worked days data available.</td></tr>';
                    return;
                }

                var shiftMap = @json($shiftMap);
                var platformMap = @json($platformMap);
                var unitMap = @json($unitMap);

                Object.keys(worked).forEach(function(dateStr) {
                    var info = worked[dateStr] || {};
                    var workedFlag = (info.worked && String(info.worked) === '1');
                    var entries = (info.entries && Array.isArray(info.entries)) ? info.entries : [];

                    var rowsHtml = '';
                    if (workedFlag && entries.length) {
                        var inner = '';
                        entries.forEach(function(ent) {
                            var sId = ('shift_id' in ent) ? ent.shift_id : null;
                            var pId = ('platform_id' in ent) ? ent.platform_id : null;
                            var uId = ('unit_id' in ent) ? ent.unit_id : null;
                            var hrs = ('hours' in ent) ? ent.hours : null;

                            var sName = (sId && (sId in shiftMap)) ? shiftMap[sId] : '—';
                            var pName = (pId && (pId in platformMap)) ? platformMap[pId] : '—';
                            var uName = (uId && (uId in unitMap)) ? unitMap[uId] : '—';

                            inner += '<tr>' +
                                '<td style="width:28%">' + sName + '</td>' +
                                '<td style="width:14%">' + (hrs === null ? '—' : Number(hrs).toFixed(
                                    2)) + '</td>' +
                                '<td style="width:30%">' + pName + '</td>' +
                                '<td style="width:28%">' + uName + '</td>' +
                                '</tr>';
                        });
                        rowsHtml =
                            '<div class="table-responsive"><table class="table table-sm mb-0">' +
                            '<thead><tr><th style="width:28%">Shift</th><th style="width:14%">Hours</th><th style="width:30%">Platform</th><th style="width:28%">Unit</th></tr></thead>' +
                            '<tbody>' + inner + '</tbody></table></div>';
                    } else if (workedFlag) {
                        rowsHtml = '<span class="text-muted">No entries captured for this day.</span>';
                    } else {
                        rowsHtml = '<span class="text-muted">—</span>';
                    }

                    body.insertAdjacentHTML('beforeend',
                        '<tr>' +
                        '<td>' + formatDayMonth(dateStr) + '</td>' +
                        '<td>' + (workedFlag ? '<span class="badge bg-success">Yes</span>' :
                            '<span class="badge bg-secondary">No</span>') + '</td>' +
                        '<td>' + rowsHtml + '</td>' +
                        '</tr>'
                    );
                });
            }
        })();
    </script>
@endsection
