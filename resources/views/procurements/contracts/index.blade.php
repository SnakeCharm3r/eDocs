@extends('layouts.template')

@php
    use Illuminate\Support\Str;
    use App\Models\CcbrtContract;
@endphp

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Error:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white text-dark border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 text-dark">
                            <i class="fas fa-file-contract me-2"></i>Contract Dashboard
                        </h4>
                        <small class="text-muted">
                            <i class="fas fa-chart-line me-1"></i>Total Contracts:
                            <strong>{{ $totalContracts ?? 0 }}</strong> |
                            <i class="fas fa-money-bill-wave me-1"></i>Total Value:
                            <strong>{{ number_format($totalValue ?? 0, 2) }}</strong> /=
                        </small>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @if (($myPendingCount ?? 0) > 0)
                            <a href="{{ route('procurements.contracts.index', ['view' => 'my_pending']) }}"
                                class="btn btn-warning btn-sm text-dark fw-semibold d-flex align-items-center gap-1"
                                style="animation:contract-alert-pulse 2s ease-in-out infinite">
                                <i class="fas fa-exclamation-circle"></i>
                                Pending My Action
                                <span class="badge bg-danger text-white ms-1">{{ $myPendingCount }}</span>
                            </a>
                        @endif
                        <a href="{{ route('procurements.contracts.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Add Contract
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    @php
                        $user = auth()->user();
                        $isHecMember = $user && $user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro']);
                        $isLineManager = $user && $user->hasRole('line-manager');
                        $isProcurementOfficer = $user && $user->hasRole('procurement-officer');
                        $colClass = 'col';
                    @endphp

                    {{-- Row of clickable stat cards --}}
                    <div class="row g-3 mb-4">
                        @php
                            $cards = [
                                ['view'=>'active',      'id'=>'card-active',      'label'=>'Active',          'count'=>$activeContractsCount,                      'value'=>$activeContractsValue,                    'icon'=>'fa-check-circle',  'accent'=>'#28a745'],
                                ['view'=>'expiring',    'id'=>'card-expiring',    'label'=>'Expiring Soon',   'count'=>$soonToExpireContractsCount,                 'value'=>$soonToExpireContractsValue,               'icon'=>'fa-clock',         'accent'=>'#ffc107'],
                                ['view'=>'expired',     'id'=>'card-expired',     'label'=>'Expired',         'count'=>$expiredContractsCount,                      'value'=>$expiredContractsValue,                    'icon'=>'fa-times-circle',  'accent'=>'#dc3545'],
                                ['view'=>'archived',    'id'=>'card-archived',    'label'=>'Archived',        'count'=>$archivedContractsCount ?? 0,                'value'=>$archivedContractsValue ?? 0,              'icon'=>'fa-archive',       'accent'=>'#6c757d'],
                            ];
                        @endphp
                        @foreach ($cards as $card)
                        <div class="col-sm-6 col-xl-3">
                            <a href="{{ route('procurements.contracts.index', ['view' => $card['view']]) }}" class="text-decoration-none">
                                <div id="{{ $card['id'] }}" class="stat-card bg-white border rounded shadow-sm card-hover h-100"
                                    style="border-left: 4px solid {{ $card['accent'] }} !important; padding: 1.1rem 1.25rem;{{ ($viewType ?? 'active') === $card['view'] ? 'box-shadow:0 0 0 2px '.$card['accent'].'40!important;' : '' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-semibold">{{ $card['label'] }}</span>
                                        <i class="fas {{ $card['icon'] }} fa-lg" style="color:{{ $card['accent'] }}"></i>
                                    </div>
                                    <div class="fw-bold text-dark" style="font-size:1.7rem;line-height:1">{{ $card['count'] }}</div>
                                    <div class="text-muted small mt-1" style="font-size:0.78rem">{{ number_format($card['value'], 2) }} /=</div>
                                    @if ($card['view'] === 'expiring' && $isProcurementOfficer && $soonToExpireContractsCount > 0)
                                        <form action="{{ route('procurements.contracts.send-bulk-reminders') }}" method="POST" class="mt-2"
                                            onsubmit="return confirm('Send renewal reminders to Line Managers for ALL {{ $soonToExpireContractsCount }} expiring contracts?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-warning btn-sm w-100 py-1" style="font-size:0.75rem">
                                                <i class="fas fa-bell me-1"></i>Send All Reminders
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </a>
                        </div>
                        @endforeach
                        @if ($isProcurementOfficer)
                        <div class="col-sm-6 col-xl-3">
                            <a href="{{ route('procurements.contracts.index', ['view' => 'procurement']) }}" class="text-decoration-none">
                                <div id="card-procurement" class="stat-card bg-white border rounded shadow-sm card-hover h-100"
                                    style="border-left:4px solid #0dcaf0!important;padding:1.1rem 1.25rem;{{ ($viewType ?? 'active') === 'procurement' ? 'box-shadow:0 0 0 2px #0dcaf040!important;' : '' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-semibold">Pending Procurement</span>
                                        <i class="fas fa-tasks fa-lg" style="color:#0dcaf0"></i>
                                    </div>
                                    <div class="fw-bold text-dark" style="font-size:1.7rem;line-height:1">{{ $pendingProcurementReviewCount ?? 0 }}</div>
                                    <div class="text-muted small mt-1" style="font-size:0.78rem">{{ number_format($pendingProcurementReviewValue ?? 0, 2) }} /=</div>
                                </div>
                            </a>
                        </div>
                        @endif
                    </div>

                    {{-- Filters and Export --}}
                    <div class="mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom">
                                <h6 class="mb-0 text-dark"><i class="fas fa-filter me-2"></i>Filter Contracts</h6>
                            </div>
                            <div class="card-body">
                                <form id="filterForm" class="row g-3 align-items-end">
                                    <div class="col-md-2">
                                        <label for="filterStatus" class="form-label">Status</label>
                                        <select id="filterStatus" class="form-select">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="draft">Draft</option>
                                            <option value="expired">Expired</option>
                                            <option value="terminated">Terminated</option>
                                            <option value="soonToExpire">Soon To Expire</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filterDateType" class="form-label">Date Type</label>
                                        <select id="filterDateType" class="form-select">
                                            <option value="start_date">Start Date</option>
                                            <option value="end_date">End Date</option>
                                            <option value="created_at">Created At</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filterYear" class="form-label">Year</label>
                                        <select id="filterYear" class="form-select">
                                            <option value="">All Years</option>
                                            @foreach ($availableYears ?? [] as $year)
                                                <option value="{{ $year }}"
                                                    {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filterMonth" class="form-label">Month</label>
                                        <select id="filterMonth" class="form-select">
                                            <option value="">All Months</option>
                                            @for ($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}">
                                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filterDay" class="form-label">Day</label>
                                        <input type="number" id="filterDay" class="form-control" min="1"
                                            max="31" placeholder="All Days">
                                    </div>
                                    <div class="col-md-2 d-flex gap-2 justify-content-end align-items-end">
                                        <button type="button" class="btn btn-secondary" onclick="applyFilters()"
                                            title="Apply Filters">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()"
                                            title="Clear Filters">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="exportTable()"
                                            title="Export Table">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Contract Tables --}}
                    <div id="table-section">

                        {{-- My Pending Actions (all roles) --}}
                        <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'my_pending' ? '' : 'd-none' }}"
                            id="table-my_pending">

                            @php $myPendingCnt = ($myPendingContracts ?? collect())->count(); @endphp

                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="display:inline-block;width:4px;height:1.2rem;background:#fd7e14;border-radius:2px"></span>
                                    <h6 class="mb-0 fw-semibold text-dark">Contracts Pending My Action</h6>
                                    <span class="badge bg-warning text-dark">{{ $myPendingCnt }}</span>
                                </div>
                                @if ($myPendingCnt > 0)
                                    <span class="text-muted small"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Click <strong>Review</strong> on each contract to take action.</span>
                                @endif
                            </div>
                            <table id="datatable-my_pending" class="table table-hover table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Contract</th>
                                        <th>Department</th>
                                        <th>Validity</th>
                                        <th>Status</th>
                                        <th>Stage</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($myPendingContracts ?? [] as $index => $contract)
                                        <tr data-contract-url="{{ route('procurements.contracts.show', $contract->id) }}" style="cursor:pointer">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ Str::limit($contract->title, 50) }}
                                                @if ($contract->contract_number)
                                                    <br><small class="text-muted">#{{ $contract->contract_number }}</small>
                                                @endif
                                                <br><small class="text-muted">{{ number_format($contract->cost ?? 0, 2) }} {{ $contract->currency ?? 'TZS' }}</small>
                                            </td>
                                            <td>
                                                @if ($contract->division)
                                                    <small class="text-muted d-block">{{ $contract->division->name }}</small>
                                                @endif
                                                {{ $contract->department ? Str::limit($contract->department->dept_name, 25) : '-' }}
                                            </td>
                                            @php
                                                $vStart = $contract->start_date ? \Carbon\Carbon::parse($contract->start_date) : null;
                                                $vEnd   = $contract->end_date   ? \Carbon\Carbon::parse($contract->end_date)   : null;
                                                $vDays  = $vEnd ? \Carbon\Carbon::now()->diffInDays($vEnd, false) : null;
                                                if ($vDays !== null) {
                                                    if ($vDays < 0)       { $vTxt = 'Expired '.abs($vDays).'d ago'; $vCls = 'text-danger small'; }
                                                    elseif ($vDays == 0)  { $vTxt = 'Expires today';                $vCls = 'text-danger fw-bold small'; }
                                                    elseif ($vDays <= 30) { $vTxt = $vDays.' days left';            $vCls = 'text-warning fw-bold small'; }
                                                    elseif ($vDays <= 365){ $vTxt = round($vDays/30).' mo left';    $vCls = 'text-success small'; }
                                                    else { $vYr=floor($vDays/365); $vMo=round(($vDays%365)/30); $vTxt=$vYr.'y '.($vMo>0?$vMo.'m ':'').'left'; $vCls='text-success small'; }
                                                }
                                            @endphp
                                            <td>
                                                @if ($vStart || $vEnd)
                                                    <small class="text-muted d-block">{{ $vStart?->format('d M Y') ?? '—' }} → {{ $vEnd?->format('d M Y') ?? '—' }}</small>
                                                @endif
                                                @if ($vDays !== null)
                                                    <span class="{{ $vCls }}">{{ $vTxt }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $stColors = ['active'=>'success','expired'=>'danger','soonToExpire'=>'warning','draft'=>'secondary','in_progress'=>'info','renewed'=>'primary','terminated'=>'dark'];
                                                    $stColor  = $stColors[$contract->status ?? ''] ?? 'secondary';
                                                    $stLabel  = $contract->status === 'soonToExpire' ? 'Soon to Expire' : ucfirst(str_replace('_',' ',$contract->status ?? 'Draft'));
                                                @endphp
                                                <span class="badge bg-{{ $stColor }}">{{ $stLabel }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $stageKey = $contract->approval_stage ?? '';
                                                    $stageCfg = [
                                                        'line_manager' => ['label'=>'Line Manager Review', 'bg'=>'#856404', 'color'=>'#fff'],
                                                        'hec'          => ['label'=>'HEC Approval',        'bg'=>'#0d6efd', 'color'=>'#fff'],
                                                        'procurement'  => ['label'=>'Procurement',          'bg'=>'#0a58ca', 'color'=>'#fff'],
                                                    ];
                                                    $sc = $stageCfg[$stageKey] ?? ['label'=>ucfirst($stageKey),'bg'=>'#6c757d','color'=>'#fff'];
                                                @endphp
                                                <span class="badge" style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }}">
                                                    {{ $sc['label'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('procurements.contracts.show', $contract->id) }}" class="btn btn-sm btn-success">
                                                    <i class="fas fa-eye"></i> Review
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if (($myPendingContracts ?? collect())->isEmpty())
                                        <tr><td colspan="7" class="text-center text-muted py-3">No contracts pending your action</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- Procurement work queue (only for Procurement Officer) --}}
                        @if ($isProcurementOfficer)
                            <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'procurement' ? '' : 'd-none' }}"
                                id="table-procurement">
                                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span style="display:inline-block;width:4px;height:1.2rem;background:#0dcaf0;border-radius:2px"></span>
                                        <h6 class="mb-0 fw-semibold text-dark">Pending Procurement Action</h6>
                                        <span class="badge bg-info">{{ ($pendingProcurementReviewContracts ?? collect())->count() }}</span>
                                    </div>
                                </div>
                                <table id="datatable-procurement" class="table table-hover table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Contract</th>
                                            <th>Department</th>
                                            <th>Validity</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (($pendingProcurementReviewContracts ?? collect()) as $index => $contract)
                                            <tr data-start-date="{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '' }}"
                                                data-end-date="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '' }}"
                                                data-contract-url="{{ route('procurements.contracts.show', $contract->id) }}"
                                                style="cursor:pointer">
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    {{ Str::limit($contract->title, 50) }}
                                                    @if ($contract->contract_number)
                                                        <br><small class="text-muted">#{{ $contract->contract_number }}</small>
                                                    @endif
                                                    <br><small class="text-muted">{{ number_format($contract->cost ?? 0, 2) }} {{ $contract->currency ?? 'TZS' }}</small>
                                                    <br><small class="text-muted">Renewal waiting for Procurement finalization</small>
                                                </td>
                                                <td>
                                                    @if ($contract->department)
                                                        {{ Str::limit($contract->department->dept_name, 25) }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                @php
                                                    $vStart = $contract->start_date ? \Carbon\Carbon::parse($contract->start_date) : null;
                                                    $vEnd   = $contract->end_date   ? \Carbon\Carbon::parse($contract->end_date)   : null;
                                                    $vDays  = $vEnd ? \Carbon\Carbon::now()->diffInDays($vEnd, false) : null;
                                                    if ($vDays !== null) {
                                                        if ($vDays < 0)       { $vTxt = 'Expired '.abs($vDays).'d ago';  $vCls = 'text-danger small'; }
                                                        elseif ($vDays <= 30) { $vTxt = $vDays.' days left';               $vCls = 'text-warning fw-bold small'; }
                                                        elseif ($vDays <= 365){ $vTxt = round($vDays/30).' mo left';       $vCls = 'text-success small'; }
                                                        else { $vYr = floor($vDays/365); $vMo = round(($vDays%365)/30); $vTxt = $vYr.'y '.($vMo>0?$vMo.'m ':'').'left'; $vCls = 'text-success small'; }
                                                    }
                                                @endphp
                                                <td>
                                                    @if ($vStart || $vEnd)
                                                        <small class="text-muted d-block">{{ $vStart ? $vStart->format('d M Y') : '—' }} → {{ $vEnd ? $vEnd->format('d M Y') : '—' }}</small>
                                                    @endif
                                                    @if ($vDays !== null)
                                                        <span class="{{ $vCls }}">{{ $vTxt }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ ucfirst($contract->status ?? 'In progress') }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                        class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-play me-1"></i> Finalize
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @if (($pendingProcurementReviewContracts ?? collect())->isEmpty())
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No contracts pending procurement action</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Active --}}
                        <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'active' ? '' : 'd-none' }}"
                            id="table-active">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="display:inline-block;width:4px;height:1.2rem;background:#28a745;border-radius:2px"></span>
                                    <h6 class="mb-0 fw-semibold text-dark">Active Contracts</h6>
                                    <span class="badge bg-success">{{ $activeContracts->count() }}</span>
                                </div>
                            </div>
                            <table id="datatable-active" class="table table-hover table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Contract</th>
                                        <th>Department</th>
                                        <th>Validity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($activeContracts as $index => $contract)
                                        <tr data-start-date="{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '' }}"
                                            data-end-date="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '' }}"
                                            data-contract-url="{{ route('procurements.contracts.show', $contract->id) }}"
                                            style="cursor:pointer">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ Str::limit($contract->title, 50) }}
                                                @if ($contract->contract_number)
                                                    <br><small class="text-muted">#{{ $contract->contract_number }}</small>
                                                @endif
                                                @if ($contract->vendor)
                                                    <br><small class="text-muted">Vendor: {{ Str::limit($contract->vendor->name, 30) }}</small>
                                                @endif
                                                <br><small class="text-muted">{{ number_format($contract->cost ?? 0, 2) }} {{ $contract->currency ?? 'TZS' }}</small>
                                            </td>
                                            <td>
                                                @if ($contract->division)
                                                    <small class="text-muted d-block">{{ $contract->division->name }}</small>
                                                @endif
                                                @if ($contract->department)
                                                    {{ Str::limit($contract->department->dept_name, 25) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            @php
                                                $vStart = $contract->start_date ? \Carbon\Carbon::parse($contract->start_date) : null;
                                                $vEnd   = $contract->end_date   ? \Carbon\Carbon::parse($contract->end_date)   : null;
                                                $vDays  = $vEnd ? \Carbon\Carbon::now()->diffInDays($vEnd, false) : null;
                                                if ($vDays !== null) {
                                                    if ($vDays < 0)       { $vTxt = 'Expired '.abs($vDays).'d ago';  $vCls = 'text-danger small'; }
                                                    elseif ($vDays == 0)  { $vTxt = 'Expires today';                  $vCls = 'text-danger fw-bold small'; }
                                                    elseif ($vDays <= 30) { $vTxt = $vDays.' days left';               $vCls = 'text-warning fw-bold small'; }
                                                    elseif ($vDays <= 365){ $vTxt = round($vDays/30).' mo left';       $vCls = 'text-success small'; }
                                                    else { $vYr = floor($vDays/365); $vMo = round(($vDays%365)/30); $vTxt = $vYr.'y '.($vMo>0?$vMo.'m ':'').'left'; $vCls = 'text-success small'; }
                                                }
                                                $stColors = ['active'=>'success','expired'=>'danger','soonToExpire'=>'warning','draft'=>'secondary','in_progress'=>'info','renewed'=>'primary','terminated'=>'dark'];
                                                $stColor  = $stColors[$contract->status ?? ''] ?? 'secondary';
                                                $stLabel  = $contract->status === 'soonToExpire' ? 'Soon to Expire' : ucfirst(str_replace('_',' ',$contract->status ?? 'Draft'));
                                            @endphp
                                            <td>
                                                @if ($vStart || $vEnd)
                                                    <small class="text-muted d-block">{{ $vStart ? $vStart->format('d M Y') : '—' }} → {{ $vEnd ? $vEnd->format('d M Y') : '—' }}</small>
                                                @endif
                                                @if ($vDays !== null)
                                                    <span class="{{ $vCls }}">{{ $vTxt }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $stColor }}">{{ $stLabel }}</span>
                                                @if ($contract->parent_contract_id || $contract->renewal_status === 'renewed')
                                                    <br><span class="badge bg-secondary mt-1" style="font-size:0.7rem"><i class="fas fa-sync-alt me-1"></i>Renewed</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                        class="btn btn-sm btn-outline-secondary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @php
                                                        $user = auth()->user();
                                                        $canEdit =
                                                            $user &&
                                                            $user->hasAnyRole([
                                                                'procurement-officer',
                                                                'hr',
                                                                'super-admin',
                                                            ]) && !$user->hasPermissionTo('ceo_view_only');
                                                    @endphp
                                                    @if ($canEdit)
                                                        <a href="{{ route('procurements.contracts.edit', $contract->id) }}"
                                                            class="btn btn-sm btn-outline-secondary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endif
                                                    @if (auth()->user()->hasRole('super-admin') && !auth()->user()->hasPermissionTo('ceo_view_only'))
                                                        <form
                                                            action="{{ route('procurements.contracts.destroy', $contract->id) }}"
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('Are you sure you want to delete this contract? This action cannot be undone.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($activeContracts->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No active contracts found</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- Expiring --}}
                        <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'expiring' ? '' : 'd-none' }}"
                            id="table-expiring">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="display:inline-block;width:4px;height:1.2rem;background:#ffc107;border-radius:2px"></span>
                                    <h6 class="mb-0 fw-semibold text-dark">Expiring Soon <small class="text-muted fw-normal">(within 30 days)</small></h6>
                                    <span class="badge bg-warning text-dark">{{ $soonToExpireContracts->count() }}</span>
                                </div>
                                @php $isProcurementOfficer = auth()->user() && auth()->user()->hasRole('procurement-officer'); @endphp
                                @if ($isProcurementOfficer && $soonToExpireContracts->isNotEmpty())
                                    <form action="{{ route('procurements.contracts.send-bulk-reminders') }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Send renewal reminders to Line Managers for ALL {{ $soonToExpireContracts->count() }} expiring contracts?');">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="fas fa-bell me-1"></i> Send All Reminders ({{ $soonToExpireContracts->count() }})
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <table id="datatable-expiring" class="table table-hover table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Contract</th>
                                        <th>Department</th>
                                        <th>Validity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($soonToExpireContracts as $index => $contract)
                                        <tr data-start-date="{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '' }}"
                                            data-end-date="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '' }}"
                                            data-contract-url="{{ route('procurements.contracts.show', $contract->id) }}"
                                            style="cursor:pointer">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ Str::limit($contract->title, 50) }}
                                                @if ($contract->contract_number)
                                                    <br><small class="text-muted">#{{ $contract->contract_number }}</small>
                                                @endif
                                                @if ($contract->vendor)
                                                    <br><small class="text-muted">Vendor: {{ Str::limit($contract->vendor->name, 30) }}</small>
                                                @endif
                                                <br><small class="text-muted">{{ number_format($contract->cost ?? 0, 2) }} {{ $contract->currency ?? 'TZS' }}</small>
                                            </td>
                                            <td>
                                                @if ($contract->division)
                                                    <small class="text-muted d-block">{{ $contract->division->name }}</small>
                                                @endif
                                                @if ($contract->department)
                                                    {{ Str::limit($contract->department->dept_name, 25) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            @php
                                                $vStart = $contract->start_date ? \Carbon\Carbon::parse($contract->start_date) : null;
                                                $vEnd   = $contract->end_date   ? \Carbon\Carbon::parse($contract->end_date)   : null;
                                                $vDays  = $vEnd ? \Carbon\Carbon::now()->diffInDays($vEnd, false) : null;
                                                if ($vDays !== null) {
                                                    if ($vDays < 0)       { $vTxt = 'Expired '.abs($vDays).'d ago';  $vCls = 'text-danger small'; }
                                                    elseif ($vDays == 0)  { $vTxt = 'Expires today';                  $vCls = 'text-danger fw-bold small'; }
                                                    elseif ($vDays <= 30) { $vTxt = $vDays.' days left';               $vCls = 'text-warning fw-bold small'; }
                                                    elseif ($vDays <= 365){ $vTxt = round($vDays/30).' mo left';       $vCls = 'text-success small'; }
                                                    else { $vYr = floor($vDays/365); $vMo = round(($vDays%365)/30); $vTxt = $vYr.'y '.($vMo>0?$vMo.'m ':'').'left'; $vCls = 'text-success small'; }
                                                }
                                                $stColors = ['active'=>'success','expired'=>'danger','soonToExpire'=>'warning','draft'=>'secondary','in_progress'=>'info','renewed'=>'primary','terminated'=>'dark'];
                                                $stColor  = $stColors[$contract->status ?? ''] ?? 'secondary';
                                                $stLabel  = $contract->status === 'soonToExpire' ? 'Soon to Expire' : ucfirst(str_replace('_',' ',$contract->status ?? 'Draft'));
                                            @endphp
                                            <td>
                                                @if ($vStart || $vEnd)
                                                    <small class="text-muted d-block">{{ $vStart ? $vStart->format('d M Y') : '—' }} → {{ $vEnd ? $vEnd->format('d M Y') : '—' }}</small>
                                                @endif
                                                @if ($vDays !== null)
                                                    <span class="{{ $vCls }}">{{ $vTxt }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $stColor }}">{{ $stLabel }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                        class="btn btn-sm btn-outline-secondary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @php
                                                        $user = auth()->user();
                                                        // Only Procurement Officers, HR, and Super Admins can edit contracts
                                                        // Line Managers and HEC members are NOT allowed to edit
                                                        $canEdit =
                                                            $user &&
                                                            $user->hasAnyRole([
                                                                'procurement-officer',
                                                                'hr',
                                                                'super-admin',
                                                            ]) && !$user->hasPermissionTo('ceo_view_only');
                                                        // Check if user has procurement officer role - EXACT match only
                                                        $isProcurementOfficer =
                                                            $user && $user->hasRole('procurement-officer');
                                                        $isSuperAdmin = $user && $user->hasRole('super-admin');
                                                        // Check renew permission
                                                        $isLineManager = $user && $user->hasRole('line-manager');
                                                        $isDepartmentLineManager = false;
                                                        if ($isLineManager && $contract->department) {
                                                            $isDepartmentLineManager =
                                                                $user->deptId == $contract->department_id;
                                                        }
                                                        // Can renew if: (Line Manager of department OR Procurement Officer OR Super Admin) AND contract is expiring/expired AND not already pending/renewed
                                                        $canRenew =
                                                            ($isDepartmentLineManager || $isProcurementOfficer || $isSuperAdmin) &&
                                                            ($contract->status == 'expired' ||
                                                                ($contract->end_date &&
                                                                    \Carbon\Carbon::parse(
                                                                        $contract->end_date,
                                                                    )->diffInDays(\Carbon\Carbon::now(), false) <=
                                                                        30)) &&
                                                            $contract->renewal_status != 'pending' &&
                                                            $contract->renewal_status != 'renewed';
                                                    @endphp
                                                    @if ($canEdit)
                                                        <a href="{{ route('procurements.contracts.edit', $contract->id) }}"
                                                            class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit" style="color: #28a745;"></i>
                                                        </a>
                                                    @endif
                                                    @if ($canRenew)
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            title="Renew Contract" data-bs-toggle="modal"
                                                            data-bs-target="#renewContractModal{{ $contract->id }}">
                                                            <i class="fas fa-redo"></i> Renew
                                                        </button>
                                                    @endif
                                                    @if (($isProcurementOfficer || $isSuperAdmin) && $contract->department)
                                                        <button type="button" class="btn btn-sm btn-warning"
                                                            title="Send Reminder" data-bs-toggle="modal"
                                                            data-bs-target="#sendReminderModal{{ $contract->id }}">
                                                            <i class="fas fa-bell"></i> Send Reminder
                                                        </button>
                                                    @endif
                                                    @if (auth()->user()->hasRole('super-admin') && !auth()->user()->hasPermissionTo('ceo_view_only'))
                                                        <form
                                                            action="{{ route('procurements.contracts.destroy', $contract->id) }}"
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('Delete this contract?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($soonToExpireContracts->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No contracts expiring soon</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- Expired --}}
                        <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'expired' ? '' : 'd-none' }}"
                            id="table-expired">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="display:inline-block;width:4px;height:1.2rem;background:#dc3545;border-radius:2px"></span>
                                    <h6 class="mb-0 fw-semibold text-dark">Expired Contracts</h6>
                                    <span class="badge bg-danger">{{ $expiredContracts->count() }}</span>
                                </div>
                            </div>
                            <table id="datatable-expired" class="table table-hover table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Contract</th>
                                        <th>Department</th>
                                        <th>Validity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($expiredContracts as $index => $contract)
                                        <tr data-start-date="{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '' }}"
                                            data-end-date="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '' }}"
                                            data-contract-url="{{ route('procurements.contracts.show', $contract->id) }}"
                                            style="cursor:pointer">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ Str::limit($contract->title, 50) }}
                                                @if ($contract->contract_number)
                                                    <br><small class="text-muted">#{{ $contract->contract_number }}</small>
                                                @endif
                                                @if ($contract->vendor)
                                                    <br><small class="text-muted">Vendor: {{ Str::limit($contract->vendor->name, 30) }}</small>
                                                @endif
                                                <br><small class="text-muted">{{ number_format($contract->cost ?? 0, 2) }} {{ $contract->currency ?? 'TZS' }}</small>
                                            </td>
                                            <td>
                                                @if ($contract->division)
                                                    <small class="text-muted d-block">{{ $contract->division->name }}</small>
                                                @endif
                                                @if ($contract->department)
                                                    {{ Str::limit($contract->department->dept_name, 25) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($contract->end_date)
                                                    {{ \Carbon\Carbon::parse($contract->end_date)->format('d M Y') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ ucfirst($contract->status ?? 'Expired') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                        class="btn btn-sm btn-outline-secondary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @php
                                                        $user = auth()->user();
                                                        // Only Procurement Officers, HR, and Super Admins can edit contracts
                                                        // Line Managers and HEC members are NOT allowed to edit
                                                        $canEdit =
                                                            $user &&
                                                            $user->hasAnyRole([
                                                                'procurement-officer',
                                                                'hr',
                                                                'super-admin',
                                                            ]) && !$user->hasPermissionTo('ceo_view_only');
                                                        $isLineManager = $user && $user->hasRole('line-manager');
                                                        $isProcurementOfficer =
                                                            $user && $user->hasRole('procurement-officer');
                                                        $isSuperAdmin = $user && $user->hasRole('super-admin');
                                                        $isDepartmentLineManager = false;
                                                        if ($isLineManager && $contract->department) {
                                                            $isDepartmentLineManager =
                                                                $user->deptId == $contract->department_id;
                                                        }
                                                        $canRenew =
                                                            ($isDepartmentLineManager || $isProcurementOfficer || $isSuperAdmin) &&
                                                            $contract->status == 'expired' &&
                                                            $contract->renewal_status != 'pending' &&
                                                            $contract->renewal_status != 'renewed';
                                                    @endphp
                                                    @if ($canEdit)
                                                        <a href="{{ route('procurements.contracts.edit', $contract->id) }}"
                                                            class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit" style="color: #28a745;"></i>
                                                        </a>
                                                    @endif
                                                    @if ($canRenew)
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            title="Renew Contract" data-bs-toggle="modal"
                                                            data-bs-target="#renewContractModal{{ $contract->id }}">
                                                            <i class="fas fa-redo"></i> Renew
                                                        </button>
                                                    @endif
                                                    @if ($isProcurementOfficer || $isSuperAdmin)
                                                        <form
                                                            action="{{ route('procurements.contracts.file', $contract->id) }}"
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('File this expired contract for record-keeping? It will move to Archived Contracts.');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                                title="File contract (archive for record)">
                                                                <i class="fas fa-archive"></i> File
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if (auth()->user()->hasRole('super-admin') && !auth()->user()->hasPermissionTo('ceo_view_only'))
                                                        <form
                                                            action="{{ route('procurements.contracts.destroy', $contract->id) }}"
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('Delete this contract?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($expiredContracts->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No expired contracts found</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- Archived (Filed) Contracts --}}
                        <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'archived' ? '' : 'd-none' }}"
                            id="table-archived">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="display:inline-block;width:4px;height:1.2rem;background:#6c757d;border-radius:2px"></span>
                                    <h6 class="mb-0 fw-semibold text-dark">Archived (Filed) Contracts</h6>
                                    <span class="badge bg-secondary">{{ ($archivedContracts ?? collect())->count() }}</span>
                                </div>
                            </div>
                            <table id="datatable-archived" class="table table-hover table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Contract</th>
                                        <th>Department</th>
                                        <th>Validity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($archivedContracts ?? [] as $index => $contract)
                                        <tr data-start-date="{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '' }}"
                                            data-end-date="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '' }}"
                                            data-contract-url="{{ route('procurements.contracts.show', $contract->id) }}"
                                            style="cursor:pointer">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ Str::limit($contract->title, 50) }}
                                                @if ($contract->contract_number)
                                                    <br><small class="text-muted">#{{ $contract->contract_number }}</small>
                                                @endif
                                                <br><small class="text-muted">{{ number_format($contract->cost ?? 0, 2) }} {{ $contract->currency ?? 'TZS' }}</small>
                                            </td>
                                            <td>
                                                @if ($contract->division)
                                                    <small class="text-muted d-block">{{ $contract->division->name }}</small>
                                                @endif
                                                @if ($contract->department)
                                                    {{ Str::limit($contract->department->dept_name, 25) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            @php
                                                $vStart = $contract->start_date ? \Carbon\Carbon::parse($contract->start_date) : null;
                                                $vEnd   = $contract->end_date   ? \Carbon\Carbon::parse($contract->end_date)   : null;
                                            @endphp
                                            <td>
                                                @if ($vStart || $vEnd)
                                                    <small class="text-muted d-block">{{ $vStart ? $vStart->format('d M Y') : '—' }} → {{ $vEnd ? $vEnd->format('d M Y') : '—' }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><i class="fas fa-archive me-1"></i>Filed</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                    class="btn btn-sm btn-outline-secondary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if (($archivedContracts ?? collect())->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No archived (filed) contracts.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Renew Contract Modals -->
    @foreach ($expiredContracts->merge($soonToExpireContracts)->unique('id') as $contract)
        @php
            $user = auth()->user();
            $isLineManager = $user->hasRole('line-manager');
            $isProcurementOfficer = $user->hasRole('procurement-officer');
            $isSuperAdmin = $user->hasRole('super-admin');
            $isDepartmentLineManager = false;
            if ($isLineManager && $contract->department) {
                $isDepartmentLineManager = $user->deptId == $contract->department_id;
            }
            // Can renew if: (Line Manager of department OR Procurement Officer OR Super Admin) AND contract is expiring/expired AND not already pending/renewed
            $canRenew =
                ($isDepartmentLineManager || $isProcurementOfficer || $isSuperAdmin) &&
                ($contract->status == 'expired' ||
                    ($contract->end_date &&
                        \Carbon\Carbon::parse($contract->end_date)->diffInDays(\Carbon\Carbon::now(), false) <= 30)) &&
                $contract->renewal_status != 'pending' &&
                $contract->renewal_status != 'renewed';
        @endphp
        @if ($canRenew)
            <div class="modal fade" id="renewContractModal{{ $contract->id }}" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title">
                                <i class="fas fa-redo me-2"></i>Renew Contract
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="renewContractForm{{ $contract->id }}"
                            action="{{ route('procurements.contracts.renewal.initiate', $contract->id) }}"
                            method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-4 p-3 border rounded">
                                    <div class="mb-2">
                                        <strong>Contract:</strong> {{ $contract->title }}
                                    </div>
                                    @if ($contract->vendor)
                                        <div>
                                            <strong>Vendor:</strong> {{ $contract->vendor->name }}
                                        </div>
                                    @endif
                                </div>

                                @if ($isDepartmentLineManager)
                                    <div class="mb-4">
                                        <label class="form-label">
                                            Contract Rating <span class="text-danger">*</span>
                                        </label>
                                        <div class="star-rating mb-2"
                                            data-rating-id="contract_rating{{ $contract->id }}">
                                            <input type="hidden" id="contract_rating{{ $contract->id }}"
                                                name="contract_rating" value="" required>
                                            <div class="stars">
                                                <span class="star" data-value="1" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Poor">
                                                    <i class="far fa-star"></i>
                                                </span>
                                                <span class="star" data-value="2" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Adequate">
                                                    <i class="far fa-star"></i>
                                                </span>
                                                <span class="star" data-value="3" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Good">
                                                    <i class="far fa-star"></i>
                                                </span>
                                                <span class="star" data-value="4" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Very Good">
                                                    <i class="far fa-star"></i>
                                                </span>
                                                <span class="star" data-value="5" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Excellent">
                                                    <i class="far fa-star"></i>
                                                </span>
                                            </div>
                                            <div class="rating-text mt-2">
                                                <small class="text-muted">1 Poor • 2 Adequate • 3 Good • 4 Very Good • 5 Excellent</small>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block">Rate the performance of this specific contract.
                                            Note: One vendor may have multiple contracts, each rated separately.</small>
                                    </div>
                                @else
                                    <div class="mb-4 p-3 border rounded">
                                        <small class="text-muted">You are initiating the renewal process.
                                            The Line Manager will review and rate this contract (1-5), then it will go to HEC for
                                            review and rating, then to Procurement Officer for finalization.</small>
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <label for="renewal_comments{{ $contract->id }}" class="form-label">
                                        Additional Comments (Optional)
                                    </label>
                                    <textarea class="form-control" id="renewal_comments{{ $contract->id }}" name="renewal_comments" rows="3"
                                        placeholder="Any additional comments about the renewal..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer border-top">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" id="submitRenewalBtn{{ $contract->id }}"
                                    class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> <span
                                        id="submitRenewalBtnText{{ $contract->id }}">Submit Renewal Request</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <!-- Send Reminder Modals for Expiring Contracts -->
    @foreach ($soonToExpireContracts as $contract)
        @php
            $user = auth()->user();
            // Check if user has procurement officer role - EXACT match only
            $isProcurementOfficer = $user && $user->hasRole('procurement-officer');
            $daysUntilExpiry = $contract->end_date
                ? \Carbon\Carbon::parse($contract->end_date)->diffInDays(\Carbon\Carbon::now(), false)
                : null;
        @endphp
        @if ($isProcurementOfficer && $contract->department)
        @php
            $daysLeft = $contract->end_date ? (int) \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($contract->end_date), false) : null;
            $urgencyColor = $daysLeft !== null ? ($daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'success')) : 'secondary';

            // Resolve HEC member(s) for this department
            $hecMembersForModal = collect();
            if ($contract->department->hec_id) {
                $hecObj = \App\Models\Hec::find($contract->department->hec_id);
                if ($hecObj) {
                    $hecRoleMap = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CCDRO' => 'ccdro'];
                    $hecRoleSlug = $hecRoleMap[strtoupper(trim($hecObj->hec_level_name))] ?? 'cms';
                    $hecMembersForModal = \App\Models\User::role($hecRoleSlug)->where('status', 'active')->get();
                }
            }
        @endphp
            <div class="modal fade" id="sendReminderModal{{ $contract->id }}" tabindex="-1"
                aria-labelledby="sendReminderModalLabel{{ $contract->id }}" aria-hidden="true"
                data-contract-id="{{ $contract->id }}" data-department-id="{{ $contract->department_id }}"
                data-department-name="{{ $contract->department->dept_name ?? 'N/A' }}">
                <div class="modal-dialog modal-dialog-centered modal-md">
                    <div class="modal-content border-0 shadow">

                        {{-- Header --}}
                        <div class="modal-header border-0 pb-0 px-4 pt-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="bg-light border rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                                    <i class="fas fa-bell text-muted"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title mb-0 fw-semibold" id="sendReminderModalLabel{{ $contract->id }}">Send Renewal Reminder</h5>
                                    <small class="text-muted">Notify relevant staff about contract expiry</small>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form id="sendReminderForm{{ $contract->id }}"
                            action="{{ route('procurements.contracts.send-reminder', $contract->id) }}" method="POST">
                            @csrf
                            <div class="modal-body px-4 py-3">

                                {{-- Feedback area --}}
                                <div id="reminderMessage{{ $contract->id }}" class="alert d-none mb-3" role="alert">
                                    <span id="reminderMessageText{{ $contract->id }}"></span>
                                </div>

                                {{-- Contract info card --}}
                                <div class="rounded-3 border p-3 mb-3 bg-light">
                                    <div class="fw-semibold text-dark mb-1" style="font-size:.9rem;">
                                        <i class="fas fa-file-contract text-primary me-1"></i>
                                        {{ $contract->title }}
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 mt-2" style="font-size:.82rem;">
                                        @if ($contract->vendor)
                                        <span class="text-muted">
                                            <i class="fas fa-building me-1"></i>{{ $contract->vendor->name }}
                                        </span>
                                        @endif
                                        @if ($contract->end_date)
                                        <span class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>Expires {{ \Carbon\Carbon::parse($contract->end_date)->format('d M Y') }}
                                        </span>
                                        @endif
                                        @if ($daysLeft !== null)
                                        <span class="badge bg-light text-muted border fw-semibold px-2 py-1" style="font-size:.78rem;">
                                            <i class="fas fa-clock me-1"></i>{{ $daysLeft > 0 ? $daysLeft.' days left' : 'Expired' }}
                                        </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Recipients --}}
                                <p class="text-muted small fw-semibold mb-2 text-uppercase" style="letter-spacing:.04em;">Recipients</p>

                                {{-- Line Manager (always checked) --}}
                                <div class="border rounded-3 p-3 mb-2 d-flex align-items-start gap-3">
                                    <input type="hidden" name="send_to_line_manager" value="1">
                                    <input class="form-check-input mt-1 flex-shrink-0" type="checkbox"
                                        id="sendToLM{{ $contract->id }}" value="1" checked disabled>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold" style="font-size:.88rem;">
                                            <i class="fas fa-user-tie text-primary me-1"></i>Line Manager
                                            <span class="badge bg-success ms-1" style="font-size:.65rem;">Required</span>
                                        </div>
                                        <div id="lineManagerInfo{{ $contract->id }}" class="mt-1">
                                            <small class="text-muted">Loading...</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- HEC Member (optional) --}}
                                <div class="border rounded-3 p-3 mb-3 d-flex align-items-start gap-3">
                                    <input class="form-check-input mt-1 flex-shrink-0" type="checkbox"
                                        name="send_to_hec" id="sendToHEC{{ $contract->id }}" value="1">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold" style="font-size:.88rem;">
                                            <i class="fas fa-users text-info me-1"></i>HEC Member
                                            <span class="badge bg-secondary ms-1" style="font-size:.65rem;">Optional</span>
                                        </div>
                                        @if ($hecMembersForModal->isNotEmpty())
                                            @foreach ($hecMembersForModal as $hecM)
                                            <div class="mt-1 p-2 bg-light rounded" style="font-size:.82rem;">
                                                <span class="text-dark">{{ trim(($hecM->fname ?? '') . ' ' . ($hecM->lname ?? '')) ?: $hecM->name }}</span>
                                                <span class="text-muted ms-1">&lt;{{ $hecM->email }}&gt;</span>
                                            </div>
                                            @endforeach
                                        @else
                                            <small class="text-muted">No HEC member found for {{ $contract->department->dept_name ?? 'this department' }}</small>
                                        @endif
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-2 bg-light rounded-3 p-3" style="font-size:.82rem;color:#555;">
                                    <i class="fas fa-info-circle text-muted mt-1 flex-shrink-0"></i>
                                    <span>A reminder email will be sent to the selected recipients regarding this contract's upcoming expiry.</span>
                                </div>
                            </div>

                            <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                                <button type="submit" id="sendReminderBtn{{ $contract->id }}" class="btn btn-success">
                                    <i class="fas fa-paper-plane me-1"></i>
                                    <span id="sendReminderBtnText{{ $contract->id }}">Send Reminder</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach


    @push('styles')
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
        <style>
            /* Single dropdown arrow for length select */
            .dataTables_wrapper .dataTables_length select {
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                appearance: none !important;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
                background-repeat: no-repeat !important;
                background-position: right 0.5rem center !important;
                background-size: 16px 12px !important;
                padding-right: 2rem !important;
            }
            /* Green accent for card header icons */
            .card-header .fas,
            .card-header .fa {
                color: #198754;
            }

            .card-hover {
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .card-hover:hover {
                transform: translateY(-3px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
                border-color: #6c757d !important;
            }

            .stat-card {
                height: 100%;
                transition: all 0.3s ease;
            }

            .stat-card .stat-icon {
                opacity: 0.6;
            }

            .stat-card:hover .stat-icon {
                opacity: 1;
            }


            /* Filter section styling */
            #filterForm .form-label {
                font-weight: 600;
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }

            #filterForm .form-select,
            #filterForm .form-control {
                border: 1px solid #ced4da;
                transition: all 0.3s ease;
            }

            #filterForm .form-select:focus,
            #filterForm .form-control:focus {
                border-color: #0d6efd;
                box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
            }

            #filterResults {
                margin-top: 1rem;
                padding: 0.75rem 1rem;
                border-radius: 0.375rem;
            }

            /* Icon-only filter buttons */
            #filterForm .btn i {
                font-size: 1rem;
            }

            #filterForm .btn {
                min-width: 38px;
                padding: 0.375rem 0.75rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            #filterForm .btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            }

            /* Responsive filter buttons */
            @media (max-width: 768px) {
                #filterForm .col-md-2 {
                    margin-bottom: 1rem;
                }

                #filterForm .d-flex {
                    flex-direction: column;
                    width: 100%;
                }

                #filterForm .d-flex .btn {
                    width: 100%;
                    margin-bottom: 0.5rem;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script>
            // Suppress DataTables warnings
            (function() {
                const originalWarn = console.warn;
                console.warn = function(...args) {
                    // Suppress DataTables reinitialization warnings
                    if (args.length > 0 && typeof args[0] === 'string' &&
                        (args[0].includes('Cannot reinitialise DataTable') ||
                            args[0].includes('DataTables warning'))) {
                        return; // Suppress this warning
                    }
                    // Allow other warnings
                    originalWarn.apply(console, args);
                };
            })();

            // Store DataTable instances - declare early so all functions can access it
            const dataTableInstances = {};

            // Destroy all DataTable instances
            function destroyAllDataTables() {
                if (typeof $.fn.DataTable === 'undefined') return;

                Object.keys(dataTableInstances).forEach(tableId => {
                    try {
                        const tableEl = document.getElementById(tableId);
                        if (tableEl && $.fn.DataTable.isDataTable(tableEl)) {
                            $(tableEl).DataTable().destroy(true);
                            tableEl.removeAttribute('data-dt-initialized');
                        }
                        delete dataTableInstances[tableId];
                    } catch (e) { /* ignore */ }
                });

                document.querySelectorAll('table[id^="datatable-"]').forEach(tableEl => {
                    try {
                        if ($.fn.DataTable.isDataTable(tableEl)) {
                            $(tableEl).DataTable().destroy(true);
                            tableEl.removeAttribute('data-dt-initialized');
                        }
                    } catch (e) { /* ignore */ }
                });

                Object.keys(dataTableInstances).forEach(key => delete dataTableInstances[key]);
            }

            // Initialize DataTables for a specific table
            function initDataTable(tableId) {
                const tableEl = document.getElementById(tableId);
                if (!tableEl) return;

                // Check if table is visible
                const tableSection = tableEl.closest('.table-section');
                if (tableSection && tableSection.classList.contains('d-none')) {
                    return; // Don't initialize hidden tables
                }

                // Check if already initialized
                if (typeof $.fn.DataTable !== 'undefined') {
                    if ($.fn.DataTable.isDataTable(tableEl)) {
                        return; // Already initialized
                    }

                    try {
                        // Remove colspan rows before init (they cause DT errors)
                        const tbody = tableEl.querySelector('tbody');
                        if (tbody) {
                            tbody.querySelectorAll('tr td[colspan]').forEach(cell => {
                                const row = cell.closest('tr');
                                if (row) row.remove();
                            });
                        }

                        const columnCount = tableEl.querySelectorAll('thead tr th').length;
                        const actionsColumnIndex = columnCount - 1;

                        const dt = $(tableEl).DataTable({
                            pageLength: 25,
                            lengthMenu: [
                                [10, 25, 50, 100, -1],
                                [10, 25, 50, 100, "All"]
                            ],
                            order: [
                                [0, 'asc']
                            ],
                            columnDefs: [{
                                    orderable: false,
                                    targets: actionsColumnIndex
                                } // Actions column (last column)
                            ],
                            language: {
                                emptyTable: "No contracts found in this category",
                                search: "Search:",
                                lengthMenu: "Show _MENU_ entries",
                                info: "Showing _START_ to _END_ of _TOTAL_ contracts",
                                infoEmpty: "No contracts available",
                                infoFiltered: "(filtered from _MAX_ total contracts)"
                            },
                            autoWidth: false,
                            responsive: true,
                            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                            destroy: false // Don't allow destroy option to prevent conflicts
                        });

                        dataTableInstances[tableId] = dt;
                        tableEl.dataset.dtInitialized = '1';
                    } catch (error) {
                        // Suppress console errors for DataTables reinitialization
                        if (error.message && error.message.includes('reinitialise')) {
                            // Silently handle reinitialization errors
                            return;
                        }
                        console.error('Error initializing DataTable:', error);
                    }
                }
            }

            function showTable(type, element) {
                // Destroy all existing DataTables first to prevent reinitialization errors
                destroyAllDataTables();

                // Hide all table sections
                document.querySelectorAll('.table-section').forEach(el => el.classList.add('d-none'));

                const targetTable = document.getElementById('table-' + type);
                if (targetTable) {
                    targetTable.classList.remove('d-none');

                    // Wait to ensure all DataTables are fully destroyed and DOM is ready
                    setTimeout(() => {
                        const tableEl = document.getElementById('datatable-' + type);
                        if (!tableEl) return;

                        // Final cleanup of the target table
                        try {
                            if (typeof DataTable !== 'undefined') {
                                const existingTable = DataTable.get(tableEl);
                                if (existingTable) {
                                    existingTable.destroy(true);
                                }
                            }
                        } catch (e) {
                            // Ignore - table doesn't exist or already destroyed
                        }

                        // Clean up any DataTable artifacts
                        tableEl.removeAttribute('data-dt-initialized');
                        tableEl.classList.remove('dataTable');

                        // Remove any DataTable wrapper if it exists
                        const wrapper = tableEl.closest('.dataTables_wrapper');
                        if (wrapper && wrapper.parentNode) {
                            const parent = wrapper.parentNode;
                            parent.insertBefore(tableEl, wrapper);
                            wrapper.remove();
                        }

                        // Small delay before initializing to ensure cleanup is complete
                        setTimeout(() => {
                            // Now initialize the new DataTable
                            initDataTable('datatable-' + type);

                            // Re-apply filters if any are set
                            setTimeout(() => {
                                const hasFilters = document.getElementById('filterStatus').value ||
                                    document.getElementById('filterYear').value ||
                                    document.getElementById('filterMonth').value ||
                                    document.getElementById('filterDay').value;
                                if (hasFilters) {
                                    applyFilters();
                                }
                            }, 100);
                        }, 100);
                    }, 400);
                }

                document.querySelectorAll('.card-hover').forEach(el => el.style.backgroundColor = '');
                element.style.backgroundColor = '#d4edda';
            }

            // Filter functionality with DataTables integration
            function applyFilters() {
                const status = document.getElementById('filterStatus').value.toLowerCase();
                const dateType = document.getElementById('filterDateType').value;
                const year = document.getElementById('filterYear').value;
                const month = document.getElementById('filterMonth').value;
                const day = document.getElementById('filterDay').value;

                // Determine which table is visible
                const visibleTableSection = document.querySelector('.table-section:not(.d-none)');
                if (!visibleTableSection) return;

                const tableId = visibleTableSection.querySelector('table')?.id;
                if (!tableId) return;

                // Get DataTable instance if it exists
                const tableEl = document.getElementById(tableId);
                if (!tableEl) return;

                let dataTable = null;
                try {
                    if (typeof DataTable !== 'undefined') {
                        dataTable = DataTable.get(tableEl);
                    }
                } catch (e) {
                    // DataTable not initialized yet - this is expected
                }

                // Get all rows (excluding header and empty message rows)
                const tbody = tableEl.querySelector('tbody');
                if (!tbody) return;

                const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => {
                    // Skip empty message rows
                    return !row.querySelector('td[colspan]');
                });

                rows.forEach(row => {
                    let show = true;

                    // Filter by status (column index 4: #, Contract, Dept, EndDate, Status)
                    if (status) {
                        const statusCell = row.cells[4];
                        if (statusCell) {
                            const statusText = statusCell.innerText.toLowerCase().trim();
                            if (!statusText.includes(status)) {
                                show = false;
                            }
                        }
                    }

                    // Filter by date based on selected date type
                    if (show && (year || month || day)) {
                        let dateText = '';

                        // Determine which date to check
                        if (dateType === 'start_date') {
                            dateText = row.getAttribute('data-start-date') || '';
                        } else if (dateType === 'end_date') {
                            dateText = row.getAttribute('data-end-date') || '';
                        } else if (dateType === 'created_at') {
                            // Get from table cell (column index 4)
                            const dateCell = row.cells[4];
                            if (dateCell) {
                                dateText = dateCell.innerText.trim();
                            }
                        }

                        if (dateText && dateText !== '-' && !dateText.includes('No ')) {
                            // Parse date (format: YYYY-MM-DD or YYYY-MM-DD HH:MM)
                            const dateMatch = dateText.match(/(\d{4})-(\d{1,2})-(\d{1,2})/);
                            if (dateMatch) {
                                const dateYear = dateMatch[1];
                                const dateMonth = String(dateMatch[2]).padStart(2, '0');
                                const dateDay = String(dateMatch[3]).padStart(2, '0');
                                const filterMonth = month ? String(month).padStart(2, '0') : '';
                                const filterDay = day ? String(day).padStart(2, '0') : '';

                                if (year && dateYear !== year) show = false;
                                if (filterMonth && dateMonth !== filterMonth) show = false;
                                if (filterDay && dateDay !== filterDay) show = false;
                            } else {
                                // Try to parse as Date object as fallback
                                try {
                                    const parsedDate = new Date(dateText);
                                    if (!isNaN(parsedDate.getTime())) {
                                        const dateYear = String(parsedDate.getFullYear());
                                        const dateMonth = String(parsedDate.getMonth() + 1).padStart(2, '0');
                                        const dateDay = String(parsedDate.getDate()).padStart(2, '0');
                                        const filterMonth = month ? String(month).padStart(2, '0') : '';
                                        const filterDay = day ? String(day).padStart(2, '0') : '';

                                        if (year && dateYear !== year) show = false;
                                        if (filterMonth && dateMonth !== filterMonth) show = false;
                                        if (filterDay && dateDay !== filterDay) show = false;
                                    } else {
                                        show = false;
                                    }
                                } catch (e) {
                                    show = false;
                                }
                            }
                        } else {
                            // If date is empty or placeholder, hide the row when filtering
                            if (dateType === 'created_at') {
                                show = false;
                            }
                            // For start_date and end_date, if empty, we might want to show it
                            // depending on requirements - for now, we'll hide it
                        }
                    }

                    // Apply filter
                    row.style.display = show ? '' : 'none';
                });

                // If DataTable is initialized, trigger search to update pagination
                if (dataTable) {
                    dataTable.draw();
                }

                // Show filter results count
                const visibleRows = rows.filter(row => row.style.display !== 'none');
                showFilterResults(visibleRows.length, rows.length);
            }

            // Clear all filters
            function clearFilters() {
                document.getElementById('filterStatus').value = '';
                document.getElementById('filterDateType').value = 'start_date';
                document.getElementById('filterYear').value = '';
                document.getElementById('filterMonth').value = '';
                document.getElementById('filterDay').value = '';

                // Show all rows
                const visibleTableSection = document.querySelector('.table-section:not(.d-none)');
                if (visibleTableSection) {
                    const tbody = visibleTableSection.querySelector('tbody');
                    if (tbody) {
                        const rows = tbody.querySelectorAll('tr');
                        rows.forEach(row => {
                            // Only show data rows, not empty message rows
                            if (!row.querySelector('td[colspan]')) {
                                row.style.display = '';
                            }
                        });
                    }
                }

                // Reset DataTable search if initialized
                const visibleTable = document.querySelector('.table-section:not(.d-none) table');
                if (visibleTable) {
                    try {
                        if (typeof DataTable !== 'undefined') {
                            const dataTable = DataTable.get(visibleTable);
                            if (dataTable) {
                                dataTable.search('').draw();
                            }
                        }
                    } catch (e) {
                        // DataTable not initialized - this is expected
                    }
                }

                // Hide filter results
                hideFilterResults();
            }

            // Show filter results count
            function showFilterResults(visible, total) {
                let resultsDiv = document.getElementById('filterResults');
                if (!resultsDiv) {
                    resultsDiv = document.createElement('div');
                    resultsDiv.id = 'filterResults';
                    resultsDiv.className = 'alert alert-info mt-2';
                    const filterForm = document.getElementById('filterForm');
                    filterForm.parentElement.appendChild(resultsDiv);
                }
                resultsDiv.innerHTML =
                    `<i class="fas fa-info-circle me-2"></i>Showing <strong>${visible}</strong> of <strong>${total}</strong> contracts`;
                resultsDiv.style.display = 'block';
            }

            // Hide filter results
            function hideFilterResults() {
                const resultsDiv = document.getElementById('filterResults');
                if (resultsDiv) {
                    resultsDiv.style.display = 'none';
                }
            }

            // Export visible table as CSV with detailed contract and vendor information
            function exportTable() {
                const table = document.querySelector('.table-section:not(.d-none) table');
                if (!table) return;

                // Get the current view type
                const viewType = '{{ $viewType ?? 'active' }}';

                // Build export URL with view type
                const exportUrl = `{{ route('procurements.contracts.export') }}?view=${viewType}`;

                // Create a temporary form to submit
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = exportUrl;
                form.target = '_blank';
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }


            document.addEventListener("DOMContentLoaded", function() {
                // Clickable rows - navigate to contract detail on row click (skip last/actions cell)
                document.addEventListener('click', function(e) {
                    const td = e.target.closest('td');
                    if (!td) return;
                    const tr = td.closest('tr[data-contract-url]');
                    if (!tr) return;
                    // Skip if click was on action buttons/links/forms
                    if (e.target.closest('a, button, form')) return;
                    window.location = tr.dataset.contractUrl;
                });

                // Show pending review table by default if user has pending reviews
                @php
                    $isLineManager = auth()->user() && auth()->user()->hasRole('line-manager');
                    $isHecMember =
                        auth()->user() &&
                        auth()
                            ->user()
                            ->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro']);
                    $isProcurementOfficer = auth()->user() && auth()->user()->hasRole('procurement-officer');
                @endphp
                // Initialize DataTable for the visible table based on viewType
                @php
                    $currentViewType = $viewType ?? 'active';
                @endphp

                // Map viewType to table ID
                const viewTypeToTableId = {
                    'my_pending': 'datatable-my_pending',
                    'active': 'datatable-active',
                    'expiring': 'datatable-expiring',
                    'expired': 'datatable-expired',
                    'archived': 'datatable-archived',
                    'procurement': 'datatable-procurement'
                };

                const tableId = viewTypeToTableId['{{ $currentViewType }}'] || 'datatable-active';

                // Initialize DataTable for the current view
                setTimeout(() => {
                    initDataTable(tableId);
                }, 300);

                // Debug: Check if modals exist
                const reminderButtons = document.querySelectorAll('[data-bs-target^="#sendReminderModal"]');
                const reminderModals = document.querySelectorAll('[id^="sendReminderModal"]');
                console.log('Reminder buttons found:', reminderButtons.length);
                console.log('Reminder modals found:', reminderModals.length);

                // Ensure Bootstrap modals are initialized
                reminderModals.forEach(function(modal) {
                    // Bootstrap 5 automatically handles modals with data-bs-toggle, but we can verify
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        // Modal will be initialized automatically by Bootstrap when button is clicked
                    }
                });

                // Use event delegation for form submissions (works even if forms are added dynamically)
                document.addEventListener('submit', function(e) {
                    const form = e.target;
                    if (form && form.id && form.id.startsWith('sendReminderForm')) {
                        e.preventDefault();
                        const contractId = form.id.replace('sendReminderForm', '');
                        handleReminderSubmit(contractId, form);
                    }
                });

                // Load Line Manager info when reminder modals are opened
                document.querySelectorAll('[id^="sendReminderModal"]').forEach(function(modal) {
                    modal.addEventListener('show.bs.modal', function(event) {
                        const contractId = this.dataset.contractId;
                        const departmentId = this.dataset.departmentId;
                        const infoDiv = document.getElementById('lineManagerInfo' + contractId);

                        // Reset form state when modal opens
                        const messageDiv = document.getElementById('reminderMessage' + contractId);
                        if (messageDiv) {
                            messageDiv.classList.add('d-none');
                            messageDiv.classList.remove('alert-success', 'alert-danger');
                        }
                        const btn = document.getElementById('sendReminderBtn' + contractId);
                        if (btn) {
                            btn.disabled = false;
                            const btnText = document.getElementById('sendReminderBtnText' + contractId);
                            if (btnText) btnText.textContent = 'Send Reminder';
                        }

                        if (infoDiv && departmentId) {
                            loadLineManagerInfo(contractId, departmentId);
                        } else if (infoDiv) {
                            infoDiv.innerHTML =
                                '<small class="text-danger">No department assigned</small>';
                        }
                    });
                });
            });

            function handleReminderSubmit(contractId, form) {
                const messageDiv = document.getElementById('reminderMessage' + contractId);
                const messageText = document.getElementById('reminderMessageText' + contractId);
                const btn = document.getElementById('sendReminderBtn' + contractId);
                const btnText = document.getElementById('sendReminderBtnText' + contractId);
                const formData = new FormData(form);
                const url = form.action;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                // Show loading state
                btn.disabled = true;
                if (btnText) btnText.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';

                // Hide previous messages
                if (messageDiv) {
                    messageDiv.classList.add('d-none');
                    messageDiv.classList.remove('alert-success', 'alert-danger');
                }

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => {
                        // Check if response is JSON
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json().then(data => {
                                // If status is not ok, treat as error
                                if (!response.ok) {
                                    return {
                                        success: false,
                                        message: data.message || 'An error occurred'
                                    };
                                }
                                return data;
                            });
                        }
                        // If not JSON, it might be a redirect or HTML response
                        if (response.redirected || !response.ok) {
                            return {
                                success: false,
                                message: 'Request failed. Please try again.'
                            };
                        }
                        return response.text().then(text => {
                            return {
                                success: false,
                                message: text || 'An error occurred'
                            };
                        });
                    })
                    .then(data => {
                        // Show success message
                        if (messageDiv && messageText) {
                            messageDiv.classList.remove('d-none');
                            if (data.success !== false) {
                                messageDiv.classList.add('alert-success');
                                messageDiv.classList.remove('alert-danger');
                                messageText.textContent = data.message || 'Successfully sent email to Line Manager' + (data
                                    .recipients ? ': ' + data.recipients : '');
                            } else {
                                messageDiv.classList.add('alert-danger');
                                messageDiv.classList.remove('alert-success');
                                messageText.textContent = data.message || 'Failed to send email. Please try again.';
                            }
                        }

                        // Reset button
                        btn.disabled = false;
                        if (btnText) btnText.textContent = 'Send Reminder';

                        // Auto-close modal after 2 seconds if successful
                        if (data.success !== false && messageDiv) {
                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(document.getElementById(
                                    'sendReminderModal' + contractId));
                                if (modal) {
                                    modal.hide();
                                }
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (messageDiv && messageText) {
                            messageDiv.classList.remove('d-none');
                            messageDiv.classList.add('alert-danger');
                            messageDiv.classList.remove('alert-success');
                            messageText.textContent = 'Failed to send email: ' + (error.message || 'Network error');
                        }
                        btn.disabled = false;
                        if (btnText) btnText.textContent = 'Send Reminder';
                    });
            }

            function loadLineManagerInfo(contractId, departmentId) {
                const infoDiv = document.getElementById('lineManagerInfo' + contractId);
                if (!infoDiv || !departmentId) {
                    if (infoDiv) {
                        infoDiv.innerHTML = '<small class="text-danger">No department assigned</small>';
                    }
                    return;
                }

                infoDiv.innerHTML = '<small class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</small>';

                const url = `{{ url('/procurements/contracts/departments') }}/${departmentId}/line-manager`;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.line_manager) {
                            const lm = data.line_manager;
                            const departmentName = document.querySelector(`[data-contract-id="${contractId}"]`)?.dataset
                                .departmentName || 'N/A';
                            infoDiv.innerHTML = `
                    <div class="mt-1">
                        <strong>Name:</strong> ${lm.name || 'N/A'}<br>
                        <strong>Email:</strong> <a href="mailto:${lm.email || ''}">${lm.email || 'No email'}</a><br>
                        <small class="text-muted">Department: ${departmentName}</small>
                    </div>
                `;
                        } else {
                            infoDiv.innerHTML =
                                `<small class="text-danger">${data.message || 'No Line Manager found for this department'}</small>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading Line Manager:', error);
                        infoDiv.innerHTML =
                            '<small class="text-danger">Error loading Line Manager info. Please try again.</small>';
                    });
            }

            // Star Rating Functionality
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize star ratings when modals are shown
                document.querySelectorAll('[id^="renewContractModal"]').forEach(function(modal) {
                    modal.addEventListener('show.bs.modal', function() {
                        const ratingContainer = this.querySelector('.star-rating');
                        if (ratingContainer) {
                            initStarRating(ratingContainer);
                        }

                        // Reset form when modal opens
                        const form = this.querySelector('form');
                        if (form) {
                            form.reset();
                            const hiddenInput = form.querySelector(
                                'input[type="hidden"][name="contract_rating"]');
                            if (hiddenInput) {
                                hiddenInput.value = '';
                                hiddenInput.removeAttribute('required'); // Remove required temporarily
                            }
                            const ratingText = ratingContainer?.querySelector('.rating-text');
                            if (ratingText) {
                                ratingText.innerHTML =
                                    '<small class="text-muted">Click on a star to rate</small>';
                            }
                            // Reset stars
                            const stars = ratingContainer?.querySelectorAll('.star');
                            if (stars) {
                                stars.forEach(function(star) {
                                    star.innerHTML = '<i class="far fa-star"></i>';
                                    star.style.color = '#6c757d';
                                });
                            }
                        }
                    });

                    // Handle form submission
                    const form = modal.querySelector('form');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            const formId = this.id;
                            const contractId = formId ? formId.replace('renewContractForm', '') : '';
                            const hiddenInput = this.querySelector(
                                'input[type="hidden"][name="contract_rating"]');
                            const submitBtn = document.getElementById('submitRenewalBtn' +
                                contractId) || this.querySelector('button[type="submit"]');
                            const submitBtnText = document.getElementById('submitRenewalBtnText' +
                                contractId);

                            console.log('Form submit triggered', {
                                formId: formId,
                                contractId: contractId,
                                ratingValue: hiddenInput?.value,
                                hasRequired: hiddenInput?.hasAttribute('required')
                            });

                            // Check if rating is required (Line Manager) and not set
                            if (hiddenInput && hiddenInput.hasAttribute('required') && !hiddenInput
                                .value) {
                                e.preventDefault();
                                e.stopPropagation();
                                alert('Please select a contract rating before submitting.');
                                return false;
                            }

                            // Show loading state
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                if (submitBtnText) {
                                    submitBtnText.innerHTML =
                                        '<i class="fas fa-spinner fa-spin me-1"></i> Submitting...';
                                } else {
                                    const btnText = submitBtn.querySelector('span');
                                    if (btnText) {
                                        btnText.innerHTML =
                                            '<i class="fas fa-spinner fa-spin me-1"></i> Submitting...';
                                    }
                                }
                            }

                            // Allow form to submit normally
                            console.log('Form submitting...');
                            return true;
                        });
                    }
                });

                function initStarRating(container) {
                    const stars = container.querySelectorAll('.star');
                    const hiddenInput = container.querySelector('input[type="hidden"]');
                    const ratingText = container.querySelector('.rating-text');
                    const ratingLabels = {
                        1: 'Poor',
                        2: 'Adequate',
                        3: 'Good',
                        4: 'Excellent'
                    };

                    // Initialize Bootstrap tooltips
                    stars.forEach(function(star) {
                        new bootstrap.Tooltip(star);
                    });

                    stars.forEach(function(star) {
                        star.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const value = parseInt(this.dataset.value);
                            hiddenInput.value = value;
                            updateStars(stars, value);
                            if (ratingText) {
                                ratingText.innerHTML =
                                    `<small class="text-muted">Selected: ${value} - ${ratingLabels[value]}</small>`;
                            }
                            // Hide tooltips after selection
                            stars.forEach(function(s) {
                                const tooltip = bootstrap.Tooltip.getInstance(s);
                                if (tooltip) {
                                    tooltip.hide();
                                }
                            });
                        });

                        star.addEventListener('mouseenter', function() {
                            const value = parseInt(this.dataset.value);
                            highlightStars(stars, value);
                        });
                    });

                    container.addEventListener('mouseleave', function() {
                        const currentValue = parseInt(hiddenInput.value) || 0;
                        updateStars(stars, currentValue);
                    });
                }

                function updateStars(stars, value) {
                    stars.forEach(function(star) {
                        const starValue = parseInt(star.dataset.value);
                        if (starValue <= value) {
                            star.innerHTML = '<i class="fas fa-star"></i>';
                            star.style.color = '#ffc107';
                        } else {
                            star.innerHTML = '<i class="far fa-star"></i>';
                            star.style.color = '#6c757d';
                        }
                    });
                }

                function highlightStars(stars, value) {
                    stars.forEach(function(star) {
                        const starValue = parseInt(star.dataset.value);
                        if (starValue <= value) {
                            star.innerHTML = '<i class="fas fa-star"></i>';
                            star.style.color = '#ffc107';
                        } else {
                            star.innerHTML = '<i class="far fa-star"></i>';
                            star.style.color = '#6c757d';
                        }
                    });
                }
            });
        </script>

        <style>
            .star-rating .stars {
                display: flex;
                gap: 5px;
                font-size: 24px;
            }

            .star-rating .star {
                cursor: pointer;
                color: #6c757d;
                transition: color 0.2s;
            }

            .star-rating .star:hover {
                color: #ffc107;
            }

            .star-rating .star i {
                display: inline-block;
            }
        </style>
    @endpush
@endsection
