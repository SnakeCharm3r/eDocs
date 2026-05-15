@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* Sticky action column */
        #staffTable th:last-child,
        #staffTable td:last-child {
            position: sticky;
            right: 0;
            background: #fff;
            z-index: 10;
            box-shadow: -2px 0 5px rgba(0,0,0,.07);
        }
        #staffTable th:last-child { background: #f8f9fa; z-index: 11; }

        /* Summary cards */
        .stat-card {
            border-radius: 6px;
            border: 1px solid #e9ecef;
            transition: box-shadow .15s;
            cursor: pointer;
        }
        .stat-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .stat-card.selected { border-color: #adb5bd; }

        .expiry-filter-card { transition: box-shadow .15s, transform .15s; }

        .staff-header-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .staff-header-actions .btn {
            padding: .4rem .8rem;
            font-size: .82rem;
            border-radius: .45rem;
            white-space: nowrap;
        }

        /* Table tweaks */
        #staffTable td, #staffTable th { vertical-align: middle; font-size: .82rem; }
        .badge { font-size: .72rem; }

        @media (max-width: 767.98px) {
            .staff-header-actions {
                justify-content: flex-start;
                margin-top: .75rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-users me-2"></i>Staff Details
                        </h3>
                    </div>
                    <div class="col-sm-6">
                        <div class="staff-header-actions">
                        <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="fas fa-file-excel me-1"></i> Export Staff Details
                        </button>
                        <a href="{{ route('staff.add') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-user-plus me-1"></i> Add New Staff
                        </a>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {!! session('error') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- ===== Status Summary Cards ===== --}}
            @php
                $totalAll        = collect($statusCounts ?? [])->sum();
                $activeCount     = (int)(($statusCounts ?? collect())['active']      ?? 0);
                $inactiveCount   = (int)(($statusCounts ?? collect())['inactive']    ?? 0);
                $deactivatedCount= (int)(($statusCounts ?? collect())['deactivated'] ?? 0);
            @endphp

            <div class="row g-2 mb-2">
                <div class="col-6 col-md-3">
                    <div class="stat-card px-3 py-2 bg-white" onclick="filterByStatus('')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="text-muted mb-0" style="font-size:.7rem">Total Staff</p><p class="fw-bold mb-0" style="font-size:1.1rem">{{ number_format($totalAll) }}</p></div>
                            <i class="fas fa-users text-muted" style="font-size:.9rem"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card px-3 py-2 bg-white" onclick="filterByStatus('active')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="text-muted mb-0" style="font-size:.7rem">Active</p><p class="fw-bold mb-0 text-success" style="font-size:1.1rem">{{ number_format($activeCount) }}</p></div>
                            <i class="fas fa-user-check text-success" style="font-size:.9rem"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card px-3 py-2 bg-white" onclick="filterByStatus('inactive')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="text-muted mb-0" style="font-size:.7rem">Inactive</p><p class="fw-bold mb-0 text-secondary" style="font-size:1.1rem">{{ number_format($inactiveCount) }}</p></div>
                            <i class="fas fa-user-slash text-secondary" style="font-size:.9rem"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card px-3 py-2 bg-white" onclick="filterByStatus('deactivated')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="text-muted mb-0" style="font-size:.7rem">Deactivated</p><p class="fw-bold mb-0 text-danger" style="font-size:1.1rem">{{ number_format($deactivatedCount) }}</p></div>
                            <i class="fas fa-user-times text-danger" style="font-size:.9rem"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bulk Actions --}}
            @role('super-admin|admin|hr|it|coo|cfo|cms')
                <div id="bulkActions" class="alert alert-light d-flex align-items-center justify-content-between py-2 mb-3" style="display:none!important;">
                    <span id="selectedCount" class="fw-semibold">0 selected</span>
                    <div>
                        <button class="btn btn-sm btn-success me-2" onclick="bulkActivate()"><i class="fas fa-check me-1"></i>Activate</button>
                        <button class="btn btn-sm btn-warning" onclick="bulkDeactivate()"><i class="fas fa-ban me-1"></i>Deactivate</button>
                    </div>
                </div>
            @endrole

            {{-- ===== Department & Contract Summary (side by side, collapsible) ===== --}}
            <div class="row g-3 mb-3">
                {{-- Department Summary --}}
                @if (isset($deptSummaries) && count($deptSummaries) > 0)
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0">
                                <button class="btn btn-link text-decoration-none p-0 text-dark fw-semibold w-100 text-start"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#deptSummary">
                                    <i class="fas fa-chart-bar me-2 text-muted"></i>Department Summary
                                    <i class="fas fa-chevron-down float-end text-muted"></i>
                                </button>
                            </h6>
                        </div>
                        <div id="deptSummary" class="collapse">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Department</th>
                                                @foreach ($statusColumns ?? [] as $status)
                                                    <th class="text-center">{{ ucfirst($status) }}</th>
                                                @endforeach
                                                <th class="text-center"><strong>Total</strong></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($deptSummaries ?? [] as $deptId => $summary)
                                                <tr>
                                                    <td>{{ $summary['name'] }}</td>
                                                    @foreach ($statusColumns ?? [] as $status)
                                                        <td class="text-center">{{ $summary['totals'][$status] ?? 0 }}</td>
                                                    @endforeach
                                                    <td class="text-center"><strong>{{ $summary['sum'] ?? 0 }}</strong></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Contract Summary --}}
                @if (!empty($contractSummaries) && count($contractSummaries) > 0)
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0">
                                <button class="btn btn-link text-decoration-none p-0 text-dark fw-semibold w-100 text-start"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#contractSummary">
                                    <i class="fas fa-file-contract me-2 text-muted"></i>Employment Contract Summary
                                    <i class="fas fa-chevron-down float-end text-muted"></i>
                                </button>
                            </h6>
                        </div>
                        <div id="contractSummary" class="collapse">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Contract Type</th>
                                                @foreach ($statusColumns ?? [] as $status)
                                                    <th class="text-center">{{ ucfirst($status) }}</th>
                                                @endforeach
                                                <th class="text-center"><strong>Total</strong></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($contractSummaries as $type => $summary)
                                                <tr>
                                                    <td>{{ $type }}</td>
                                                    @foreach ($statusColumns ?? [] as $status)
                                                        <td class="text-center">{{ $summary['totals'][$status] ?? 0 }}</td>
                                                    @endforeach
                                                    <td class="text-center"><strong>{{ $summary['sum'] ?? 0 }}</strong></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td><strong>Total</strong></td>
                                                @foreach ($statusColumns ?? [] as $status)
                                                    <td class="text-center"><strong>{{ collect($contractSummaries)->sum(fn($s) => $s['totals'][$status] ?? 0) }}</strong></td>
                                                @endforeach
                                                <td class="text-center"><strong>{{ collect($contractSummaries)->sum('sum') }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- ===== Contract Expiry Summary Cards ===== --}}
            @if(isset($expiringStaff) && $expiringStaff->count() > 0)
                @php
                    $expiring30  = $expiringStaff->filter(fn($s) => $s->days_until_expiry <= 30);
                    $expiring60  = $expiringStaff->filter(fn($s) => $s->days_until_expiry > 30 && $s->days_until_expiry <= 60);
                    $expiring90  = $expiringStaff->filter(fn($s) => $s->days_until_expiry > 60 && $s->days_until_expiry <= 90);
                    $withRequisition = $expiringStaff->filter(fn($s) => $s->active_requisition_id);
                    $withoutRequisition = $expiringStaff->filter(fn($s) => !$s->active_requisition_id);
                @endphp
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Contracts Expiring Within 90 Days
                            <span class="badge bg-danger ms-2">{{ $expiringStaff->count() }}</span>
                        </h6>
                    </div>
                    <div class="card-body py-3">
                        <div class="row g-2">
                            <div class="col-6 col-md-2">
                                <div class="border rounded px-3 py-2 text-center expiry-filter-card" data-range="all" style="cursor:pointer;">
                                    <div class="text-muted small">All Expiring</div>
                                    <div class="h5 fw-bold mb-0">{{ $expiringStaff->count() }}</div>
                                </div>
                            </div>
                            @if($expiring30->count() > 0)
                            <div class="col-6 col-md-2">
                                <div class="border border-danger rounded px-3 py-2 text-center expiry-filter-card" data-range="30" style="cursor:pointer;">
                                    <div class="text-muted small"><i class="fas fa-fire text-danger me-1"></i>≤ 30 days</div>
                                    <div class="h5 fw-bold mb-0 text-danger">{{ $expiring30->count() }}</div>
                                </div>
                            </div>
                            @endif
                            @if($expiring60->count() > 0)
                            <div class="col-6 col-md-2">
                                <div class="border border-warning rounded px-3 py-2 text-center expiry-filter-card" data-range="60" style="cursor:pointer;">
                                    <div class="text-muted small"><i class="fas fa-clock text-warning me-1"></i>31–60 days</div>
                                    <div class="h5 fw-bold mb-0 text-warning">{{ $expiring60->count() }}</div>
                                </div>
                            </div>
                            @endif
                            @if($expiring90->count() > 0)
                            <div class="col-6 col-md-2">
                                <div class="border border-info rounded px-3 py-2 text-center expiry-filter-card" data-range="90" style="cursor:pointer;">
                                    <div class="text-muted small"><i class="fas fa-info-circle text-info me-1"></i>61–90 days</div>
                                    <div class="h5 fw-bold mb-0 text-info">{{ $expiring90->count() }}</div>
                                </div>
                            </div>
                            @endif
                            <div class="col-6 col-md-2">
                                <div class="border border-success rounded px-3 py-2 text-center expiry-filter-card" data-range="has-req" style="cursor:pointer;">
                                    <div class="text-muted small"><i class="fas fa-check-circle text-success me-1"></i>Renewal Initiated</div>
                                    <div class="h5 fw-bold mb-0 text-success">{{ $withRequisition->count() }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="border border-danger rounded px-3 py-2 text-center expiry-filter-card" data-range="no-req" style="cursor:pointer;">
                                    <div class="text-muted small"><i class="fas fa-times-circle text-danger me-1"></i>No Renewal Process</div>
                                    <div class="h5 fw-bold mb-0 text-danger">{{ $withoutRequisition->count() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ===== Staff Table ===== --}}
            {{-- Columns: 0=checkbox, 1=#, 2=Code, 3=Name, 4=Department, 5=Contract Type, 6=Status, 7=Actions --}}
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table id="staffTable" class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Department / Entity</th>
                                <th>Contract Type</th>
                                <th>Contract Details</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Build lookup for expiring staff with requisition data
                                $expiringLookup = collect($expiringStaff ?? [])->keyBy('id');
                            @endphp
                            @foreach ($users as $user)
                                @php
                                    $statusClass = match ($user->status) {
                                        'active'      => 'success',
                                        'inactive'    => 'secondary',
                                        'pending'     => 'warning',
                                        'deactivated' => 'danger',
                                        default       => 'secondary',
                                    };
                                    $endDate = $user->ending_date ? \Carbon\Carbon::parse($user->ending_date) : null;
                                    $daysLeft = $endDate ? (int) now()->startOfDay()->diffInDays($endDate, false) : null;
                                    $expiringInfo = $expiringLookup[$user->id] ?? null;
                                @endphp
                                <tr data-employment-type="{{ optional($user->employmentType)->employment_type ?? '' }}"
                                    data-days-left="{{ $daysLeft ?? '' }}"
                                    data-has-req="{{ ($expiringInfo && $expiringInfo->active_requisition_id) ? '1' : '0' }}">
                                    @php
                                        $deptEntity = optional($user->department)->divisions->first()->name ?? null;
                                    @endphp
                                    <td class="text-muted small"></td>
                                    <td>
                                        <div class="fw-semibold">{{ $user->fname }} {{ $user->lname }}</div>
                                        <small class="text-muted">{{ $user->ccbrt_code ?? '—' }}</small>
                                    </td>
                                    <td>
                                        {{ optional($user->department)->dept_name ?? '—' }}
                                        @if ($deptEntity)
                                            <br><small class="text-muted">{{ $deptEntity }}</small>
                                        @endif
                                    </td>
                                    <td>{{ optional($user->employmentType)->employment_type ?? '—' }}</td>
                                    <td>
                                        @if($endDate)
                                            @php
                                                $isRenewalApproved = $expiringInfo && ($expiringInfo->requisition_status ?? '') === 'approved';
                                                $isRenewalInProgress = $expiringInfo && $expiringInfo->active_requisition_id && !$isRenewalApproved;
                                            @endphp
                                            <div style="font-size:.8rem">
                                                <i class="fas fa-calendar-alt text-muted me-1"></i>{{ $endDate->format('d M Y') }}
                                                @if($daysLeft !== null && $user->status === 'active')
                                                    @php
                                                        if ($daysLeft <= 0) {
                                                            $daysLabel = 'Expired';
                                                            $badgeClass = 'bg-dark';
                                                        } elseif ($daysLeft == 1) {
                                                            $daysLabel = '1 day left';
                                                            $badgeClass = 'bg-danger';
                                                        } elseif ($daysLeft <= 7) {
                                                            $daysLabel = $daysLeft . ' days left';
                                                            $badgeClass = 'bg-danger';
                                                        } elseif ($daysLeft <= 30) {
                                                            $daysLabel = $daysLeft . ' days left';
                                                            $badgeClass = 'bg-warning text-dark';
                                                        } elseif ($daysLeft <= 90) {
                                                            $months = intdiv($daysLeft, 30);
                                                            $remainDays = $daysLeft % 30;
                                                            $daysLabel = $months . ' month' . ($months > 1 ? 's' : '');
                                                            if ($remainDays > 0) $daysLabel .= ', ' . $remainDays . ' day' . ($remainDays > 1 ? 's' : '');
                                                            $daysLabel .= ' left';
                                                            $badgeClass = 'bg-info';
                                                        } else {
                                                            $months = intdiv($daysLeft, 30);
                                                            $remainDays = $daysLeft % 30;
                                                            if ($months >= 12) {
                                                                $years = intdiv($months, 12);
                                                                $remMonths = $months % 12;
                                                                $daysLabel = $years . ' yr' . ($years > 1 ? 's' : '');
                                                                if ($remMonths > 0) $daysLabel .= ', ' . $remMonths . ' mo';
                                                            } else {
                                                                $daysLabel = $months . ' month' . ($months > 1 ? 's' : '');
                                                                if ($remainDays > 0) $daysLabel .= ', ' . $remainDays . ' day' . ($remainDays > 1 ? 's' : '');
                                                            }
                                                            $daysLabel .= ' left';
                                                            $badgeClass = 'bg-secondary';
                                                        }
                                                        if ($isRenewalApproved && $daysLeft <= 90) $badgeClass = 'bg-success';
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}" style="font-size:.68rem">{{ $daysLabel }}</span>
                                                @endif
                                            </div>
                                            {{-- Requisition / renewal info --}}
                                            @if($isRenewalApproved)
                                                @php
                                                    $newContractLabel = match($expiringInfo->requisition_contract_type ?? '') {
                                                        'minimal_1_year' => '1+ Year Contract',
                                                        'termed_less_1_year' => '< 1 Year Contract',
                                                        'health_volunteer' => 'Health Volunteer',
                                                        'work_exposure' => 'Work Exposure',
                                                        default => 'Contract',
                                                    };
                                                    $newStart = $expiringInfo->requisition_required_start
                                                        ? \Carbon\Carbon::parse($expiringInfo->requisition_required_start)->format('d M Y')
                                                        : null;
                                                @endphp
                                                <div style="margin-top:3px">
                                                    <small class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>Renewed — {{ $newContractLabel }}</small>
                                                    @if($newStart)
                                                        <br><small class="text-muted" style="font-size:.7rem"><i class="fas fa-calendar-plus me-1"></i>New contract starts {{ $newStart }}</small>
                                                    @endif
                                                    <br><a href="{{ route('requisitions.show', $expiringInfo->active_requisition_access_id) }}" style="font-size:.72rem" title="View Requisition">
                                                        <i class="fas fa-file-alt me-1"></i>{{ $expiringInfo->active_requisition_access_id }}
                                                    </a>
                                                </div>
                                            @elseif($isRenewalInProgress)
                                                @php
                                                    $reqStatus = $expiringInfo->requisition_status;
                                                    $reqStatusLabel = match($reqStatus) {
                                                        'pending_hr' => 'Pending HR',
                                                        'pending_ceo' => 'Pending CEO',
                                                        'pending_cfo' => 'Pending CFO',
                                                        'pending_hec' => 'Pending HEC',
                                                        'pending_payroll' => 'Pending Payroll',
                                                        default => ucfirst(str_replace('_', ' ', $reqStatus ?? '')),
                                                    };
                                                    $renewalContractLabel = match($expiringInfo->requisition_contract_type ?? '') {
                                                        'minimal_1_year' => '1+ Year',
                                                        'termed_less_1_year' => '< 1 Year',
                                                        'health_volunteer' => 'Volunteer',
                                                        'work_exposure' => 'Exposure',
                                                        default => '',
                                                    };
                                                @endphp
                                                <div style="margin-top:3px">
                                                    <small class="text-warning fw-semibold"><i class="fas fa-clock me-1"></i>Renewal {{ $reqStatusLabel }}</small>
                                                    @if($renewalContractLabel)
                                                        <small class="badge bg-light text-dark border" style="font-size:.65rem">{{ $renewalContractLabel }}</small>
                                                    @endif
                                                    <br><a href="{{ route('requisitions.show', $expiringInfo->active_requisition_access_id) }}" style="font-size:.72rem" title="View Requisition">
                                                        <i class="fas fa-file-alt me-1"></i>{{ $expiringInfo->active_requisition_access_id }}
                                                    </a>
                                                </div>
                                            @elseif($expiringInfo && !$expiringInfo->active_requisition_id && $daysLeft !== null && $daysLeft <= 90 && $user->status === 'active')
                                                <div style="margin-top:3px">
                                                    <small class="text-danger"><i class="fas fa-times-circle me-1"></i>No renewal process</small>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-{{ $statusClass }}">{{ ucfirst($user->status ?? '—') }}</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('employees_details.show', ['id' => $user->id]) }}"
                                           class="btn btn-sm btn-outline-secondary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @role('super-admin|admin|hr|it|coo|cfo|cms')
                                            <a href="{{ route('user.edit', $user->id) }}"
                                               class="btn btn-sm btn-outline-primary ms-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endrole
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- ===== Staff Export Modal ===== --}}
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('staff.export') }}" id="staffExportForm">
                    <div class="modal-header bg-light border-bottom">
                        <h5 class="modal-title text-dark fw-semibold" id="exportModalLabel">
                            <i class="fas fa-file-excel me-2 text-success"></i>Export Staff Report
                        </h5>
                        <button type="button" class="btn-close" id="exportCloseBtn" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            <i class="fas fa-info-circle me-1"></i>Select filters to narrow down the data you want to export. Leave blank to export all staff.
                        </p>
                        <div class="row g-3" id="exportFields">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-filter me-1 text-success"></i>Status
                                </label>
                                <select name="export_status" id="expStatus" class="form-select form-select-sm">
                                    <option value="all">All Statuses</option>
                                    @foreach ($statusColumns ?? [] as $status)
                                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-building me-1 text-success"></i>Entity
                                </label>
                                <select name="entity" id="expEntity" class="form-select form-select-sm">
                                    <option value="">All Entities</option>
                                    @foreach ($entities ?? [] as $entity)
                                        <option value="{{ $entity->name }}">{{ $entity->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-sitemap me-1 text-success"></i>Department
                                </label>
                                <select name="department" id="expDepartment" class="form-select form-select-sm">
                                    <option value="">All Departments</option>
                                    @foreach ($departments ?? [] as $dept)
                                        <option value="{{ $dept->dept_name }}">{{ $dept->dept_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-file-contract me-1 text-success"></i>Contract Type
                                </label>
                                <select name="contract_type" id="expContractType" class="form-select form-select-sm">
                                    <option value="">All Contract Types</option>
                                    @foreach ($contractTypeCounts ?? [] as $type => $count)
                                        <option value="{{ $type }}">{{ $type }} ({{ $count }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">
                                    <i class="fas fa-clock me-1 text-success"></i>Contract Expiry
                                </label>
                                <select name="expiry_range" id="expExpiryRange" class="form-select form-select-sm">
                                    <option value="">All (no expiry filter)</option>
                                    <option value="30">Expiring within 30 days</option>
                                    <option value="60">Expiring within 60 days</option>
                                    <option value="90">Expiring within 90 days</option>
                                </select>
                            </div>
                        </div>

                        {{-- Progress area --}}
                        <div id="staffExportProgress" style="display:none;" class="mt-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                                <span class="small fw-semibold text-muted" id="staffExportProgressLabel">Preparing your report…</span>
                            </div>
                            <div class="progress" style="height:6px; border-radius:3px;">
                                <div id="staffExportProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width:0%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" id="staffExportCancelBtn" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm" id="staffExportSubmitBtn">
                            <i class="fas fa-download me-1"></i> Download Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Contract Type Summary Modal --}}
    <div class="modal fade" id="contractTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" id="contractTypeModalHeader">
                    <h5 class="modal-title" id="contractTypeModalTitle">
                        <i class="fas fa-file-contract me-2"></i><span id="contractTypeModalName"></span> — Staff Summary
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Status mini-cards --}}
                    <div class="row g-2 mb-3" id="contractTypeStatusCards"></div>

                    {{-- Department breakdown --}}
                    <h6 class="fw-bold mb-2"><i class="fas fa-sitemap me-1"></i> By Department</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered" id="contractTypeDeptTable">
                            <thead class="table-light">
                                <tr id="contractTypeDeptHead"></tr>
                            </thead>
                            <tbody id="contractTypeDeptBody"></tbody>
                        </table>
                    </div>

                    {{-- User list --}}
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0"><i class="fas fa-users me-1"></i> Staff List</h6>
                        <span id="contractTypeActiveFilter" class="badge bg-secondary" style="display:none!important;font-size:.8rem;"></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="contractTypeUserTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Job Title</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="contractTypeUserBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Professional License Management Modal --}}
    <div class="modal fade" id="licenseModal" tabindex="-1" aria-labelledby="licenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="licenseModalLabel">
                        <i class="fas fa-certificate me-2"></i>Manage Professional License
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="licenseForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="license_user_id" name="user_id">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Staff:</strong> <span id="license_user_name"></span>
                        </div>

                        <div class="mb-3">
                            <label for="professional_reg_number" class="form-label">
                                Professional Registration Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="professional_reg_number"
                                name="professional_reg_number" placeholder="e.g., MCT: 12345" required>
                            <small class="form-text text-muted">
                                Format: Type:Number (e.g., MCT:12345, TNMC:67890)
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="license_provider" class="form-label">
                                        License Provider/Authority <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" id="license_provider" name="license_provider" required>
                                        <option value="">--- Select Provider ---</option>
                                        <option value="Medical Council of Tanzania">Medical Council of Tanzania (MCT)
                                        </option>
                                        <option value="Tanzania Nursing and Midwifery Council">Tanzania Nursing and
                                            Midwifery Council (TNMC)</option>
                                        <option value="Tanzania Pharmacy Board">Tanzania Pharmacy Board (TPB)</option>
                                        <option value="Tanzania Physiotherapy Council">Tanzania Physiotherapy Council (TPC)
                                        </option>
                                        <option value="Tanzania Medical and Dental Council">Tanzania Medical and Dental
                                            Council (TMDC)</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Select the licensing authority for this professional registration
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="license_valid_until" class="form-label">
                                        License Valid Until <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="license_valid_until"
                                        name="license_valid_until" min="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="professional_reg_verified"
                                    name="professional_reg_verified" value="1">
                                <label class="form-check-label" for="professional_reg_verified">
                                    <strong>I verify that the professional registration number is active, valid, and the
                                        user is working under a provider of this license.</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Save License Information
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Contract type data for modal --}}
    @php
        $contractTypeUsersJson = $users->map(function($u) {
            $entity = optional($u->department)->divisions->first()->name ?? '—';
            return [
                'id'         => $u->id,
                'code'       => $u->ccbrt_code ?? 'N/A',
                'name'       => trim($u->fname . ' ' . $u->lname),
                'entity'     => $entity,
                'department' => optional($u->department)->dept_name ?? '—',
                'job_title'  => optional($u->jobTitle)->job_title ?? '—',
                'status'     => $u->status ?? '—',
                'contract'   => optional($u->employmentType)->employment_type ?? '',
            ];
        })->values();
    @endphp
    <script>
        var contractTypeUsers = @json($contractTypeUsersJson);

        // ---- Contract type modal globals ----
        var _ctStatusColor = { active: 'success', inactive: 'secondary', deactivated: 'danger', pending: 'warning' };
        var _ctStatusIcon  = { active: 'fa-user-check', inactive: 'fa-user-slash', deactivated: 'fa-user-times', pending: 'fa-clock' };
        var _ctColorMap    = {
            'Consultant': '#6f42c1', 'Permanent': '#198754', 'Contract': '#0d6efd',
            'Intern': '#fd7e14',     'Volunteer': '#20c997', 'Locum':   '#dc3545'
        };
        var _modalUsers = [];

        function _cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '—'; }

        function renderModalUserList(users, activeStatus) {
            var toShow = activeStatus
                ? users.filter(function(u) { return u.status.toLowerCase() === activeStatus.toLowerCase(); })
                : users;

            if (activeStatus) {
                var sc = _ctStatusColor[activeStatus] || 'secondary';
                document.getElementById('contractTypeActiveFilter').textContent =
                    'Showing: ' + _cap(activeStatus) + ' (' + toShow.length + ')';
                document.getElementById('contractTypeActiveFilter').className = 'badge bg-' + sc;
                document.getElementById('contractTypeActiveFilter').style.display = 'inline-block';
            } else {
                document.getElementById('contractTypeActiveFilter').style.display = 'none';
            }

            var html = '';
            toShow.forEach(function(u, i) {
                var sc = _ctStatusColor[u.status] || 'secondary';
                html += '<tr>'
                    + '<td>' + (i + 1) + '</td>'
                    + '<td>' + u.code + '</td>'
                    + '<td>' + u.name + '</td>'
                    + '<td>' + u.department + '</td>'
                    + '<td>' + u.job_title + '</td>'
                    + '<td><span class="badge bg-' + sc + '">' + _cap(u.status) + '</span></td>'
                    + '</tr>';
            });
            document.getElementById('contractTypeUserBody').innerHTML =
                html || '<tr><td colspan="6" class="text-center text-muted">No staff found.</td></tr>';
        }

        function filterByContractType(type) {
            if (!type) return;

            _modalUsers = contractTypeUsers.filter(function(u) {
                return u.contract.toLowerCase() === type.toLowerCase();
            });

            // Header colour
            var color = _ctColorMap[type] || '#6c757d';
            var header = document.getElementById('contractTypeModalHeader');
            header.style.backgroundColor = color;
            header.style.color = '#fff';
            document.getElementById('contractTypeModalName').textContent = type;

            // Status counts
            var statusCount = {};
            _modalUsers.forEach(function(u) {
                var s = u.status || '—';
                statusCount[s] = (statusCount[s] || 0) + 1;
            });

            // Status filter cards (All + per status)
            var cardsHtml = '<div class="col-auto">'
                + '<div class="card border px-3 py-2 text-center modal-status-card" style="cursor:pointer;" data-status="">'
                + '<div class="text-muted small"><i class="fas fa-users me-1"></i>All</div>'
                + '<div class="h5 fw-bold mb-0">' + _modalUsers.length + '</div>'
                + '</div></div>';
            Object.keys(statusCount).forEach(function(s) {
                var n  = statusCount[s];
                var sc = _ctStatusColor[s] || 'secondary';
                var si = _ctStatusIcon[s]  || 'fa-user';
                cardsHtml += '<div class="col-auto">'
                    + '<div class="card border border-' + sc + ' px-3 py-2 text-center modal-status-card" style="cursor:pointer;" data-status="' + s + '">'
                    + '<div class="text-muted small"><i class="fas ' + si + ' me-1"></i>' + _cap(s) + '</div>'
                    + '<div class="h5 fw-bold mb-0 text-' + sc + '">' + n + '</div>'
                    + '</div></div>';
            });
            document.getElementById('contractTypeStatusCards').innerHTML = cardsHtml;

            // Department breakdown
            var statuses = Object.keys(statusCount).sort();
            var deptMap  = {};
            _modalUsers.forEach(function(u) {
                var d = u.department || '—';
                if (!deptMap[d]) deptMap[d] = { sum: 0 };
                deptMap[d][u.status] = (deptMap[d][u.status] || 0) + 1;
                deptMap[d].sum++;
            });

            var headHtml = '<th>Department</th>';
            statuses.forEach(function(s) {
                headHtml += '<th class="text-center text-' + (_ctStatusColor[s] || 'secondary') + '">' + _cap(s) + '</th>';
            });
            headHtml += '<th class="text-center"><strong>Total</strong></th>';
            document.getElementById('contractTypeDeptHead').innerHTML = headHtml;

            var bodyHtml = '';
            Object.keys(deptMap).sort().forEach(function(dept) {
                var data = deptMap[dept];
                bodyHtml += '<tr><td><strong>' + dept + '</strong></td>';
                statuses.forEach(function(s) {
                    var n  = data[s] || 0;
                    var sc = _ctStatusColor[s] || 'secondary';
                    bodyHtml += '<td class="text-center">'
                        + (n > 0 ? '<span class="badge bg-' + sc + ' bg-opacity-75">' + n + '</span>' : '<span class="text-muted">0</span>')
                        + '</td>';
                });
                bodyHtml += '<td class="text-center"><strong>' + data.sum + '</strong></td></tr>';
            });
            document.getElementById('contractTypeDeptBody').innerHTML = bodyHtml;

            // Bind status card clicks
            document.getElementById('contractTypeStatusCards').addEventListener('click', function(e) {
                var card = e.target.closest('.modal-status-card');
                if (!card) return;
                document.querySelectorAll('.modal-status-card').forEach(function(c) {
                    c.style.boxShadow = '';
                    c.style.transform = '';
                });
                card.style.boxShadow = '0 0 0 3px rgba(0,0,0,0.25)';
                card.style.transform = 'scale(1.05)';
                renderModalUserList(_modalUsers, card.dataset.status);
            }, { once: false });

            // Show all users initially
            renderModalUserList(_modalUsers, '');

            var modal = new bootstrap.Modal(document.getElementById('contractTypeModal'));
            modal.show();
        }
    </script>

@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Suppress DataTables warnings
        (function() {
            var _warn = console.warn;
            console.warn = function() {
                if (arguments[0] && typeof arguments[0] === 'string' &&
                    arguments[0].includes('DataTables')) return;
                _warn.apply(console, arguments);
            };
        })();

        $(document).ready(function() {
            if (!$('#staffTable').length || typeof $.fn.DataTable === 'undefined') return;

            // Cols: 0=#, 1=Name, 2=Dept/Entity, 3=Contract Type, 4=Contract Details, 5=Status, 6=Actions
            var staffTable = $('#staffTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [[1, 'asc']],
                autoWidth: false,
                responsive: true,
                columnDefs: [
                    { orderable: false, targets: [6] }
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ staff',
                    infoEmpty: 'No staff available',
                    infoFiltered: '(filtered from _MAX_ total)',
                    emptyTable: 'No staff found'
                },
                drawCallback: function(settings) {
                    var api = this.api();
                    api.column(0, { search: 'applied', order: 'applied' }).nodes().each(function(cell, i) {
                        cell.innerHTML = (api.page() * api.page.len()) + i + 1;
                    });
                }
            });

            // ===== Export Modal =====
            $('#exportModal').on('show.bs.modal', function() {
                // Reset filter defaults
                $('#expStatus').val('all');
                $('#expEntity').val('');
                $('#expDepartment').val('');
                $('#expContractType').val('');
                // Reset progress
                $('#exportFields').show();
                $('#staffExportProgress').hide();
                $('#staffExportSubmitBtn').prop('disabled', false).html('<i class="fas fa-download me-1"></i> Download Excel');
                $('#exportCloseBtn, #staffExportCancelBtn').prop('disabled', false);
            });

            // Export — direct download (no iframe needed)
            $('#staffExportSubmitBtn').on('click', function(e) {
                e.preventDefault();

                // Build URL from form fields
                var params = $('#staffExportForm').serialize();
                var url = $('#staffExportForm').attr('action') + '?' + params;

                // Show progress
                $('#staffExportProgress').show();
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generating…');
                $('#exportCloseBtn, #staffExportCancelBtn').prop('disabled', true);

                var bar = $('#staffExportProgressBar');
                var progress = 0;
                var interval = setInterval(function() {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90;
                    bar.css('width', progress + '%');
                }, 300);

                // Use fetch to detect when download is ready, then trigger it
                fetch(url, { credentials: 'same-origin' }).then(function(response) {
                    return response.blob();
                }).then(function(blob) {
                    clearInterval(interval);
                    bar.css('width', '100%');
                    $('#staffExportProgressLabel').text('Download complete!');

                    // Trigger browser download from blob
                    var a = document.createElement('a');
                    a.href = window.URL.createObjectURL(blob);
                    a.download = 'Staff_Report_' + new Date().toISOString().slice(0,10) + '.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(a.href);

                    setTimeout(function() {
                        var modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
                        if (modal) modal.hide();
                    }, 800);
                }).catch(function() {
                    clearInterval(interval);
                    bar.css('width', '100%');
                    $('#staffExportProgressLabel').text('Error generating report.');
                    $('#exportCloseBtn, #staffExportCancelBtn').prop('disabled', false);
                    $('#staffExportSubmitBtn').prop('disabled', false).html('<i class="fas fa-download me-1"></i> Download Excel');
                });
            });

        // Status card filter
        function filterByStatus(status) {
            staffTable.search(status).draw();
            document.querySelectorAll('.stat-card').forEach(function(c) { c.classList.remove('selected'); });
            if (event && event.currentTarget) event.currentTarget.classList.add('selected');
        }

        // Bulk Selection
        $('#selectAll').on('change', function() {
            $('.staff-checkbox').prop('checked', this.checked);
            updateBulkActions();
        });

        $('.staff-checkbox').on('change', function() {
            updateBulkActions();
            $('#selectAll').prop('checked', $('.staff-checkbox:checked').length === $('.staff-checkbox').length);
        });

        function updateBulkActions() {
            var count = $('.staff-checkbox:checked').length;
            $('#selectedCount').text(count + ' selected');
            document.getElementById('bulkActions').style.display = count > 0 ? 'flex' : 'none';
        }

        // Bulk Actions
        function bulkActivate() {
            var ids = $('.staff-checkbox:checked').map(function() {
                return this.value;
            }).get();
            if (ids.length === 0) return;
            performBulkAction(ids, 'activate', 'Activate');
        }

        function bulkDeactivate() {
            var ids = $('.staff-checkbox:checked').map(function() {
                return this.value;
            }).get();
            if (ids.length === 0) return;
            performBulkAction(ids, 'deactivate', 'Deactivate');
        }

        function performBulkAction(ids, action, label) {
            $.ajax({
                url: '{{ route('staff.bulk-action') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: ids,
                    action: action
                },
                success: function(response) {
                    Swal.fire('Success!', response.message || `${label} completed.`, 'success')
                        .then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred.', 'error');
                }
            });
        }

        // Professional License Management
        function openLicenseModal(userId, userName, professionalRegNumber, licenseData) {
            $('#license_user_id').val(userId);
            $('#license_user_name').text(userName);
            $('#professional_reg_number').val(professionalRegNumber || '');
            $('#licenseForm').attr('action', '{{ route('staff.manage-license') }}');

            // Set license data if available
            if (licenseData) {
                $('#license_provider').val(licenseData.license_provider || '');
                $('#license_valid_until').val(licenseData.license_valid_until || '');
                $('#professional_reg_verified').prop('checked', licenseData.professional_reg_verified || false);
            } else {
                $('#license_provider').val('');
                $('#license_valid_until').val('');
                $('#professional_reg_verified').prop('checked', false);
            }

            // Reset form and set values
            $('#licenseForm')[0].reset();
            $('#license_user_id').val(userId);
            $('#license_user_name').text(userName);
            if (professionalRegNumber) {
                $('#professional_reg_number').val(professionalRegNumber);
                // Suggest provider based on registration number
                suggestLicenseProvider(professionalRegNumber);
            }
            if (licenseData) {
                $('#license_provider').val(licenseData.license_provider || '');
                $('#license_valid_until').val(licenseData.license_valid_until || '');
                $('#professional_reg_verified').prop('checked', licenseData.professional_reg_verified || false);
            }

            $('#licenseModal').modal('show');
        }

        // Auto-suggest provider from registration number (optional helper)
        function suggestLicenseProvider(regNumber) {
            const licenseProviders = {
                'MCT': 'Medical Council of Tanzania',
                'TNMC': 'Tanzania Nursing and Midwifery Council',
                'TPB': 'Tanzania Pharmacy Board',
                'TPC': 'Tanzania Physiotherapy Council',
                'TMDC': 'Tanzania Medical and Dental Council',
                'Other': 'Other'
            };

            if (regNumber) {
                const match = regNumber.match(/^([A-Z]+):/);
                if (match) {
                    const regType = match[1];
                    const suggestedProvider = licenseProviders[regType] || 'Other';
                    // Set the select value if it matches
                    const $select = $('#license_provider');
                    if ($select.find(`option:contains("${suggestedProvider}")`).length > 0) {
                        $select.val(suggestedProvider);
                    }
                }
            }
        }

        // Optional: Auto-suggest provider when registration number changes
        $('#professional_reg_number').on('input', function() {
            suggestLicenseProvider($(this).val());
        });

        // Handle form submission
        $('#licenseForm').on('submit', function(e) {
            e.preventDefault();

            const formData = $(this).serialize();
            const userId = $('#license_user_id').val();

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                success: function(response) {
                    Swal.fire('Success!', response.message || 'License information saved successfully.',
                            'success')
                        .then(() => {
                            $('#licenseModal').modal('hide');
                            location.reload();
                        });
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while saving license information.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        errorMessage = errors.join('<br>');
                    }
                    Swal.fire('Error!', errorMessage, 'error');
                }
            });
        });

        // Expiry section filter cards — filter main staffTable
        var activeExpiryRange = null;
        $(document).on('click', '.expiry-filter-card', function() {
            var range = $(this).data('range');
            $('.expiry-filter-card').css({ 'box-shadow': '', 'transform': '' });
            $(this).css({ 'box-shadow': '0 0 0 3px rgba(0,0,0,0.2)', 'transform': 'scale(1.05)' });

            // Toggle off if clicking same card
            if (activeExpiryRange === range) {
                activeExpiryRange = null;
                $('.expiry-filter-card').css({ 'box-shadow': '', 'transform': '' });
            } else {
                activeExpiryRange = range;
            }

            staffTable.draw();
        });

        // Add expiry range filter to DataTables
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'staffTable') return true;
            if (!activeExpiryRange) return true;

            var row = staffTable.row(dataIndex).node();
            var daysStr = $(row).data('days-left');
            var hasReq = $(row).data('has-req');

            if (activeExpiryRange === 'all') {
                return daysStr !== '' && parseInt(daysStr) >= 0 && parseInt(daysStr) <= 90;
            } else if (activeExpiryRange === '30') {
                return daysStr !== '' && parseInt(daysStr) >= 0 && parseInt(daysStr) <= 30;
            } else if (activeExpiryRange === '60') {
                return daysStr !== '' && parseInt(daysStr) > 30 && parseInt(daysStr) <= 60;
            } else if (activeExpiryRange === '90') {
                return daysStr !== '' && parseInt(daysStr) > 60 && parseInt(daysStr) <= 90;
            } else if (activeExpiryRange === 'has-req') {
                return daysStr !== '' && parseInt(daysStr) >= 0 && parseInt(daysStr) <= 90 && hasReq == 1;
            } else if (activeExpiryRange === 'no-req') {
                return daysStr !== '' && parseInt(daysStr) >= 0 && parseInt(daysStr) <= 90 && hasReq == 0;
            }
            return true;
        });

        }); // end document.ready
    </script>
@endpush
