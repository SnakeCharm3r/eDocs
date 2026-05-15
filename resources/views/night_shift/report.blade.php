@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .report-card { border: 0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .report-card .card-header { background: #fff; border-bottom: 1px solid #eee; border-radius: 10px 10px 0 0; }
        .table-hover tbody tr:hover { background-color: #f0f7f8 !important; }
        .js-export-btn:disabled { opacity: 0.7; cursor: not-allowed; }
        .js-export-btn .js-spinner { display: inline-block; }
        .js-export-btn .js-spinner.d-none { display: none !important; }

        /* ── Step badges (same as approve page) ── */
        .step-badge { font-size: 0.72rem; padding: 4px 10px; border-radius: 20px; display: inline-block; }

        /* ── Summary stat bar ── */
        .summary-stat {
            display: flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px;
            cursor: default; transition: background 0.15s; user-select: none;
        }
        .summary-stat:hover { background: #f0f0f0; }
        .summary-stat-count { font-size: 1.1rem; font-weight: 700; line-height: 1; }
        .summary-stat-label { font-size: 0.75rem; color: #6c757d; white-space: nowrap; }
        .summary-stat-total    .summary-stat-count { color: #0d6efd; }
        .summary-stat-pending  .summary-stat-count { color: #fd7e14; }
        .summary-stat-approved .summary-stat-count { color: #198754; }
        .summary-stat-rejected .summary-stat-count { color: #dc3545; }
        .summary-stat-amount   .summary-stat-count { color: #198754; }

        /* ── Action buttons ── */
        .action-btn { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Page Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-0 text-dark">Night Allowances Report</h5>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('night-shift.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            {{-- ===== Analytics & Trends ===== --}}
            <div class="card report-card mb-4">
                <div class="card-header py-3">
                    <form method="GET" action="{{ route('night-shift.report') }}" id="analyticsFilterForm">

                        {{-- Title Row --}}
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-chart-line me-2" style="color:#61ce70;"></i>Analytics & Trends
                            </h6>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-success fw-semibold">
                                    <i class="fas fa-filter me-1"></i> Apply
                                </button>
                                @if($quickFilter || request()->hasAny(['from_year','to_year','analytics_dept']))
                                <a href="{{ route('night-shift.report') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-redo me-1"></i> Reset
                                </a>
                                @endif
                                <button type="button" id="analyticsExportBtn"
                                        class="btn btn-sm btn-outline-success js-export-btn"
                                        data-loading-label="Exporting...">
                                    <span class="btn-text"><i class="fas fa-file-excel me-1"></i>Export</span>
                                    <span class="spinner-border spinner-border-sm ms-1 d-none js-spinner" role="status"></span>
                                </button>
                            </div>
                        </div>

                        {{-- Filters Row --}}
                        <div class="row g-2 align-items-end">
                            {{-- Quick filters --}}
                            <div class="col-12 col-lg-auto">
                                <label class="text-muted small fw-semibold mb-1 d-block">
                                    <i class="fas fa-bolt me-1"></i>Quick:
                                </label>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn quick-filter-btn {{ $quickFilter === 'this_month'    ? 'btn-success' : 'btn-outline-secondary' }}" data-filter="this_month">This Month</button>
                                    <button type="button" class="btn quick-filter-btn {{ $quickFilter === 'last_month'    ? 'btn-success' : 'btn-outline-secondary' }}" data-filter="last_month">Last Month</button>
                                    <button type="button" class="btn quick-filter-btn {{ $quickFilter === 'last_3_months' ? 'btn-success' : 'btn-outline-secondary' }}" data-filter="last_3_months">Last 3M</button>
                                    <button type="button" class="btn quick-filter-btn {{ $quickFilter === 'this_year'     ? 'btn-success' : 'btn-outline-secondary' }}" data-filter="this_year">This Year</button>
                                </div>
                                <input type="hidden" name="quick_filter" id="quick_filter" value="{{ $quickFilter }}">
                            </div>

                            {{-- From --}}
                            <div class="col-6 col-sm-4 col-lg-2">
                                <label class="text-muted small fw-semibold mb-1 d-block">
                                    <i class="fas fa-calendar-alt me-1"></i>From:
                                </label>
                                @php $fromVal = $startDate->format('Y-m'); @endphp
                                <input type="month" id="from_month_input" class="form-control form-control-sm"
                                       value="{{ $fromVal }}" min="2020-01" max="{{ date('Y-m') }}">
                                <input type="hidden" name="from_year"  id="from_year"  value="{{ $startDate->year }}">
                                <input type="hidden" name="from_month" id="from_month" value="{{ $startDate->month }}">
                            </div>

                            {{-- To --}}
                            <div class="col-6 col-sm-4 col-lg-2">
                                <label class="text-muted small fw-semibold mb-1 d-block">
                                    <i class="fas fa-calendar-check me-1"></i>To:
                                </label>
                                @php $toVal = $endDate->format('Y-m'); @endphp
                                <input type="month" id="to_month_input" class="form-control form-control-sm"
                                       value="{{ $toVal }}" min="2020-01" max="{{ date('Y-m') }}">
                                <input type="hidden" name="to_year"  id="to_year"  value="{{ $endDate->year }}">
                                <input type="hidden" name="to_month" id="to_month" value="{{ $endDate->month }}">
                            </div>

                            {{-- Department --}}
                            <div class="col-12 col-sm-4 col-lg-3">
                                <label class="text-muted small fw-semibold mb-1 d-block">
                                    <i class="fas fa-building me-1"></i>Department:
                                </label>
                                <select id="analytics_dept" name="analytics_dept" class="form-select form-select-sm">
                                    <option value="_all">All Departments</option>
                                    @foreach($allDepartments as $dept)
                                        <option value="{{ $dept->dept_name }}"
                                            {{ $selectedDept === $dept->dept_name ? 'selected' : '' }}>
                                            {{ $dept->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            function syncMonth(inputId, yearId, monthId) {
                                var el = document.getElementById(inputId);
                                if (!el) return;
                                el.addEventListener('change', function () {
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

                            document.querySelectorAll('.quick-filter-btn').forEach(function (btn) {
                                btn.addEventListener('click', function () {
                                    document.getElementById('quick_filter').value = this.dataset.filter;
                                    document.getElementById('analyticsFilterForm').submit();
                                });
                            });

                            document.getElementById('analyticsExportBtn')?.addEventListener('click', function () {
                                var qs = new URLSearchParams({
                                    pay_year:  document.getElementById('to_year').value,
                                    pay_month: document.getElementById('to_month').value,
                                    from_year: document.getElementById('from_year').value,
                                    from_month:document.getElementById('from_month').value,
                                    to_year:   document.getElementById('to_year').value,
                                    to_month:  document.getElementById('to_month').value,
                                });
                                var dept = document.getElementById('analytics_dept').value;
                                if (dept && dept !== '_all') qs.set('department', dept);
                                var qf = document.getElementById('quick_filter').value;
                                if (qf) qs.set('quick_filter', qf);

                                var btn = this;
                                btn.disabled = true;
                                btn.querySelector('.btn-text').textContent = btn.dataset.loadingLabel;
                                btn.querySelector('.js-spinner').classList.remove('d-none');
                                window.location.href = '{{ route('night-shift.report.export') }}?' + qs.toString();
                                setTimeout(function () {
                                    btn.disabled = false;
                                    btn.querySelector('.btn-text').innerHTML = '<i class="fas fa-file-excel me-1"></i>Export';
                                    btn.querySelector('.js-spinner').classList.add('d-none');
                                }, 4000);
                            });
                        });
                        </script>
                    </form>
                </div>

                <div class="card-body">
                    @php
                        $trendsArray   = array_values($analytics['monthly_trends'] ?? []);
                        $totalAmount   = array_sum(array_column($trendsArray, 'amount'));
                        $totalClaims   = array_sum(array_column($trendsArray, 'count'));
                    @endphp

                    {{-- Bar Chart --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white py-2">
                                    <h6 class="mb-0" style="color:#333; font-weight:600;">
                                        <i class="fas fa-chart-bar me-2" style="color:#3b82f6;"></i>
                                        Claims Cost by Month — {{ $startDate->format('M Y') }} to {{ $endDate->format('M Y') }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($totalAmount > 0)
                                    <p class="text-muted small mb-2">
                                        Total Cost: <strong>TZS {{ number_format($totalAmount, 0) }}</strong>
                                    </p>
                                    @endif
                                    <div id="nightTrendChart" style="min-height:320px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Department Performance Table --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0" style="color:#333; font-weight:600;">
                                <i class="fas fa-building me-2 text-info"></i>Department Performance Summary
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm" id="deptSummaryTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Department</th>
                                            <th class="text-end">Total Amount (TZS)</th>
                                            <th class="text-end">Claims</th>
                                            <th class="text-end">Days</th>
                                            <th class="text-end">Employees</th>
                                            <th class="text-end">Avg / Claim</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(count($analytics['department_trends'] ?? []) > 0)
                                            @php $maxAmt = collect($analytics['department_trends'])->max('total_amount'); @endphp
                                            @foreach($analytics['department_trends'] as $dName => $dData)
                                            @php $pct = $maxAmt > 0 ? ($dData['total_amount'] / $maxAmt) * 100 : 0; @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ $dName }}</strong>
                                                    <div class="progress mt-1" style="height:4px;">
                                                        <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                                                    </div>
                                                </td>
                                                <td class="text-end fw-semibold">{{ number_format($dData['total_amount'], 0) }}</td>
                                                <td class="text-end">{{ $dData['total_requests'] }}</td>
                                                <td class="text-end">{{ $dData['total_days'] }}</td>
                                                <td class="text-end">{{ $dData['total_employees'] }}</td>
                                                <td class="text-end">{{ number_format($dData['avg_per_request'], 0) }}</td>
                                            </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="fas fa-info-circle me-2"></i>No data for the selected period
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    @if(count($analytics['department_trends'] ?? []) > 0)
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td>TOTAL</td>
                                            <td class="text-end">{{ number_format(collect($analytics['department_trends'])->sum('total_amount'), 0) }}</td>
                                            <td class="text-end">{{ collect($analytics['department_trends'])->sum('total_requests') }}</td>
                                            <td class="text-end">{{ collect($analytics['department_trends'])->sum('total_days') }}</td>
                                            <td class="text-end">{{ collect($analytics['department_trends'])->sum('total_employees') }}</td>
                                            <td class="text-end">—</td>
                                        </tr>
                                    </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Claims Table ===== --}}
            @php
                $claimTotal    = $actionedRequests->count();
                $claimPending  = $actionedRequests->where('status', 'pending')->count();
                $claimApproved = $actionedRequests->where('status', 'approved')->count();
                $claimRejected = $actionedRequests->where('status', 'rejected')->count();
                $claimAmount   = $actionedRequests->sum(fn($c) => $c->total_days_worked * $ratePerDay);
            @endphp

            {{-- Summary stat bar --}}
            @if($claimTotal > 0)
            <div class="card mb-3 border-0 shadow-sm" style="border-radius:10px;">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-1">
                            <i class="fas fa-chart-bar text-muted me-1" style="font-size:0.85rem;"></i>
                            <small class="text-muted fw-semibold">Claims Summary</small>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="summary-stat summary-stat-total">
                                <span class="summary-stat-count">{{ $claimTotal }}</span>
                                <span class="summary-stat-label"><i class="fas fa-file-alt me-1"></i>Total</span>
                            </div>
                            <div class="summary-stat summary-stat-pending">
                                <span class="summary-stat-count">{{ $claimPending }}</span>
                                <span class="summary-stat-label"><i class="fas fa-clock me-1"></i>Pending</span>
                            </div>
                            <div class="summary-stat summary-stat-approved">
                                <span class="summary-stat-count">{{ $claimApproved }}</span>
                                <span class="summary-stat-label"><i class="fas fa-check-circle me-1"></i>Approved</span>
                            </div>
                            <div class="summary-stat summary-stat-rejected">
                                <span class="summary-stat-count">{{ $claimRejected }}</span>
                                <span class="summary-stat-label"><i class="fas fa-times-circle me-1"></i>Rejected</span>
                            </div>
                            <div class="summary-stat summary-stat-amount">
                                <span class="summary-stat-count">{{ number_format($claimAmount, 0) }}</span>
                                <span class="summary-stat-label"><i class="fas fa-money-bill-wave me-1"></i>TZS</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="card shadow-sm mb-4" style="border-radius:10px; border:1px solid #e9ecef;">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-radius:10px 10px 0 0;">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-table me-2 text-muted"></i>All Claims
                    </h6>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <select id="filterStatus" class="form-select form-select-sm" style="width:auto;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <select id="filterYear" class="form-select form-select-sm" style="width:auto;">
                            <option value="">All Years</option>
                            @foreach($actionedRequests->pluck('year')->unique()->sortDesc() as $y)
                                <option value="{{ $y }}" {{ (string)$y === date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <select id="filterMonth" class="form-select form-select-sm" style="width:auto;">
                            <option value="">All Months</option>
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $mn)
                                <option value="{{ $mn }}" {{ $mn === date('F') ? 'selected' : '' }}>{{ $mn }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body p-3">
                    @if($actionedRequests->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0 fw-semibold">No claims found</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table id="nightClaimsTable" class="table table-hover table-striped align-middle mb-0" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:2rem">#</th>
                                    <th>Submitted By</th>
                                    <th>Department</th>
                                    <th>Month / Year</th>
                                    <th class="text-center">Days</th>
                                    <th class="text-end">Amount (TZS)</th>
                                    <th>Status</th>
                                    <th>Current Step</th>
                                    <th>Date</th>
                                    <th class="text-center no-sort" style="width:50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($actionedRequests as $i => $claim)
                                @php
                                    $fullName = collect([
                                        $claim->submitter->fname ?? null,
                                        $claim->submitter->mname ?? null,
                                        $claim->submitter->lname ?? null,
                                    ])->filter()->implode(' ') ?: '—';
                                    $code     = $claim->submitter->ccbrt_code ?? '—';
                                    $deptName = $claim->department?->dept_name ?? '—';
                                    $amount   = $claim->total_days_worked * $ratePerDay;

                                    $histories   = $claim->workflow?->histories ?? collect();
                                    $pendingStep = $histories->firstWhere('status', 0);
                                    $stepName    = $pendingStep?->step_name ?? null;

                                    $statusColors = [
                                        'approved' => ['bg' => '#d1e7dd', 'text' => '#0f5132', 'icon' => 'check-circle'],
                                        'rejected' => ['bg' => '#f8d7da', 'text' => '#842029', 'icon' => 'times-circle'],
                                        'pending'  => ['bg' => '#fff3cd', 'text' => '#856404', 'icon' => 'clock'],
                                    ];
                                    $stc = $statusColors[$claim->status] ?? $statusColors['pending'];

                                    $stepColors = [
                                        'Incharge Approval'          => ['bg' => '#fff3cd', 'text' => '#856404', 'icon' => 'user-shield'],
                                        'Platform Manager Approval'  => ['bg' => '#cfe2ff', 'text' => '#084298', 'icon' => 'sitemap'],
                                        'Line Manager Approval'      => ['bg' => '#d1e7dd', 'text' => '#0f5132', 'icon' => 'user-tie'],
                                        'HEC Approval'               => ['bg' => '#e2d9f3', 'text' => '#4a1d91', 'icon' => 'landmark'],
                                        'HR Approval'                => ['bg' => '#f8d7da', 'text' => '#842029', 'icon' => 'users'],
                                    ];
                                    $sc = $stepName ? ($stepColors[$stepName] ?? ['bg' => '#e2e3e5', 'text' => '#41464b', 'icon' => 'clock']) : null;

                                    $pendingWith = null;
                                    if ($claim->status === 'pending' && $pendingStep) {
                                        $ab = $pendingStep->attendedBy;
                                        $pendingWith = $ab ? trim($ab->fname.' '.$ab->lname) : null;
                                    }

                                    $dateLabel = $claim->status === 'approved' && $claim->approved_at
                                        ? \Carbon\Carbon::parse($claim->approved_at)->format('d M Y')
                                        : $claim->created_at->format('d M Y');
                                    $dateAgo = $claim->status === 'approved' && $claim->approved_at
                                        ? \Carbon\Carbon::parse($claim->approved_at)->diffForHumans()
                                        : $claim->created_at->diffForHumans();
                                @endphp
                                <tr data-year="{{ $claim->year }}" data-month="{{ $claim->month }}" data-status="{{ $claim->status }}">
                                    <td class="text-muted small">{{ $i + 1 }}</td>
                                    <td>
                                        <strong>{{ $fullName }}</strong>
                                        @if($code !== '—')
                                        <br><small class="text-muted">{{ $code }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $deptName }}</td>
                                    <td><strong>{{ $claim->month }} {{ $claim->year }}</strong></td>
                                    <td class="text-center">{{ $claim->total_days_worked }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($amount, 2) }}</td>
                                    <td>
                                        <span class="step-badge" style="background:{{ $stc['bg'] }}; color:{{ $stc['text'] }};">
                                            <i class="fas fa-{{ $stc['icon'] }} me-1" style="font-size:0.65rem;"></i>{{ ucfirst($claim->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($sc && $claim->status === 'pending')
                                            <span class="step-badge" style="background:{{ $sc['bg'] }}; color:{{ $sc['text'] }};">
                                                <i class="fas fa-{{ $sc['icon'] }} me-1" style="font-size:0.65rem;"></i>{{ $stepName }}
                                            </span>
                                            @if($pendingWith)
                                                <br><small class="text-muted">{{ $pendingWith }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted" data-order="{{ $claim->created_at->format('Y-m-d') }}">
                                        {{ $dateLabel }}
                                        <br><small>{{ $dateAgo }}</small>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('night-shift.show', $claim) }}" class="btn btn-sm action-btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

    <!-- ApexCharts -->
    <script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ── Bar Chart ──────────────────────────────────────────────────
        @php
            $chartLabels  = array_column($trendsArray, 'label');
            $chartAmounts = array_map(fn($t) => round($t['amount'] / 1000, 1), $trendsArray);
        @endphp
        var chartLabels  = @json($chartLabels);
        var chartAmounts = @json($chartAmounts);

        if (document.getElementById('nightTrendChart')) {
            var chartOptions = {
                series: [{ name: 'Amount (TZS K)', data: chartAmounts }],
                chart: { type: 'bar', height: 320, toolbar: { show: true } },
                colors: ['#61ce70'],
                xaxis: { categories: chartLabels },
                yaxis: {
                    labels: {
                        formatter: function (v) { return 'TZS ' + v + 'k'; }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (v) { return v > 0 ? 'TZS ' + v + 'k' : ''; }
                },
                tooltip: {
                    y: { formatter: function (v) { return 'TZS ' + (v * 1000).toLocaleString(); } }
                },
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            };
            new ApexCharts(document.getElementById('nightTrendChart'), chartOptions).render();
        }
    });
    </script>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var tbl = document.getElementById('nightClaimsTable');
    if (!tbl) return;

    // Register the custom filter BEFORE DataTable init so it applies on first draw
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'nightClaimsTable') return true;
        var row = settings.aoData[dataIndex].nTr;
        if (!row) return true;
        var st = document.getElementById('filterStatus').value;
        var yr = document.getElementById('filterYear').value;
        var mo = document.getElementById('filterMonth').value;
        var matchSt = !st || row.dataset.status === st;
        var matchYr = !yr || String(row.dataset.year) === yr;
        var matchMo = !mo || row.dataset.month === mo;
        return matchSt && matchYr && matchMo;
    });

    // Init DataTable (prevent double-init)
    if (!$.fn.dataTable.isDataTable(tbl)) {
        var dt = $(tbl).DataTable({
            pageLength: 20,
            lengthMenu: [[20, 50, 100], [20, 50, 100]],
            ordering: true,
            autoWidth: false,
            order: [[8, 'desc']],
            columnDefs: [{ targets: 'no-sort', orderable: false }]
        });
    } else {
        var dt = $(tbl).DataTable();
    }

    function redraw() { dt.draw(); }
    document.getElementById('filterStatus')?.addEventListener('change', redraw);
    document.getElementById('filterYear')?.addEventListener('change', redraw);
    document.getElementById('filterMonth')?.addEventListener('change', redraw);
});
</script>
@endpush
