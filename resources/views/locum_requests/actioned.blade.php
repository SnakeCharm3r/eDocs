@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .request-table th,
        .request-table td {
            border: 1px solid #000;
            padding: 10px;
        }

        .request-table th {
            background: #f8f8f8;
        }

        .status-badge {
            font-size: 0.9em;
            padding: 0.4em 0.8em;
        }

        .status-badge.bg-success {
            background-color: #198754 !important;
            color: #fff;
        }

        .status-badge.bg-danger {
            background-color: #dc3545 !important;
            color: #fff;
        }

        .status-badge.bg-warning {
            background-color: #ffc107 !important;
            color: #000;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .filter-chip {
            font-size: .9rem;
        }

        .page-sub-header .btn-group .btn {
            min-width: 9.5rem;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <div class="btn-group" role="group" aria-label="Locum Navigation">
                                <a href="/locum-agreement-show" class="btn btn-outline-primary btn-sm me-2">
                                    Locum Agreements
                                </a>
                                <a href="{{ route('locum-requests.view') }}" class="btn btn-outline-primary btn-sm me-2">
                                    Locum Requests
                                </a>
                                <a href="{{ route('locum-requests.report') }}" class="btn btn-primary btn-sm">
                                    Reports
                                </a>
                            </div>
                            <h4 class="page-title mb-0">Locum Requests</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
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

            {{-- ===== Department Summary (with Year/Month filters for the Summary only) ===== --}}
            <form method="GET" action="{{ route('locum-requests.report') }}" class="row g-2 align-items-end mb-2">
                <div class="col-auto">
                    <label for="summary_year" class="form-label mb-0">Year</label>
                    <select id="summary_year" name="summary_year" class="form-select form-select-sm">
                        <option value="">All</option>
                        @php
                            $__years = isset($summaryYearOptions)
                                ? $summaryYearOptions
                                : (isset($actionedRequests)
                                    ? $actionedRequests
                                        ->filter(fn($r) => !is_null($r->created_at))
                                        ->map(fn($r) => \Carbon\Carbon::parse($r->created_at)->year)
                                        ->unique()
                                        ->sortDesc()
                                        ->values()
                                    : collect());
                        @endphp
                        @foreach ($__years as $y)
                            <option value="{{ $y }}" @selected((string) ($summaryYear ?? '') === (string) $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <label for="summary_month" class="form-label mb-0">Month</label>
                    <select id="summary_month" name="summary_month" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $num => $name)
                            <option value="{{ $num }}" @selected((string) ($summaryMonth ?? '') === (string) $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                    @if (!empty($summaryYear) || !empty($summaryMonth))
                        <a href="{{ route('locum-requests.actioned') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>

            @if (isset($deptSummary) && $deptSummary->count())
                <div class="card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-muted">Department Summary</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover mb-0">
                                <thead class="table-secondary">
                                <tr>
                                    <th>#</th>
                                    <th>Department</th>
                                    <th class="text-end">Requests</th>
                                    <th class="text-end">Employees</th>
                                    <th class="text-end text-success">Approved</th>
                                    <th class="text-end">Hours</th>
                                    <th class="text-end">Amount (TZS)</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($deptSummary as $dept => $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $dept }}</td>
                                        <td class="text-end">{{ number_format($row['requests'] ?? 0) }}</td>
                                        <td class="text-end">{{ number_format($row['employees'] ?? 0) }}</td>
                                        <td class="text-end text-success">{{ number_format($row['approved'] ?? 0) }}
                                        </td>
                                        <td class="text-end">{{ number_format($row['hours'] ?? 0, 2) }}</td>
                                        <td class="text-end">{{ number_format($row['amount'] ?? 0, 2, '.', ',') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot class="table-secondary">
                                <tr>
                                    <th>#</th>
                                    <th>Grand Totals (Approved Only)</th>
                                    <th class="text-end">{{ number_format($grandTotals['requests'] ?? 0) }}</th>
                                    <th class="text-end">{{ number_format($grandTotals['employees'] ?? 0) }}</th>
                                    <th class="text-end text-success">
                                        {{ number_format($grandTotals['approved'] ?? 0) }}</th>
                                    <th class="text-end">{{ number_format($grandTotals['hours'] ?? 0, 2) }}</th>
                                    <th class="text-end">{{ number_format($grandTotals['amount'] ?? 0, 2, '.', ',') }}</th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Filters + Export -->
            <p>
                <a class="btn btn-primary btn-sm" data-bs-toggle="collapse" href="#filterCollapse" role="button"
                   aria-expanded="true" aria-controls="filterCollapse">
                    Filter & Export
                </a>
            </p>

            <div class="collapse show" id="filterCollapse">
                <div class="card card-body mb-3">
                    <form id="filterForm" class="row g-3 align-items-center" method="GET"
                          action="{{ route('locum-requests.actioned.export') }}" target="_blank">

                        @php
                            $years = $actionedRequests
                                ->filter(fn($r) => !is_null($r->created_at))
                                ->map(fn($r) => \Carbon\Carbon::parse($r->created_at)->year)
                                ->unique()
                                ->sortDesc();

                            $deptNames = $actionedRequests
                                ->map(fn($r) => $r->user?->department?->dept_name)
                                ->filter()
                                ->unique()
                                ->sort();
                        @endphp

                        {{-- Year --}}
                        <div class="col-auto">
                            <label for="filterYear" class="col-form-label">Year</label>
                        </div>
                        <div class="col-auto">
                            <select id="filterYear" name="year" class="form-select">
                                <option value="">All Years</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Month --}}
                        <div class="col-auto">
                            <label for="filterMonth" class="col-form-label">Month</label>
                        </div>
                        <div class="col-auto">
                            <select id="filterMonth" name="month" class="form-select">
                                <option value="">All Months</option>
                                @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $num => $name)
                                    <option value="{{ $num }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Department --}}
                        <div class="col-auto">
                            <label for="filterDept" class="col-form-label">Department</label>
                        </div>
                        <div class="col-auto">
                            <select id="filterDept" name="department" class="form-select">
                                <option value="_all">All Departments</option>
                                @foreach ($deptNames as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Basis --}}
                        <div class="col-auto">
                            <label for="filterBasis" class="col-form-label">Basis</label>
                        </div>
                        <div class="col-auto">
                            <select id="filterBasis" name="basis" class="form-select">
                                <option value="approved" selected>Approved Month (Payment)</option>
                                <option value="locum">Claim Month</option>
                                <option value="created">Submitted Month</option>
                            </select>
                        </div>

                        {{-- Export HR Approved only --}}
                        <div class="col-auto">
                            <button type="submit" class="btn btn-outline-success btn-sm" name="hr" value="1">
                                <i class="fas fa-file-excel"></i> Export Approved
                            </button>
                        </div>

                        {{-- Visual chips --}}
                        <div class="col-12">
                            <span id="chipYear" class="badge rounded-pill text-bg-light filter-chip d-none">Year:
                                —</span>
                            <span id="chipMonth" class="badge rounded-pill text-bg-light filter-chip d-none">Month:
                                —</span>
                            <span id="chipDept" class="badge rounded-pill text-bg-light filter-chip d-none">Department:
                                —</span>
                            <span id="chipBasis" class="badge rounded-pill text-bg-light filter-chip d-none">Basis:
                                —</span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Grouped Requests by Year and Month -->
            @php
                // Group by created_at Year and locum_month Month (fallback to created_at month)
                $groupedRequests = $actionedRequests
                    ->filter(fn($r) => !is_null($r->created_at))
                    ->groupBy([
                        fn($r) => \Carbon\Carbon::parse($r->created_at)->year,
                        function ($r) {
                            try {
                                return $r->locum_month
                                    ? \Carbon\Carbon::parse($r->locum_month)->month
                                    : \Carbon\Carbon::parse($r->created_at)->month;
                            } catch (\Throwable $e) {
                                return \Carbon\Carbon::parse($r->created_at)->month;
                            }
                        },
                    ])
                    ->sortKeysDesc();
            @endphp

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="accordion" id="requestsAccordion">
                                @forelse ($groupedRequests as $year => $months)
                                    <div class="accordion-item year-acc" data-year="{{ $year }}">
                                        <h2 class="accordion-header" id="heading-{{ $year }}">
                                            <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapse-{{ $year }}"
                                                    aria-expanded="false" aria-controls="collapse-{{ $year }}">
                                                {{ $year }}
                                            </button>
                                        </h2>
                                        <div id="collapse-{{ $year }}" class="accordion-collapse collapse"
                                             aria-labelledby="heading-{{ $year }}"
                                             data-bs-parent="#requestsAccordion">
                                            <div class="accordion-body">
                                                <div class="accordion" id="monthsAccordion-{{ $year }}">
                                                    @foreach ($months as $month => $requests)
                                                        @php $monthName = \Carbon\Carbon::create()->month($month)->format('F'); @endphp
                                                        <div class="accordion-item month-acc"
                                                             data-month="{{ $month }}">
                                                            <h3 class="accordion-header"
                                                                id="heading-{{ $year }}-{{ $month }}">
                                                                <button class="accordion-button collapsed" type="button"
                                                                        data-bs-toggle="collapse"
                                                                        data-bs-target="#collapse-{{ $year }}-{{ $month }}"
                                                                        aria-expanded="false"
                                                                        aria-controls="collapse-{{ $year }}-{{ $month }}">
                                                                    {{ $monthName }}
                                                                </button>
                                                            </h3>
                                                            <div id="collapse-{{ $year }}-{{ $month }}"
                                                                 class="accordion-collapse collapse"
                                                                 aria-labelledby="heading-{{ $year }}-{{ $month }}"
                                                                 data-bs-parent="#monthsAccordion-{{ $year }}">
                                                                <div class="accordion-body">
                                                                    <div class="table-responsive">
                                                                        <table
                                                                            class="table table-striped table-hover table-bordered request-table w-100"
                                                                            id="table-{{ $year }}-{{ $month }}">
                                                                            <thead class="table-success">
                                                                            <tr>
                                                                                <th>#</th>
                                                                                <th>Employee</th>
                                                                                <th>Department</th>
                                                                                <th>Claim Month</th>
                                                                                <th>Submitted On</th>
                                                                                <th>HR Approved On</th>
                                                                                <th>Days</th>
                                                                                <th>Hours</th>
                                                                                <th>Amount (TZS)</th>
                                                                                <th>Status</th>
                                                                            </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                            @foreach ($requests as $index => $request)
                                                                                @php
                                                                                    $deptName =
                                                                                        $request->user?->department
                                                                                            ?->dept_name ?? 'N/A';

                                                                                    // Latest action by current user (already loaded)
                                                                                    $histories = $request->workflow
                                                                                        ? $request->workflow
                                                                                            ->histories
                                                                                        : collect();
                                                                                    $last = $histories
                                                                                        ->sortByDesc('updated_at')
                                                                                        ->first();

                                                                                    // Try locate HR approval (if this viewer is HR, it will exist; otherwise may be null)
                                                                                    $hrApprovedHistory = $request->workflow?->histories
                                                                                        ?->where(
                                                                                            'step_name',
                                                                                            'HR Approval',
                                                                                        )
                                                                                        ->where('status', 1)
                                                                                        ->sortByDesc('updated_at')
                                                                                        ->first();

                                                                                    $submittedOn = $request->created_at
                                                                                        ? \Carbon\Carbon::parse(
                                                                                            $request->created_at,
                                                                                        )->format('Y-m-d H:i')
                                                                                        : '—';
                                                                                    $hrApprovedOn = $hrApprovedHistory
                                                                                        ? \Carbon\Carbon::parse(
                                                                                            $hrApprovedHistory->updated_at ??
                                                                                                $hrApprovedHistory->created_at,
                                                                                        )->format('Y-m-d H:i')
                                                                                        : '—';

                                                                                    // Claim month text
                                                                                    try {
                                                                                        $claimText = $request->locum_month
                                                                                            ? \Carbon\Carbon::parse(
                                                                                                $request->locum_month,
                                                                                            )->format('F Y')
                                                                                            : \Carbon\Carbon::parse(
                                                                                                $request->created_at,
                                                                                            )->format('F Y');
                                                                                    } catch (\Throwable $e) {
                                                                                        try {
                                                                                            $claimText = $request->locum_month
                                                                                                ? \Carbon\Carbon::parse(
                                                                                                    '1 ' .
                                                                                                        $request->locum_month,
                                                                                                )->format('F Y')
                                                                                                : \Carbon\Carbon::parse(
                                                                                                    $request->created_at,
                                                                                                )->format('F Y');
                                                                                        } catch (\Throwable $e2) {
                                                                                            $claimText =
                                                                                                $request->locum_month ?:
                                                                                                '—';
                                                                                        }
                                                                                    }

                                                                                    $hasThree =
                                                                                        optional(
                                                                                            optional($request->user)
                                                                                                ->department,
                                                                                        )
                                                                                            ->has_three_level_approval ??
                                                                                        false;

                                                                                    $pendingLabel = function (
                                                                                        ?string $step,
                                                                                    ) {
                                                                                        return match ($step) {
                                                                                            'Line Manager Approval'
                                                                                                => 'Pending Line Manager Approval',
                                                                                            'HEC Approval'
                                                                                                => 'Pending HEC Approval',
                                                                                            'HR Approval'
                                                                                                => 'Pending HR Approval',
                                                                                            default => 'Pending',
                                                                                        };
                                                                                    };

                                                                                    $map3 = [
                                                                                        1 => [
                                                                                            'label' =>
                                                                                                'Pending Line Manager Approval',
                                                                                            'class' => 'bg-warning',
                                                                                        ],
                                                                                        2 => [
                                                                                            'label' =>
                                                                                                'Pending HEC Approval',
                                                                                            'class' => 'bg-info',
                                                                                        ],
                                                                                        3 => [
                                                                                            'label' =>
                                                                                                'Pending HR Approval',
                                                                                            'class' => 'bg-primary',
                                                                                        ],
                                                                                        4 => [
                                                                                            'label' => 'Approved',
                                                                                            'class' => 'bg-success',
                                                                                        ],
                                                                                        5 => [
                                                                                            'label' => 'Rejected',
                                                                                            'class' => 'bg-danger',
                                                                                        ],
                                                                                    ];
                                                                                    $map2 = [
                                                                                        1 => [
                                                                                            'label' =>
                                                                                                'Pending Line Manager Approval',
                                                                                            'class' => 'bg-warning',
                                                                                        ],
                                                                                        2 => [
                                                                                            'label' =>
                                                                                                'Pending HR Approval',
                                                                                            'class' => 'bg-primary',
                                                                                        ],
                                                                                        3 => [
                                                                                            'label' => 'Approved',
                                                                                            'class' => 'bg-success',
                                                                                        ],
                                                                                        4 => [
                                                                                            'label' => 'Rejected',
                                                                                            'class' => 'bg-danger',
                                                                                        ],
                                                                                        5 => [
                                                                                            'label' => 'Rejected',
                                                                                            'class' => 'bg-danger',
                                                                                        ],
                                                                                    ];
                                                                                    $map = $hasThree
                                                                                        ? $map3
                                                                                        : $map2;

                                                                                    $label = 'Pending';
                                                                                    $class = 'bg-warning';
                                                                                    $tooltip = null;

                                                                                    if ($last) {
                                                                                        $code =
                                                                                            (int) ($last->locum_request_status ??
                                                                                                0);

                                                                                        if (
                                                                                            (string) $last->status ===
                                                                                                '2' ||
                                                                                            $code === 5
                                                                                        ) {
                                                                                            $label = 'Rejected';
                                                                                            $class = 'bg-danger';
                                                                                            if (
                                                                                                !empty(
                                                                                                    $last->rejection_reason
                                                                                                )
                                                                                            ) {
                                                                                                $tooltip =
                                                                                                    $last->rejection_reason;
                                                                                            }
                                                                                        } elseif (
                                                                                            $request->workflow &&
                                                                                            (int) $request->workflow
                                                                                                ->work_flow_completed ===
                                                                                                1
                                                                                        ) {
                                                                                            if (
                                                                                                $hrApprovedHistory
                                                                                            ) {
                                                                                                $label =
                                                                                                    'HR Approved';
                                                                                            } else {
                                                                                                $label = 'Approved';
                                                                                            }
                                                                                            $class = 'bg-success';
                                                                                        } else {
                                                                                            $nextPending = $histories->firstWhere(
                                                                                                'status',
                                                                                                0,
                                                                                            );
                                                                                            if ($nextPending) {
                                                                                                $label = $pendingLabel(
                                                                                                    $nextPending->step_name,
                                                                                                );
                                                                                                $class =
                                                                                                    'bg-warning';
                                                                                            } elseif (
                                                                                                $code &&
                                                                                                isset($map[$code])
                                                                                            ) {
                                                                                                $label =
                                                                                                    $map[$code][
                                                                                                        'label'
                                                                                                    ];
                                                                                                $class =
                                                                                                    $map[$code][
                                                                                                        'class'
                                                                                                    ];
                                                                                            } else {
                                                                                                if (
                                                                                                    (string) $last->status ===
                                                                                                    '0'
                                                                                                ) {
                                                                                                    $label = $pendingLabel(
                                                                                                        $last->step_name,
                                                                                                    );
                                                                                                    $class =
                                                                                                        'bg-warning';
                                                                                                } elseif (
                                                                                                    (string) $last->status ===
                                                                                                    '1'
                                                                                                ) {
                                                                                                    $label =
                                                                                                        $last->step_name ===
                                                                                                        'HR Approval'
                                                                                                            ? 'HR Approved'
                                                                                                            : 'Approved';
                                                                                                    $class =
                                                                                                        'bg-success';
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }

                                                                                    $employeeName = $request->user
                                                                                        ? $request->user
                                                                                                ->username ??
                                                                                            trim(
                                                                                                ($request->user
                                                                                                    ->fname ??
                                                                                                    '') .
                                                                                                    ' ' .
                                                                                                    ($request->user
                                                                                                        ->mname ??
                                                                                                        '') .
                                                                                                    ' ' .
                                                                                                    ($request->user
                                                                                                        ->lname ??
                                                                                                        ''),
                                                                                            )
                                                                                        : 'N/A';

                                                                                    try {
                                                                                        $monthText = $request->locum_month
                                                                                            ? \Carbon\Carbon::parse(
                                                                                                $request->locum_month,
                                                                                            )->format('F Y')
                                                                                            : \Carbon\Carbon::parse(
                                                                                                $request->created_at,
                                                                                            )->format('F Y');
                                                                                    } catch (\Throwable $e) {
                                                                                        $monthText = $request->created_at
                                                                                            ? \Carbon\Carbon::parse(
                                                                                                $request->created_at,
                                                                                            )->format('F Y')
                                                                                            : '—';
                                                                                    }

                                                                                    $actedBy =
                                                                                        $last?->user?->username ??
                                                                                        (($last?->user
                                                                                            ? trim(
                                                                                                ($last->user
                                                                                                    ->fname ??
                                                                                                    '') .
                                                                                                    ' ' .
                                                                                                    ($last->user
                                                                                                        ->lname ??
                                                                                                        ''),
                                                                                            )
                                                                                            : null) ??
                                                                                            '—');

                                                                                    $actedAt = optional(
                                                                                        $last?->updated_at ??
                                                                                            $last?->created_at,
                                                                                    )?->format('d M Y H:i');
                                                                                @endphp

                                                                                <tr data-dept="{{ $deptName }}">
                                                                                    <td>{{ $index + 1 }}</td>
                                                                                    <td>{{ $employeeName }}</td>
                                                                                    <td>{{ $deptName }}</td>
                                                                                    <td>{{ $claimText }}</td>
                                                                                    <td>{{ $submittedOn }}</td>
                                                                                    <td>{{ $hrApprovedOn }}</td>
                                                                                    <td>{{ (int) ($request->number_of_days ?? 0) }}
                                                                                    </td>
                                                                                    <td>{{ number_format($request->total_hours ?? 0, 2) }}
                                                                                    </td>
                                                                                    <td>{{ number_format($request->total_amount_payable ?? 0, 2, '.', ',') }}
                                                                                    </td>

                                                                                    <td>
                                                                                            <span
                                                                                                class="badge status-badge {{ $class }}"
                                                                                                @if ($tooltip) title="{{ $tooltip }}" @endif>
                                                                                                {{ $label }}
                                                                                            </span>

                                                                                        @if (strtolower($label) === 'rejected' && $last && !empty($last->rejection_reason))
                                                                                            <button type="button"
                                                                                                    class="btn btn-outline-danger btn-sm ms-2 rejection-btn"
                                                                                                    data-bs-toggle="modal"
                                                                                                    data-bs-target="#rejectionModal"
                                                                                                    data-employee="{{ $employeeName }}"
                                                                                                    data-dept="{{ $deptName }}"
                                                                                                    data-month="{{ $monthText }}"
                                                                                                    data-step="{{ $last->step_name ?? '—' }}"
                                                                                                    data-reason="{{ e($last->rejection_reason) }}"
                                                                                                    data-by="{{ $actedBy }}"
                                                                                                    data-when="{{ $actedAt }}">
                                                                                                <i
                                                                                                    class="fas fa-eye"></i>
                                                                                            </button>
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="alert alert-info text-center" id="no-results">
                                        No actioned locum requests found.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="rejectionModalLabel">Rejection Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2 small text-muted">Employee</div>
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
                    <pre id="rejReason" class="mb-0 small" style="white-space: pre-wrap;">—</pre>
                </div>

                <div class="modal-footer">
                    <span class="text-muted small me-auto" id="rejWhen">—</span>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTables CSS & JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(function() {
            // Init DataTables for each table
            $('table.request-table').each(function() {
                $(this).DataTable({
                    pageLength: 10,
                    ordering: true,
                    searching: true,
                    paging: true,
                    info: true,
                    language: {
                        emptyTable: "No locum requests found",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries"
                    }
                });
            });

            // Chips update (visual)
            function updateChips() {
                const y = $('#filterYear').val();
                const m = $('#filterMonth').val();
                const d = $('#filterDept').val();
                const b = $('#filterBasis').val();

                const mName = $('#filterMonth option:selected').text();
                const dName = $('#filterDept option:selected').text();
                const bName = $('#filterBasis option:selected').text();

                if (y) $('#chipYear').removeClass('d-none').text('Year: ' + y);
                else $('#chipYear').addClass('d-none');
                if (m) $('#chipMonth').removeClass('d-none').text('Month: ' + mName);
                else $('#chipMonth').addClass('d-none');
                if (d && d !== '_all') $('#chipDept').removeClass('d-none').text('Department: ' + dName);
                else $('#chipDept').addClass('d-none');
                if (b) $('#chipBasis').removeClass('d-none').text('Basis: ' + bName);
                else $('#chipBasis').addClass('d-none');
            }

            // Client-side dept filter for table rows (visual only)
            function applyFilters() {
                const dept = $('#filterDept').val();
                $('table.request-table tbody tr').each(function() {
                    const rowDept = ($(this).attr('data-dept') || '').toString();
                    const show = !dept || dept === '_all' || rowDept === dept;
                    $(this).toggle(show);
                });
            }

            $('#filterYear, #filterMonth, #filterDept, #filterBasis').on('change', function() {
                updateChips();
                applyFilters();
            });

            updateChips();
            applyFilters();

            // Fill rejection modal when shown
            $('#rejectionModal').on('show.bs.modal', function(e) {
                const btn = $(e.relatedTarget);
                $('#rejEmployee').text(btn.data('employee') || '—');
                $('#rejDept').text(btn.data('dept') || '—');
                $('#rejMonth').text(btn.data('month') || '—');
                $('#rejStep').text(btn.data('step') || '—');
                $('#rejBy').text(btn.data('by') || '—');
                $('#rejWhen').text(btn.data('when') || '—');
                $('#rejReason').text(btn.data('reason') || '—'); // <pre> preserves newlines
            });
        });
    </script>
@endsection
