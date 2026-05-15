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
        .report-card { border: 0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .report-card .card-header { background: #fff; border-bottom: 1px solid #eee; border-radius: 10px 10px 0 0; }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1 fw-bold text-dark"><i class="fas fa-phone-alt me-2" style="color:#61ce70;"></i>On-Call Reports</h4>
                    <small class="text-muted">Analytics, trends & approved claims</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('oncall_requests.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-list me-1"></i>Requests</a>
                    <a href="{{ route('oncall_requests.report') }}" class="btn btn-primary btn-sm"><i class="fas fa-chart-line me-1"></i>Reports</a>
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
            @if (!empty($isHr) || !empty($isLineManager) || !empty($isHecMember))
                <div class="card report-card mb-4">
                    <div class="card-header py-3">
                        <form method="GET" action="{{ route('oncall_requests.report') }}" id="analyticsFilterForm">
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
                                    <i class="fas fa-chart-line me-2" style="color:#61ce70;"></i>Analytics & Trends
                                </h6>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-success fw-semibold">
                                        <i class="fas fa-filter me-1"></i> Apply
                                    </button>
                                    @if (!empty($selectedFromYear) || !empty($selectedFromMonth) || !empty($selectedToYear) || !empty($selectedToMonth) || !empty($selectedDept) || !empty($selectedQuickFilter))
                                        <a href="{{ route('oncall_requests.report') }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-redo me-1"></i> Reset
                                        </a>
                                    @endif
                                    @if(auth()->user()->hasRole('hr') || auth()->user()->hasAnyRole(['coo','cfo','cms','ccdro']) || auth()->user()->canAny(['view locum reports','ict_acces_report']))
                                    <button type="button" id="analyticsExportBtn" class="btn btn-sm btn-outline-success js-export-btn" data-loading-label="Exporting...">
                                        <span class="btn-text"><i class="fas fa-file-excel me-1"></i>Export</span>
                                        <span class="spinner-border spinner-border-sm ms-1 d-none js-spinner" role="status"></span>
                                    </button>
                                    @endif
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
                                            pay_year:   toYear,
                                            pay_month:  toMonth,
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

                                        window.location.href = '{{ route('oncall_requests.export') }}?' + params.toString();

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
                        {{-- Chart --}}
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-2">
                                <h6 class="mb-0" style="color: #333; font-weight: 600;">
                                    <i class="fas fa-chart-bar me-2" style="color:#61ce70;"></i>Claims Cost by Month
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="departmentTrendChart" style="min-height: 380px;"></div>
                            </div>
                        </div>

                        {{-- Department Performance Table --}}
                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-2">
                                <h6 class="mb-0" style="color: #333; font-weight: 600;">
                                    <i class="fas fa-building me-2 text-info"></i>Department Performance Summary
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-sm">
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
                                                        <td class="text-end">TZS {{ number_format($deptData['total_amount'], 0) }}</td>
                                                        <td class="text-end">{{ number_format($deptData['total_requests'], 0) }}</td>
                                                        <td class="text-end">{{ number_format($deptData['total_hours'], 1) }}</td>
                                                        <td class="text-end">TZS {{ number_format($deptData['avg_per_request'], 0) }}</td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="fas fa-info-circle me-2"></i>No data available
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- View Requests & Approval Process -->
            @if (!empty($isHr) || !empty($isLineManager) || !empty($isHecMember))
                <div class="card report-card mb-4">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-list me-2" style="color:#61ce70;"></i>
                                <span id="tableTitle">All Requests</span>
                            </h6>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <select id="tableFilterYear" class="form-select form-select-sm" style="width: auto;">
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
                                <select id="tableFilterMonth" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">All Months</option>
                                    @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $num => $name)
                                        <option value="{{ $num }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @if (!empty($isHr) || auth()->user()?->can('ict_acces_report'))
                                    <form id="filterForm" method="GET" action="{{ route('oncall_requests.export') }}" class="d-inline">
                                        <input type="hidden" name="pay_year" id="exportYear" value="">
                                        <input type="hidden" name="pay_month" id="exportMonth" value="">
                                        <input type="hidden" name="department" value="_all">
                                        <input type="hidden" name="basis" value="approved">
                                        <input type="hidden" name="hr" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-success js-export-btn" data-loading-label="Exporting...">
                                            <span class="btn-text"><i class="fas fa-file-excel me-1"></i>Export</span>
                                            <span class="spinner-border spinner-border-sm ms-1 d-none js-spinner" role="status"></span>
                                        </button>
                                    </form>
                                @endif
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

                                            $submittedOn = $request->created_at
                                                ? \Carbon\Carbon::parse($request->created_at)->format('Y-m-d H:i')
                                                : '—';

                                            $histories = $request->workflow
                                                ? $request->workflow->histories->sortBy('id')
                                                : collect();

                                            // Check if approved
                                            $isApproved = false;
                                            $approvedOn = '—';
                                            $approvedMonthYear = '—';

                                            if (
                                                $request->status === 'approved' ||
                                                ($request->workflow &&
                                                    (int) $request->workflow->work_flow_completed === 1)
                                            ) {
                                                $hrApproved = $histories
                                                    ->where('step_name', 'HR Approval')
                                                    ->where('status', 1)
                                                    ->first();
                                                if ($hrApproved) {
                                                    $isApproved = true;
                                                    $approvedAt =
                                                        $hrApproved->updated_at ??
                                                        ($hrApproved->created_at ?? $hrApproved->attend_date);
                                                    if ($approvedAt) {
                                                        $approvedDate = \Carbon\Carbon::parse($approvedAt);
                                                        $approvedOn = $approvedDate->format('Y-m-d H:i');
                                                        $approvedMonthYear = $approvedDate->format('F Y');
                                                    }
                                                } else {
                                                    // Check if any approval step is completed
                                                    $anyApproved = $histories
                                                        ->where('status', 1)
                                                        ->sortByDesc('id')
                                                        ->first();
                                                    if (
                                                        $anyApproved &&
                                                        (int) $request->workflow->work_flow_completed === 1
                                                    ) {
                                                        $isApproved = true;
                                                        $approvedAt =
                                                            $anyApproved->updated_at ??
                                                            ($anyApproved->created_at ?? $anyApproved->attend_date);
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
                                            <td>{{ (int) ($request->number_of_days ?? 0) }}</td>
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
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary view-approval-process"
                                                    data-request-id="{{ $request->id }}" data-bs-toggle="modal"
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
            @endif

            <!-- Approval Process Modal -->
            @if (!empty($isHr) || !empty($isLineManager) || !empty($isHecMember))
                <div class="modal fade" id="approvalProcessModal" tabindex="-1"
                    aria-labelledby="approvalProcessModalLabel" aria-hidden="true">
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
            @endif

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
            $('#tableFilterYear, #tableFilterMonth').on('change', function() {
                $('#exportYear').val($('#tableFilterYear').val());
                $('#exportMonth').val($('#tableFilterMonth').val());
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

            // View approval process
            $(document).on('click', '.view-approval-process', function() {
                const requestId = $(this).data('request-id');
                const content = $('#approvalProcessContent');
                content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                $.ajax({
                    url: '{{ route('oncall_requests.show-details', ':id') }}'.replace(':id', requestId),
                    method: 'GET',
                    success: function(response) {
                        let html = '<div class="timeline">';
                        if (response.workflow && response.workflow.steps) {
                            response.workflow.steps.forEach(function(step) {
                                const statusClass = step.status === 'Approved' ? 'success' : step.status === 'Rejected' ? 'danger' : 'warning';
                                const statusIcon = step.status === 'Approved' ? 'fa-check-circle' : step.status === 'Rejected' ? 'fa-times-circle' : 'fa-clock';
                                html += `<div class="d-flex mb-3">
                                    <div class="flex-shrink-0"><div class="rounded-circle bg-${statusClass} d-flex align-items-center justify-content-center" style="width:40px;height:40px;color:#fff;"><i class="fas ${statusIcon}"></i></div></div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">${step.step}</h6>
                                        <p class="mb-1 text-muted small"><strong>Attended by:</strong> ${step.attended_by}<br><strong>Status:</strong> <span class="badge bg-${statusClass}">${step.status}</span>${step.acted_at !== '—' ? '<br><strong>Acted at:</strong> ' + step.acted_at : ''}</p>
                                        ${step.remark ? '<p class="mb-1 small"><strong>Remark:</strong> ' + step.remark + '</p>' : ''}
                                        ${step.rejection ? '<p class="mb-1 small text-danger"><strong>Rejection Reason:</strong> ' + step.rejection + '</p>' : ''}
                                    </div></div>`;
                            });
                        } else { html += '<p class="text-muted">No approval process data available.</p>'; }
                        html += '</div>';
                        content.html(html);
                    },
                    error: function() { content.html('<div class="alert alert-danger">Failed to load approval process.</div>'); }
                });
            });

            // DataTable
            let approvalProcessTable;
            if ($('#approvalProcessTable').length > 0) {
                approvalProcessTable = $('#approvalProcessTable').DataTable({
                    order: [[3, 'desc']],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    pagingType: 'simple_numbers',
                    language: { search: "Search:", lengthMenu: "Show _MENU_ entries", info: "Showing _START_ to _END_ of _TOTAL_ entries", emptyTable: "No requests found" },
                    columnDefs: [{ orderable: false, targets: [9] }]
                });
            }

            // Year/Month filter for DataTable
            let yearMonthFilter = null;
            function applyYearMonthFilter() {
                if (yearMonthFilter !== null) {
                    const idx = $.fn.dataTable.ext.search.indexOf(yearMonthFilter);
                    if (idx !== -1) $.fn.dataTable.ext.search.splice(idx, 1);
                }
                const year = $('#tableFilterYear').val();
                const month = $('#tableFilterMonth').val();
                if (year || month) {
                    yearMonthFilter = function(settings, data) {
                        if (settings.nTable.id !== 'approvalProcessTable') return true;
                        const submittedOn = data[3] || '';
                        if (year && submittedOn && submittedOn.substring(0, 4) !== year) return false;
                        if (month && submittedOn) {
                            const sm = new Date(submittedOn).getMonth() + 1;
                            if (parseInt(month) !== sm) return false;
                        }
                        return true;
                    };
                    $.fn.dataTable.ext.search.push(yearMonthFilter);
                } else { yearMonthFilter = null; }
                if (approvalProcessTable) approvalProcessTable.draw();
            }
            $('#tableFilterYear, #tableFilterMonth').on('change', applyYearMonthFilter);

            // Quick filter buttons
            $('.quick-filter-btn').on('click', function() {
                const filter = $(this).data('filter');
                const now = new Date();
                let fromYear, fromMonth, toYear, toMonth;
                switch(filter) {
                    case 'this_month': fromYear = toYear = now.getFullYear(); fromMonth = toMonth = now.getMonth() + 1; break;
                    case 'last_month': { const lm = new Date(now.getFullYear(), now.getMonth() - 1, 1); fromYear = toYear = lm.getFullYear(); fromMonth = toMonth = lm.getMonth() + 1; break; }
                    case 'last_3_months': { const tm = new Date(now.getFullYear(), now.getMonth() - 2, 1); fromYear = tm.getFullYear(); fromMonth = tm.getMonth() + 1; toYear = now.getFullYear(); toMonth = now.getMonth() + 1; break; }
                    case 'this_year': fromYear = toYear = now.getFullYear(); fromMonth = 1; toMonth = now.getMonth() + 1; break;
                }
                $('#from_year').val(fromYear); $('#from_month').val(fromMonth);
                $('#to_year').val(toYear); $('#to_month').val(toMonth);
                // Sync month picker displays
                const pad = n => String(n).padStart(2,'0');
                const fromInput = document.getElementById('from_month_input');
                const toInput   = document.getElementById('to_month_input');
                if (fromInput) fromInput.value = fromYear + '-' + pad(fromMonth);
                if (toInput)   toInput.value   = toYear   + '-' + pad(toMonth);
                $('#quick_filter').val(filter);
                $('#analyticsFilterForm').submit();
            });

            // Colorful Bar Chart with Department Tooltip
            @if (isset($analytics['monthly_trends']) && !empty($analytics['monthly_trends']))
                const monthlyTrends = @json($analytics['monthly_trends']);
                const monthKeys = Object.keys(monthlyTrends);
                const monthLabels = monthKeys.map(key => monthlyTrends[key].label);
                const monthAmounts = monthKeys.map(key => monthlyTrends[key].amount);
                const grandTotal = monthAmounts.reduce((a, b) => a + b, 0);

                const themeColor = '#61ce70';
                const deptDotColors = ['#61ce70','#4db85c','#3aa248','#278c34','#1cc88a','#45d87a','#5ce48a','#73ea9a','#8af0aa','#a1f6ba','#2ed86a','#17a65a'];

                function fmtTZS(v) {
                    if (v >= 1000000) return 'TZS ' + (v / 1000000).toFixed(1) + 'M';
                    if (v >= 1000) return 'TZS ' + (v / 1000).toFixed(0) + 'k';
                    return 'TZS ' + v.toLocaleString();
                }

                if ($('#departmentTrendChart').length > 0) {
                    const chartOptions = {
                        series: [{ name: 'Claims Cost', data: monthAmounts }],
                        chart: { height: 380, type: 'bar', toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } }, animations: { enabled: true, easing: 'easeinout', speed: 800 }, fontFamily: 'inherit' },
                        plotOptions: { bar: { columnWidth: '60%', borderRadius: 6, dataLabels: { position: 'top' } } },
                        colors: [themeColor],
                        dataLabels: {
                            enabled: true, offsetY: -22,
                            style: { fontSize: '12px', fontWeight: 700, colors: ['#1f2937'] },
                            formatter: function(val) {
                                if (val >= 1000000) return 'TZS ' + (val / 1000000).toFixed(1) + 'M';
                                if (val >= 1000) return 'TZS ' + (val / 1000).toFixed(0) + 'k';
                                return 'TZS ' + val;
                            }
                        },
                        legend: { show: false },
                        xaxis: { categories: monthLabels, labels: { style: { colors: '#4b5563', fontSize: '12px', fontWeight: 600 } }, axisBorder: { show: false }, axisTicks: { show: false } },
                        yaxis: { labels: { style: { colors: '#9ca3af', fontSize: '11px' }, formatter: function(val) { return fmtTZS(val); } } },
                        grid: { borderColor: '#f3f4f6', strokeDashArray: 4, yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } }, padding: { top: 10 } },
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
                                html += '<span style="color:#6b7280;font-size:12px;margin-left:16px;">' + count + ' requests &middot; ' + hours + ' hrs</span></div>';
                                const deptEntries = Object.entries(byDept).filter(([k, v]) => v > 0).sort((a, b) => b[1] - a[1]);
                                if (deptEntries.length > 0) {
                                    html += '<div style="font-size:11px;color:#9ca3af;text-transform:uppercase;font-weight:600;margin-bottom:4px;">Department Breakdown</div>';
                                    deptEntries.forEach(([dept, amtK], i) => {
                                        const amt = amtK * 1000;
                                        const pct = total > 0 ? ((amt / total) * 100).toFixed(0) : 0;
                                        const c = deptDotColors[i % deptDotColors.length];
                                        html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:3px 0;font-size:12px;">';
                                        html += '<div style="display:flex;align-items:center;"><span style="width:8px;height:8px;border-radius:50%;background:' + c + ';display:inline-block;margin-right:6px;"></span><span style="color:#374151;">' + dept + '</span></div>';
                                        html += '<div><strong style="color:#1f2937;">TZS ' + amt.toLocaleString() + '</strong> <span style="color:#9ca3af;font-size:10px;">(' + pct + '%)</span></div></div>';
                                    });
                                } else { html += '<div style="color:#9ca3af;font-size:12px;">No department data</div>'; }
                                html += '</div>';
                                return html;
                            }
                        }
                    };
                    new ApexCharts(document.querySelector('#departmentTrendChart'), chartOptions).render();
                }
            @else
                if ($('#departmentTrendChart').length > 0) {
                    $('#departmentTrendChart').html('<div class="text-center p-5 text-muted"><i class="fas fa-chart-bar fa-3x mb-3"></i><p>No data available for the selected period</p></div>');
                }
            @endif
        });
    </script>
@endsection
