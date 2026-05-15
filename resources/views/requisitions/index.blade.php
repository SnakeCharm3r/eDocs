@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <style>
        .page-wrapper { visibility: visible !important; }

        table.dataTable { border-collapse: collapse !important; }
        table.dataTable thead th {
            font-weight: 600;
            white-space: nowrap;
            background: #f8f9fa;
            font-size: 0.85rem;
            padding: 0.65rem 0.75rem;
        }
        table.dataTable tbody td {
            vertical-align: middle;
            font-size: 0.875rem;
            padding: 0.6rem 0.75rem;
        }
        table.dataTable tbody tr:hover { background-color: #f0f7f3 !important; }

        /* DataTables controls */
        div.dataTables_wrapper div.dataTables_length label,
        div.dataTables_wrapper div.dataTables_filter label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0;
        }
        div.dataTables_wrapper div.dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.3rem 0.65rem;
            font-size: 0.85rem;
            margin-left: 0.4rem;
        }
        div.dataTables_wrapper div.dataTables_filter input:focus {
            border-color: #007A33;
            outline: none;
            box-shadow: 0 0 0 0.15rem rgba(0,122,51,0.15);
        }
        div.dataTables_wrapper div.dataTables_length select {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
            margin: 0 0.3rem;
        }
        div.dataTables_wrapper div.dataTables_info {
            font-size: 0.82rem;
            color: #6c757d;
            padding-top: 0.6rem;
        }
        div.dataTables_wrapper div.dataTables_paginate {
            padding-top: 0.3rem;
        }
        div.dataTables_wrapper div.dataTables_paginate .paginate_button {
            border-radius: 5px !important;
            font-size: 0.82rem;
            padding: 0.25rem 0.6rem !important;
        }
        div.dataTables_wrapper div.dataTables_paginate .paginate_button.current,
        div.dataTables_wrapper div.dataTables_paginate .paginate_button.current:hover {
            background: #007A33 !important;
            border-color: #007A33 !important;
            color: #fff !important;
        }
        div.dataTables_wrapper div.dataTables_paginate .paginate_button:hover {
            background: #f0f7f3 !important;
            border-color: #dee2e6 !important;
            color: #007A33 !important;
        }

        /* Card */
        .card { border-radius: 10px; }
        .card-header { border-bottom: 1px solid #e9ecef; }
        .card.shadow-sm { box-shadow: 0 1px 4px rgba(0,0,0,.07) !important; }

        /* Badge */
        .badge { font-weight: 500; padding: 0.35em 0.65em; }

        /* Filter bar */
        .filter-bar { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; }
        .filter-bar .form-select, .filter-bar .form-control {
            border-color: #dee2e6;
            font-size: 0.85rem;
        }
        .filter-bar .form-select:focus, .filter-bar .form-control:focus {
            border-color: #007A33;
            box-shadow: 0 0 0 0.15rem rgba(0,122,51,0.15);
        }
        .filter-field { min-width: 160px; flex: 1 1 160px; }
        .filter-field-wide { min-width: 190px; flex: 1.2 1 190px; }
        .filter-actions { flex: 0 0 auto; }
        .filter-summary {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 0.55rem 0.75rem;
            font-size: 0.82rem;
            color: #5f6b76;
        }
        .filter-summary strong { color: #1f2933; }
    </style>
@endpush

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light py-2">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <h6 class="mb-0">
                            <i class="fas fa-user-plus me-2" style="color:#007A33;"></i>Recruitment Requisitions
                            <span class="badge bg-secondary ms-1" style="font-size:0.75rem;">{{ $requisitions->count() }}</span>
                        </h6>
                        <div class="d-flex gap-2">
                            @if (auth()->user()->hasAnyRole(['line-manager', 'coo', 'cms', 'cfo', 'ccdro']))
                                <a href="{{ route('requisitions.create') }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i>New Requisition
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body p-3">
                    {{-- Filter Row --}}
                    <div class="filter-bar d-flex flex-wrap align-items-end gap-3 mb-3 p-3">
                        <div class="filter-field">
                            <label class="form-label small fw-semibold mb-1">Status</label>
                            <select id="filterStatus" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="Draft">Draft</option>
                                <option value="Pending Payroll Review">Pending Payroll</option>
                                <option value="Pending HEC Review">Pending HEC</option>
                                <option value="Pending CFO Approval">Pending CFO</option>
                                <option value="Pending CEO Approval">Pending CEO</option>
                                <option value="Pending HR Approval">Pending HR</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Rejected - Can Edit">Rejected - Can Edit</option>
                                <option value="Filed">Filed</option>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="form-label small fw-semibold mb-1">Position Type</label>
                            <select id="filterPositionType" class="form-select form-select-sm">
                                <option value="">All Types</option>
                            </select>
                        </div>
                        <div class="filter-field-wide">
                            <label class="form-label small fw-semibold mb-1">Entity</label>
                            <select id="filterEntity" class="form-select form-select-sm">
                                <option value="">All Entities</option>
                            </select>
                        </div>
                        <div class="filter-field-wide">
                            <label class="form-label small fw-semibold mb-1">Department</label>
                            <select id="filterDepartment" class="form-select form-select-sm">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="form-label small fw-semibold mb-1">Timeframe</label>
                            <select id="filterTimeframe" class="form-select form-select-sm">
                                <option value="">All Time</option>
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 3 months</option>
                                <option value="180">Last 6 months</option>
                                <option value="365">Last 1 year</option>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="form-label small fw-semibold mb-1">From</label>
                            <input type="date" id="filterFromDate" class="form-control form-control-sm">
                        </div>
                        <div class="filter-field">
                            <label class="form-label small fw-semibold mb-1">To</label>
                            <input type="date" id="filterToDate" class="form-control form-control-sm">
                        </div>
                        <div class="filter-actions">
                            <button id="btnResetFilters" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                        </div>
                        <div class="filter-summary ms-auto" id="filterSummary">
                            Showing <strong>{{ $requisitions->count() }}</strong> of <strong>{{ $requisitions->count() }}</strong>
                        </div>
                        <div>
                            <button id="btnExportExcel" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="fas fa-file-excel me-1"></i> Export to Excel
                            </button>
                        </div>
                    </div>

                    {{-- Trends & Analytics (HEC members / senior roles only) --}}
                    @if (!empty($chartData))
                        <div class="mb-3">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#trendsSection" aria-expanded="true">
                                <i class="fas fa-chart-bar me-1"></i> Trends &amp; Analytics
                            </button>
                        </div>
                        <div class="collapse show mb-4" id="trendsSection">
                            <div class="row g-3">
                                {{-- Monthly Trend --}}
                                <div class="col-lg-6">
                                    <div class="border rounded p-3" style="background:#fff;">
                                        <h6 class="fw-semibold mb-3" style="font-size:0.85rem; color:#555;">
                                            <i class="fas fa-chart-line me-1" style="color:#007A33;"></i> Monthly Submissions (Last 6 Months)
                                        </h6>
                                        <div style="position:relative; height:220px;"><canvas id="chartMonthly"></canvas></div>
                                    </div>
                                </div>
                                {{-- Status Distribution --}}
                                <div class="col-lg-6">
                                    <div class="border rounded p-3" style="background:#fff;">
                                        <h6 class="fw-semibold mb-3" style="font-size:0.85rem; color:#555;">
                                            <i class="fas fa-chart-pie me-1" style="color:#007A33;"></i> Status Distribution
                                        </h6>
                                        <div style="position:relative; height:220px;"><canvas id="chartStatus"></canvas></div>
                                    </div>
                                </div>
                                {{-- Department Distribution --}}
                                <div class="col-lg-6">
                                    <div class="border rounded p-3" style="background:#fff;">
                                        <h6 class="fw-semibold mb-3" style="font-size:0.85rem; color:#555;">
                                            <i class="fas fa-building me-1" style="color:#007A33;"></i> Top Departments
                                        </h6>
                                        <div style="position:relative; height:220px;"><canvas id="chartDept"></canvas></div>
                                    </div>
                                </div>
                                {{-- Position Type Distribution --}}
                                <div class="col-lg-6">
                                    <div class="border rounded p-3" style="background:#fff;">
                                        <h6 class="fw-semibold mb-3" style="font-size:0.85rem; color:#555;">
                                            <i class="fas fa-briefcase me-1" style="color:#007A33;"></i> Position Types
                                        </h6>
                                        <div style="position:relative; height:220px;"><canvas id="chartPosType"></canvas></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="requisitionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Position Type</th>
                                    <th>Job Title / Position</th>
                                    <th>Initiated By</th>
                                    <th>Status</th>
                                    <th>Submitted Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requisitions as $req)
                                    <tr data-created="{{ $req->created_at->toIso8601String() }}"
                                        data-dept="{{ $req->dept_name ?? '' }}"
                                        data-entity="{{ $req->department?->divisions?->first()?->name ?? '' }}"
                                        data-status="{{ $req->status }}"
                                        data-status-label="{{ $req->getStatusLabel() }}"
                                        data-position-type="{{ $req->position_type }}"
                                        data-position-type-label="{{ $req->getPositionTypeLabel() }}">
                                        <td>
                                            <a href="{{ route('requisitions.show', $req->access_id) }}"
                                                class="fw-semibold" style="color:#007A33; text-decoration:none;">
                                                {{ $req->access_id }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $req->getPositionTypeLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($req->position_type === 'new_position' && $req->new_job_title)
                                                {{ $req->new_job_title }}
                                                <br><small class="text-muted fst-italic">New Position</small>
                                            @elseif($req->jobTitle)
                                                {{ $req->jobTitle->job_title ?? $req->jobTitle->name ?? 'N/A' }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $req->initiator_name }}</div>
                                            <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $req->initiator_type)) }}</small>
                                            <br><small class="text-muted fst-italic" style="font-size:0.75rem;">
                                                <i class="fas fa-building" style="font-size:0.65rem;"></i>
                                                {{ $req->dept_name ?? 'N/A' }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $req->getStatusBadgeClass() }}" title="{{ $req->current_step ? 'Current Step: ' . ucfirst(str_replace('_', ' ', $req->current_step)) : '' }}">
                                                {{ $req->getStatusLabel() }}
                                            </span>
                                            @if (in_array($req->status, ['rejected', 'rejected_for_editing']) && $req->rejectedBy)
                                                <br><small class="text-danger mt-1 d-block" style="font-size: 0.7rem;">
                                                    <i class="fas fa-user-times"></i> By: {{ trim(($req->rejectedBy->fname ?? '') . ' ' . ($req->rejectedBy->lname ?? '')) ?: $req->rejectedBy->name }}
                                                    @if($req->rejection_stage)
                                                        @php
                                                            $rejectedByRoles = collect($req->rejectedBy?->getRoleNames() ?? [])
                                                                ->map(fn ($r) => strtolower((string) $r))
                                                                ->values()
                                                                ->all();
                                                            $rolePriority = ['ceo', 'coo', 'cfo', 'cms', 'ccdro', 'hr', 'it', 'line-manager', 'line_manager'];
                                                            $rejectedByRole = null;
                                                            foreach ($rolePriority as $priorityRole) {
                                                                if (in_array($priorityRole, $rejectedByRoles, true)) {
                                                                    $rejectedByRole = $priorityRole;
                                                                    break;
                                                                }
                                                            }
                                                            if (!$rejectedByRole && !empty($rejectedByRoles)) {
                                                                $rejectedByRole = $rejectedByRoles[0];
                                                            }
                                                            $roleLabelMap = [
                                                                'coo' => 'COO',
                                                                'cfo' => 'CFO',
                                                                'cms' => 'CMS',
                                                                'ccdro' => 'CCDRO',
                                                                'ceo' => 'CEO',
                                                                'line-manager' => 'Line Manager',
                                                                'line_manager' => 'Line Manager',
                                                                'hr' => 'HRBP',
                                                                'it' => 'IT',
                                                            ];
                                                            $stageLabelMap = [
                                                                'hr' => 'HRBP',
                                                                'hec' => 'HEC',
                                                                'cfo' => 'CFO',
                                                                'ceo' => 'CEO',
                                                                'payroll' => 'Payroll',
                                                                'line_manager' => 'Line Manager',
                                                                'line-manager' => 'Line Manager',
                                                            ];
                                                            $stageKey = strtolower((string) $req->rejection_stage);
                                                            $rejectionStageLabel = $roleLabelMap[$rejectedByRole]
                                                                ?? $stageLabelMap[$stageKey]
                                                                ?? ucwords(str_replace('_', ' ', $req->rejection_stage));
                                                        @endphp
                                                        <span class="text-muted">({{ $rejectionStageLabel }})</span>
                                                    @endif
                                                </small>
                                            @endif
                                            @if (auth()->user()->hasRole('hr') && $req->current_step)
                                                <br><small class="text-muted mt-1 d-block" style="font-size: 0.7rem;">
                                                    <i class="fas fa-info-circle"></i> Step: {{ ucfirst(str_replace('_', ' ', $req->current_step)) }}
                                                </small>
                                            @endif
                                        </td>
                                        <td data-order="{{ $req->created_at->toIso8601String() }}">
                                            {{ $req->created_at->format('d M Y') }}
                                            <br>
                                            <small class="text-muted">{{ $req->created_at->format('H:i') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('requisitions.show', $req->access_id) }}"
                                                class="btn btn-sm btn-outline-secondary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($requisitions->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Requisitions Found</h5>
                            <p class="text-muted">
                                @if (auth()->user()->hasAnyRole(['line-manager', 'coo', 'cms', 'cfo', 'ccdro']))
                                    Click "New Requisition" to create your first recruitment request.
                                @else
                                    You don't have any requisitions to display.
                                @endif
                            </p>
                        </div>
                    @endif

                    {{-- Approver Queue Section --}}
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
                                                        <th>Requisition</th>
                                                        <th>Initiated By</th>
                                                        <th>Submitted</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($items as $item)
                                                        <tr>
                                                            <td>
                                                                <a href="{{ route('requisitions.show', $item['access_id']) }}"
                                                                    class="fw-semibold" style="color:#007A33; text-decoration:none;">
                                                                    {{ $item['access_id'] }}
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <div>{{ $item['initiator_name'] }}</div>
                                                                <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $item['initiator_type'])) }}</small>
                                                                <br><small class="text-muted fst-italic" style="font-size:0.75rem;">
                                                                    <i class="fas fa-building" style="font-size:0.65rem;"></i>
                                                                    {{ $item['dept_name'] ?? 'N/A' }}
                                                                </small>
                                                            </td>
                                                            <td>{{ $item['created_at'] }}</td>
                                                            <td>
                                                                <span class="badge {{ $item['status_class'] }}">{{ $item['status_label'] }}</span>
                                                            </td>
                                                            <td>
                                                                <a href="{{ route('requisitions.show', $item['access_id']) }}"
                                                                    class="btn btn-outline-secondary btn-sm">
                                                                    <i class="fas fa-eye"></i> Review
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
                </div>
            </div>
        </div>
    </div>

    {{-- Export Modal --}}
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('requisitions.export') }}" id="exportForm" target="exportFrame">
                    <div class="modal-header bg-light border-bottom">
                        <h5 class="modal-title text-dark fw-semibold" id="exportModalLabel">
                            <i class="fas fa-file-excel me-2 text-success"></i>Export Requisitions Report
                        </h5>
                        <button type="button" class="btn-close" id="exportCloseBtn" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3" id="exportFields">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-calendar-alt me-1 text-success"></i>Date From
                                </label>
                                <input type="date" name="date_from" id="exp_date_from" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-calendar-alt me-1 text-success"></i>Date To
                                </label>
                                <input type="date" name="date_to" id="exp_date_to" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-filter me-1 text-success"></i>Status
                                </label>
                                <select name="export_status" class="form-select form-select-sm">
                                    <option value="all">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="pending">All Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="filed">Filed</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-briefcase me-1 text-success"></i>Position Type
                                </label>
                                <select name="position_type" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    <option value="new_position">New Position</option>
                                    <option value="replacement">Replacement</option>
                                    <option value="contract_renewal">Contract Renewal/Extension</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-building me-1 text-success"></i>Entity
                                </label>
                                <select name="entity" id="exportEntity" class="form-select form-select-sm">
                                    <option value="">All Entities</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-sitemap me-1 text-success"></i>Department
                                </label>
                                <select name="department" id="exportDepartment" class="form-select form-select-sm">
                                    <option value="">All Departments</option>
                                </select>
                            </div>
                        </div>

                        {{-- Progress area (hidden until export starts) --}}
                        <div id="exportProgress" style="display:none;" class="mt-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                                <span class="small fw-semibold text-muted" id="exportProgressLabel">Preparing your report…</span>
                            </div>
                            <div class="progress" style="height:6px; border-radius:3px;">
                                <div id="exportProgressBar"
                                    class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                    role="progressbar" style="width:0%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" id="exportCancelBtn" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm" id="exportSubmitBtn">
                            <i class="fas fa-download me-1"></i> Download Excel
                        </button>
                    </div>
                </form>
                <iframe name="exportFrame" style="display:none;"></iframe>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            function stripHtml(html) {
                const tmp = document.createElement('div');
                tmp.innerHTML = html ?? '';
                return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
            }

            // Initialize DataTable
            const table = $('#requisitionsTable').DataTable({
                dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6 d-flex justify-content-end"f>>rtip',
                pageLength: 10,
                lengthChange: true,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'All']
                ],
                order: [
                    [5, 'desc']
                ],
                orderMulti: true,
                language: {
                    search: 'Search:',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries to show',
                    infoFiltered: '(filtered from _MAX_ total)',
                    zeroRecords: 'No matching records found',
                    lengthMenu: 'Show _MENU_ entries',
                    paginate: {
                        first: 'First',
                        last: 'Last',
                        next: 'Next',
                        previous: 'Previous'
                    }
                },
                columnDefs: [
                    { targets: [6], orderable: false }
                ]
            });
            window.requisitionsDataTable = table;

            function updateFilterSummary() {
                const filtered = table.rows({ filter: 'applied' }).count();
                const total = table.rows().count();
                $('#filterSummary').html('Showing <strong>' + filtered + '</strong> of <strong>' + total + '</strong>');
            }

            // Build maps from table row data attributes
            const entityDeptMap = {}; // entity → Set of dept names
            const allDepts = new Set();
            const allTypes = new Set();
            const allEntities = new Set();

            table.rows().every(function() {
                const row = this.data();
                const tr = this.node();
                const dept   = $(tr).data('dept')   || '';
                const entity = $(tr).data('entity') || '';
                const type   = stripHtml(row[1]);
                if (dept)   allDepts.add(dept);
                if (entity) allEntities.add(entity);
                if (type)   allTypes.add(type);
                if (entity && dept) {
                    if (!entityDeptMap[entity]) entityDeptMap[entity] = new Set();
                    entityDeptMap[entity].add(dept);
                }
            });

            // Populate static selects on load
            Array.from(allTypes).sort().forEach(t =>
                $('#filterPositionType').append(`<option value="${t.replace(/"/g,'&quot;')}">${t}</option>`)
            );
            Array.from(allEntities).sort().forEach(e =>
                $('#filterEntity').append(`<option value="${e.replace(/"/g,'&quot;')}">${e}</option>`)
            );
            Array.from(allDepts).sort().forEach(d =>
                $('#filterDepartment').append(`<option value="${d.replace(/"/g,'&quot;')}">${d}</option>`)
            );

            // When entity changes → update department options to only show matching depts
            $('#filterEntity').on('change', function() {
                const selectedEntity = $(this).val();
                const $dept = $('#filterDepartment');
                const currentDept = $dept.val();
                $dept.empty().append('<option value="">All Departments</option>');

                const deptsToShow = selectedEntity
                    ? Array.from(entityDeptMap[selectedEntity] || []).sort()
                    : Array.from(allDepts).sort();

                deptsToShow.forEach(d =>
                    $dept.append(`<option value="${d.replace(/"/g,'&quot;')}">${d}</option>`)
                );

                // Keep current dept selection if it's still valid, else clear
                if (deptsToShow.includes(currentDept)) {
                    $dept.val(currentDept);
                } else {
                    $dept.val('');
                }

                table.draw();
            });

            // Apply filters
            $('#filterStatus, #filterTimeframe, #filterFromDate, #filterToDate, #filterDepartment, #filterPositionType').on('change', function() {
                table.draw();
            });

            // Combined custom filters (timeframe, date range, department, position type, status)
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'requisitionsTable') return true;

                const row = settings.aoData[dataIndex].nTr;
                const createdIso = $(row).data('created');
                if (!createdIso) return true;
                const rowDate = new Date(createdIso);

                // Timeframe filter
                const days = parseInt($('#filterTimeframe').val(), 10);
                if (days) {
                    const cutoff = new Date();
                    cutoff.setHours(0, 0, 0, 0);
                    cutoff.setDate(cutoff.getDate() - days);
                    if (rowDate < cutoff) return false;
                }

                // From/To range filter (inclusive)
                const fromDate = $('#filterFromDate').val();
                const toDate = $('#filterToDate').val();
                if (fromDate) {
                    const from = new Date(fromDate + 'T00:00:00');
                    if (rowDate < from) return false;
                }
                if (toDate) {
                    const to = new Date(toDate + 'T23:59:59');
                    if (rowDate > to) return false;
                }

                // Status filter
                const statusFilter = $('#filterStatus').val();
                if (statusFilter) {
                    const statusText = stripHtml(data[4]).toLowerCase();
                    if (!statusText.includes(statusFilter.toLowerCase())) return false;
                }

                // Entity filter (reads from data-entity attribute)
                const entityFilter = $('#filterEntity').val();
                if (entityFilter) {
                    const entityText = $(settings.aoData[dataIndex].nTr).data('entity') || '';
                    if (entityText !== entityFilter) return false;
                }

                // Department filter (reads from data-dept attribute)
                const deptFilter = $('#filterDepartment').val();
                if (deptFilter) {
                    const deptText = $(settings.aoData[dataIndex].nTr).data('dept') || '';
                    if (deptText !== deptFilter) return false;
                }

                // Position type filter
                const typeFilter = $('#filterPositionType').val();
                if (typeFilter) {
                    const typeText = stripHtml(data[1]);
                    if (typeText !== typeFilter) return false;
                }

                return true;
            });

            // Reset filters
            $('#btnResetFilters').on('click', function() {
                $('#filterStatus').val('');
                $('#filterTimeframe').val('');
                $('#filterFromDate').val('');
                $('#filterToDate').val('');
                $('#filterEntity').val('');
                $('#filterPositionType').val('');
                // Restore full department list then clear selection
                const $dept = $('#filterDepartment');
                $dept.empty().append('<option value="">All Departments</option>');
                Array.from(allDepts).sort().forEach(d =>
                    $dept.append(`<option value="${d.replace(/"/g,'&quot;')}">${d}</option>`)
                );
                $dept.val('');
                table.draw();
            });

            table.on('draw', updateFilterSummary);
            table.on('draw', function() {
                if (typeof window.updateRequisitionChartsFromTable === 'function') {
                    window.updateRequisitionChartsFromTable();
                }
            });

            // Populate export modal entity list from the same allEntities set
            Array.from(allEntities).sort().forEach(e =>
                $('#exportEntity').append(`<option value="${e.replace(/"/g,'&quot;')}">${e}</option>`)
            );
            Array.from(allDepts).sort().forEach(d =>
                $('#exportDepartment').append(`<option value="${d.replace(/"/g,'&quot;')}">${d}</option>`)
            );

            $('#exportEntity').on('change', function() {
                const selectedEntity = $(this).val();
                const $dept = $('#exportDepartment');
                const currentDept = $dept.val();
                $dept.empty().append('<option value="">All Departments</option>');

                const deptsToShow = selectedEntity
                    ? Array.from(entityDeptMap[selectedEntity] || []).sort()
                    : Array.from(allDepts).sort();

                deptsToShow.forEach(d =>
                    $dept.append(`<option value="${d.replace(/"/g,'&quot;')}">${d}</option>`)
                );

                $dept.val(deptsToShow.includes(currentDept) ? currentDept : '');
            });

            table.draw();
            updateFilterSummary();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var exportModal = document.getElementById('exportModal');
            if (!exportModal) return;

            var form        = document.getElementById('exportForm');
            var submitBtn   = document.getElementById('exportSubmitBtn');
            var cancelBtn   = document.getElementById('exportCancelBtn');
            var closeBtn    = document.getElementById('exportCloseBtn');
            var fields      = document.getElementById('exportFields');
            var progress    = document.getElementById('exportProgress');
            var progressBar = document.getElementById('exportProgressBar');
            var progressLbl = document.getElementById('exportProgressLabel');

            var exporting     = false;
            var progressTimer = null;
            var pollTimer     = null;
            var downloadToken = null;

            // Pre-fill dates (last 30 days) when modal opens
            exportModal.addEventListener('show.bs.modal', function() {
                var df = document.getElementById('exp_date_from');
                var dt = document.getElementById('exp_date_to');
                var status = exportModal.querySelector('select[name="export_status"]');
                var type = exportModal.querySelector('select[name="position_type"]');
                var entity = document.getElementById('exportEntity');
                var department = document.getElementById('exportDepartment');

                if (status) {
                    var currentStatus = document.getElementById('filterStatus')?.value || '';
                    var statusMap = {
                        'Draft': 'draft',
                        'Pending Payroll Review': 'pending',
                        'Pending HEC Review': 'pending',
                        'Pending CFO Approval': 'pending',
                        'Pending CEO Approval': 'pending',
                        'Pending HR Approval': 'pending',
                        'Approved': 'approved',
                        'Rejected': 'rejected',
                        'Rejected - Can Edit': 'rejected',
                        'Filed': 'filed'
                    };
                    status.value = statusMap[currentStatus] || 'all';
                }

                if (type) {
                    var typeLabel = document.getElementById('filterPositionType')?.value || '';
                    var typeMap = {
                        'New Position': 'new_position',
                        'Replacement': 'replacement',
                        'Contract Renewal/Extension': 'contract_renewal'
                    };
                    type.value = typeMap[typeLabel] || '';
                }

                if (entity) {
                    entity.value = document.getElementById('filterEntity')?.value || '';
                    entity.dispatchEvent(new Event('change'));
                }

                if (department) {
                    department.value = document.getElementById('filterDepartment')?.value || '';
                }

                if (document.getElementById('filterFromDate')?.value) {
                    df.value = document.getElementById('filterFromDate').value;
                }
                if (document.getElementById('filterToDate')?.value) {
                    dt.value = document.getElementById('filterToDate').value;
                }
                if (!df.value) {
                    var d = new Date();
                    dt.value = d.toISOString().split('T')[0];
                    d.setDate(d.getDate() - 30);
                    df.value = d.toISOString().split('T')[0];
                }
            });

            // Reset modal state when it closes
            exportModal.addEventListener('hidden.bs.modal', function() {
                resetExportState();
            });

            // Prevent closing while export is in progress
            exportModal.addEventListener('hide.bs.modal', function(e) {
                if (exporting) e.preventDefault();
            });

            function getCookie(name) {
                var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return match ? decodeURIComponent(match[1]) : null;
            }

            function resetExportState() {
                exporting = false;
                clearInterval(progressTimer);
                clearInterval(pollTimer);
                fields.style.display = '';
                progress.style.display = 'none';
                progressBar.style.width = '0%';
                progressBar.classList.add('progress-bar-animated', 'progress-bar-striped');
                progressLbl.textContent = 'Preparing your report…';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-download me-1"></i> Download Excel';
                cancelBtn.disabled = false;
                closeBtn.disabled = false;
                var old = form.querySelector('input[name="download_token"]');
                if (old) old.remove();
            }

            function onDownloadComplete() {
                clearInterval(progressTimer);
                clearInterval(pollTimer);
                document.cookie = 'download_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                progressBar.style.width = '100%';
                progressLbl.textContent = 'Download complete!';
                setTimeout(function() {
                    exporting = false;
                    var bsModal = bootstrap.Modal.getInstance(exportModal);
                    if (bsModal) bsModal.hide();
                }, 1000);
            }

            function startProgress() {
                exporting = true;
                fields.style.display = 'none';
                progress.style.display = 'block';
                submitBtn.disabled = true;
                cancelBtn.disabled = true;
                closeBtn.disabled = true;

                var pct = 5;
                var steps = [
                    [20, 'Querying records…'],
                    [45, 'Processing data…'],
                    [70, 'Building spreadsheet…'],
                    [88, 'Finalising file…'],
                    [96, 'Almost done…'],
                ];
                var stepIdx = 0;

                progressTimer = setInterval(function() {
                    if (stepIdx < steps.length) {
                        pct = steps[stepIdx][0];
                        progressLbl.textContent = steps[stepIdx][1];
                        stepIdx++;
                    } else {
                        pct = Math.min(pct + 1, 98);
                    }
                    progressBar.style.width = pct + '%';
                }, 700);

                pollTimer = setInterval(function() {
                    if (getCookie('download_token') === downloadToken) {
                        onDownloadComplete();
                    }
                }, 500);
            }

            // On submit: inject token, start progress
            form.addEventListener('submit', function() {
                downloadToken = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                var old = form.querySelector('input[name="download_token"]');
                if (old) old.remove();
                var inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'download_token';
                inp.value = downloadToken;
                form.appendChild(inp);
                startProgress();
            });
        });
    </script>

    @if (!empty($chartData))
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.defaults.font.size = 11;
        Chart.defaults.font.family = "'Segoe UI', Roboto, sans-serif";

        const statusColorMap = {
            'draft': '#6c757d',
            'pending_payroll': '#fd7e14',
            'pending_hec': '#ffc107',
            'pending_cfo': '#17a2b8',
            'pending_ceo': '#6f42c1',
            'pending_hr': '#20c997',
            'approved': '#007A33',
            'rejected': '#dc3545',
            'rejected_for_editing': '#e83e8c',
            'filed': '#495057'
        };
        const statusLabelMap = {
            'draft': 'Draft',
            'pending_payroll': 'Pending Payroll',
            'pending_hec': 'Pending HEC',
            'pending_cfo': 'Pending CFO',
            'pending_ceo': 'Pending CEO',
            'pending_hr': 'Pending HR',
            'approved': 'Approved',
            'rejected': 'Rejected',
            'rejected_for_editing': 'Rejected (Edit)',
            'filed': 'Filed'
        };
        const positionLabelMap = {
            'new_position': 'New Position',
            'contract_renewal': 'Contract Renewal/Extension',
            'replacement': 'Replacement'
        };
        const positionColorMap = {
            'new_position': '#007A33',
            'contract_renewal': '#fd7e14',
            'replacement': '#17a2b8'
        };
        const deptColors = ['#007A33','#28a745','#20c997','#17a2b8','#6f42c1','#fd7e14','#ffc107','#6c757d'];

        // 1. Monthly Trend (Bar chart)
        const monthlyData = @json($chartData['monthlyTrend']);
        const monthlyLabels = monthlyData.map(m => m.label);
        const monthlyChart = new Chart(document.getElementById('chartMonthly'), {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Requisitions',
                    data: monthlyData.map(m => m.count),
                    backgroundColor: 'rgba(0, 122, 51, 0.7)',
                    borderColor: '#007A33',
                    borderWidth: 1,
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Status Distribution (Doughnut)
        const statusRaw = @json($chartData['statusCounts']);
        const statusLabels = [], statusValues = [], statusColors = [];
        Object.entries(statusRaw).forEach(([key, val]) => {
            statusLabels.push(statusLabelMap[key] || key);
            statusValues.push(val);
            statusColors.push(statusColorMap[key] || '#adb5bd');
        });
        const statusChart = new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: statusColors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, padding: 8, font: { size: 10 } } }
                },
                cutout: '55%'
            }
        });

        // 3. Department Distribution (Horizontal bar)
        const deptRaw = @json($chartData['deptCounts']);
        let currentDeptBreakdown = @json($chartData['deptPositionBreakdown'] ?? []);
        let currentDeptLabels = Object.keys(deptRaw);
        const deptValues = Object.values(deptRaw);
        const deptChart = new Chart(document.getElementById('chartDept'), {
            type: 'bar',
            data: {
                labels: currentDeptLabels.map(l => l.length > 25 ? l.substring(0, 22) + '…' : l),
                datasets: [{
                    label: 'Requisitions',
                    data: deptValues,
                    backgroundColor: deptColors.slice(0, currentDeptLabels.length),
                    borderRadius: 4,
                    barPercentage: 0.7
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(items) {
                                return currentDeptLabels[items[0].dataIndex] || '';
                            },
                            label: function(context) {
                                const department = currentDeptLabels[context.dataIndex] || '';
                                const breakdown = currentDeptBreakdown[department] || {};
                                return [
                                    'Total: ' + context.parsed.x,
                                    'New Position: ' + (breakdown.new_position || 0),
                                    'Contract Renewal/Extension: ' + (breakdown.contract_renewal || 0),
                                    'Replacement: ' + (breakdown.replacement || 0)
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: '#f0f0f0' } },
                    y: { grid: { display: false } }
                }
            }
        });

        // 4. Position Type (Pie)
        const posRaw = @json($chartData['posTypeCounts']);
        const posLabels = Object.keys(posRaw);
        const posValues = Object.values(posRaw);
        const posColors = ['#007A33', '#fd7e14', '#17a2b8', '#6f42c1'];
        const posTypeChart = new Chart(document.getElementById('chartPosType'), {
            type: 'pie',
            data: {
                labels: posLabels,
                datasets: [{
                    data: posValues,
                    backgroundColor: posColors.slice(0, posLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, padding: 8, font: { size: 10 } } }
                }
            }
        });

        function monthLabelFromDate(date) {
            return date.toLocaleString('en-US', { month: 'short', year: 'numeric' });
        }

        function filteredRequisitionRows() {
            if (!window.jQuery || !$.fn.DataTable.isDataTable('#requisitionsTable')) {
                return [];
            }

            return $('#requisitionsTable').DataTable().rows({ filter: 'applied' }).nodes().toArray().map(function(row) {
                return {
                    created: row.getAttribute('data-created'),
                    dept: row.getAttribute('data-dept') || 'Unassigned',
                    status: row.getAttribute('data-status') || '',
                    statusLabel: row.getAttribute('data-status-label') || '',
                    positionType: row.getAttribute('data-position-type') || '',
                    positionTypeLabel: row.getAttribute('data-position-type-label') || ''
                };
            });
        }

        window.updateRequisitionChartsFromTable = function() {
            const rows = filteredRequisitionRows();

            const monthlyCounts = Object.fromEntries(monthlyLabels.map(label => [label, 0]));
            const statusCounts = {};
            const departmentCounts = {};
            const departmentBreakdown = {};
            const positionCounts = {};

            rows.forEach(function(row) {
                const created = row.created ? new Date(row.created) : null;
                if (created && !Number.isNaN(created.getTime())) {
                    const monthLabel = monthLabelFromDate(created);
                    if (Object.prototype.hasOwnProperty.call(monthlyCounts, monthLabel)) {
                        monthlyCounts[monthLabel] += 1;
                    }
                }

                const statusKey = row.status || row.statusLabel || 'unknown';
                statusCounts[statusKey] = (statusCounts[statusKey] || 0) + 1;

                const dept = row.dept || 'Unassigned';
                departmentCounts[dept] = (departmentCounts[dept] || 0) + 1;
                if (!departmentBreakdown[dept]) {
                    departmentBreakdown[dept] = { new_position: 0, contract_renewal: 0, replacement: 0 };
                }
                if (Object.prototype.hasOwnProperty.call(departmentBreakdown[dept], row.positionType)) {
                    departmentBreakdown[dept][row.positionType] += 1;
                }

                const positionKey = row.positionType || row.positionTypeLabel || 'unknown';
                positionCounts[positionKey] = (positionCounts[positionKey] || 0) + 1;
            });

            monthlyChart.data.datasets[0].data = monthlyLabels.map(label => monthlyCounts[label] || 0);
            monthlyChart.update();

            const nextStatusEntries = Object.entries(statusCounts);
            statusChart.data.labels = nextStatusEntries.map(([key]) => statusLabelMap[key] || key);
            statusChart.data.datasets[0].data = nextStatusEntries.map(([, value]) => value);
            statusChart.data.datasets[0].backgroundColor = nextStatusEntries.map(([key]) => statusColorMap[key] || '#adb5bd');
            statusChart.update();

            const nextDeptEntries = Object.entries(departmentCounts)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 8);
            currentDeptLabels = nextDeptEntries.map(([label]) => label);
            currentDeptBreakdown = Object.fromEntries(currentDeptLabels.map(label => [label, departmentBreakdown[label] || {}]));
            deptChart.data.labels = currentDeptLabels.map(label => label.length > 25 ? label.substring(0, 22) + '…' : label);
            deptChart.data.datasets[0].data = nextDeptEntries.map(([, value]) => value);
            deptChart.data.datasets[0].backgroundColor = deptColors.slice(0, currentDeptLabels.length);
            deptChart.update();

            const positionOrder = ['new_position', 'contract_renewal', 'replacement'];
            const nextPositionEntries = positionOrder
                .filter(key => positionCounts[key])
                .map(key => [key, positionCounts[key]]);
            Object.entries(positionCounts).forEach(function(entry) {
                if (!positionOrder.includes(entry[0])) {
                    nextPositionEntries.push(entry);
                }
            });
            posTypeChart.data.labels = nextPositionEntries.map(([key]) => positionLabelMap[key] || key);
            posTypeChart.data.datasets[0].data = nextPositionEntries.map(([, value]) => value);
            posTypeChart.data.datasets[0].backgroundColor = nextPositionEntries.map(([key]) => positionColorMap[key] || '#6f42c1');
            posTypeChart.update();
        };

        window.updateRequisitionChartsFromTable();
    });
    </script>
    @endif
@endpush
