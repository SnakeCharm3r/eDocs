@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

{{-- COMPACT + THEME STYLES --}}
<style>
    /* theme color */
    .nav-pills.theme-pills {
        --bs-primary: #198754;
    }

    /* pills base */
    .nav-pills.theme-pills .nav-link {
        border: 1px solid var(--bs-primary);
        color: var(--bs-primary);
        background: transparent;
        transition: .2s ease;
    }

    .nav-pills.theme-pills .nav-link:hover {
        background: rgba(0, 0, 0, .03);
    }

    .nav-pills.theme-pills .nav-link.active {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
        color: #fff;
    }

    .nav-pills.theme-pills .badge {
        transform: translateY(-1px);
    }

    /* compact pills */
    .nav-pills.theme-pills.nav-sm .nav-link {
        padding: .25rem .5rem;
        font-size: .85rem;
        line-height: 1.1;
        border-radius: .35rem;
    }

    .nav-pills.theme-pills.nav-sm .badge {
        font-size: .65rem;
        padding: .25em .45em;
    }

    /* compact cards/tables */
    .compact-card .card-body {
        padding: .75rem .75rem;
    }

    .request-table th,
    .request-table td {
        padding: .40rem .5rem;
        vertical-align: middle;
    }

    .request-table .badge {
        font-size: .7rem;
        padding: .35em .5em;
    }

    .request-table .nowrap {
        white-space: nowrap;
    }

    .request-table .truncate {
        display: inline-block;
        max-width: 240px;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: bottom;
    }

    @media (max-width: 1400px) {
        .request-table .truncate {
            max-width: 200px;
        }
    }

    @media (max-width: 1200px) {
        .request-table .truncate {
            max-width: 160px;
        }
    }

    /* slim buttons for actions */
    .btn-slim {
        padding: .15rem .4rem;
        font-size: .8rem;
        line-height: 1.1;
    }
</style>

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Header -->
            <div class="page-header mb-3">
                <div class="row align-items-center g-2">
                    <div class="col-12 col-lg-6">
                        <div class="page-sub-header">
                            <h3 class="page-title mb-0">Locum Agreements</h3>
                        </div>
                    </div>

                    {{-- TABS ON THE RIGHT --}}
                    <div class="col-12 col-lg-6">
                        <ul class="nav nav-pills theme-pills nav-sm justify-content-lg-end gap-2" id="locumTabs"
                            role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pending-tab" data-bs-toggle="pill"
                                    data-bs-target="#pending-pane" type="button" role="tab"
                                    aria-controls="pending-pane" aria-selected="true">
                                    Pending <span class="badge bg-warning ms-1">{{ $pending->count() }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="approved-tab" data-bs-toggle="pill"
                                    data-bs-target="#approved-pane" type="button" role="tab"
                                    aria-controls="approved-pane" aria-selected="false">
                                    Approved <span class="badge bg-success ms-1">{{ $approved->count() }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="rejected-tab" data-bs-toggle="pill"
                                    data-bs-target="#rejected-pane" type="button" role="tab"
                                    aria-controls="rejected-pane" aria-selected="false">
                                    Rejected <span class="badge bg-danger ms-1">{{ $rejected->count() }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Quick nav under header --}}
                <div class="row mt-3">
                    <div class="col-sm-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="btn-group" role="group" aria-label="Locum Navigation">
                                @php
                                    $pendingLocumAgreementCount = DB::table('work_flow_histories')
                                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                                        ->where('work_flow_histories.attended_by', Auth::id())
                                        ->where('work_flow_histories.status', 0)
                                        ->whereNotNull('workflows.locum_agreement_id')
                                        ->count();
                                @endphp

                                <a href="/locum-agreement-show" class="btn btn-primary btn-sm me-2 position-relative">
                                    Locum Agreements
                                    @if (($pendingLocumAgreementCount ?? 0) > 0)
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                                            {{ $pendingLocumAgreementCount }}
                                            <span class="visually-hidden">pending locum agreements</span>
                                        </span>
                                    @endif
                                </a>

                                <a href="{{ route('locum-requests.view') }}"
                                    class="btn btn-outline-primary btn-sm position-relative">
                                    Locum Requests
                                    @if (!empty($requests))
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                                            {{ $requests }}
                                            <span class="visually-hidden">pending requests</span>
                                        </span>
                                    @endif
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show py-2" role="alert">
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show py-2" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Oops! There were some problems:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @php
                $statusMap = $statusMap ?? [
                    0 => ['label' => 'Pending to Line Manager', 'class' => 'bg-warning'],
                    1 => ['label' => 'Pending to HR', 'class' => 'bg-info'],
                    2 => ['label' => 'Approved', 'class' => 'bg-success'],
                    3 => ['label' => 'Rejected by Line Manager', 'class' => 'bg-danger'],
                    4 => ['label' => 'Rejected by HR', 'class' => 'bg-danger'],
                ];

                $pending = $agreements->filter(fn($a) => in_array((int) $a->status, [0, 1]))->values();
                $approved = $agreements->filter(fn($a) => (int) $a->status === 2)->values();
                $rejected = $agreements->filter(fn($a) => in_array((int) $a->status, [3, 4]))->values();
            @endphp

            {{-- HR Summary --}}
            @if (auth()->user()->hasRole('hr'))
                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <h6 class="text-muted mb-1">Locum Agreements Summary</h6>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card shadow-sm h-100 compact-card">
                            <div class="card-body text-center">
                                <div class="small text-muted">Total</div>
                                <div class="h5 mb-0">{{ (int) ($hrStatusCounts->total ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card shadow-sm h-100 compact-card">
                            <div class="card-body text-center">
                                <div class="small text-muted">Pending to LM</div>
                                <div class="h5 mb-0">{{ (int) ($hrStatusCounts->pending_line_manager ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card shadow-sm h-100 compact-card">
                            <div class="card-body text-center">
                                <div class="small text-muted">Pending to HR</div>
                                <div class="h5 mb-0">{{ (int) ($hrStatusCounts->pending_hr ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card shadow-sm h-100 compact-card">
                            <div class="card-body text-center">
                                <div class="small text-muted">Approved</div>
                                <div class="h5 mb-0">{{ (int) ($hrStatusCounts->approved ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card shadow-sm h-100 compact-card">
                            <div class="card-body text-center">
                                <div class="small text-muted">Rejected</div>
                                <div class="h5 mb-0">{{ (int) ($hrStatusCounts->rejected ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="tab-content" id="locumTabsContent">
                {{-- Pending --}}
                <div class="tab-pane fade show active" id="pending-pane" role="tabpanel" aria-labelledby="pending-tab"
                    tabindex="0">
                    <div class="card mb-3 compact-card">
                        <div class="card-body">
                            <div class="table-responsive">
                                @if ($pending->isEmpty())
                                    <p class="mb-0">No pending locum agreements.</p>
                                @else
                                    <table id="locumTablePending"
                                        class="table table-sm table-striped table-hover request-table align-middle">
                                        <thead>
                                            <tr>
                                                <th style="display:none;">Created</th>
                                                <th style="width:50px;">#</th>
                                                <th>Staff Name</th>
                                                <th>Department</th>
                                                <th>CCBRT Code</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Locum Rate</th>
                                                <th>Status</th>
                                                <th class="nowrap">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($pending as $agreement)
                                                @php
                                                    $statusInfo = $statusMap[$agreement->status] ?? [
                                                        'label' => 'N/A',
                                                        'class' => 'bg-secondary',
                                                    ];
                                                @endphp
                                                <tr>
                                                    <td style="display:none;">
                                                        {{ \Carbon\Carbon::parse($agreement->created_at)->format('Y-m-d H:i:s') }}
                                                    </td>
                                                    <td></td> {{-- numbering filled by JS --}}
                                                    <td><span
                                                            class="truncate">{{ trim(($agreement->fname ?? '') . ' ' . ($agreement->lname ?? '')) ?: '—' }}</span>
                                                    </td>
                                                    <td><span
                                                            class="truncate">{{ $agreement->department_name ?? '—' }}</span>
                                                    </td>
                                                    <td class="nowrap">{{ $agreement->ccbrt_code ?? 'N/A' }}</td>
                                                    <td>{{ $agreement->start_date ? \Carbon\Carbon::parse($agreement->start_date)->format('M j, Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date)->format('M j, Y') : 'N/A' }}
                                                    </td>
                                                    <td class="nowrap">
                                                        {{ $agreement->locum_rate ? 'TZS ' . number_format($agreement->locum_rate, 0) : 'N/A' }}
                                                    </td>
                                                    <td><span
                                                            class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                                    </td>
                                                    <td class="nowrap">
                                                        <a href="{{ route('locum-agreements.show', $agreement->id) }}"
                                                            class="btn btn-outline-primary btn-slim" title="View">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Approved --}}
                <div class="tab-pane fade" id="approved-pane" role="tabpanel" aria-labelledby="approved-tab"
                    tabindex="0">
                    <div class="card mb-3 compact-card">
                        <div class="card-body">
                            <div class="table-responsive">
                                @if ($approved->isEmpty())
                                    <p class="mb-0">No approved locum agreements.</p>
                                @else
                                    <table id="locumTableApproved"
                                        class="table table-sm table-striped table-hover request-table align-middle">
                                        <thead>
                                            <tr>
                                                <th style="display:none;">Created</th>
                                                <th style="width:50px;">#</th>
                                                <th>Staff Name</th>
                                                <th>Department</th>
                                                <th>CCBRT Code</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Locum Rate</th>
                                                <th>Status</th>
                                                <th class="nowrap">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($approved as $agreement)
                                                @php
                                                    $statusInfo = $statusMap[$agreement->status] ?? [
                                                        'label' => 'N/A',
                                                        'class' => 'bg-secondary',
                                                    ];
                                                @endphp
                                                <tr>
                                                    <td style="display:none;">
                                                        {{ \Carbon\Carbon::parse($agreement->created_at)->format('Y-m-d H:i:s') }}
                                                    </td>
                                                    <td></td>
                                                    <td><span
                                                            class="truncate">{{ trim(($agreement->fname ?? '') . ' ' . ($agreement->lname ?? '')) ?: '—' }}</span>
                                                    </td>
                                                    <td><span
                                                            class="truncate">{{ $agreement->department->dept_name ?? '—' }}</span>
                                                    </td>
                                                    <td class="nowrap">{{ $agreement->ccbrt_code ?? 'N/A' }}</td>
                                                    <td>{{ $agreement->start_date ? \Carbon\Carbon::parse($agreement->start_date)->format('M j, Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date)->format('M j, Y') : 'N/A' }}
                                                    </td>
                                                    <td class="nowrap">
                                                        {{ $agreement->locum_rate ? 'TZS ' . number_format($agreement->locum_rate, 0) : 'N/A' }}
                                                    </td>
                                                    <td><span
                                                            class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                                    </td>
                                                    <td class="nowrap">
                                                        <a href="{{ route('locum-agreements.show', $agreement->id) }}"
                                                            class="btn btn-outline-primary btn-slim" title="View">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rejected --}}
                <div class="tab-pane fade" id="rejected-pane" role="tabpanel" aria-labelledby="rejected-tab"
                    tabindex="0">
                    <div class="card mb-3 compact-card">
                        <div class="card-body">
                            <div class="table-responsive">
                                @if ($rejected->isEmpty())
                                    <p class="mb-0">No rejected locum agreements.</p>
                                @else
                                    <table id="locumTableRejected"
                                        class="table table-sm table-striped table-hover request-table align-middle">
                                        <thead>
                                            <tr>
                                                <th style="display:none;">Created</th>
                                                <th style="width:50px;">#</th>
                                                <th>Staff Name</th>
                                                <th>Department</th>
                                                <th>CCBRT Code</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Locum Rate</th>
                                                <th>Status</th>
                                                <th class="nowrap">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($rejected as $agreement)
                                                @php
                                                    $statusInfo = $statusMap[$agreement->status] ?? [
                                                        'label' => 'N/A',
                                                        'class' => 'bg-secondary',
                                                    ];
                                                    $reason = trim($agreement->rejection_status ?? '');
                                                @endphp
                                                <tr>
                                                    <td style="display:none;">
                                                        {{ \Carbon\Carbon::parse($agreement->created_at)->format('Y-m-d H:i:s') }}
                                                    </td>
                                                    <td></td>
                                                    <td><span
                                                            class="truncate">{{ trim(($agreement->fname ?? '') . ' ' . ($agreement->lname ?? '')) ?: '—' }}</span>
                                                    </td>
                                                    <td><span
                                                            class="truncate">{{ $agreement->department->dept_name ?? '—' }}</span>
                                                    </td>
                                                    <td class="nowrap">{{ $agreement->ccbrt_code ?? 'N/A' }}</td>
                                                    <td>{{ $agreement->start_date ? \Carbon\Carbon::parse($agreement->start_date)->format('M j, Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date)->format('M j, Y') : 'N/A' }}
                                                    </td>
                                                    <td class="nowrap">
                                                        {{ $agreement->locum_rate ? 'TZS ' . number_format($agreement->locum_rate, 0) : 'N/A' }}
                                                    </td>
                                                    <td>
                                                        <span role="button"
                                                            class="badge {{ $statusInfo['class'] }} js-reject-badge"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{ $reason !== '' ? $reason : 'No reason provided' }}"
                                                            data-modal-reason="{{ $reason !== '' ? $reason : 'No reason provided' }}"
                                                            data-modal-name="{{ trim(($agreement->fname ?? '') . ' ' . ($agreement->lname ?? '')) ?: '—' }}"
                                                            data-modal-status="{{ $statusInfo['label'] }}"
                                                            data-modal-created="{{ \Carbon\Carbon::parse($agreement->created_at)->format('F j, Y H:i') }}">
                                                            {{ $statusInfo['label'] }}
                                                        </span>
                                                    </td>
                                                    <td class="nowrap">
                                                        <a href="{{ route('locum-agreements.show', $agreement->id) }}"
                                                            class="btn btn-outline-primary btn-slim" title="View">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back -->
            <div class="d-flex my-2">
                <a href="/locum-agreement-show" class="btn btn-secondary btn-slim">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    {{-- DataTables inits (newest first via hidden Created col index 0) --}}
    @include('components.datatable', [
        'id' => 'locumTablePending',
        'options' => ['order' => [[0, 'desc']]],
    ])
    @include('components.datatable', [
        'id' => 'locumTableApproved',
        'options' => ['order' => [[0, 'desc']]],
    ])
    @include('components.datatable', [
        'id' => 'locumTableRejected',
        'options' => ['order' => [[0, 'desc']]],
    ])

    {{-- REJECTION DETAILS MODAL --}}
    <div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-labelledby="rejectReasonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="rejectReasonModalLabel">Rejection details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Staff</dt>
                        <dd class="col-sm-8" id="rejStaff">—</dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8" id="rejStatus">Rejected</dd>
                        <dt class="col-sm-4">Created</dt>
                        <dd class="col-sm-8" id="rejCreated">—</dd>
                        <dt class="col-sm-4">Reason</dt>
                        <dd class="col-sm-8" id="rejReason" class="text-break">—</dd>
                    </dl>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- TOOLTIP + MODAL INIT + ROW NUMBERING --}}
    <script>
        (function() {
            // Enable Bootstrap tooltips
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                new bootstrap.Tooltip(el);
            });

            // Rejection details modal
            document.addEventListener('click', function(e) {
                const t = e.target.closest('.js-reject-badge');
                if (!t) return;
                document.getElementById('rejStaff').textContent = t.getAttribute('data-modal-name') || '—';
                document.getElementById('rejStatus').textContent = t.getAttribute('data-modal-status') ||
                    'Rejected';
                document.getElementById('rejCreated').textContent = t.getAttribute('data-modal-created') || '—';
                document.getElementById('rejReason').textContent = t.getAttribute('data-modal-reason') ||
                    'No reason provided';
                new bootstrap.Modal(document.getElementById('rejectReasonModal')).show();
            }, false);

            // ---- Row numbering helper (works with DataTables v2 "new DataTable") ----
            function renumber(tableId, numColIdx) {
                var el = document.getElementById(tableId);
                if (!el) return;
                var dt = (window.DataTable && typeof DataTable.get === 'function') ? DataTable.get(el) : null;
                if (!dt) return;

                var doNumber = function() {
                    var rows = dt.rows({
                        page: 'current'
                    }).nodes();
                    var i = 1;
                    rows.forEach(function(row) {
                        var cells = row.querySelectorAll('td');
                        if (cells && cells[numColIdx]) cells[numColIdx].textContent = i++;
                    });
                };
                dt.on('draw', doNumber);
                doNumber();
            }

            // After a tiny delay to ensure the component initialized DataTables
            window.addEventListener('load', function() {
                setTimeout(function() {
                    // hidden "Created" is col 0, so "#" column is index 1
                    renumber('locumTablePending', 1);
                    renumber('locumTableApproved', 1);
                    renumber('locumTableRejected', 1);
                }, 50);
            });
        })();
    </script>
@endsection
