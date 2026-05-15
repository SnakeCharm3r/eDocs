@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .status-badge { font-size: 0.85em; padding: 0.35em 0.75em; }
        .js-export-btn:disabled { opacity: 0.7; cursor: not-allowed; }
        .js-export-btn .js-spinner { display: inline-block; }
        .js-export-btn .js-spinner.d-none { display: none !important; }
        .table-hover tbody tr:hover { background-color: #f8f9fc; }
        .filter-chip { font-size: .85rem; }
        .report-card { border: 0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .report-card .card-header { background: #fff; border-bottom: 1px solid #eee; border-radius: 10px 10px 0 0; }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1 fw-bold text-dark"><i class="fas fa-chart-bar me-2" style="color:#61ce70;"></i>Locum Reports</h4>
                    <small class="text-muted">Analytics, trends & approved claims</small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="/locum-agreement-show" class="btn btn-outline-secondary btn-sm"><i class="fas fa-handshake me-1"></i>Agreements</a>
                    <a href="{{ route('locum-requests.view') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-list me-1"></i>Requests</a>
                    <a href="{{ route('locum-requests.report') }}" class="btn btn-success btn-sm"><i class="fas fa-chart-line me-1"></i>Reports</a>
                    <a href="{{ route('night-shift.report') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-moon me-1"></i>Night Allowances Report</a>
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
            <div class="card report-card mb-4">
                <div class="card-header py-3">
                    <form method="GET" action="{{ route('locum-requests.report') }}" id="analyticsFilterForm">
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
                            $selectedQuickFilter = request()->query('quick_filter');
                        @endphp

                        {{-- Title Row --}}
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-chart-line me-2" style="color:#61ce70;"></i>
                                Analytics & Trends
                            </h6>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-success fw-semibold">
                                    <i class="fas fa-filter me-1"></i> Apply
                                </button>
                                @if (!empty($selectedFromYear) || !empty($selectedFromMonth) || !empty($selectedToYear) || !empty($selectedToMonth) || !empty($selectedDept) || !empty($selectedQuickFilter))
                                    <a href="{{ route('locum-requests.report') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-redo me-1"></i> Reset
                                    </a>
                                @endif
                                @canany(['view locum reports', 'ict_acces_report'])
                                <button type="button" id="analyticsExportBtn" class="btn btn-sm btn-outline-success js-export-btn" data-loading-label="Exporting...">
                                    <span class="btn-text"><i class="fas fa-file-excel me-1"></i>Export</span>
                                    <span class="spinner-border spinner-border-sm ms-1 d-none js-spinner" role="status"></span>
                                </button>
                                @endcanany
                            </div>
                        </div>

                        {{-- Quick Filters + Advanced in one compact row --}}
                        <div class="row g-2 align-items-end">
                            {{-- Quick filter pills --}}
                            <div class="col-12 col-lg-auto">
                                <label class="text-muted small fw-semibold mb-1 d-block"><i class="fas fa-bolt me-1"></i>Quick:</label>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn quick-filter-btn {{ $selectedQuickFilter === 'this_month' ? 'btn-success' : 'btn-outline-secondary' }}" data-filter="this_month">This Month</button>
                                    <button type="button" class="btn quick-filter-btn {{ $selectedQuickFilter === 'last_month' ? 'btn-success' : 'btn-outline-secondary' }}" data-filter="last_month">Last Month</button>
                                    <button type="button" class="btn quick-filter-btn {{ $selectedQuickFilter === 'last_3_months' ? 'btn-success' : 'btn-outline-secondary' }}" data-filter="last_3_months">Last 3M</button>
                                    <button type="button" class="btn quick-filter-btn {{ $selectedQuickFilter === 'this_year' ? 'btn-success' : 'btn-outline-secondary' }}" data-filter="this_year">This Year</button>
                                </div>
                                <input type="hidden" name="quick_filter" id="quick_filter" value="{{ $selectedQuickFilter }}">
                            </div>

                            {{-- From month --}}
                            <div class="col-6 col-sm-4 col-lg-2">
                                <label class="text-muted small fw-semibold mb-1 d-block"><i class="fas fa-calendar-alt me-1"></i>From:</label>
                                @php
                                    $fromVal = ($selectedFromYear && $selectedFromMonth)
                                        ? $selectedFromYear . '-' . str_pad($selectedFromMonth, 2, '0', STR_PAD_LEFT)
                                        : date('Y') . '-01';
                                @endphp
                                <input type="month" id="from_month_input" class="form-control form-control-sm"
                                    value="{{ $fromVal }}" min="2020-01" max="{{ date('Y-m') }}">
                                <input type="hidden" name="from_year"  id="from_year"  value="{{ $selectedFromYear  ?? date('Y') }}">
                                <input type="hidden" name="from_month" id="from_month" value="{{ $selectedFromMonth ?? 1 }}">
                            </div>

                            {{-- To month --}}
                            <div class="col-6 col-sm-4 col-lg-2">
                                <label class="text-muted small fw-semibold mb-1 d-block"><i class="fas fa-calendar-check me-1"></i>To:</label>
                                @php
                                    $toVal = ($selectedToYear && $selectedToMonth)
                                        ? $selectedToYear . '-' . str_pad($selectedToMonth, 2, '0', STR_PAD_LEFT)
                                        : date('Y-m');
                                @endphp
                                <input type="month" id="to_month_input" class="form-control form-control-sm"
                                    value="{{ $toVal }}" min="2020-01" max="{{ date('Y-m') }}">
                                <input type="hidden" name="to_year"  id="to_year"  value="{{ $selectedToYear  ?? date('Y') }}">
                                <input type="hidden" name="to_month" id="to_month" value="{{ $selectedToMonth ?? (int)date('n') }}">
                            </div>

                            {{-- Department --}}
                            <div class="col-12 col-sm-4 col-lg-3">
                                <label class="text-muted small fw-semibold mb-1 d-block"><i class="fas fa-building me-1"></i>Department:</label>
                                <select id="analytics_dept" name="analytics_dept" class="form-select form-select-sm">
                                    <option value="_all">All Departments</option>
                                    @foreach ($allDepartments ?? [] as $dept)
                                        <option value="{{ $dept->dept_name }}" @selected($selectedDept === $dept->dept_name)>{{ $dept->dept_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- JS: sync month inputs → hidden year/month fields --}}
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            function syncMonth(inputId, yearId, monthId) {
                                var el = document.getElementById(inputId);
                                if (!el) return;
                                el.addEventListener('change', function() {
                                    var parts = this.value.split('-');
                                    if (parts.length === 2) {
                                        document.getElementById(yearId).value  = parts[0];
                                        document.getElementById(monthId).value = parseInt(parts[1], 10);
                                    }
                                    document.getElementById('quick_filter').value = '';
                                });
                            }
                            syncMonth('from_month_input', 'from_year', 'from_month');
                            syncMonth('to_month_input',   'to_year',   'to_month');

                            var analyticsExportBtn = document.getElementById('analyticsExportBtn');
                            if (analyticsExportBtn) {
                                analyticsExportBtn.addEventListener('click', function() {
                                    var fromYear  = document.getElementById('from_year').value;
                                    var fromMonth = document.getElementById('from_month').value;
                                    var toYear    = document.getElementById('to_year').value;
                                    var toMonth   = document.getElementById('to_month').value;
                                    var dept      = document.getElementById('analytics_dept') ? document.getElementById('analytics_dept').value : '_all';
                                    var qf        = document.getElementById('quick_filter').value;

                                    var params = new URLSearchParams({
                                        year:       toYear,
                                        month:      toMonth,
                                        from_year:  fromYear,
                                        from_month: fromMonth,
                                        to_year:    toYear,
                                        to_month:   toMonth,
                                        department: dept,
                                        basis:      'approved',
                                        hr:         '1'
                                    });
                                    if (qf) params.set('quick_filter', qf);

                                    var btn = this;
                                    var btnText = btn.querySelector('.btn-text');
                                    var spinner = btn.querySelector('.js-spinner');
                                    btn.disabled = true;
                                    if (btnText) btnText.textContent = btn.dataset.loadingLabel || 'Exporting...';
                                    if (spinner) spinner.classList.remove('d-none');

                                    window.location.href = '{{ route('locum-requests.actioned.export') }}?' + params.toString();

                                    setTimeout(function() {
                                        btn.disabled = false;
                                        if (btnText) { btnText.innerHTML = '<i class="fas fa-file-excel me-1"></i>Export'; }
                                        if (spinner) spinner.classList.add('d-none');
                                    }, 4000);
                                });
                            }
                        });
                        </script>
                    </form>
                </div>
                <div class="card-body">
                    {{-- Summary Cards with Growth Indicators --}}
                    @php
                        $trendsArray = array_values($analytics['monthly_trends'] ?? []);
                        $totalAmount = 0;
                        $totalRequests = 0;
                        $totalHours = 0;
                        $avgPerMonth = 0;
                        
                        if (!empty($trendsArray)) {
                            $totalAmount = array_sum(array_column($trendsArray, 'amount'));
                            $totalRequests = array_sum(array_column($trendsArray, 'count'));
                            $totalHours = array_sum(array_column($trendsArray, 'hours'));
                            $avgPerMonth = count($trendsArray) > 0 ? $totalAmount / count($trendsArray) : 0;
                        }
                        
                        // Count unique employees from department trends
                        $totalEmployees = 0;
                        if (isset($analytics['department_trends'])) {
                            $totalEmployees = collect($analytics['department_trends'])->sum('total_employees');
                        }
                        
                        $growthAmount = $analytics['growth_amount'] ?? 0;
                        $growthCount = $analytics['growth_count'] ?? 0;
                        $growthPercent = $analytics['growth_percent'] ?? 0;
                        $countGrowthPercent = $analytics['count_growth_percent'] ?? 0;
                    @endphp
                    

                    {{-- Charts Row --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white py-2">
                                    <h6 class="mb-0" style="color: #333; font-weight: 600;">
                                        <i class="fas fa-chart-bar me-2" style="color:#3b82f6;"></i>Claims Cost by Month — {{ date('Y') }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="departmentTrendChart" style="min-height: 380px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Department Performance Table with Monthly Breakdown --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0" style="color: #333; font-weight: 600;">
                                <i class="fas fa-building me-2 text-info"></i>Department Performance Summary
                            </h6>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary active" id="viewSummary">
                                    <i class="fas fa-table"></i> Summary
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="viewMonthly">
                                    <i class="fas fa-calendar-alt"></i> Monthly
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Summary View --}}
                            <div id="summaryTableView">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-sm" id="deptSummaryTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Department</th>
                                                <th class="text-end">Total Amount (TZS)</th>
                                                <th class="text-end">Requests</th>
                                                <th class="text-end">Hours</th>
                                                <th class="text-end">Employees</th>
                                                <th class="text-end">Avg/Request</th>
                                                <th class="text-center">Trend</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if (isset($analytics['department_trends']) && count($analytics['department_trends']) > 0)
                                                @php
                                                    $maxAmount = collect($analytics['department_trends'])->max('total_amount');
                                                @endphp
                                                @foreach ($analytics['department_trends'] as $deptName => $deptData)
                                                    @php
                                                        $percentage = $maxAmount > 0 ? ($deptData['total_amount'] / $maxAmount) * 100 : 0;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $deptName }}</strong>
                                                            <div class="progress mt-1" style="height: 4px;">
                                                                <div class="progress-bar bg-success" style="width: {{ $percentage }}%"></div>
                                                            </div>
                                                        </td>
                                                        <td class="text-end fw-semibold">{{ number_format($deptData['total_amount'], 0) }}</td>
                                                        <td class="text-end">{{ number_format($deptData['total_requests'], 0) }}</td>
                                                        <td class="text-end">{{ number_format($deptData['total_hours'], 1) }}</td>
                                                        <td class="text-end">{{ number_format($deptData['total_employees'] ?? 0, 0) }}</td>
                                                        <td class="text-end">{{ number_format($deptData['avg_per_request'], 0) }}</td>
                                                        <td class="text-center">
                                                            <div id="sparkline-{{ Str::slug($deptName) }}" style="height: 25px; width: 80px; display: inline-block;"></div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-4">
                                                        <i class="fas fa-info-circle me-2"></i>No data available for the selected period
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                        @if (isset($analytics['department_trends']) && count($analytics['department_trends']) > 0)
                                            <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td>TOTAL</td>
                                                    <td class="text-end">{{ number_format(collect($analytics['department_trends'])->sum('total_amount'), 0) }}</td>
                                                    <td class="text-end">{{ number_format(collect($analytics['department_trends'])->sum('total_requests'), 0) }}</td>
                                                    <td class="text-end">{{ number_format(collect($analytics['department_trends'])->sum('total_hours'), 1) }}</td>
                                                    <td class="text-end">{{ number_format($totalEmployees, 0) }}</td>
                                                    <td class="text-end">—</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            {{-- Monthly Breakdown View (Hidden by default) --}}
                            <div id="monthlyTableView" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-sm" id="monthlyBreakdownTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="sticky-col">Department</th>
                                                @if (!empty($trendsArray))
                                                    @foreach ($trendsArray as $trend)
                                                        <th class="text-end" style="min-width: 100px;">{{ $trend['label'] }}</th>
                                                    @endforeach
                                                @endif
                                                <th class="text-end bg-light fw-bold">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if (isset($analytics['department_trends']) && count($analytics['department_trends']) > 0)
                                                @foreach ($analytics['department_trends'] as $deptName => $deptData)
                                                    <tr>
                                                        <td class="sticky-col"><strong>{{ $deptName }}</strong></td>
                                                        @if (!empty($trendsArray))
                                                            @foreach ($trendsArray as $monthKey => $trend)
                                                                @php
                                                                    $monthAmount = $trend['by_department'][$deptName] ?? 0;
                                                                    // Convert from K back to actual amount for display
                                                                    $displayAmount = $monthAmount * 1000;
                                                                @endphp
                                                                <td class="text-end {{ $monthAmount > 0 ? '' : 'text-muted' }}">
                                                                    {{ $monthAmount > 0 ? number_format($displayAmount, 0) : '—' }}
                                                                </td>
                                                            @endforeach
                                                        @endif
                                                        <td class="text-end bg-light fw-bold">{{ number_format($deptData['total_amount'], 0) }}</td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="{{ count($trendsArray) + 2 }}" class="text-center text-muted py-4">
                                                        <i class="fas fa-info-circle me-2"></i>No data available for the selected period
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                        @if (isset($analytics['department_trends']) && count($analytics['department_trends']) > 0)
                                            <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td class="sticky-col">TOTAL</td>
                                                    @if (!empty($trendsArray))
                                                        @foreach ($trendsArray as $trend)
                                                            <td class="text-end">{{ number_format($trend['amount'], 0) }}</td>
                                                        @endforeach
                                                    @endif
                                                    <td class="text-end bg-warning-subtle">{{ number_format(collect($analytics['department_trends'])->sum('total_amount'), 0) }}</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View Requests & Approval Process -->
            <div class="card report-card mb-4">
                <div class="card-header py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-list me-2" style="color:#4e73df;"></i>
                            <span id="tableTitle">All Requests</span>
                        </h6>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <select id="filterStatus" class="form-select form-select-sm" style="width: auto; min-width: 130px;">
                                <option value="">All Status</option>
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <select id="filterYear" class="form-select form-select-sm" style="width: auto;">
                                <option value="">All Years</option>
                                @php
                                    $filterYears = $actionedRequests
                                        ->filter(fn($r) => !is_null($r->created_at))
                                        ->map(fn($r) => \Carbon\Carbon::parse($r->created_at)->year)
                                        ->unique()
                                        ->sortDesc();
                                @endphp
                                @foreach ($filterYears as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                            <select id="filterMonth" class="form-select form-select-sm" style="width: auto;">
                                <option value="">All Months</option>
                                @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $num => $name)
                                    <option value="{{ $num }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @canany(['view locum reports', 'ict_acces_report'])
                            <form id="filterForm" method="GET" action="{{ route('locum-requests.actioned.export') }}" class="d-inline">
                                @php
                                    $exportYears = $actionedRequests->filter(fn($r) => !is_null($r->created_at))->map(fn($r) => \Carbon\Carbon::parse($r->created_at)->year)->unique()->sortDesc();
                                @endphp
                                <input type="hidden" name="year" id="exportYear" value="">
                                <input type="hidden" name="month" id="exportMonth" value="">
                                <input type="hidden" name="department" value="_all">
                                <input type="hidden" name="basis" value="approved">
                                <input type="hidden" name="hr" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-success js-export-btn" data-loading-label="Exporting...">
                                    <span class="btn-text"><i class="fas fa-file-excel me-1"></i>Export</span>
                                    <span class="spinner-border spinner-border-sm ms-1 d-none js-spinner" role="status"></span>
                                </button>
                            </form>
                            @endcanany
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
                                    <th>Status</th>
                                    <th>Approval</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($actionedRequests as $index => $request)
                                    @php
                                        $deptName = $request->user?->department?->dept_name ?? 'N/A';
                                        $employeeName =
                                            trim(
                                                collect([
                                                    $request->user->fname ?? null,
                                                    $request->user->mname ?? null,
                                                    $request->user->lname ?? null,
                                                ])
                                                    ->filter()
                                                    ->implode(' '),
                                            ) ?:
                                            $request->user->username ?? 'N/A';

                                        $claimText = '—';
                                        try {
                                            if ($request->locum_month) {
                                                $claimText = \Carbon\Carbon::parse(
                                                    '1 ' . $request->locum_month,
                                                )->format('F Y');
                                            } elseif ($request->created_at) {
                                                $claimText = \Carbon\Carbon::parse($request->created_at)->format('F Y');
                                            }
                                        } catch (\Throwable $e) {
                                            $claimText = $request->created_at
                                                ? \Carbon\Carbon::parse($request->created_at)->format('F Y')
                                                : '—';
                                        }

                                        $submittedOn = $request->created_at
                                            ? \Carbon\Carbon::parse($request->created_at)->format('Y-m-d H:i')
                                            : '—';

                                        $hrApprovedOn = '—';
                                        $histories = $request->workflow
                                            ? $request->workflow->histories->sortBy('id')
                                            : collect();
                                        $hrApproval = $histories
                                            ->where('step_name', 'HR Approval')
                                            ->where('status', 1)
                                            ->first();
                                        if ($hrApproval) {
                                            $hrApprovedAt = $hrApproval->updated_at ?? $hrApproval->created_at;
                                            if ($hrApprovedAt) {
                                                $hrApprovedOn = \Carbon\Carbon::parse($hrApprovedAt)->format(
                                                    'Y-m-d H:i',
                                                );
                                            }
                                        }

                                        // Determine status with specific approval levels
                                        $statusLabel = 'Pending';
                                        $statusClass = 'warning';

                                        // Check for rejection first
                                        $rejected = $histories->where('status', 2)->first();
                                        if ($rejected) {
                                            $rejectedBy = 'Unknown';
                                            if ($rejected->attendedBy) {
                                                $rejectedBy =
                                                    trim(
                                                        collect([
                                                            $rejected->attendedBy->fname ?? null,
                                                            $rejected->attendedBy->mname ?? null,
                                                            $rejected->attendedBy->lname ?? null,
                                                        ])
                                                            ->filter()
                                                            ->implode(' '),
                                                    ) ?:
                                                    $rejected->attendedBy->username ?? 'Unknown';
                                            }
                                            $statusLabel = 'Rejected by ' . $rejectedBy;
                                            $statusClass = 'danger';
                                        } elseif (
                                            $request->workflow &&
                                            (int) $request->workflow->work_flow_completed === 1
                                        ) {
                                            // Check if HR approved
                                            $hrApproved = $histories
                                                ->where('step_name', 'HR Approval')
                                                ->where('status', 1)
                                                ->first();
                                            if ($hrApproved) {
                                                $statusLabel = 'HR Approved';
                                                $statusClass = 'success';
                                            } else {
                                                // Check other approval levels
                                                $approved = $histories->where('status', 1)->sortByDesc('id')->first();
                                                if ($approved) {
                                                    $statusLabel = $approved->step_name . ' Approved';
                                                    $statusClass = 'success';
                                                } else {
                                                    $statusLabel = 'Approved';
                                                    $statusClass = 'success';
                                                }
                                            }
                                        } else {
                                            // Find pending step
                                            $pending = $histories->where('status', 0)->first();
                                            if ($pending) {
                                                $statusLabel = 'Pending ' . $pending->step_name;
                                                $statusClass = 'warning';
                                            } else {
                                                // Check last step to determine pending level
                                                $lastStep = $histories->sortByDesc('id')->first();
                                                if ($lastStep) {
                                                    $statusLabel = 'Pending ' . $lastStep->step_name;
                                                } else {
                                                    $statusLabel = 'Pending';
                                                }
                                            }
                                        }
                                        // Determine status category for filtering
                                        $statusCategory = 'pending'; // default
                                        if ($statusClass === 'success') {
                                            $statusCategory = 'approved';
                                        } elseif ($statusClass === 'danger') {
                                            $statusCategory = 'rejected';
                                        }
                                    @endphp
                                    <tr data-status="{{ $statusCategory }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $employeeName }}</td>
                                        <td>{{ $deptName }}</td>
                                        <td>{{ $submittedOn }}</td>
                                        <td>{{ (int) ($request->number_of_days ?? 0) }}</td>
                                        <td>{{ number_format($request->total_hours ?? 0, 2, '.', ',') }}</td>
                                        <td>{{ number_format($request->total_amount_payable ?? 0, 2, '.', ',') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary view-approval-process"
                                                    data-request-id="{{ $request->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#approvalProcessModal" title="View Approval Process">
                                                    <i class="fas fa-eye me-1"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-success view-worked-days-report"
                                                    data-request-id="{{ $request->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#workedDaysModal" title="View Worked Days">
                                                    <i class="fas fa-calendar-day me-1"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Approval Process Modal -->
            <div class="modal fade" id="approvalProcessModal" tabindex="-1" aria-labelledby="approvalProcessModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="approvalProcessModalLabel">Approval Process Timeline</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
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

            <!-- Worked Days Modal -->
            <div class="modal fade" id="workedDaysModal" tabindex="-1" aria-labelledby="workedDaysModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="workedDaysModalLabel">Worked Days</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="workedDaysContent">
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

    <!-- ApexCharts for Analytics -->
    <script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>

    <script>
        $(function() {
            // Sync export hidden fields with table filter values
            $('#filterYear, #filterMonth').on('change', function() {
                $('#exportYear').val($('#filterYear').val());
                $('#exportMonth').val($('#filterMonth').val());
            });

            // Export button loading state
            $('#filterForm').on('submit', function() {
                const exportBtn = $(this).find('.js-export-btn');
                if (!exportBtn.length) return;
                exportBtn.prop('disabled', true);
                exportBtn.find('.btn-text').html('<i class="fas fa-file-excel me-1"></i>Exporting...');
                exportBtn.find('.js-spinner').removeClass('d-none');
                setTimeout(function() {
                    exportBtn.prop('disabled', false);
                    exportBtn.find('.btn-text').html('<i class="fas fa-file-excel me-1"></i>Export');
                    exportBtn.find('.js-spinner').addClass('d-none');
                }, 3000);
            });

            // View approval process (using event delegation for dynamically loaded content)
            $(document).on('click', '.view-approval-process', function() {
                const requestId = $(this).data('request-id');
                const modal = $('#approvalProcessModal');
                const content = $('#approvalProcessContent');

                content.html(
                    '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                );

                $.ajax({
                    url: '{{ route('locum-requests.showLocumRequest', ':id') }}'.replace(':id',
                        requestId),
                    method: 'GET',
                    success: function(response) {
                        let html = '<div class="timeline">';

                        if (response.workflow && response.workflow.steps) {
                            response.workflow.steps.forEach(function(step, index) {
                                const statusClass = step.status === 'Approved' ?
                                    'success' :
                                    step.status === 'Rejected' ? 'danger' : 'warning';
                                const statusIcon = step.status === 'Approved' ?
                                    'fa-check-circle' :
                                    step.status === 'Rejected' ? 'fa-times-circle' :
                                    'fa-clock';

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
                            html +=
                                '<p class="text-muted">No approval process data available.</p>';
                        }

                        html += '</div>';
                        content.html(html);
                    },
                    error: function() {
                        content.html(
                            '<div class="alert alert-danger">Failed to load approval process. Please try again.</div>'
                        );
                    }
                });
            });

            // View worked days (using event delegation for dynamically loaded content)
            $(document).on('click', '.view-worked-days-report', function() {
                const requestId = $(this).data('request-id');
                const content = $('#workedDaysContent');

                content.html(
                    '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                );

                $.ajax({
                    url: '{{ route('locum-requests.worked-days', ':id') }}'.replace(':id', requestId),
                    method: 'GET',
                    success: function(data) {
                        let html = '<div class="table-responsive">';
                        html += '<table class="table table-bordered table-sm">';
                        html += '<thead class="table-light">';
                        html += '<tr><th>Date</th><th>Worked</th><th>Entries (Shift / Hours / Platform / Unit)</th></tr>';
                        html += '</thead><tbody>';

                        const wdMap = data.workedDays || {};
                        const btMap = data.bioTimeData || {};
                        
                        // Union of dates from biotime + worked_days so we show whole month
                        const dateSet = new Set([
                            ...Object.keys(btMap || {}),
                            ...Object.keys(wdMap || {}),
                        ]);
                        const allDates = Array.from(dateSet).sort();

                        if (allDates.length === 0) {
                            html += '<tr><td colspan="3" class="text-center text-muted">No worked days data available.</td></tr>';
                        } else {
                            allDates.forEach(dateIso => {
                                const info = wdMap[dateIso] || {};
                                const entries = Array.isArray(info.entries) ? info.entries : [];
                                const workedFlag = (info.worked === '1' || info.worked === 1 || entries.length > 0);

                                // Date label (e.g. "01 Nov")
                                const dateObj = new Date(dateIso);
                                const dateLabel = dateObj.toLocaleDateString('en-GB', {
                                    day: '2-digit',
                                    month: 'short'
                                });

                                // Worked badge
                                const workedBadge = workedFlag ?
                                    '<span class="badge bg-success">Yes</span>' :
                                    '<span class="badge bg-secondary">No</span>';

                                // Entries details
                                let entriesHtml = '';
                                if (entries.length > 0) {
                                    entriesHtml += '<table class="table table-sm table-bordered mb-0">';
                                    entriesHtml += '<thead><tr><th>Shift</th><th>Hours</th><th>Platform</th><th>Unit</th></tr></thead><tbody>';
                                    entries.forEach(e => {
                                        const shift = (e?.shift_name || '—').toString();
                                        const hours = parseFloat(e?.hours || 0).toFixed(2);
                                        const platform = (e?.platform_name || '—').toString();
                                        const unit = (e?.unit_name || '—').toString();
                                        entriesHtml += `<tr><td>${shift}</td><td>${hours}</td><td>${platform}</td><td>${unit}</td></tr>`;
                                    });
                                    entriesHtml += '</tbody></table>';
                                } else {
                                    entriesHtml = '—';
                                }

                                html += `<tr>`;
                                html += `<td>${dateLabel}</td>`;
                                html += `<td>${workedBadge}</td>`;
                                html += `<td>${entriesHtml}</td>`;
                                html += `</tr>`;
                            });
                        }

                        html += '</tbody></table></div>';
                        content.html(html);
                    },
                    error: function() {
                        content.html(
                            '<div class="alert alert-danger">Failed to load worked days. Please try again.</div>'
                        );
                    }
                });
            });

            // Initialize DataTable for Approval Process Table
            let approvalProcessTable;
            if ($('#approvalProcessTable').length > 0) {
                approvalProcessTable = $('#approvalProcessTable').DataTable({
                    order: [
                        [3, 'desc']
                    ], // Sort by Submitted On descending (now column 3)
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
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
                    columnDefs: [{
                            orderable: false,
                            targets: [8]
                        } // Disable sorting on Approval Process column (now column 8)
                    ]
                });
            }

            // Combined filters for DataTable (Status + Year + Month)
            let combinedFilter = null;

            function updateTableTitle() {
                const status = $('#filterStatus').val();
                const titleMap = {
                    '': 'All Requests',
                    'approved': 'Approved Requests',
                    'pending': 'Pending Requests',
                    'rejected': 'Rejected Requests'
                };
                $('#tableTitle').text(titleMap[status] || 'All Requests');
            }

            function applyAllFilters() {
                // Remove existing filter if any
                if (combinedFilter !== null) {
                    const index = $.fn.dataTable.ext.search.indexOf(combinedFilter);
                    if (index !== -1) {
                        $.fn.dataTable.ext.search.splice(index, 1);
                    }
                }

                const status = $('#filterStatus').val();
                const year = $('#filterYear').val();
                const month = $('#filterMonth').val();

                // Update title based on status
                updateTableTitle();

                // Only add filter if at least one is selected
                if (status || year || month) {
                    combinedFilter = function(settings, data, dataIndex) {
                        if (settings.nTable.id !== 'approvalProcessTable') {
                            return true;
                        }

                        const row = $(approvalProcessTable.row(dataIndex).node());

                        // Filter by status using data attribute
                        if (status) {
                            const rowStatus = row.data('status') || '';
                            if (rowStatus !== status) {
                                return false;
                            }
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

                    $.fn.dataTable.ext.search.push(combinedFilter);
                } else {
                    combinedFilter = null;
                }

                if (approvalProcessTable) {
                    approvalProcessTable.draw();
                }
            }

            $('#filterStatus, #filterYear, #filterMonth').on('change', applyAllFilters);

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

            // Quick filter buttons functionality
            $('.quick-filter-btn').on('click', function() {
                const filter = $(this).data('filter');
                const now = new Date();
                let fromYear, fromMonth, toYear, toMonth;

                switch(filter) {
                    case 'this_month':
                        fromYear = toYear = now.getFullYear();
                        fromMonth = toMonth = now.getMonth() + 1;
                        break;
                    case 'last_month':
                        const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                        fromYear = toYear = lastMonth.getFullYear();
                        fromMonth = toMonth = lastMonth.getMonth() + 1;
                        break;
                    case 'last_3_months':
                        const threeMonthsAgo = new Date(now.getFullYear(), now.getMonth() - 2, 1);
                        fromYear = threeMonthsAgo.getFullYear();
                        fromMonth = threeMonthsAgo.getMonth() + 1;
                        toYear = now.getFullYear();
                        toMonth = now.getMonth() + 1;
                        break;
                    case 'this_year':
                        fromYear = toYear = now.getFullYear();
                        fromMonth = 1;
                        toMonth = now.getMonth() + 1;
                        break;
                }

                // Update form fields
                $('#from_year').val(fromYear);
                $('#from_month').val(fromMonth);
                $('#to_year').val(toYear);
                $('#to_month').val(toMonth);
                $('#quick_filter').val(filter);

                // Submit the form
                $('#analyticsFilterForm').submit();
            });

            // Toggle between Summary and Monthly view
            $('#viewSummary').on('click', function() {
                $(this).addClass('active');
                $('#viewMonthly').removeClass('active');
                $('#summaryTableView').show();
                $('#monthlyTableView').hide();
            });

            $('#viewMonthly').on('click', function() {
                $(this).addClass('active');
                $('#viewSummary').removeClass('active');
                $('#summaryTableView').hide();
                $('#monthlyTableView').show();
            });

            // Initialize Monthly Trends Chart - Colorful Bar Chart with Department Tooltip
            @if (isset($analytics['monthly_trends']) && !empty($analytics['monthly_trends']))
                const monthlyTrends = @json($analytics['monthly_trends']);
                const topDepts = @json($analytics['top_departments'] ?? []);
                const deptTrends = @json($analytics['department_trends'] ?? []);

                // Prepare data
                const monthKeys = Object.keys(monthlyTrends);
                const monthLabels = monthKeys.map(key => monthlyTrends[key].label);
                const monthAmounts = monthKeys.map(key => monthlyTrends[key].amount);
                const grandTotal = monthAmounts.reduce((a, b) => a + b, 0);

                // Theme color
                const themeColor = '#61ce70';
                const deptDotColors = ['#61ce70','#4db85c','#3aa248','#278c34','#1cc88a','#45d87a','#5ce48a','#73ea9a','#8af0aa','#a1f6ba','#2ed86a','#17a65a'];

                // Format TZS helper
                function fmtTZS(v) {
                    if (v >= 1000000) return 'TZS ' + (v / 1000000).toFixed(1) + 'M';
                    if (v >= 1000) return 'TZS ' + (v / 1000).toFixed(0) + 'k';
                    return 'TZS ' + v.toLocaleString();
                }

                // Show total above chart
                $('#departmentTrendChart').before(
                    '<div class="text-center mb-2"><span class="text-muted small">Total Cost:</span> <strong style="color:#1f2937;font-size:1.1rem;">TZS ' + grandTotal.toLocaleString('en-US') + '</strong></div>'
                );

                if ($('#departmentTrendChart').length > 0) {
                    const chartOptions = {
                        series: [{
                            name: 'Claims Cost',
                            data: monthAmounts
                        }],
                        chart: {
                            height: 380,
                            type: 'bar',
                            toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
                            animations: { enabled: true, easing: 'easeinout', speed: 800 },
                            fontFamily: 'inherit'
                        },
                        plotOptions: {
                            bar: {
                                columnWidth: '60%',
                                borderRadius: 6,
                                dataLabels: { position: 'top' }
                            }
                        },
                        colors: [themeColor],
                        dataLabels: {
                            enabled: true,
                            offsetY: -22,
                            style: { fontSize: '12px', fontWeight: 700, colors: ['#1f2937'] },
                            formatter: function(val) {
                                if (val >= 1000000) return 'TZS ' + (val / 1000000).toFixed(1) + 'M';
                                if (val >= 1000) return 'TZS ' + (val / 1000).toFixed(0) + 'k';
                                return 'TZS ' + val;
                            }
                        },
                        legend: { show: false },
                        xaxis: {
                            categories: monthLabels,
                            labels: { style: { colors: '#4b5563', fontSize: '12px', fontWeight: 600 } },
                            axisBorder: { show: false },
                            axisTicks: { show: false }
                        },
                        yaxis: {
                            labels: {
                                style: { colors: '#9ca3af', fontSize: '11px' },
                                formatter: function(val) { return fmtTZS(val); }
                            }
                        },
                        grid: {
                            borderColor: '#f3f4f6',
                            strokeDashArray: 4,
                            yaxis: { lines: { show: true } },
                            xaxis: { lines: { show: false } },
                            padding: { top: 10 }
                        },
                        tooltip: {
                            custom: function({ series, seriesIndex, dataPointIndex, w }) {
                                const monthKey = monthKeys[dataPointIndex];
                                const monthData = monthlyTrends[monthKey];
                                const total = monthData.amount;
                                const count = monthData.count;
                                const hours = monthData.hours;
                                const byDept = monthData.by_department || {};

                                let html = '<div style="padding:12px 16px;min-width:260px;font-family:inherit;">';
                                html += '<div style="font-weight:700;font-size:14px;margin-bottom:6px;color:#1f2937;">' + monthData.label + '</div>';
                                html += '<div style="display:flex;justify-content:space-between;margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid #e5e7eb;">';
                                html += '<span style="color:#6b7280;font-size:12px;">Total: <strong style="color:#1f2937;">TZS ' + total.toLocaleString() + '</strong></span>';
                                html += '<span style="color:#6b7280;font-size:12px;margin-left:16px;">' + count + ' requests &middot; ' + hours + ' hrs</span>';
                                html += '</div>';

                                // Department breakdown sorted by amount desc
                                const deptEntries = Object.entries(byDept).filter(([k, v]) => v > 0).sort((a, b) => b[1] - a[1]);
                                if (deptEntries.length > 0) {
                                    html += '<div style="font-size:11px;color:#9ca3af;text-transform:uppercase;font-weight:600;margin-bottom:4px;">Department Breakdown</div>';
                                    deptEntries.forEach(([dept, amtK], i) => {
                                        const amt = amtK * 1000;
                                        const pct = total > 0 ? ((amt / total) * 100).toFixed(0) : 0;
                                        const c = deptDotColors[i % deptDotColors.length];
                                        html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:3px 0;font-size:12px;">';
                                        html += '<div style="display:flex;align-items:center;"><span style="width:8px;height:8px;border-radius:50%;background:' + c + ';display:inline-block;margin-right:6px;"></span><span style="color:#374151;">' + dept + '</span></div>';
                                        html += '<div><strong style="color:#1f2937;">TZS ' + amt.toLocaleString() + '</strong> <span style="color:#9ca3af;font-size:10px;">(' + pct + '%)</span></div>';
                                        html += '</div>';
                                    });
                                } else {
                                    html += '<div style="color:#9ca3af;font-size:12px;">No department data</div>';
                                }
                                html += '</div>';
                                return html;
                            }
                        }
                    };
                    const trendChart = new ApexCharts(document.querySelector('#departmentTrendChart'), chartOptions);
                    trendChart.render();
                }

                // Initialize sparklines for each department
                Object.keys(deptTrends).forEach(function(deptName) {
                    const sparklineId = '#sparkline-' + deptName.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');
                    if ($(sparklineId).length > 0) {
                        // Get monthly data for this department
                        const sparkData = Object.keys(monthlyTrends).map(key => {
                            return (monthlyTrends[key].by_department[deptName] || 0) * 1000; // Convert back from K
                        });

                        const sparkOptions = {
                            chart: {
                                type: 'line',
                                height: 25,
                                width: 80,
                                sparkline: {
                                    enabled: true
                                },
                                animations: {
                                    enabled: false
                                }
                            },
                            series: [{
                                data: sparkData
                            }],
                            stroke: {
                                width: 2,
                                curve: 'smooth'
                            },
                            colors: ['#10b981'],
                            tooltip: {
                                enabled: false
                            }
                        };

                        const sparkChart = new ApexCharts($(sparklineId)[0], sparkOptions);
                        sparkChart.render();
                    }
                });
            @else
                if ($('#departmentTrendChart').length > 0) {
                    $('#departmentTrendChart').html('<div class="text-center p-5 text-muted"><i class="fas fa-chart-bar fa-3x mb-3"></i><p>No data available for the selected period</p></div>');
                }
            @endif
        });
    </script>

    <style>
        .sticky-col {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 1;
        }
        .quick-filter-btn {
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            border-radius: 0;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
            transition: all 0.2s ease;
        }
        .quick-filter-btn:first-child {
            border-radius: 6px 0 0 6px;
        }
        .quick-filter-btn:last-child {
            border-radius: 0 6px 6px 0;
        }
        .quick-filter-btn:hover {
            background-color: #e9ecef;
            color: #333;
            border-color: #adb5bd;
        }
        .quick-filter-btn.active {
            background-color: #61ce70 !important;
            color: #fff !important;
            font-weight: 600;
            border-color: #61ce70;
            box-shadow: 0 2px 4px rgba(97,206,112,0.3);
        }
        #monthlyBreakdownTable {
            font-size: 0.85rem;
        }
        #monthlyBreakdownTable th, #monthlyBreakdownTable td {
            white-space: nowrap;
        }
        .card-header .form-select-sm {
            font-size: 0.875rem;
        }
        .card-header label {
            font-size: 0.8rem;
            opacity: 0.95;
        }
    </style>
@endsection
