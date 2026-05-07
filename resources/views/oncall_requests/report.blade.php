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
            background-color: #61ce70 !important;
            color: #fff;
        }

        .status-badge.bg-danger {
            background-color: #dc3545 !important;
            color: #fff;
        }

        /* Export button loading state */
        .js-export-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .js-export-btn .js-spinner {
            display: inline-block;
        }

        .js-export-btn .js-spinner.d-none {
            display: none !important;
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
                            <div class="btn-group" role="group" aria-label="OnCall Navigation">
                                <a href="{{ route('oncall_requests.index') }}" class="btn btn-outline-primary btn-sm me-2">
                                    OnCall Requests
                                </a>
                                <a href="{{ route('oncall_requests.report') }}" class="btn btn-primary btn-sm">
                                    Reports
                                </a>
                            </div>
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

            {{-- ===== Analytics & Trends Dashboard ===== --}}
            <div class="card mb-4" style="border-color: #61ce70;">
                <div class="card-header text-white" style="background-color: #61ce70;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 text-white">
                            <i class="fas fa-chart-line me-2"></i>
                            Analytics & Trends Dashboard
                        </h5>
                        <form method="GET" action="{{ route('oncall_requests.report') }}"
                            class="d-flex gap-2 align-items-center flex-wrap">
                            @php
                                $analyticsYears = isset($summaryYearOptions)
                                    ? $summaryYearOptions
                                    : (isset($actionedRequests)
                                        ? $actionedRequests
                                            ->filter(fn($r) => !is_null($r->created_at))
                                            ->map(fn($r) => \Carbon\Carbon::parse($r->created_at)->year)
                                            ->unique()
                                            ->sortDesc()
                                            ->values()
                                        : collect());
                                $selectedFromYear = request()->query('from_year');
                                $selectedFromMonth = request()->query('from_month');
                                $selectedToYear = request()->query('to_year');
                                $selectedToMonth = request()->query('to_month');
                                $selectedDept = request()->query('analytics_dept');
                            @endphp

                            {{-- Department Filter --}}
                            <div class="d-flex align-items-center gap-1">
                                <span class="text-white small">Dept:</span>
                                <select id="analytics_dept" name="analytics_dept" class="form-select form-select-sm"
                                    style="width: auto; min-width: 150px;">
                                    <option value="_all">All Departments</option>
                                    @foreach ($allDepartments ?? [] as $dept)
                                        <option value="{{ $dept->dept_name }}" 
                                            @selected($selectedDept === $dept->dept_name)>
                                            {{ $dept->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Date Range Filter: From --}}
                            <div class="d-flex align-items-center gap-1">
                                <span class="text-white small">From:</span>
                                <select id="from_year" name="from_year" class="form-select form-select-sm"
                                    style="width: auto; min-width: 100px;">
                                    <option value="">Year</option>
                                    @foreach ($analyticsYears as $y)
                                        <option value="{{ $y }}" @selected((string) ($selectedFromYear ?? '') === (string) $y)>{{ $y }}
                                        </option>
                                    @endforeach
                                </select>
                                <select id="from_month" name="from_month" class="form-select form-select-sm"
                                    style="width: auto; min-width: 120px;">
                                    <option value="">Month</option>
                                    @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $num => $name)
                                        <option value="{{ $num }}" @selected((string) ($selectedFromMonth ?? '') === (string) $num)>{{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Date Range Filter: To --}}
                            <div class="d-flex align-items-center gap-1">
                                <span class="text-white small">To:</span>
                                <select id="to_year" name="to_year" class="form-select form-select-sm"
                                    style="width: auto; min-width: 100px;">
                                    <option value="">Year</option>
                                    @foreach ($analyticsYears as $y)
                                        <option value="{{ $y }}" @selected((string) ($selectedToYear ?? '') === (string) $y)>
                                            {{ $y }}</option>
                                    @endforeach
                                </select>
                                <select id="to_month" name="to_month" class="form-select form-select-sm"
                                    style="width: auto; min-width: 120px;">
                                    <option value="">Month</option>
                                    @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $num => $name)
                                        <option value="{{ $num }}" @selected((string) ($selectedToMonth ?? '') === (string) $num)>
                                            {{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-sm"
                                style="background-color: #fff; border-color: #fff; color: #61ce70;">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            @if (!empty($selectedFromYear) || !empty($selectedFromMonth) || !empty($selectedToYear) || !empty($selectedToMonth) || !empty($selectedDept))
                                <a href="{{ route('oncall_requests.report') }}" class="btn btn-sm"
                                    style="background-color: #fff; border-color: #fff; color: #61ce70;">
                                    Reset
                                </a>
                            @endif
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Summary Cards with Growth Indicators --}}
                    @php
                        $trendsArray = array_values($analytics['monthly_trends'] ?? []);
                        $totalAmount = 0;
                        $totalRequests = 0;
                        $avgPerMonth = 0;
                        
                        if (!empty($trendsArray)) {
                            $totalAmount = array_sum(array_column($trendsArray, 'amount'));
                            $totalRequests = array_sum(array_column($trendsArray, 'count'));
                            $avgPerMonth = count($trendsArray) > 0 ? $totalAmount / count($trendsArray) : 0;
                        }
                        
                        $growthAmount = $analytics['growth_amount'] ?? 0;
                        $growthCount = $analytics['growth_count'] ?? 0;
                        $growthPercent = $analytics['growth_percent'] ?? 0;
                        $countGrowthPercent = $analytics['count_growth_percent'] ?? 0;
                    @endphp
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card shadow-sm" style="border-left: 4px solid #3b82f6; background: #fff;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1" style="color: #6b7280; font-weight: 600; font-size: 0.9rem;">
                                                <i class="fas fa-money-bill-wave me-2" style="color: #3b82f6;"></i>Total Amount
                                            </h6>
                                            <h3 class="mb-0" style="color: #1f2937; font-weight: 700;">{{ number_format($totalAmount, 0) }} TZS</h3>
                                            @if (count($trendsArray) >= 2)
                                                <small class="d-flex align-items-center mt-2" style="color: #6b7280;">
                                                    @if ($growthPercent > 0)
                                                        <i class="fas fa-arrow-up me-1 text-success"></i>
                                                        <span class="text-success">+{{ number_format($growthPercent, 1) }}%</span>
                                                    @elseif ($growthPercent < 0)
                                                        <i class="fas fa-arrow-down me-1 text-danger"></i>
                                                        <span class="text-danger">{{ number_format($growthPercent, 1) }}%</span>
                                                    @else
                                                        <span class="text-muted">No change</span>
                                                    @endif
                                                    <span class="ms-2">vs previous month</span>
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm" style="border-left: 4px solid #10b981; background: #fff;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1" style="color: #6b7280; font-weight: 600; font-size: 0.9rem;">
                                                <i class="fas fa-file-alt me-2" style="color: #10b981;"></i>Total Requests
                                            </h6>
                                            <h3 class="mb-0" style="color: #1f2937; font-weight: 700;">{{ number_format($totalRequests, 0) }}</h3>
                                            @if (count($trendsArray) >= 2)
                                                <small class="d-flex align-items-center mt-2" style="color: #6b7280;">
                                                    @if ($countGrowthPercent > 0)
                                                        <i class="fas fa-arrow-up me-1 text-success"></i>
                                                        <span class="text-success">+{{ number_format($countGrowthPercent, 1) }}%</span>
                                                    @elseif ($countGrowthPercent < 0)
                                                        <i class="fas fa-arrow-down me-1 text-danger"></i>
                                                        <span class="text-danger">{{ number_format($countGrowthPercent, 1) }}%</span>
                                                    @else
                                                        <span class="text-muted">No change</span>
                                                    @endif
                                                    <span class="ms-2">vs previous month</span>
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm" style="border-left: 4px solid #f59e0b; background: #fff;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1" style="color: #6b7280; font-weight: 600; font-size: 0.9rem;">
                                                <i class="fas fa-calendar-alt me-2" style="color: #f59e0b;"></i>Avg per Month
                                            </h6>
                                            <h3 class="mb-0" style="color: #1f2937; font-weight: 700;">{{ number_format($avgPerMonth, 0) }} TZS</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Main Chart --}}
                    <div id="departmentTrendChart" style="min-height: 400px;"></div>
                    
                    {{-- Department Performance Table --}}
                    <div class="mt-4">
                        <h6 class="mb-3" style="color: #333; font-weight: 600;">
                            <i class="fas fa-building me-2"></i>Department Performance
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Department</th>
                                        <th class="text-end">Total Amount (TZS)</th>
                                        <th class="text-end">Total Requests</th>
                                        <th class="text-end">Total Hours</th>
                                        <th class="text-end">Avg per Request</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($analytics['department_trends']) && count($analytics['department_trends']) > 0)
                                        @foreach ($analytics['department_trends'] as $deptName => $deptData)
                                            <tr>
                                                <td><strong>{{ $deptName }}</strong></td>
                                                <td class="text-end">{{ number_format($deptData['total_amount'], 0) }}</td>
                                                <td class="text-end">{{ number_format($deptData['total_requests'], 0) }}</td>
                                                <td class="text-end">{{ number_format($deptData['total_hours'], 1) }}</td>
                                                <td class="text-end">{{ number_format($deptData['avg_per_request'], 0) }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle me-2"></i>No data available for the selected period
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Report Section --}}
            <div class="card mb-4" style="border-color: #61ce70;">
                <div class="card-header text-white" style="background-color: #61ce70;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Payment Report (HR Approved)
                        </h5>
                        <form method="GET" action="{{ route('oncall_requests.report') }}" class="d-flex gap-2 align-items-center">
                            <select name="payment_year" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <option value="">All Years</option>
                                @foreach ($paymentYearOptions ?? [] as $y)
                                    <option value="{{ $y }}" @selected((string) ($paymentYear ?? '') === (string) $y)>{{ $y }}</option>
                                @endforeach
                            </select>
                            <select name="payment_month" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <option value="">All Months</option>
                                @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $num => $name)
                                    <option value="{{ $num }}" @selected((string) ($paymentMonth ?? '') === (string) $num)>{{ $name }}</option>
                                @endforeach
                            </select>
                            @if (!empty($paymentYear) || !empty($paymentMonth))
                                <a href="{{ route('oncall_requests.report') }}" class="btn btn-sm" style="background-color: #fff; border-color: #fff; color: #61ce70;">
                                    Reset
                                </a>
                            @endif
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card shadow-sm border-success">
                                <div class="card-body py-3">
                                    <div class="text-muted small">Total Requests</div>
                                    <div class="h4 mb-0">{{ number_format($paymentReportSummary['total_requests'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow-sm border-success">
                                <div class="card-body py-3">
                                    <div class="text-muted small">Total Employees</div>
                                    <div class="h4 mb-0">{{ number_format($paymentReportSummary['total_employees'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow-sm border-success">
                                <div class="card-body py-3">
                                    <div class="text-muted small">Total Hours</div>
                                    <div class="h4 mb-0">{{ number_format($paymentReportSummary['total_hours'] ?? 0, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow-sm border-success">
                                <div class="card-body py-3">
                                    <div class="text-muted small">Total Amount (TZS)</div>
                                    <div class="h4 mb-0">{{ number_format($paymentReportSummary['total_amount'] ?? 0, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if (!empty($paymentReportSummary['by_department']))
                        <div class="mt-3">
                            <h6 class="mb-2">By Department</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Department</th>
                                            <th>Requests</th>
                                            <th>Employees</th>
                                            <th>Hours</th>
                                            <th>Amount (TZS)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($paymentReportSummary['by_department'] as $dept => $data)
                                            <tr>
                                                <td>{{ $dept }}</td>
                                                <td>{{ number_format($data['count']) }}</td>
                                                <td>{{ number_format($data['employees']) }}</td>
                                                <td>{{ number_format($data['hours'], 2) }}</td>
                                                <td>{{ number_format($data['amount'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Filters + Export + Summary -->
            <p>
                <a class="btn btn-sm" style="background-color: #61ce70; border-color: #61ce70; color: #fff;" data-bs-toggle="collapse" href="#filterCollapse" role="button"
                   aria-expanded="true" aria-controls="filterCollapse">
                    Filter & Export
                </a>
            </p>

            <div class="collapse show" id="filterCollapse">
                <div class="card card-body mb-3">
                    <form id="filterForm" class="row g-3 align-items-center" method="GET"
                          action="{{ route('oncall_requests.export') }}">

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

                            // Summary by submitted month
                            $summaryByMonth = $actionedRequests->groupBy(function($r) {
                                return \Carbon\Carbon::parse($r->created_at)->format('Y-m');
                            })->map(function($group) {
                                return [
                                    'count' => $group->count(),
                                    'amount' => $group->sum('total_amount_payable'),
                                    'approved_count' => $group->filter(fn($r) => $r->status === 'approved')->count(),
                                    'approved_amount' => $group->filter(fn($r) => $r->status === 'approved')->sum('total_amount_payable'),
                                ];
                            })->sortKeys()->reverse();

                            $grandTotal = [
                                'count' => $actionedRequests->count(),
                                'amount' => $actionedRequests->sum('total_amount_payable'),
                                'approved_count' => $actionedRequests->filter(fn($r) => $r->status === 'approved')->count(),
                                'approved_amount' => $actionedRequests->filter(fn($r) => $r->status === 'approved')->sum('total_amount_payable'),
                            ];
                        @endphp

                        {{-- Year --}}
                        <div class="col-auto">
                            <label for="filterYear" class="col-form-label">Year</label>
                        </div>
                        <div class="col-auto">
                            <select id="filterYear" name="pay_year" class="form-select">
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
                            <select id="filterMonth" name="pay_month" class="form-select">
                                <option value="">All Months</option>
                                @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $num => $name)
                                    <option value="{{ $num }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Department --}}
                        <div class="col-auto">
                            <label for="filterDept" class="col-form-label">Department (optional)</label>
                        </div>
                        <div class="col-auto">
                            <select id="filterDept" name="department" class="form-select">
                                <option value="_all">All</option>
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
                            <button type="submit" class="btn btn-outline-success btn-sm js-export-btn" name="hr" value="1" data-loading-label="Exporting...">
                                <span class="btn-text">
                                <i class="fas fa-file-excel"></i> Export Approved
                                </span>
                                <span class="spinner-border spinner-border-sm ms-2 d-none js-spinner" role="status" aria-hidden="true"></span>
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

                    {{-- Summary Cards by Submitted Month --}}
                    <div class="row g-3 mt-3">
                        <div class="col-12">
                            <h6 class="mb-2">Summary by Submitted Month</h6>
                        </div>
                        @foreach ($summaryByMonth as $monthKey => $summary)
                            @php
                                $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $monthKey);
                                $monthName = $monthDate->format('F Y');
                            @endphp
                            <div class="col-md-3">
                                <div class="card shadow-sm">
                                    <div class="card-body py-2">
                                        <div class="text-muted small">{{ $monthName }}</div>
                                        <div class="small">Requests: <strong>{{ number_format($summary['count']) }}</strong></div>
                                        <div class="small">Amount: <strong>{{ number_format($summary['amount'], 2) }}</strong></div>
                                        <div class="small text-success">Approved: <strong>{{ number_format($summary['approved_count']) }}</strong></div>
                                        <div class="small text-success">Approved Amount: <strong>{{ number_format($summary['approved_amount'], 2) }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Grand Total --}}
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <div class="card shadow-sm border-success">
                                <div class="card-body py-3">
                                    <div class="text-muted small">Actioned Claims</div>
                                    <div class="h4 mb-0">{{ number_format($grandTotal['count']) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm border-success">
                                <div class="card-body py-3">
                                    <div class="text-muted small">Total Amount (TZS)</div>
                                    <div class="h4 mb-0">{{ number_format($grandTotal['amount'], 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View Requests & Approval Process -->
            <div class="card mb-4" style="border-color: #61ce70;">
                <div class="card-header text-white" style="background-color: #61ce70;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">
                            <i class="fas fa-list me-2"></i>
                            Pending requests
                        </h5>
                        <div class="d-flex gap-2 align-items-center">
                            <select id="tableFilterYear" class="form-select form-select-sm" style="width: auto;">
                                <option value="">All Years</option>
                                @php
                                    $filterYears = isset($filterYears) ? $filterYears : ($actionedRequests
                    ->filter(fn($r) => !is_null($r->created_at))
                                        ->map(fn($r) => \Carbon\Carbon::parse($r->created_at)->year)
                                        ->unique()
                                        ->sortDesc());
            @endphp
                                @foreach ($filterYears as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                            <select id="tableFilterMonth" class="form-select form-select-sm" style="width: auto;">
                                <option value="">All Months</option>
                                @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $num => $name)
                                    <option value="{{ $num }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="approvalProcessTable" class="table table-striped table-hover table-bordered w-100">
                            <thead class="table-success">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Submitted On</th>
                                    <th>Days</th>
                                    <th>Hours</th>
                                    <th>Amount (TZS)</th>
                                    <th>Approved</th>
                                    <th>Approved On</th>
                                    <th>Approval Process</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($actionedRequests as $index => $request)
                                    @php
                                        $deptName = $request->user?->department?->dept_name ?? 'N/A';
                                        $employeeName = trim(collect([
                                            $request->user->fname ?? null,
                                            $request->user->mname ?? null,
                                            $request->user->lname ?? null,
                                        ])->filter()->implode(' ')) ?: ($request->user->username ?? 'N/A');

                                        $submittedOn = $request->created_at ? \Carbon\Carbon::parse($request->created_at)->format('Y-m-d H:i') : '—';

                                        $histories = $request->workflow ? $request->workflow->histories->sortBy('id') : collect();

                                        // Check if approved
                                        $isApproved = false;
                                        $approvedOn = '—';
                                        $approvedMonthYear = '—';

                                        if ($request->status === 'approved' || ($request->workflow && (int)$request->workflow->work_flow_completed === 1)) {
                                            $hrApproved = $histories->where('step_name', 'HR Approval')->where('status', 1)->first();
                                            if ($hrApproved) {
                                                $isApproved = true;
                                                $approvedAt = $hrApproved->updated_at ?? $hrApproved->created_at ?? $hrApproved->attend_date;
                                                if ($approvedAt) {
                                                    $approvedDate = \Carbon\Carbon::parse($approvedAt);
                                                    $approvedOn = $approvedDate->format('Y-m-d H:i');
                                                    $approvedMonthYear = $approvedDate->format('F Y');
                                                }
                                            } else {
                                                // Check if any approval step is completed
                                                $anyApproved = $histories->where('status', 1)->sortByDesc('id')->first();
                                                if ($anyApproved && (int)$request->workflow->work_flow_completed === 1) {
                                                    $isApproved = true;
                                                    $approvedAt = $anyApproved->updated_at ?? $anyApproved->created_at ?? $anyApproved->attend_date;
                                                    if ($approvedAt) {
                                                        $approvedDate = \Carbon\Carbon::parse($approvedAt);
                                                        $approvedOn = $approvedDate->format('Y-m-d H:i');
                                                        $approvedMonthYear = $approvedDate->format('F Y');
                                                    }
                                                }
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $employeeName }}</td>
                                        <td>{{ $deptName }}</td>
                                        <td>{{ $submittedOn }}</td>
                                        <td>{{ (int)($request->number_of_days ?? 0) }}</td>
                                        <td>{{ number_format($request->total_hours ?? 0, 2, '.', ',') }}</td>
                                        <td>{{ number_format($request->total_amount_payable ?? 0, 2, '.', ',') }}</td>
                                        <td>
                                            @if ($isApproved)
                                                <span class="badge bg-success">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                        <td>{{ $approvedMonthYear }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary view-approval-process"
                                                    data-request-id="{{ $request->id }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#approvalProcessModal">
                                                <i class="fas fa-eye me-1"></i> View Process
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Approval Process Modal -->
            <div class="modal fade" id="approvalProcessModal" tabindex="-1" aria-labelledby="approvalProcessModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="approvalProcessModalLabel">Approval Process Timeline</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="approvalProcessContent">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- DataTables CSS & JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- ApexCharts for Analytics -->
    <script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>

    <script>
        $(function() {
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

            $('#filterYear, #filterMonth, #filterDept, #filterBasis').on('change', function() {
                updateChips();
            });

            updateChips();

            // Export button loading state and download handling
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();

                const exportBtn = $(this).find('.js-export-btn');
                if (!exportBtn.length) {
                    return;
                }

                const loadingLabel = exportBtn.data('loading-label') || 'Exporting...';
                const form = $(this);
                const formData = form.serialize();
                const actionUrl = form.attr('action');

                // Show loading state
                exportBtn.prop('disabled', true);
                exportBtn.find('.btn-text').html('<i class="fas fa-file-excel"></i> ' + loadingLabel);
                exportBtn.find('.js-spinner').removeClass('d-none');

                // Create a hidden iframe to download the file
                const iframe = $('<iframe>', {
                    id: 'export-iframe',
                    style: 'display: none;',
                    src: actionUrl + '?' + formData
                });

                $('body').append(iframe);

                // Remove loading state after download starts
                setTimeout(function() {
                    exportBtn.prop('disabled', false);
                    exportBtn.find('.btn-text').html('<i class="fas fa-file-excel"></i> Export Approved');
                    exportBtn.find('.js-spinner').addClass('d-none');

                    // Remove iframe after a delay
                    setTimeout(function() {
                        $('#export-iframe').remove();
                    }, 2000);
                }, 3000);
            });

            // View approval process (using event delegation)
            $(document).on('click', '.view-approval-process', function() {
                const requestId = $(this).data('request-id');
                const modal = $('#approvalProcessModal');
                const content = $('#approvalProcessContent');

                content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                $.ajax({
                    url: '{{ route("oncall_requests.show-details", ":id") }}'.replace(':id', requestId),
                    method: 'GET',
                    success: function(response) {
                        let html = '<div class="timeline">';

                        if (response.workflow && response.workflow.steps) {
                            response.workflow.steps.forEach(function(step, index) {
                                const statusClass = step.status === 'Approved' ? 'success' :
                                                   step.status === 'Rejected' ? 'danger' : 'warning';
                                const statusIcon = step.status === 'Approved' ? 'fa-check-circle' :
                                                  step.status === 'Rejected' ? 'fa-times-circle' : 'fa-clock';

                                html += `
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            <div class="rounded-circle bg-${statusClass} d-flex align-items-center justify-content-center"
                                                 style="width: 40px; height: 40px; color: white;">
                                                <i class="fas ${statusIcon}"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">${step.step}</h6>
                                            <p class="mb-1 text-muted small">
                                                <strong>Attended by:</strong> ${step.attended_by}<br>
                                                <strong>Status:</strong> <span class="badge bg-${statusClass}">${step.status}</span>
                                                ${step.acted_at !== '—' ? '<br><strong>Acted at:</strong> ' + step.acted_at : ''}
                                            </p>
                                            ${step.remark ? '<p class="mb-1 small"><strong>Remark:</strong> ' + step.remark + '</p>' : ''}
                                            ${step.rejection ? '<p class="mb-1 small text-danger"><strong>Rejection Reason:</strong> ' + step.rejection + '</p>' : ''}
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            html += '<p class="text-muted">No approval process data available.</p>';
                        }

                        html += '</div>';
                        content.html(html);
                    },
                    error: function() {
                        content.html('<div class="alert alert-danger">Failed to load approval process. Please try again.</div>');
                    }
                });
            });

            // Initialize DataTable for Approval Process Table
            let approvalProcessTable;
            if ($('#approvalProcessTable').length > 0) {
                approvalProcessTable = $('#approvalProcessTable').DataTable({
                    order: [[3, 'desc']], // Sort by Submitted On descending
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    pagingType: 'simple_numbers',
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        emptyTable: "No requests found",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    columnDefs: [
                        { orderable: false, targets: [9] } // Disable sorting on Approval Process column
                    ]
                });
            }

            // Year and Month filters for DataTable
            let yearMonthFilter = null;

            function applyYearMonthFilter() {
                // Remove existing filter if any
                if (yearMonthFilter !== null) {
                    const index = $.fn.dataTable.ext.search.indexOf(yearMonthFilter);
                    if (index !== -1) {
                        $.fn.dataTable.ext.search.splice(index, 1);
                    }
                }

                const year = $('#tableFilterYear').val();
                const month = $('#tableFilterMonth').val();

                // Only add filter if at least one is selected
                if (year || month) {
                    yearMonthFilter = function(settings, data, dataIndex) {
                        if (settings.nTable.id !== 'approvalProcessTable') {
                            return true;
                        }

                        // Get submitted date from column 3 (index 3)
                        const submittedOn = data[3] || '';

                        // Filter by year
                        if (year && submittedOn) {
                            const submittedYear = submittedOn.substring(0, 4);
                            if (submittedYear !== year) {
                                return false;
                            }
                        }

                        // Filter by month - check submitted date month
                        if (month && submittedOn) {
                            const submittedDate = new Date(submittedOn);
                            const submittedMonth = submittedDate.getMonth() + 1; // getMonth() returns 0-11
                            if (parseInt(month) !== submittedMonth) {
                                return false;
                            }
                        }

                        return true;
                    };

                    $.fn.dataTable.ext.search.push(yearMonthFilter);
                } else {
                    yearMonthFilter = null;
                }

                if (approvalProcessTable) {
                    approvalProcessTable.draw();
                }
            }

            $('#tableFilterYear, #tableFilterMonth').on('change', applyYearMonthFilter);

            // Initialize Monthly Trends Chart - Line Chart with Department Breakdown
            @if (isset($analytics['monthly_trends']) && !empty($analytics['monthly_trends']))
                const monthlyTrends = @json($analytics['monthly_trends']);
                const topDepts = @json($analytics['top_departments'] ?? []);
                
                // Prepare chart data
                const monthLabels = Object.keys(monthlyTrends).map(key => monthlyTrends[key].label);
                
                // Create series for each top department
                const series = topDepts.map(deptName => {
                    const data = Object.keys(monthlyTrends).map(key => {
                        return monthlyTrends[key].by_department[deptName] || 0;
                    });
                    return {
                        name: deptName,
                        data: data
                    };
                });

                // Add total line
                const totalData = Object.keys(monthlyTrends).map(key => {
                    return monthlyTrends[key].amount / 1000; // Convert to thousands
                });
                series.push({
                    name: 'Total',
                    data: totalData,
                    type: 'line',
                    strokeWidth: 3
                });

                // Color palette for departments
                const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#6366f1', '#f97316'];
                
                if ($('#departmentTrendChart').length > 0) {
                    const chartOptions = {
                        chart: {
                            height: 450,
                            type: 'line',
                            stacked: false,
                            toolbar: {
                                show: true,
                                tools: {
                                    download: true,
                                    selection: true,
                                    zoom: true,
                                    zoomin: true,
                                    zoomout: true,
                                    pan: true,
                                    reset: true
                                }
                            },
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 800
                            }
                        },
                        colors: colors,
                        stroke: {
                            width: [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 3],
                            curve: 'smooth'
                        },
                        dataLabels: {
                            enabled: false
                        },
                        series: series,
                        markers: {
                            size: 4,
                            hover: {
                                size: 6
                            }
                        },
                        grid: {
                            borderColor: '#e5e7eb',
                            strokeDashArray: 4,
                            xaxis: {
                                lines: {
                                    show: true
                                }
                            },
                            yaxis: {
                                lines: {
                                    show: true
                                }
                            }
                        },
                        xaxis: {
                            categories: monthLabels,
                            labels: {
                                rotate: -45,
                                rotateAlways: true,
                                style: {
                                    colors: '#6b7280',
                                    fontSize: '11px'
                                }
                            },
                            title: {
                                text: 'Month',
                                style: {
                                    color: '#6b7280',
                                    fontSize: '12px'
                                }
                            }
                        },
                        yaxis: {
                            title: {
                                text: 'Amount (TZS Thousands)',
                                style: {
                                    color: '#6b7280',
                                    fontSize: '12px'
                                }
                            },
                            labels: {
                                style: {
                                    colors: '#6b7280',
                                    fontSize: '11px'
                                },
                                formatter: function(val) {
                                    return val.toFixed(0) + 'K';
                                }
                            }
                        },
                        tooltip: {
                            shared: true,
                            intersect: false,
                            style: {
                                fontSize: '12px'
                            },
                            y: {
                                formatter: function(val) {
                                    return 'TZS ' + val.toLocaleString('en-US', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }) + 'K';
                                }
                            }
                        },
                        legend: {
                            show: true,
                            position: 'bottom',
                            horizontalAlign: 'center',
                            floating: false,
                            fontSize: '11px',
                            itemMargin: {
                                horizontal: 10,
                                vertical: 5
                            }
                        }
                    };
                    const chart = new ApexCharts(document.querySelector('#departmentTrendChart'), chartOptions);
                    chart.render();
                }
            @else
                // Show message if no data
                if ($('#departmentTrendChart').length > 0) {
                    $('#departmentTrendChart').html('<div class="text-center p-5 text-muted"><i class="fas fa-chart-line fa-3x mb-3"></i><p>No data available for the selected period</p></div>');
                }
            @endif
        });
    </script>
@endsection

