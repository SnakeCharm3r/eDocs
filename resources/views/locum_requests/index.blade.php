@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@php
    $user = auth()->user();
    $roles = ['hr', 'Admin', 'super-admin', 'line-manager', 'requester', 'price_committee', 'incharge', 'it'];

    // Gatekeeping messages
    $today = now()->startOfDay();
    $userEndDate = $user->ending_date ? \Carbon\Carbon::parse($user->ending_date)->endOfDay() : null;
    $isUserEndDateValid = !$userEndDate || $today->lte($userEndDate);

    $canRequestLocum = false;
    $statusMessage = '';

    if ($agreement && in_array((int) $agreement->status, [3, 4])) {
        $resubmitUrl = route('locum-agreements.index', $agreement->id);
        $rejectionReason = trim($agreement->rejection_status ?? '') ?: 'No reason provided.';
        $statusMessage =
            '
            <div class="alert alert-warning mb-0">
                <div class="fw-semibold">This locum agreement was rejected.</div>
                <div class="small mt-1"><span class="text-muted">Reason:</span> ' .
            e($rejectionReason) .
            '</div>
                <a href="' .
            $resubmitUrl .
            '" class="btn btn-outline-secondary btn-sm mt-2">
                    <i class="fas fa-edit"></i> Update & Resubmit
                </a>
            </div>';
    } elseif (!$agreement || !$agreement->has_contract) {
        $statusMessage =
            '
            <div class="alert alert-warning mb-0">
                <div class="fw-semibold">No active locum agreement found.</div>
                <div class="small">You must create a locum agreement before submitting claims.</div>
                <a href="' .
            route('locum-agreements.index') .
            '" class="btn btn-primary btn-sm mt-2">
                    Create Agreement
                </a>
            </div>';
    } elseif ($agreement->end_date && $today->gt(\Carbon\Carbon::parse($agreement->end_date)->endOfDay())) {
        $statusMessage =
            '
            <div class="alert alert-warning mb-0">
                <div class="fw-semibold">Your locum agreement has expired.</div>
                <div class="small">Expired on ' .
            \Carbon\Carbon::parse($agreement->end_date)->format('F j, Y') .
            '. Please contact HR to renew.</div>
            </div>';
    } elseif ($user->status !== 'active') {
        $statusMessage =
            '
            <div class="alert alert-warning mb-0">
                <div class="fw-semibold">Your account is not active (' .
            $user->status .
            ').</div>
                <div class="small">Please contact HR to activate your account.</div>
            </div>';
    } elseif (!$isUserEndDateValid) {
        $statusMessage =
            '
            <div class="alert alert-warning mb-0">
                <div class="fw-semibold">Your account validity has expired.</div>
                <div class="small">Expired on ' .
            $userEndDate->format('F j, Y') .
            '. Please contact HR to extend your account validity.</div>
            </div>';
    } else {
        $canRequestLocum = true;
    }

    // Counts for pills
    $pillCounts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
    foreach ($requests as $r) {
        $wf = $r->workflow;
        $hist = $wf ? $wf->histories : collect();
        $last = $hist ? $hist->sortByDesc('updated_at')->first() : null;
        $code = (int) ($last->locum_request_status ?? 0);
        $label = 'Pending';
        if ($last) {
            if ((string) $last->status === '2' || $code === 5) {
                $label = 'Rejected';
            } elseif ($wf && (int) $wf->work_flow_completed === 1) {
                $label = 'Approved';
            }
        }
        if (isset($pillCounts[$label])) {
            $pillCounts[$label]++;
        }
    }
@endphp

@push('styles')
    <style>
        :root {
            --brand: #198754;
        }

        .page-tools .btn {
            border-radius: .5rem;
        }

        .page-tools .btn-primary {
            background: var(--brand);
            border-color: var(--brand);
        }

        .page-tools .btn-primary:hover {
            filter: brightness(.95);
        }

        .status-pills .btn-filter {
            border: 1px solid var(--brand);
            color: var(--brand);
            background: #fff;
            padding: .25rem .5rem;
            font-size: .85rem;
            line-height: 1.1;
            border-radius: .4rem;
        }

        .status-pills .btn-filter:hover,
        .status-pills .btn-filter.active {
            background: var(--brand);
            color: #fff;
        }

        .status-pills .badge {
            font-size: .7rem;
            margin-left: .25rem;
        }

        table.dataTable td,
        table.dataTable th {
            white-space: nowrap;
            vertical-align: middle;
            padding: .45rem .6rem;
        }

        .status-badge {
            font-size: .75rem;
        }

        .alert-narrow {
            padding: .6rem .8rem;
            border-radius: .6rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Header --}}
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-12 col-lg-6">
                        <h3 class="page-title mb-1">Locum Claims</h3>
                        {{-- <div class="text-muted small">Submit and track your monthly locum claims</div> --}}
                    </div>
                    <div class="col-12 col-lg-6 d-flex justify-content-lg-end gap-2 page-tools mt-3 mt-lg-0">
                        @if ($agreement)
                            <a href="{{ route('locum-agreements.view', $agreement->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-file-contract me-1"></i> Locum Agreement
                            </a>
                        @endif
                        @if ($user && $user->hasRole('incharge'))
                            <a href="{{ route('locum-requests.create-for-staff') }}" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i> Staff
                            </a>
                        @endif
                        @if ($agreement && (int) $agreement->status === 2)
                            <a href="{{ route('locum-requests.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Claim Locum
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Gate / Info --}}
                @if ($user && $user->hasAnyRole($roles) && !$canRequestLocum)
                    <div class="row mt-3">
                        <div class="col-12">{!! $statusMessage !!}</div>
                    </div>
                @endif
            </div>

            {{-- Flash alerts --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show alert-narrow" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show alert-narrow" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Status pills --}}
            <div class="d-flex flex-wrap align-items-center gap-2 status-pills mb-2" id="statusPills">
                <button type="button" class="btn btn-filter" data-filter="all">
                    All <span
                        class="badge bg-secondary">{{ $pillCounts['Pending'] + $pillCounts['Approved'] + $pillCounts['Rejected'] }}</span>
                </button>
                <button type="button" class="btn btn-filter active" data-filter="Pending">
                    Pending <span class="badge bg-warning text-dark">{{ $pillCounts['Pending'] }}</span>
                </button>
                <button type="button" class="btn btn-filter" data-filter="Approved">
                    Approved <span class="badge bg-success">{{ $pillCounts['Approved'] }}</span>
                </button>
                <button type="button" class="btn btn-filter" data-filter="Rejected">
                    Rejected <span class="badge bg-danger">{{ $pillCounts['Rejected'] }}</span>
                </button>
            </div>

            {{-- Table --}}
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="locumTable" class="table table-striped table-hover align-middle" style="width:100%">
                            <thead class="table-success">
                                <tr>
                                    {{-- <th>#</th> --}}
                                    <th>Locum Month</th>
                                    <th>Number of Days</th>
                                    <th>Amount (TZS)</th>
                                    <th>Status</th>
                                    <th>Worked Days</th>
                                    <th>Actions</th>
                                    <th style="display:none;">Created</th> {{-- hidden for default sorting --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    @php
                                        $wf = $request->workflow;
                                        $histories = $wf ? $wf->histories : collect();
                                        $last = $histories->sortByDesc('updated_at')->first();
                                        $code = (int) ($last->locum_request_status ?? 0);

                                        $label = 'Pending';
                                        $class = 'bg-warning';
                                        $tooltip = null;
                                        if ($last) {
                                            if ((string) $last->status === '2' || $code === 5) {
                                                $label = 'Rejected';
                                                $class = 'bg-danger';
                                                $tooltip = trim($last->rejection_reason ?? '') ?: null;
                                            } elseif ($wf && (int) $wf->work_flow_completed === 1) {
                                                $label =
                                                    $last->step_name === 'HR Approval' && (string) $last->status === '1'
                                                        ? 'HR Approved'
                                                        : 'Approved';
                                                $class = 'bg-success';
                                            } else {
                                                $nextPending = $histories->firstWhere('status', 0);
                                                if ($nextPending) {
                                                    $map = [
                                                        'Line Manager Approval' => 'Pending Line Manager Approval',
                                                        'HEC Approval' => 'Pending HEC Approval',
                                                        'HR Approval' => 'Pending HR Approval',
                                                    ];
                                                    $label = $map[$nextPending->step_name] ?? 'Pending';
                                                }
                                            }
                                        }

                                        $workedDays = $request->worked_days;
                                        if (is_string($workedDays)) {
                                            $workedDays = json_decode($workedDays, true);
                                        }

                                        $createdIso = \Carbon\Carbon::parse($request->created_at)->format(
                                            'Y-m-d H:i:s',
                                        );
                                        $monthLabel =
                                            $request->locum_month .
                                            ' / ' .
                                            \Carbon\Carbon::parse($request->created_at)->format('Y');
                                    @endphp
                                    <tr data-status="{{ $label }}">
                                        {{-- <td data-rownum></td> --}}
                                        <td>{{ $monthLabel }}</td>
                                        <td>{{ $request->number_of_days }}</td>
                                        <td>{{ number_format($request->total_amount_payable, 2) }}</td>

                                        <td>
                                            <span class="badge status-badge {{ $class }}"
                                                @if ($tooltip) title="{{ $tooltip }}" @endif>
                                                {{ $label }}
                                            </span>

                                            @if (strtolower($label) === 'rejected' && $last && !empty($last->rejection_reason))
                                                @php
                                                    $deptName = $request->user?->department?->dept_name ?? 'N/A';
                                                    $employeeName = $request->user
                                                        ? ($request->user->username ?:
                                                        trim(
                                                            ($request->user->fname ?? '') .
                                                                ' ' .
                                                                ($request->user->lname ?? ''),
                                                        ))
                                                        : 'N/A';
                                                    $monthText = $request->locum_month
                                                        ? $request->locum_month .
                                                            ' ' .
                                                            \Carbon\Carbon::parse($request->created_at)->format('Y')
                                                        : \Carbon\Carbon::parse($request->created_at)->format('F Y');
                                                    $actedBy =
                                                        $last?->user?->username ?:
                                                        ($last?->user
                                                            ? trim(
                                                                ($last->user->fname ?? '') .
                                                                    ' ' .
                                                                    ($last->user->lname ?? ''),
                                                            )
                                                            : '—');
                                                    $actedAt = optional(
                                                        $last?->updated_at ?? $last?->created_at,
                                                    )?->format('d M Y H:i');
                                                @endphp
                                                <button type="button" class="btn btn-outline-danger btn-sm ms-2"
                                                    data-bs-toggle="modal" data-bs-target="#rejectionModal"
                                                    title="View Rejection Reason" data-employee="{{ $employeeName }}"
                                                    data-dept="{{ $deptName }}" data-month="{{ $monthText }}"
                                                    data-step="{{ $last->step_name ?? '—' }}"
                                                    data-reason="{{ e($last->rejection_reason) }}"
                                                    data-by="{{ $actedBy }}" data-when="{{ $actedAt }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @endif
                                        </td>

                                        <td>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#workedDaysModal{{ $request->id }}">
                                                <i class="fas fa-calendar-day"></i> View
                                            </button>

                                            <div class="modal fade" id="workedDaysModal{{ $request->id }}" tabindex="-1"
                                                aria-labelledby="workedDaysModalLabel{{ $request->id }}"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-md">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="workedDaysModalLabel{{ $request->id }}">
                                                                Worked Days — {{ $request->locum_month }}
                                                                {{ \Carbon\Carbon::parse($request->created_at)->format('Y') }}
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            @if (is_array($workedDays) && count($workedDays))
                                                                <table class="table table-bordered table-sm">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Date</th>
                                                                            <th>Worked</th>
                                                                            <th>Hours</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($workedDays as $dateStr => $info)
                                                                            <tr>
                                                                                <td>{{ \Carbon\Carbon::parse($dateStr)->format('d F') }}
                                                                                </td>
                                                                                <td>
                                                                                    @if (!empty($info['worked']) && $info['worked'] == '1')
                                                                                        <span
                                                                                            class="badge bg-success">Yes</span>
                                                                                    @else
                                                                                        <span
                                                                                            class="badge bg-secondary">No</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td>{{ $info['hours'] ?? '-' }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            @else
                                                                <span class="text-muted">No worked days data
                                                                    available.</span>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-primary view-details"
                                                data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                data-id="{{ $request->id }}" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            @if ($label === 'Rejected')
                                                <a href="{{ route('locum-requests.edit', $request->id) }}"
                                                    class="btn btn-sm btn-outline-info" title="Modify & Resubmit">
                                                    <i class="fas fa-sync-alt"></i>
                                                </a>
                                            @endif
                                        </td>

                                        <td style="display:none;">{{ $createdIso }}</td>
                                    </tr>
                                @endforeach
                                {{-- NOTE: Do NOT render a colspan row when empty; DataTables shows emptyTable message --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- DataTable init via your component - adds emptyTable text and keeps numbering/order --}}
    @include('components.datatable', [
        'id' => 'locumTable',
        'options' => [
            'order' => [[7, 'desc']], // hidden Created column
            'hideColumns' => [7], // hide Created
            'pageLength' => 25,
            'searchPlaceholder' => 'Search claims…',
            'autoNumber' => true,
            'numberColumnIndex' => 0,
            'numberContinuous' => true,
            'language' => [
                'emptyTable' => 'No locum requests found.',
            ],
        ],
    ])

    {{-- Global Modals: Rejection + Details --}}
    <div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="rejectionModalLabel">Rejection Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <div class="small text-muted mb-1">Reason</div>
                    <pre id="rejReason" class="mb-0 small" style="white-space:pre-wrap;">—</pre>
                </div>
                <div class="modal-footer">
                    <span class="text-muted small me-auto" id="rejWhen">—</span>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Locum Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="workflow-summary" class="mb-2">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span id="wf-badge" class="badge bg-secondary">Loading…</span>
                            <small id="wf-tooltip" class="text-muted"></small>
                        </div>
                        <div class="small">
                            <span class="text-muted">Pending with:</span> <span id="wf-pending-with">—</span>
                            <span class="text-muted ms-2">Step:</span> <span id="wf-pending-step">—</span>
                        </div>
                    </div>

                    <h6 class="mt-3 mb-2">Workflow Timeline</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle" id="wf-steps-table">
                            <thead>
                                <tr>
                                    <th style="width:28%">Step</th>
                                    <th style="width:22%">Attended By</th>
                                    <th style="width:22%">Forwarded By</th>
                                    <th style="width:12%">Status</th>
                                    <th style="width:16%">Acted At</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactions: pills, details loader, rejection modal fill --}}
    <script>
        (function() {
            const STATUS_COL = 4;
            const RX = {
                Pending: '^\\s*Pending',
                Approved: '^\\s*(Approved|HR Approved)\\s*$',
                Rejected: '^\\s*Rejected\\s*$'
            };

            function getDT() {
                if (window.jQuery && $.fn.dataTable && $.fn.dataTable.isDataTable('#locumTable')) {
                    return $('#locumTable').DataTable();
                }
                if (window.DataTable && typeof DataTable.get === 'function') {
                    return DataTable.get(document.getElementById('locumTable'));
                }
                return null;
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Status pills filtering
                document.querySelectorAll('#statusPills .btn-filter').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const dt = getDT();
                        if (!dt) return;
                        document.querySelectorAll('#statusPills .btn-filter').forEach(b => b
                            .classList.remove('active'));
                        this.classList.add('active');
                        const f = this.dataset.filter;
                        if (f === 'all') dt.column(STATUS_COL).search('', true, false).draw();
                        else dt.column(STATUS_COL).search(RX[f] || '', true, false).draw();
                    });
                });

                // Load details via AJAX
                document.querySelectorAll('.view-details').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        $.ajax({
                            url: '{{ route('locum-requests.showLocumRequest', ':id') }}'
                                .replace(':id', id),
                            method: 'GET',
                            success: function(data) {
                                const wf = data.workflow || {};
                                const $badge = $('#wf-badge');
                                const kind = (wf.current_class || 'secondary')
                                    .replace('bg-', '');
                                const badgeClass = {
                                    'success': 'bg-success',
                                    'warning': 'bg-warning',
                                    'danger': 'bg-danger',
                                    'secondary': 'bg-secondary'
                                } [kind] || 'bg-secondary';
                                $badge.removeClass(
                                    'bg-success bg-warning bg-danger bg-secondary'
                                    ).addClass(badgeClass).text(wf
                                    .current_label ?? '—');
                                $('#wf-tooltip').text(wf.tooltip || '');
                                $('#wf-pending-with').text(wf.pending_with || (wf
                                    .completed ? '—' : 'Unknown'));
                                $('#wf-pending-step').text(wf.pending_step || (wf
                                    .completed ? '—' : 'Pending'));

                                const $tbody = $('#wf-steps-table tbody').empty();
                                (wf.steps || []).forEach(step => {
                                    const sClass = step.status ===
                                        'Approved' ? 'bg-success' : (step
                                            .status === 'Rejected' ?
                                            'bg-danger' : 'bg-warning');
                                    const extra = step.rejection ?
                                        `<div class="small text-danger mt-1">${step.rejection}</div>` :
                                        (step.remark ?
                                            `<div class="small text-muted mt-1">${step.remark}</div>` :
                                            '');
                                    $tbody.append(`
                <tr>
                  <td><div class="fw-semibold">${step.step}</div>${extra}</td>
                  <td>${step.attended_by ?? '—'}</td>
                  <td>${step.forwarded_by ?? '—'}</td>
                  <td><span class="badge ${sClass}">${step.status}</span></td>
                  <td>${step.acted_at ?? '—'}</td>
                </tr>
              `);
                                });
                            },
                            error: function(xhr) {
                                alert('Failed to load details: ' + (xhr.responseJSON
                                    ?.message || 'Unknown error'));
                            }
                        });
                    });
                });

                // Rejection modal fill
                const rejModal = document.getElementById('rejectionModal');
                if (rejModal) {
                    rejModal.addEventListener('show.bs.modal', function(e) {
                        const btn = e.relatedTarget;
                        if (!btn) return;
                        const get = n => btn.getAttribute('data-' + n) || '—';
                        document.getElementById('rejEmployee').textContent = get('employee');
                        document.getElementById('rejDept').textContent = get('dept');
                        document.getElementById('rejMonth').textContent = get('month');
                        document.getElementById('rejStep').textContent = get('step');
                        document.getElementById('rejBy').textContent = get('by');
                        document.getElementById('rejWhen').textContent = get('when');
                        document.getElementById('rejReason').textContent = get('reason');
                    });
                }
            });
        })();
    </script>
@endsection
