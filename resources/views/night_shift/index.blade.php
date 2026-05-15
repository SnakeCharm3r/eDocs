@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@push('styles')
<style>
    :root { --brand: #2fb565; }

    .page-tools .btn { border-radius: .5rem; }
    .page-tools .btn-primary { background: var(--brand); border-color: var(--brand); }
    .page-tools .btn-primary:hover { filter: brightness(.95); }

    .status-pills .btn-filter {
        border: 1px solid var(--brand); color: var(--brand);
        background: #fff; padding: .25rem .65rem;
        font-size: .82rem; border-radius: .4rem;
    }
    .status-pills .btn-filter:hover,
    .status-pills .btn-filter.active { background: var(--brand); color: #fff; }
    .status-pills .badge { font-size: .7rem; margin-left: .25rem; }

    .table td, .table th { vertical-align: middle; }
    .badge.status { font-size: .75rem; }
</style>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
@endpush

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">

    {{-- Header --}}
    <div class="page-header">
        <div class="d-flex align-items-end justify-content-between gap-2 flex-wrap">
            <div>
                <h3 class="page-title mb-1">
                    Night Allowances Claims
                </h3>
            </div>
            <div class="page-tools d-flex gap-2">
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <a href="{{ route('night-shift.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> New Claim
                </a>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Filter pills --}}
    @php $tot = $claims->count(); @endphp
    <div class="d-flex flex-wrap align-items-center gap-2 status-pills mb-3" id="statusPills">
        <button type="button" class="btn btn-filter active" data-filter="all">
            All <span class="badge bg-secondary">{{ $tot }}</span>
        </button>
        <button type="button" class="btn btn-filter" data-filter="pending">
            Pending <span class="badge bg-warning text-dark">{{ $pillCounts['Pending'] }}</span>
        </button>
        <button type="button" class="btn btn-filter" data-filter="approved">
            Approved <span class="badge bg-success">{{ $pillCounts['Approved'] }}</span>
        </button>
        <button type="button" class="btn btn-filter" data-filter="rejected">
            Rejected <span class="badge bg-danger">{{ $pillCounts['Rejected'] }}</span>
        </button>
    </div>

    {{-- Main table --}}
    <div class="card">
        <div class="card-body p-0">
            @if($claims->isEmpty())
            <div class="text-center py-5 text-muted">
                                <p class="mb-1 fw-semibold">No night allowances claims yet</p>
                <small>Submit your first monthly night duty claim</small><br>
                <a href="{{ route('night-shift.create') }}" class="btn btn-sm btn-success mt-3">
                    <i class="fas fa-plus me-1"></i> Submit Claim
                </a>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="claimsTable">
                    <thead class="table-success">
                        <tr>
                            <th>Month</th>
                            <th>Applicable Year</th>
                            <th class="text-center">Days</th>
                            <th>Amount (TZS)</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>View</th>
                            <th style="display:none;">CreatedISO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($claims as $claim)
                        @php
                            $amount    = $claim->total_days_worked * $ratePerDay;
                            $statusLow = strtolower($claim->status);
                            $badgeCls  = match($statusLow) {
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default    => 'bg-warning text-dark',
                            };

                            // Workflow pending step
                            $wf          = $claim->workflow;
                            $pendingStep = $wf ? $wf->histories->firstWhere('status', 0) : null;
                            $statusTip   = $pendingStep
                                ? 'Pending with: '.($pendingStep->attendedBy
                                    ? trim($pendingStep->attendedBy->fname.' '.$pendingStep->attendedBy->lname)
                                    : '—').' — '.$pendingStep->step_name
                                : null;

                            // Rejection reason
                            $rejReason = null;
                            $canResubmit = false;
                            if ($statusLow === 'rejected') {
                                $rejStep   = $wf ? $wf->histories->where('status', 2)->last() : null;
                                $rejReason = $rejStep?->rejection_reason ?? $claim->rejection_reason;
                                $canResubmit = $claim->created_at->addMonth()->isFuture();
                            }
                        @endphp
                        <tr data-status="{{ $statusLow }}">
                            <td><strong>{{ $claim->month }} / {{ $claim->year }}</strong></td>
                            <td>{{ $claim->year }}</td>
                            <td class="text-center">{{ $claim->total_days_worked }}</td>
                            <td>{{ number_format($amount, 2) }}</td>
                            <td>
                                <span class="badge status {{ $badgeCls }}"
                                    @if($statusTip) title="{{ $statusTip }}" @endif>
                                    {{ ucfirst($claim->status) }}
                                </span>
                                @if($rejReason)
                                    <div class="mt-1" style="font-size:0.72rem; color:#842029; max-width:200px; word-break:break-word;">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ Str::limit($rejReason, 60) }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $claim->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <button class="btn btn-outline-primary btn-sm view-details"
                                            data-url="{{ route('night-shift.details', $claim) }}"
                                            data-month="{{ $claim->month }} / {{ $claim->year }}"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if($canResubmit)
                                        <a href="{{ route('night-shift.edit', $claim) }}"
                                           class="btn btn-warning btn-sm"
                                           title="Edit &amp; Resubmit (expires {{ $claim->created_at->addMonth()->format('d M Y') }})">
                                            <i class="fas fa-redo"></i>
                                        </a>
                                    @elseif($statusLow === 'rejected')
                                        <span class="btn btn-outline-secondary btn-sm disabled"
                                              title="Edit window expired ({{ $claim->created_at->addMonth()->format('d M Y') }})">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td style="display:none;">{{ $claim->created_at->format('Y-m-d H:i:s') }}</td>
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
@endsection

{{-- Detail Modal --}}
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

                {{-- Submitted / Approved timestamps --}}
                <div class="d-flex flex-wrap gap-3 mb-2 small">
                    <span class="text-muted">Submitted at:</span> <span id="m-submitted-at">—</span>
                    <span class="text-muted">Approved at:</span>  <span id="m-approved-at">—</span>
                </div>

                {{-- Status strip --}}
                <div class="p-2 border rounded mb-4">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span id="m-status-badge" class="badge bg-secondary">—</span>
                        <small id="m-status-tip" class="text-muted"></small>
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
                            <table class="table table-sm table-hover align-middle mb-0" id="m-steps-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:30%">Step</th>
                                        <th style="width:20%">Attended By</th>
                                        <th style="width:20%">Forwarded By</th>
                                        <th style="width:12%">Status</th>
                                        <th style="width:18%">Acted At</th>
                                    </tr>
                                </thead>
                                <tbody id="m-steps-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Worked Days --}}
                <h6 class="fw-bold mb-2">Worked Days</h6>
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Platform</th>
                                        <th>Unit</th>
                                        <th class="text-center">Hours</th>
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

@push('scripts')
<script>
$(function () {
    var table = $('#claimsTable').DataTable({
        order: [[7, 'desc']],
        columnDefs: [{ targets: [5, 6, 7], orderable: false }],
        pageLength: 10,
        language: { search: 'Search:' }
    });

    // Filter pills
    $('#statusPills .btn-filter').on('click', function () {
        $('#statusPills .btn-filter').removeClass('active');
        $(this).addClass('active');
        var filter = $(this).data('filter');
        if (filter === 'all') {
            table.column(4).search('').draw();
        } else {
            table.column(4).search(filter, false, false).draw();
        }
    });

    // View Details modal
    $(document).on('click', '.view-details', function () {
        var url   = $(this).data('url');
        var month = $(this).data('month');

        $('#modal-month').text(month);
        $('#m-steps-body').html('<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-1"></i> Loading…</td></tr>');
        $('#m-days-body').html('');
        $('#m-days-foot').html('');
        $('#m-status-badge').removeClass().addClass('badge bg-secondary').text('Loading…');
        $('#m-pending-with, #m-pending-step, #m-submitted-at, #m-approved-at, #m-status-tip').text('—');

        new bootstrap.Modal(document.getElementById('detailModal')).show();

        $.getJSON(url, function (d) {
            // Timestamps
            $('#m-submitted-at').text(d.submitted_at || '—');
            $('#m-approved-at').text(d.approved_at || '—');

            // Status badge
            var badgeCls = d.status === 'approved' ? 'bg-success'
                         : d.status === 'rejected'  ? 'bg-danger'
                         : 'bg-warning text-dark';
            var badgeLabel = d.status === 'approved' ? 'HR Approved'
                           : d.status === 'rejected' ? 'Rejected'
                           : 'Pending';
            $('#m-status-badge').removeClass().addClass('badge ' + badgeCls).text(badgeLabel);

            if (d.pending_with) {
                $('#m-pending-with').text(d.pending_with);
                $('#m-pending-step').text(d.pending_step || '—');
                $('#m-status-tip').text('');
            } else {
                $('#m-pending-with').text('—');
                $('#m-pending-step').text('—');
            }

            // Workflow steps
            var stepsHtml = '';
            if (d.steps && d.steps.length) {
                d.steps.forEach(function (s) {
                    var sc = s.status === 1 ? 'bg-success' : s.status === 2 ? 'bg-danger' : 'bg-warning text-dark';
                    var sl = s.status === 1 ? 'Approved'  : s.status === 2 ? 'Rejected'  : 'Pending';
                    var rowBg = s.status === 0 ? 'style="background:#fffbeb;"' : '';
                    stepsHtml += '<tr ' + rowBg + '>'
                        + '<td class="fw-semibold">' + (s.status === 0 ? '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;margin-right:5px;"></span>' : '') + s.step_name + '</td>'
                        + '<td>' + s.attended_by + '</td>'
                        + '<td>' + s.forwarded_by + '</td>'
                        + '<td><span class="badge ' + sc + '">' + sl + '</span></td>'
                        + '<td class="text-muted small">' + s.acted_at + '</td>'
                        + '</tr>';
                });
            } else {
                stepsHtml = '<tr><td colspan="5" class="text-muted text-center py-2">No workflow steps yet.</td></tr>';
            }
            $('#m-steps-body').html(stepsHtml);

            // Worked days
            var daysHtml = '';
            var totalHours = 0;
            if (d.days && d.days.length) {
                d.days.forEach(function (day) {
                    totalHours += parseFloat(day.hours) || 0;
                    daysHtml += '<tr>'
                        + '<td>' + day.date + '</td>'
                        + '<td class="text-muted small">' + day.weekday + '</td>'
                        + '<td>' + day.platform + '</td>'
                        + '<td>' + day.unit + '</td>'
                        + '<td class="text-center">' + (day.hours ? day.hours + ' hrs' : '—') + '</td>'
                        + '<td class="text-end">' + formatAmount(d.rate_per_day) + '</td>'
                        + '</tr>';
                });
            } else {
                daysHtml = '<tr><td colspan="6" class="text-muted text-center py-2">No days recorded.</td></tr>';
            }
            $('#m-days-body').html(daysHtml);

            var totalAmt = (d.days ? d.days.length : 0) * (d.rate_per_day || 0);
            $('#m-days-foot').html(
                '<tr><td colspan="4" class="text-end pe-3">Total</td>'
                + '<td class="text-center">' + totalHours + ' hrs</td>'
                + '<td class="text-end">' + formatAmount(totalAmt) + '</td></tr>'
            );
        }).fail(function () {
            $('#m-steps-body').html('<tr><td colspan="5" class="text-danger text-center py-2">Failed to load details.</td></tr>');
        });
    });

    function formatAmount(v) {
        return parseFloat(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
});
</script>
@endpush
