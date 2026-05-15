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

    /* Table improvements like clearance page */
    .table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #dee2e6;
    }

    .table tbody td {
        padding: 0.875rem 0.75rem;
        vertical-align: middle;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .table-striped > tbody > tr:nth-of-type(odd) > td {
        background-color: rgba(0, 0, 0, 0.02);
    }

    /* Filter buttons - smaller size */
    .filter-btn {
        padding: 0.35rem 0.75rem;
        font-size: 0.8rem;
        line-height: 1.2;
    }

    .filter-btn i {
        font-size: 0.75rem;
    }

    /* Badge numbers - more visible */
    .filter-btn .badge {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.25em 0.5em;
        min-width: 1.5em;
        text-align: center;
    }

    /* Ensure white text on colored badges for better visibility */
    .filter-btn .badge.bg-primary,
    .filter-btn .badge.bg-warning,
    .filter-btn .badge.bg-success,
    .filter-btn .badge.bg-danger {
        color: #fff !important;
    }

    /* Active filter button styling */
    .filter-btn.active {
        font-weight: 500;
    }

    /* Stat Cards */
    .stat-card {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 0.65rem 0.85rem;
        background: #fff;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }
    .stat-card:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .stat-card.active-filter { border-color: #198754; box-shadow: 0 0 0 2px rgba(25,135,84,0.2); }
    .stat-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; }
    .stat-number { font-size: 1.15rem; font-weight: 700; line-height: 1; color: #212529; }
    .stat-label { font-size: 0.68rem; color: #6c757d; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-icon-all { background: #e9ecef; color: #495057; }
    .stat-icon-active { background: #d1e7dd; color: #198754; }
    .stat-icon-pending { background: #fff3cd; color: #856404; }
    .stat-icon-approved { background: #d1e7dd; color: #007A33; }
    .stat-icon-rejected { background: #f8d7da; color: #dc3545; }
    .stat-icon-expired { background: #e9ecef; color: #6c757d; }

    /* Active contract row */
    .active-contract-row { background: rgba(25, 135, 84, 0.04) !important; }
    .active-contract-row:hover { background: rgba(25, 135, 84, 0.08) !important; }
    .active-indicator {
        width: 8px; height: 8px; border-radius: 50%;
        background: #198754; display: inline-block; flex-shrink: 0;
        animation: active-pulse 2s infinite;
    }
    @keyframes active-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(25,135,84,0.4); }
        50% { box-shadow: 0 0 0 4px rgba(25,135,84,0.1); }
    }

    /* Filter bar */
    .filter-bar { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 10px; }
    .filter-bar .form-select { border-color: #dee2e6; font-size: 0.85rem; }
    .filter-bar .form-select:focus { border-color: #198754; box-shadow: 0 0 0 0.15rem rgba(25,135,84,0.15); }
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
                    5 => ['label' => 'Expired', 'class' => 'bg-secondary'],
                ];
                $activeIds = $activeAgreementIds ?? collect();
            @endphp

            {{-- Summary Stats Cards --}}
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-2">
                    <div class="stat-card" data-filter="all">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon stat-icon-all"><i class="fas fa-layer-group"></i></div>
                            <div>
                                <div class="stat-number">{{ $hrStatusCounts->total ?? 0 }}</div>
                                <div class="stat-label">Total</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card" data-filter="active">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon stat-icon-active"><i class="fas fa-bolt"></i></div>
                            <div>
                                <div class="stat-number">{{ $activeIds->count() }}</div>
                                <div class="stat-label">Active</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card" data-filter="pending">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon stat-icon-pending"><i class="fas fa-clock"></i></div>
                            <div>
                                <div class="stat-number">{{ ($hrStatusCounts->pending_line_manager ?? 0) + ($hrStatusCounts->pending_hr ?? 0) }}</div>
                                <div class="stat-label">Pending</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card" data-filter="approved">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon stat-icon-approved"><i class="fas fa-check-circle"></i></div>
                            <div>
                                <div class="stat-number">{{ $hrStatusCounts->approved ?? 0 }}</div>
                                <div class="stat-label">Approved</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card" data-filter="rejected">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon stat-icon-rejected"><i class="fas fa-times-circle"></i></div>
                            <div>
                                <div class="stat-number">{{ $hrStatusCounts->rejected ?? 0 }}</div>
                                <div class="stat-label">Rejected</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card" data-filter="expired">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon stat-icon-expired"><i class="fas fa-calendar-times"></i></div>
                            <div>
                                <div class="stat-number">{{ $agreements->where('status', 5)->count() }}</div>
                                <div class="stat-label">Expired</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Table Card --}}
            <div class="card mb-3 compact-card">
                <div class="card-body">
                    {{-- Filter Bar --}}
                    <div class="filter-bar d-flex flex-wrap align-items-end gap-3 mb-3 p-3">
                        <div>
                            <label class="form-label small fw-semibold mb-1">Status</label>
                            <select id="filterStatus" class="form-select form-select-sm" style="min-width:170px">
                                <option value="">All Statuses</option>
                                <option value="Pending to Line Manager">Pending LM</option>
                                <option value="Pending to HR">Pending HR</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Expired">Expired</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Department</label>
                            <select id="filterDepartment" class="form-select form-select-sm" style="min-width:180px">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Contract Year</label>
                            <select id="filterYear" class="form-select form-select-sm" style="min-width:130px">
                                <option value="">All Years</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Show</label>
                            <select id="filterActiveOnly" class="form-select form-select-sm" style="min-width:160px">
                                <option value="">All Contracts</option>
                                <option value="active">Active Contracts Only</option>
                            </select>
                        </div>
                        <div>
                            <button id="btnResetFilters" class="btn btn-sm btn-outline-secondary mt-auto">
                                <i class="fas fa-undo me-1"></i>Reset
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        @if ($agreements->isEmpty())
                            <div class="text-center py-5">
                                <div class="mb-3"><i class="fas fa-file-contract fa-3x text-muted opacity-50"></i></div>
                                <h5 class="text-muted">No locum agreements found</h5>
                                <p class="text-muted small">Agreements will appear here once they are created.</p>
                            </div>
                        @else
                            <table id="locumTableAll" class="table table-hover table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="display:none;">Created</th>
                                        <th style="width:50px;">#</th>
                                        <th>Staff Name</th>
                                        <th>CCBRT Code</th>
                                        <th>Department</th>
                                        <th>Contract Period</th>
                                        <th>Locum Rate</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($agreements as $agreement)
                                        @php
                                            $statusInfo = $statusMap[(int)($agreement->status ?? 0)] ?? [
                                                'label' => 'N/A',
                                                'class' => 'bg-secondary',
                                            ];
                                            $isActive = $activeIds->contains($agreement->id);
                                            $startDate = $agreement->start_date ? \Carbon\Carbon::parse($agreement->start_date) : null;
                                            $endDate = $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date) : null;
                                            $contractYear = $startDate ? $startDate->year : '';
                                            $isCurrentlyValid = $endDate && $endDate->gte(\Carbon\Carbon::today()) && (int)$agreement->status === 2;
                                            $daysRemaining = $isCurrentlyValid ? \Carbon\Carbon::today()->diffInDays($endDate) : null;
                                        @endphp
                                        <tr class="{{ $isActive ? 'active-contract-row' : '' }}"
                                            data-status="{{ $statusInfo['label'] }}"
                                            data-dept="{{ $agreement->department_name ?? '' }}"
                                            data-year="{{ $contractYear }}"
                                            data-active="{{ $isActive ? '1' : '0' }}">
                                            <td style="display:none;">
                                                {{ \Carbon\Carbon::parse($agreement->created_at)->format('Y-m-d H:i:s') }}
                                            </td>
                                            <td></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($isActive)
                                                        <span class="active-indicator" title="Active/Current Contract"></span>
                                                    @endif
                                                    <div>
                                                        <strong>{{ trim(($agreement->fname ?? '') . ' ' . ($agreement->lname ?? '')) ?: '—' }}</strong>
                                                        @if($isActive && $isCurrentlyValid && $daysRemaining !== null)
                                                            <br><small class="text-success fw-semibold" style="font-size:0.7rem;">
                                                                <i class="fas fa-clock"></i> {{ $daysRemaining }} days remaining
                                                            </small>
                                                        @elseif($isActive && in_array((int)$agreement->status, [0, 1]))
                                                            <br><small class="text-warning fw-semibold" style="font-size:0.7rem;">
                                                                <i class="fas fa-hourglass-half"></i> Awaiting approval
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="nowrap">{{ $agreement->ccbrt_code ?? 'N/A' }}</td>
                                            <td><span class="truncate">{{ $agreement->department_name ?? '—' }}</span></td>
                                            <td class="nowrap small">
                                                <div>
                                                    {{ $startDate ? $startDate->format('d M Y') : 'N/A' }}
                                                    –
                                                    {{ $endDate ? $endDate->format('d M Y') : 'N/A' }}
                                                </div>
                                                @if($contractYear)
                                                    <small class="text-muted" style="font-size:0.7rem;">
                                                        <i class="fas fa-calendar-alt"></i> {{ $contractYear }}/{{ $contractYear + 1 }}
                                                    </small>
                                                @endif
                                            </td>
                                            <td class="nowrap">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span>{{ $agreement->locum_rate ? 'TZS ' . number_format($agreement->locum_rate, 0) : 'N/A' }}</span>
                                                    @if (auth()->user()->hasRole('hr') || auth()->user()->hasRole('super-admin'))
                                                        @php
                                                            $staffName = trim(($agreement->fname ?? '') . ' ' . ($agreement->lname ?? ''));
                                                            $ccbrtCode = $agreement->ccbrt_code ?? '';
                                                            $department = $agreement->department_name ?? '';
                                                            $educationLevel = $agreement->education_level ?? '';
                                                            $locumRate = $agreement->locum_rate ?? 0;
                                                        @endphp
                                                        <button type="button" class="btn btn-sm btn-outline-info edit-locum-rate-btn" 
                                                            data-agreement-id="{{ $agreement->id }}"
                                                            data-education-level="{{ e($educationLevel) }}"
                                                            data-locum-rate="{{ $locumRate }}"
                                                            data-staff-name="{{ e($staffName) }}"
                                                            data-ccbrt-code="{{ e($ccbrtCode) }}"
                                                            data-department="{{ e($department) }}"
                                                            title="Edit Locum Rate">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if (in_array((int)($agreement->status ?? -1), [3, 4]))
                                                    @php
                                                        $reason = trim($agreement->rejection_status ?? '');
                                                    @endphp
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
                                                @else
                                                    <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                                @endif
                                            </td>
                                            <td class="nowrap text-center">
                                                <a href="{{ route('locum-agreements.show', $agreement->id) }}"
                                                    class="btn btn-outline-primary btn-slim" title="View Agreement">
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

            {{-- Department Summary (HR/Super-Admin) --}}
            @if ((auth()->user()->hasRole('hr') || auth()->user()->hasRole('super-admin')) && ($departmentSummary ?? collect())->isNotEmpty())
                <div class="card mb-3 compact-card">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="fas fa-building me-2"></i>Department Summary</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Department</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Pending HR</th>
                                        <th class="text-center">Approved</th>
                                        <th class="text-center">Rejected</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($departmentSummary as $dept)
                                        <tr>
                                            <td class="fw-semibold">{{ $dept->department }}</td>
                                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $dept->total }}</span></td>
                                            <td class="text-center">
                                                @if($dept->pending_hr > 0)
                                                    <span class="badge bg-info">{{ $dept->pending_hr }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($dept->approved > 0)
                                                    <span class="badge bg-success">{{ $dept->approved }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($dept->rejected > 0)
                                                    <span class="badge bg-danger">{{ $dept->rejected }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Back -->
            <div class="d-flex my-2">
                <button type="button" onclick="history.back()" class="btn btn-secondary btn-slim">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
            </div>
        </div>
    </div>

    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />

    {{-- EDIT LOCUM RATE MODAL --}}
    <div class="modal fade" id="editLocumRateModal" tabindex="-1" aria-labelledby="editLocumRateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="editLocumRateModalLabel">
                        <i class="fas fa-edit text-info me-2"></i>Edit Locum Rate
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editLocumRateForm" method="POST">
                    @csrf
                    <div class="modal-body pt-0">
                        {{-- Staff Details Section --}}
                        <div class="card bg-light mb-3">
                            <div class="card-body py-2">
                                <h6 class="card-title mb-2 text-muted">
                                    <i class="fas fa-user me-2"></i>Staff Details
                                </h6>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <strong>Name:</strong> <span id="modal_staff_name">—</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>CCBRT Code:</strong> <span id="modal_ccbrt_code">—</span>
                                    </div>
                                    <div class="col-12">
                                        <strong>Department:</strong> <span id="modal_department">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_education_level" class="form-label">Education Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit_education_level" name="education_level" required>
                                <option value="" disabled>Select Education Level</option>
                                @foreach ($locumRates as $rate)
                                    <option value="{{ $rate->education_level }}" data-rate="{{ $rate->rate }}">
                                        {{ $rate->education_level }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_locum_rate" class="form-label">Locum Rate (TZS) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_locum_rate" name="locum_rate" 
                                min="0" step="1" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelEditRateBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="updateRateBtn">
                            <span class="btn-text">
                                <i class="fas fa-save me-1"></i> Update Rate
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Updating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

    {{-- DataTables JS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- TOOLTIP + MODAL INIT + ROW NUMBERING --}}
    <script>
        $(document).ready(function() {
            // Enable Bootstrap tooltips (only if bootstrap is available)
            if (typeof bootstrap !== 'undefined') {
                $('[data-bs-toggle="tooltip"]').each(function() {
                    try {
                        new bootstrap.Tooltip(this);
                    } catch (e) {
                        console.warn('Bootstrap Tooltip initialization failed:', e);
                    }
                });
            }

            // Rejection details modal
            $(document).on('click', '.js-reject-badge', function(e) {
                e.preventDefault();
                const t = $(this);
                $('#rejStaff').text(t.attr('data-modal-name') || '—');
                $('#rejStatus').text(t.attr('data-modal-status') || 'Rejected');
                $('#rejCreated').text(t.attr('data-modal-created') || '—');
                $('#rejReason').text(t.attr('data-modal-reason') || 'No reason provided');
                $('#rejectReasonModal').modal('show');
            });

            // Edit Locum Rate Modal - Use click event for better compatibility with DataTables
            const editLocumRateModal = $('#editLocumRateModal');
            let educationLevelChangeHandler = null;
            
            // Use event delegation to handle clicks on edit buttons (works with DataTables)
            $(document).on('click', '.edit-locum-rate-btn', function(e) {
                e.preventDefault();
                const button = $(this);
                
                // Get all data attributes
                const agreementId = button.attr('data-agreement-id');
                const educationLevel = button.attr('data-education-level') || '';
                const locumRate = button.attr('data-locum-rate') || '0';
                const staffName = button.attr('data-staff-name') || '';
                const ccbrtCode = button.attr('data-ccbrt-code') || '';
                const department = button.attr('data-department') || '';

                console.log('Button clicked with data:', {
                    agreementId,
                    educationLevel,
                    locumRate,
                    staffName,
                    ccbrtCode,
                    department
                });

                // Update staff details
                $('#modal_staff_name').text((staffName && staffName.trim() !== '' && staffName !== 'N/A') ? staffName : '—');
                $('#modal_ccbrt_code').text((ccbrtCode && ccbrtCode.trim() !== '' && ccbrtCode !== 'N/A') ? ccbrtCode : '—');
                $('#modal_department').text((department && department.trim() !== '' && department !== 'N/A') ? department : '—');

                // Update form action
                const form = $('#editLocumRateForm');
                if (form.length && agreementId) {
                    form.attr('action', `/locum-agreements/${agreementId}/update-rate`);
                }

                const educationLevelSelect = $('#edit_education_level');
                const locumRateInput = $('#edit_locum_rate');

                if (educationLevelSelect.length && locumRateInput.length) {
                    // Remove previous event listener if it exists
                    educationLevelSelect.off('change', educationLevelChangeHandler);
                    
                    // Set the current education level
                    if (educationLevel && educationLevel.trim() !== '') {
                        educationLevelSelect.val(educationLevel);
                    } else {
                        educationLevelSelect.val('');
                    }
                    
                    // Always set the current rate from the agreement
                    if (locumRate && locumRate !== '0' && locumRate.trim() !== '') {
                        // Remove any formatting (commas, TZS, etc.) and convert to number
                        const cleanRate = locumRate.toString().replace(/[^\d.]/g, '');
                        locumRateInput.val(cleanRate);
                    } else {
                        locumRateInput.val('');
                    }
                    
                    // Add new event listener for when user changes education level
                    educationLevelChangeHandler = function() {
                        const selectedOption = $(this).find('option:selected');
                        if (selectedOption.length && selectedOption.attr('data-rate')) {
                            // When user changes education level, update to the default rate for that level
                            locumRateInput.val(selectedOption.attr('data-rate'));
                        }
                    };
                    
                    educationLevelSelect.on('change', educationLevelChangeHandler);
                }

                // Show the modal using jQuery
                editLocumRateModal.modal('show');
            });

            // Handle form submission with loading state
            $('#editLocumRateForm').on('submit', function(e) {
                const form = $(this);
                const updateBtn = $('#updateRateBtn');
                const cancelBtn = $('#cancelEditRateBtn');
                const btnText = updateBtn.find('.btn-text');
                const btnLoading = updateBtn.find('.btn-loading');

                // Disable buttons and show loading
                updateBtn.prop('disabled', true);
                cancelBtn.prop('disabled', true);
                btnText.addClass('d-none');
                btnLoading.removeClass('d-none');
            });
        });
    </script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            let dataTable = $('#locumTableAll').DataTable({
                paging: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: true,
                order: [[0, 'desc']], // Sort by Created date (newest first)
                columnDefs: [
                    {
                        targets: [0], // Created column
                        visible: false,
                        searchable: true
                    },
                    {
                        targets: -1, // Actions column
                        orderable: false
                    }
                ],
                language: {
                    search: "Search agreements:",
                    lengthMenu: "Show _MENU_ agreements per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ agreements",
                    infoEmpty: "Showing 0 to 0 of 0 agreements",
                    infoFiltered: "(filtered from _MAX_ total agreements)",
                    zeroRecords: "No matching agreements found",
                    emptyTable: "No agreements found",
                    paginate: {
                        previous: 'Previous',
                        next: 'Next'
                    }
                },
                drawCallback: function() {
                    // Row numbering (column index 1 = '#' column, since column 0 is hidden 'Created')
                    var api = this.api();
                    var startIndex = api.page.info().start;
                    api.column(1, {page: 'current'}).nodes().each(function(cell, i) {
                        cell.innerHTML = startIndex + i + 1;
                    });
                }
            });

            // Populate department and year dropdowns from table data
            const allDepts = new Set();
            const allYears = new Set();
            dataTable.rows().every(function() {
                const tr = this.node();
                const dept = ($(tr).data('dept') || '').trim();
                const year = ($(tr).data('year') || '').toString().trim();
                if (dept && dept !== '—') allDepts.add(dept);
                if (year) allYears.add(year);
            });
            Array.from(allDepts).sort().forEach(d =>
                $('#filterDepartment').append(`<option value="${d}">${d}</option>`)
            );
            Array.from(allYears).sort().reverse().forEach(y =>
                $('#filterYear').append(`<option value="${y}">${y}/${parseInt(y)+1}</option>`)
            );

            // Custom filter function
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'locumTableAll') return true;
                const tr = settings.aoData[dataIndex].nTr;

                // Status filter
                const statusFilter = $('#filterStatus').val();
                if (statusFilter) {
                    const rowStatus = ($(tr).data('status') || '').toLowerCase();
                    if (!rowStatus.includes(statusFilter.toLowerCase())) return false;
                }

                // Department filter
                const deptFilter = $('#filterDepartment').val();
                if (deptFilter) {
                    const rowDept = $(tr).data('dept') || '';
                    if (rowDept !== deptFilter) return false;
                }

                // Year filter
                const yearFilter = $('#filterYear').val();
                if (yearFilter) {
                    const rowYear = ($(tr).data('year') || '').toString();
                    if (rowYear !== yearFilter) return false;
                }

                // Active only filter
                const activeFilter = $('#filterActiveOnly').val();
                if (activeFilter === 'active') {
                    const isActive = $(tr).data('active');
                    if (isActive !== 1 && isActive !== '1') return false;
                }

                return true;
            });

            // Wire up filter changes
            $('#filterStatus, #filterDepartment, #filterYear, #filterActiveOnly').on('change', function() {
                dataTable.draw();
            });

            // Reset filters
            $('#btnResetFilters').on('click', function() {
                $('#filterStatus, #filterDepartment, #filterYear, #filterActiveOnly').val('');
                $('.stat-card').removeClass('active-filter');
                dataTable.draw();
            });

            // Stat card click filtering
            $('.stat-card').on('click', function() {
                const filter = $(this).data('filter');
                $('.stat-card').removeClass('active-filter');
                $(this).addClass('active-filter');

                // Reset all filters first
                $('#filterStatus').val('');
                $('#filterActiveOnly').val('');

                switch(filter) {
                    case 'all':
                        break;
                    case 'active':
                        $('#filterActiveOnly').val('active');
                        break;
                    case 'pending':
                        $('#filterStatus').val('Pending');
                        break;
                    case 'approved':
                        $('#filterStatus').val('Approved');
                        break;
                    case 'rejected':
                        $('#filterStatus').val('Rejected');
                        break;
                    case 'expired':
                        $('#filterStatus').val('Expired');
                        break;
                }
                dataTable.draw();
            });
        });
    </script>
@endsection
