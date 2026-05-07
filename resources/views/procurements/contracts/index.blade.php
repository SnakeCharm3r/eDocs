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
                    <div class="d-flex gap-2">
                        <a href="{{ route('procurements.contracts.create') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-plus me-1"></i> Add Contract
                        </a>
                        @php
                            $user = auth()->user();
                            $canManageNotifications =
                                $user &&
                                ($user->hasAnyRole(['super-admin', 'procurement-officer']) ||
                                    CcbrtContract::where('status', 'active')
                                        ->where(function ($q) use ($user) {
                                            $q->where('contract_manager_id', $user->id)->orWhere(
                                                'created_by',
                                                $user->id,
                                            );
                                        })
                                        ->exists());
                        @endphp
                        @if ($canManageNotifications)
                            <a href="{{ route('procurements.contracts.notification-management') }}"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-bell me-1"></i> Notifications
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">

                    @php
                        $user = auth()->user();
                        $isHecMember = $user && $user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo']);
                        $isLineManager = $user && $user->hasRole('line-manager');
                        $isProcurementOfficer = $user && $user->hasRole('procurement-officer');

                        // Calculate number of cards to show
                        $cardCount = 3; // Active, Expiring, and Expired are always shown
                        if ($isLineManager) {
                            $cardCount++;
                        }
                        if ($isHecMember) {
                            $cardCount++;
                        }
                        if ($isProcurementOfficer) {
                            $cardCount++;
                        }

                        // Use flex-based equal width columns for all cards in one row
                        // Bootstrap's col class with flex will distribute evenly
$colClass = 'col';
                    @endphp

                    {{-- Row of clickable mini-cards --}}
                    <div class="row g-3 mb-4">
                        @if ($isLineManager)
                            <div class="{{ $colClass }}">
                                <a href="{{ route('procurements.contracts.index', ['view' => 'pending-line-manager']) }}"
                                    class="text-decoration-none">
                                    <div id="card-pending-line-manager"
                                        class="stat-card p-4 bg-white border rounded shadow-sm card-hover {{ ($viewType ?? 'active') === 'pending-line-manager' ? 'border-primary' : '' }}">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="stat-icon me-3">
                                                <i class="fas fa-user-check fa-2x text-muted"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 text-muted small">Pending My Review</h6>
                                                <h3 class="mb-0 text-dark mt-1">{{ $pendingLineManagerReviewCount ?? 0 }}
                                                </h3>
                                            </div>
                                        </div>
                                        <div class="border-top pt-2">
                                            <small class="text-muted">Amount</small>
                                            <p class="mb-0 fw-bold text-dark">
                                                {{ number_format($pendingLineManagerReviewValue ?? 0, 2) }}</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                        @if ($isHecMember)
                            <div class="{{ $colClass }}">
                                <a href="{{ route('procurements.contracts.index', ['view' => 'pending-hec']) }}"
                                    class="text-decoration-none">
                                    <div id="card-pending-hec"
                                        class="stat-card p-4 bg-white border rounded shadow-sm card-hover {{ ($viewType ?? 'active') === 'pending-hec' ? 'border-primary' : '' }}">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="stat-icon me-3">
                                                <i class="fas fa-user-check fa-2x text-muted"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 text-muted small">Pending HEC Review</h6>
                                                <h3 class="mb-0 text-dark mt-1">{{ $pendingHecReviewCount ?? 0 }}</h3>
                                            </div>
                                        </div>
                                        <div class="border-top pt-2">
                                            <small class="text-muted">Amount</small>
                                            <p class="mb-0 fw-bold text-dark">
                                                {{ number_format($pendingHecReviewValue ?? 0, 2) }}</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                        @if ($isProcurementOfficer)
                            <div class="{{ $colClass }}">
                                <a href="{{ route('procurements.contracts.index', ['view' => 'pending-procurement']) }}"
                                    class="text-decoration-none">
                                    <div id="card-pending-procurement"
                                        class="stat-card p-4 bg-white border rounded shadow-sm card-hover {{ ($viewType ?? 'active') === 'pending-procurement' ? 'border-primary' : '' }}">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="stat-icon me-3">
                                                <i class="fas fa-tasks fa-2x text-muted"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 text-muted small">Pending Processing</h6>
                                                <h3 class="mb-0 text-dark mt-1">{{ $pendingProcurementReviewCount ?? 0 }}
                                                </h3>
                                            </div>
                                        </div>
                                        <div class="border-top pt-2">
                                            <small class="text-muted">Amount</small>
                                            <p class="mb-0 fw-bold text-dark">
                                                {{ number_format($pendingProcurementReviewValue ?? 0, 2) }}</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                        <div class="{{ $colClass }}">
                            <a href="{{ route('procurements.contracts.index', ['view' => 'active']) }}"
                                class="text-decoration-none">
                                <div id="card-active"
                                    class="stat-card p-4 bg-white border rounded shadow-sm card-hover {{ ($viewType ?? 'active') === 'active' ? 'border-primary' : '' }}">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-check-circle fa-2x text-muted"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Active Contracts</h6>
                                            <h3 class="mb-0 text-dark mt-1">{{ $activeContractsCount }}</h3>
                                        </div>
                                    </div>
                                    <div class="border-top pt-2">
                                        <small class="text-muted">Amount</small>
                                        <p class="mb-0 fw-bold text-dark">{{ number_format($activeContractsValue, 2) }}</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="{{ $colClass }}">
                            <a href="{{ route('procurements.contracts.index', ['view' => 'expiring']) }}"
                                class="text-decoration-none">
                                <div id="card-expiring"
                                    class="stat-card p-4 bg-white border rounded shadow-sm card-hover {{ ($viewType ?? 'active') === 'expiring' ? 'border-primary' : '' }}">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-clock fa-2x text-muted"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Expiring Soon</h6>
                                            <h3 class="mb-0 text-dark mt-1">{{ $soonToExpireContractsCount }}</h3>
                                        </div>
                                    </div>
                                    <div class="border-top pt-2">
                                        <small class="text-muted">Amount</small>
                                        <p class="mb-0 fw-bold text-dark">
                                            {{ number_format($soonToExpireContractsValue, 2) }}</p>
                                        @if ($isProcurementOfficer && $soonToExpireContractsCount > 0)
                                            <form action="{{ route('procurements.contracts.send-bulk-reminders') }}"
                                                method="POST" class="mt-2"
                                                onsubmit="return confirm('Send renewal reminders to Line Managers for ALL {{ $soonToExpireContractsCount }} expiring contracts?');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                                    <i class="fas fa-bell me-1"></i> Send All Reminders
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="{{ $colClass }}">
                            <a href="{{ route('procurements.contracts.index', ['view' => 'expired']) }}"
                                class="text-decoration-none">
                                <div id="card-expired"
                                    class="stat-card p-4 bg-white border rounded shadow-sm card-hover {{ ($viewType ?? 'active') === 'expired' ? 'border-primary' : '' }}">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-times-circle fa-2x text-muted"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-muted small">Expired Contracts</h6>
                                            <h3 class="mb-0 text-dark mt-1">{{ $expiredContractsCount }}</h3>
                                        </div>
                                    </div>
                                    <div class="border-top pt-2">
                                        <small class="text-muted">Amount</small>
                                        <p class="mb-0 fw-bold text-dark">{{ number_format($expiredContractsValue, 2) }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </div>
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

                        {{-- Active --}}
                        <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'active' ? '' : 'd-none' }}"
                            id="table-active">
                            <h5 class="mb-3 text-dark">Active Contracts</h5>
                            <table id="datatable-active" class="table table-striped table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Contract Name</th>
                                        <th>Renewal</th>
                                        <th>Vendor</th>
                                        <th>Cost</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($activeContracts as $index => $contract)
                                        <tr data-start-date="{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '' }}"
                                            data-end-date="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '' }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ Str::limit($contract->title, 50) }}</strong>
                                                @if ($contract->contract_number)
                                                    <br><small
                                                        class="text-muted">#{{ $contract->contract_number }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($contract->parent_contract_id)
                                                    <span class="badge bg-secondary">Term
                                                        {{ $contract->renewal_term_number ?? 2 }}</span>
                                                    @if ($contract->parentContract)
                                                        <br><small class="text-muted">
                                                            <a href="{{ route('procurements.contracts.show', $contract->parent_contract_id) }}"
                                                                class="text-decoration-none">
                                                                First Contract
                                                            </a>
                                                        </small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">First</span>
                                                    @if ($contract->renewals && $contract->renewals->count() > 0)
                                                        <br><small class="text-muted">{{ $contract->renewals->count() }}
                                                            renewal(s)</small>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if ($contract->vendor)
                                                    {{ Str::limit($contract->vendor->name, 30) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ number_format($contract->cost ?? 0, 2) }}</strong>
                                                <small class="text-muted">{{ $contract->currency ?? 'TZS' }}</small>
                                            </td>
                                            <td>
                                                @if ($contract->start_date)
                                                    {{ \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($contract->end_date)
                                                    {{ \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge bg-secondary">
                                                        {{ ucfirst($contract->status ?? 'Draft') }}
                                                    </span>
                                                    @if ($contract->parent_contract_id || $contract->renewal_status === 'renewed')
                                                        <span class="badge bg-secondary" style="font-size: 0.75rem;">
                                                            <i class="fas fa-sync-alt me-1"></i>Renewed
                                                        </span>
                                                    @endif
                                                </div>
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
                                                            ]);
                                                    @endphp
                                                    @if ($canEdit)
                                                        <a href="{{ route('procurements.contracts.edit', $contract->id) }}"
                                                            class="btn btn-sm btn-outline-secondary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endif
                                                    @if (auth()->user()->hasRole('super-admin'))
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
                                            <td colspan="9" class="text-center text-muted">No active contracts found
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        @php
                            $user = auth()->user();
                            $isHecMember = $user && $user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo']);
                            $isLineManager = $user && $user->hasRole('line-manager');
                        @endphp

                        @if ($isLineManager)
                            {{-- Pending Line Manager Review --}}
                            <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'pending-line-manager' ? '' : 'd-none' }}"
                                id="table-pending-line-manager">
                                <h5 class="mb-3 text-dark">
                                    <i class="fas fa-user-check me-2"></i>Contracts Pending Your Review
                                    @if (($pendingLineManagerReviewContracts ?? collect())->isEmpty())
                                        <small class="text-muted">(No contracts pending review)</small>
                                    @endif
                                </h5>
                                <table id="datatable-pending-line-manager"
                                    class="table table-striped table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Contract Name</th>
                                            <th>Renewal</th>
                                            <th>Vendor</th>
                                            <th>Department</th>
                                            <th>Cost</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pendingLineManagerReviewContracts ?? collect() as $index => $contract)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ Str::limit($contract->title, 50) }}</strong>
                                                    @if ($contract->contract_number)
                                                        <br><small
                                                            class="text-muted">#{{ $contract->contract_number }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($contract->parent_contract_id)
                                                        <span class="badge bg-secondary">Term
                                                            {{ $contract->renewal_term_number ?? 2 }}</span>
                                                        @if ($contract->parentContract)
                                                            <br><small class="text-muted">
                                                                <a href="{{ route('procurements.contracts.show', $contract->parent_contract_id) }}"
                                                                    class="text-decoration-none">
                                                                    First Contract
                                                                </a>
                                                            </small>
                                                        @endif
                                                    @elseif($contract->renewal_status === 'pending' || $contract->lifecycle_stage === 'renewal')
                                                        <span class="badge bg-secondary">Renewal Pending</span>
                                                    @else
                                                        <span class="text-muted">First</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($contract->vendor)
                                                        {{ Str::limit($contract->vendor->name, 30) }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($contract->department)
                                                        {{ $contract->department->deptName ?? '-' }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>{{ number_format($contract->cost ?? 0, 2) }}</strong>
                                                    <small class="text-muted">{{ $contract->currency ?? 'TZS' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        Pending Review
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                            class="btn btn-sm btn-outline-secondary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            title="Review & Approve">
                                                            <i class="fas fa-check-circle"></i> Review
                                                        </a>
                                                        @if (auth()->user()->hasRole('super-admin'))
                                                            <form
                                                                action="{{ route('procurements.contracts.destroy', $contract->id) }}"
                                                                method="POST" class="d-inline"
                                                                onsubmit="return confirm('Delete this contract?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-outline-danger" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @if (($pendingLineManagerReviewContracts ?? collect())->isEmpty())
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">
                                                    <i class="fas fa-check-circle me-2"></i>No contracts pending your
                                                    review
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if ($isHecMember)
                            {{-- Pending HEC Review --}}
                            <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'pending-hec' ? '' : 'd-none' }}"
                                id="table-pending-hec">
                                <h5 class="mb-3 text-dark">
                                    <i class="fas fa-user-check me-2"></i>Contracts Pending Your Review
                                    @if (($pendingHecReviewContracts ?? collect())->isEmpty())
                                        <small class="text-muted">(No contracts pending review)</small>
                                    @endif
                                </h5>
                                <table id="datatable-pending-hec" class="table table-striped table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Contract Name</th>
                                            <th>Renewal</th>
                                            <th>Vendor</th>
                                            <th>Department</th>
                                            <th>Cost</th>
                                            <th>Line Manager Rating</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pendingHecReviewContracts ?? collect() as $index => $contract)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ Str::limit($contract->title, 50) }}</strong>
                                                    @if ($contract->contract_number)
                                                        <br><small
                                                            class="text-muted">#{{ $contract->contract_number }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($contract->parent_contract_id)
                                                        <span class="badge bg-secondary">Term
                                                            {{ $contract->renewal_term_number ?? 2 }}</span>
                                                        @if ($contract->parentContract)
                                                            <br><small class="text-muted">
                                                                <a href="{{ route('procurements.contracts.show', $contract->parent_contract_id) }}"
                                                                    class="text-decoration-none">
                                                                    First Contract
                                                                </a>
                                                            </small>
                                                        @endif
                                                    @elseif($contract->renewal_status === 'pending' || $contract->lifecycle_stage === 'renewal')
                                                        <span class="badge bg-secondary">Renewal Pending</span>
                                                    @else
                                                        <span class="text-muted">First</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($contract->vendor)
                                                        {{ Str::limit($contract->vendor->name, 30) }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($contract->department)
                                                        {{ $contract->department->deptName ?? '-' }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>{{ number_format($contract->cost ?? 0, 2) }}</strong>
                                                    <small class="text-muted">{{ $contract->currency ?? 'TZS' }}</small>
                                                </td>
                                                <td>
                                                    @if ($contract->line_manager_rating)
                                                        <span class="badge bg-secondary">
                                                            {{ number_format($contract->line_manager_rating, 1) }}/5
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        Pending HEC Review
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                            class="btn btn-sm btn-outline-secondary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            title="Review & Approve">
                                                            <i class="fas fa-check-circle"></i> Review
                                                        </a>
                                                        @if (auth()->user()->hasRole('super-admin'))
                                                            <form
                                                                action="{{ route('procurements.contracts.destroy', $contract->id) }}"
                                                                method="POST" class="d-inline"
                                                                onsubmit="return confirm('Delete this contract?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-outline-danger" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @if (($pendingHecReviewContracts ?? collect())->isEmpty())
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">
                                                    <i class="fas fa-check-circle me-2"></i>No contracts pending your
                                                    review
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if ($isProcurementOfficer)
                            {{-- Pending Procurement Processing --}}
                            <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'pending-procurement' ? '' : 'd-none' }}"
                                id="table-pending-procurement">
                                <h5 class="mb-3 text-dark">
                                    <i class="fas fa-tasks me-2"></i>Contracts Pending Processing
                                    @if (($pendingProcurementReviewContracts ?? collect())->isEmpty())
                                        <small class="text-muted">(No contracts pending processing)</small>
                                    @endif
                                </h5>
                                <table id="datatable-pending-procurement"
                                    class="table table-striped table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Contract Name</th>
                                            <th>Renewal</th>
                                            <th>Vendor</th>
                                            <th>Department</th>
                                            <th>Cost</th>
                                            <th>Line Manager Rating</th>
                                            <th>HEC Rating</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pendingProcurementReviewContracts ?? collect() as $index => $contract)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ Str::limit($contract->title, 50) }}</strong>
                                                    @if ($contract->contract_number)
                                                        <br><small
                                                            class="text-muted">#{{ $contract->contract_number }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($contract->parent_contract_id)
                                                        <span class="badge bg-secondary">Term
                                                            {{ $contract->renewal_term_number ?? 2 }}</span>
                                                        @if ($contract->parentContract)
                                                            <br><small class="text-muted">
                                                                <a href="{{ route('procurements.contracts.show', $contract->parent_contract_id) }}"
                                                                    class="text-decoration-none">
                                                                    First Contract
                                                                </a>
                                                            </small>
                                                        @endif
                                                    @elseif($contract->renewal_status === 'pending' || $contract->lifecycle_stage === 'renewal')
                                                        <span class="badge bg-secondary">Renewal Pending</span>
                                                    @else
                                                        <span class="text-muted">First</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($contract->vendor)
                                                        {{ Str::limit($contract->vendor->name, 30) }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($contract->department)
                                                        {{ $contract->department->deptName ?? '-' }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>{{ number_format($contract->cost ?? 0, 2) }}</strong>
                                                    <small class="text-muted">{{ $contract->currency ?? 'TZS' }}</small>
                                                </td>
                                                <td>
                                                    @if ($contract->line_manager_rating)
                                                        <span class="badge bg-secondary">
                                                            {{ number_format($contract->line_manager_rating, 1) }}/5
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($contract->hec_rating)
                                                        <span class="badge bg-secondary">
                                                            {{ number_format($contract->hec_rating, 1) }}/5
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        Ready to Process
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                            class="btn btn-sm btn-outline-secondary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            title="Process & Finalize">
                                                            <i class="fas fa-check-circle"></i> Process
                                                        </a>
                                                        @if (auth()->user()->hasRole('super-admin'))
                                                            <form
                                                                action="{{ route('procurements.contracts.destroy', $contract->id) }}"
                                                                method="POST" class="d-inline"
                                                                onsubmit="return confirm('Delete this contract?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-outline-danger" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @if (($pendingProcurementReviewContracts ?? collect())->isEmpty())
                                            <tr>
                                                <td colspan="10" class="text-center text-muted">
                                                    <i class="fas fa-check-circle me-2"></i>No contracts pending processing
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Expiring --}}
                        <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'expiring' ? '' : 'd-none' }}"
                            id="table-expiring">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 text-dark">
                                    Expiring Soon (within 30 days)
                                    @if ($soonToExpireContracts->isEmpty())
                                        <small class="text-muted">(No contracts expiring soon)</small>
                                    @endif
                                </h5>
                                @php
                                    $user = auth()->user();
                                    // Check if user has procurement officer role - EXACT match only
                                    $isProcurementOfficer = $user && $user->hasRole('procurement-officer');
                                @endphp
                                @if ($isProcurementOfficer)
                                    @if ($soonToExpireContracts->isNotEmpty())
                                        <form action="{{ route('procurements.contracts.send-bulk-reminders') }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Send renewal reminders to Line Managers for ALL {{ $soonToExpireContracts->count() }} expiring contracts?');">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-bell me-1"></i> Send Reminders to All
                                                ({{ $soonToExpireContracts->count() }})
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary">No contracts to send reminders for</span>
                                    @endif
                                @endif
                            </div>
                            <table id="datatable-expiring" class="table table-striped table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Contract Name</th>
                                        <th>Renewal</th>
                                        <th>Vendor</th>
                                        <th>Cost</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($soonToExpireContracts as $index => $contract)
                                        <tr data-start-date="{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '' }}"
                                            data-end-date="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '' }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ Str::limit($contract->title, 50) }}</strong>
                                                @if ($contract->contract_number)
                                                    <br><small
                                                        class="text-muted">#{{ $contract->contract_number }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($contract->parent_contract_id)
                                                    <span class="badge bg-secondary">Term
                                                        {{ $contract->renewal_term_number ?? 2 }}</span>
                                                    @if ($contract->parentContract)
                                                        <br><small class="text-muted">
                                                            <a href="{{ route('procurements.contracts.show', $contract->parent_contract_id) }}"
                                                                class="text-decoration-none">
                                                                First Contract
                                                            </a>
                                                        </small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">First</span>
                                                    @if ($contract->renewals && $contract->renewals->count() > 0)
                                                        <br><small class="text-muted">{{ $contract->renewals->count() }}
                                                            renewal(s)</small>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if ($contract->vendor)
                                                    {{ Str::limit($contract->vendor->name, 30) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ number_format($contract->cost ?? 0, 2) }}</strong>
                                                <small class="text-muted">{{ $contract->currency ?? 'TZS' }}</small>
                                            </td>
                                            <td>
                                                @if ($contract->end_date)
                                                    {{ \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ ucfirst($contract->status ?? 'Draft') }}
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
                                                            ]);
                                                        // Check if user has procurement officer role - EXACT match only
                                                        $isProcurementOfficer =
                                                            $user && $user->hasRole('procurement-officer');
                                                        // Check renew permission
                                                        $isLineManager = $user && $user->hasRole('line-manager');
                                                        $isDepartmentLineManager = false;
                                                        if ($isLineManager && $contract->department) {
                                                            $isDepartmentLineManager =
                                                                $user->deptId == $contract->department_id;
                                                        }
                                                        // Can renew if: (Line Manager of department OR Procurement Officer) AND contract is expiring/expired AND not already pending/renewed
                                                        $canRenew =
                                                            ($isDepartmentLineManager || $isProcurementOfficer) &&
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
                                                    @if ($isProcurementOfficer && $contract->department)
                                                        <button type="button" class="btn btn-sm btn-warning"
                                                            title="Send Reminder" data-bs-toggle="modal"
                                                            data-bs-target="#sendReminderModal{{ $contract->id }}">
                                                            <i class="fas fa-bell"></i> Send Reminder
                                                        </button>
                                                    @endif
                                                    @if (auth()->user()->hasRole('super-admin'))
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
                                            <td colspan="8" class="text-center text-muted">No contracts expiring soon
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- Expired --}}
                        <div class="table-responsive table-section {{ ($viewType ?? 'active') === 'expired' ? '' : 'd-none' }}"
                            id="table-expired">
                            <h5 class="mb-3 text-dark">Expired Contracts</h5>
                            <table id="datatable-expired" class="table table-striped table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Contract Name</th>
                                        <th>Renewal</th>
                                        <th>Vendor</th>
                                        <th>Cost</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($expiredContracts as $index => $contract)
                                        <tr data-start-date="{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '' }}"
                                            data-end-date="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '' }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ Str::limit($contract->title, 50) }}</strong>
                                                @if ($contract->contract_number)
                                                    <br><small
                                                        class="text-muted">#{{ $contract->contract_number }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($contract->parent_contract_id)
                                                    <span class="badge bg-secondary">Term
                                                        {{ $contract->renewal_term_number ?? 2 }}</span>
                                                    @if ($contract->parentContract)
                                                        <br><small class="text-muted">
                                                            <a href="{{ route('procurements.contracts.show', $contract->parent_contract_id) }}"
                                                                class="text-decoration-none">
                                                                First Contract
                                                            </a>
                                                        </small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">First</span>
                                                    @if ($contract->renewals && $contract->renewals->count() > 0)
                                                        <br><small class="text-muted">{{ $contract->renewals->count() }}
                                                            renewal(s)</small>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if ($contract->vendor)
                                                    {{ Str::limit($contract->vendor->name, 30) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ number_format($contract->cost ?? 0, 2) }}</strong>
                                                <small class="text-muted">{{ $contract->currency ?? 'TZS' }}</small>
                                            </td>
                                            <td>
                                                @if ($contract->end_date)
                                                    {{ \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') }}
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
                                                            ]);
                                                        $isLineManager = $user && $user->hasRole('line-manager');
                                                        $isProcurementOfficer =
                                                            $user && $user->hasRole('procurement-officer');
                                                        $isDepartmentLineManager = false;
                                                        if ($isLineManager && $contract->department) {
                                                            $isDepartmentLineManager =
                                                                $user->deptId == $contract->department_id;
                                                        }
                                                        $canRenew =
                                                            ($isDepartmentLineManager || $isProcurementOfficer) &&
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
                                                    @if (auth()->user()->hasRole('super-admin'))
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
                                            <td colspan="8" class="text-center text-muted">No expired contracts found
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
    </div>

    <!-- Renew Contract Modals -->
    @foreach ($expiredContracts->merge($soonToExpireContracts)->unique('id') as $contract)
        @php
            $user = auth()->user();
            $isLineManager = $user->hasRole('line-manager');
            $isProcurementOfficer = $user->hasRole('procurement-officer');
            $isDepartmentLineManager = false;
            if ($isLineManager && $contract->department) {
                $isDepartmentLineManager = $user->deptId == $contract->department_id;
            }
            // Can renew if: (Line Manager of department OR Procurement Officer) AND contract is expiring/expired AND not already pending/renewed
            $canRenew =
                ($isDepartmentLineManager || $isProcurementOfficer) &&
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
                                                    data-bs-placement="top" title="Excellent">
                                                    <i class="far fa-star"></i>
                                                </span>
                                            </div>
                                            <div class="rating-text mt-2">
                                                <small class="text-muted">Click on a star to rate</small>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block">Rate the performance of this specific contract.
                                            Note: One vendor may have multiple contracts, each rated separately.</small>
                                    </div>
                                @else
                                    <div class="mb-4 p-3 border rounded">
                                        <small class="text-muted">As a Procurement Officer, you are initiating the renewal.
                                            The Line Manager will review and rate this contract, then it will go to HEC for
                                            review and rating.</small>
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
            <div class="modal fade" id="sendReminderModal{{ $contract->id }}" tabindex="-1"
                aria-labelledby="sendReminderModalLabel{{ $contract->id }}" aria-hidden="true"
                data-contract-id="{{ $contract->id }}" data-department-id="{{ $contract->department_id }}"
                data-department-name="{{ $contract->department->dept_name ?? 'N/A' }}">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-light border-bottom">
                            <h5 class="modal-title" id="sendReminderModalLabel{{ $contract->id }}">
                                <i class="fas fa-bell me-2"></i>Send Renewal Reminder
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form id="sendReminderForm{{ $contract->id }}"
                            action="{{ route('procurements.contracts.send-reminder', $contract->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <!-- Message Alert Area -->
                                <div id="reminderMessage{{ $contract->id }}" class="alert d-none" role="alert">
                                    <span id="reminderMessageText{{ $contract->id }}"></span>
                                </div>

                                <div class="mb-3 p-3 border rounded">
                                    <strong>Contract:</strong> {{ $contract->title }}
                                    @if ($contract->vendor)
                                        <br><strong>Vendor:</strong> {{ $contract->vendor->name }}
                                    @endif
                                    @if ($daysUntilExpiry !== null)
                                        <br><strong>Days Until Expiry:</strong> <span
                                            class="badge bg-secondary">{{ abs($daysUntilExpiry) }} days</span>
                                    @endif
                                    @if ($contract->end_date)
                                        <br><strong>Expiry Date:</strong>
                                        {{ \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') }}
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <p class="mb-2"><strong>Select recipients:</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="send_to_line_manager"
                                            id="sendToLM{{ $contract->id }}" value="1" checked disabled>
                                        <label class="form-check-label" for="sendToLM{{ $contract->id }}">
                                            <strong>Line Manager</strong>
                                            <div id="lineManagerInfo{{ $contract->id }}"
                                                class="mt-2 p-2 bg-light rounded">
                                                <small class="text-muted">Loading...</small>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="send_to_hec"
                                            id="sendToHEC{{ $contract->id }}" value="1">
                                        <label class="form-check-label" for="sendToHEC{{ $contract->id }}">
                                            <strong>HEC Member</strong>
                                            <small class="text-muted d-block">(Optional - Will also send to HEC member for
                                                {{ $contract->department->dept_name ?? 'the department' }})</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="alert alert-secondary">
                                    <small>This will send a reminder email to the selected recipients about the contract
                                        expiration.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" id="sendReminderBtn{{ $contract->id }}" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> <span
                                        id="sendReminderBtnText{{ $contract->id }}">Send Reminder</span>
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
        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
        <style>
            /* Green icons styling */
            .fas,
            .fa,
            i[class*="fa-"] {
                color: #28a745 !important;
            }

            /* Override for specific cases where we want different colors */
            .badge .fas,
            .badge .fa {
                color: inherit !important;
            }

            .btn .fas,
            .btn .fa {
                color: inherit !important;
            }

            .text-warning .fas,
            .text-warning .fa {
                color: #ffc107 !important;
            }

            .text-danger .fas,
            .text-danger .fa {
                color: #dc3545 !important;
            }

            .text-success .fas,
            .text-success .fa {
                color: #28a745 !important;
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
        <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
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
                if (typeof DataTable === 'undefined') return;

                // First, destroy all stored instances
                Object.keys(dataTableInstances).forEach(tableId => {
                    try {
                        const tableEl = document.getElementById(tableId);
                        if (tableEl) {
                            try {
                                const existingTable = DataTable.get(tableEl);
                                if (existingTable) {
                                    existingTable.destroy(true); // true = remove from DOM
                                    delete dataTableInstances[tableId];
                                    tableEl.removeAttribute('data-dt-initialized');
                                }
                            } catch (e) {
                                // Table might already be destroyed, try to clean up anyway
                                tableEl.removeAttribute('data-dt-initialized');
                            }
                        }
                    } catch (e) {
                        // Ignore errors when destroying
                    }
                });

                // Also try to destroy any DataTables that might exist but aren't in our storage
                document.querySelectorAll('table[id^="datatable-"]').forEach(tableEl => {
                    try {
                        // Check if DataTable is initialized on this element
                        if (tableEl.classList.contains('dataTable') || tableEl.hasAttribute('data-dt-initialized')) {
                            try {
                                const existingTable = DataTable.get(tableEl);
                                if (existingTable) {
                                    existingTable.destroy(true); // true = remove from DOM
                                    tableEl.removeAttribute('data-dt-initialized');
                                }
                            } catch (e) {
                                // Clean up attributes even if destroy fails
                                tableEl.removeAttribute('data-dt-initialized');
                                tableEl.classList.remove('dataTable');
                            }
                        }
                    } catch (e) {
                        // Ignore errors
                    }
                });

                // Clear the instances object
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

                // Check if DataTable already exists - if so, don't reinitialize
                if (typeof DataTable !== 'undefined') {
                    // First, check if DataTable is already initialized on this element
                    try {
                        const existingTable = DataTable.get(tableEl);
                        if (existingTable) {
                            // Already initialized, don't reinitialize
                            return;
                        }
                    } catch (e) {
                        // DataTable.get() throws if not initialized, which is fine
                    }

                    // Check if table has DataTable classes/attributes indicating it was initialized
                    if (tableEl.classList.contains('dataTable') || tableEl.hasAttribute('data-dt-initialized')) {
                        // Clean up any leftover DataTable artifacts
                        try {
                            const existingTable = DataTable.get(tableEl);
                            if (existingTable) {
                                existingTable.destroy(true);
                            }
                        } catch (e) {
                            // Ignore - might already be destroyed
                        }
                        // Clean up attributes and classes
                        tableEl.removeAttribute('data-dt-initialized');
                        tableEl.classList.remove('dataTable');
                        // Remove any DataTable wrapper elements
                        const wrapper = tableEl.closest('.dataTables_wrapper');
                        if (wrapper && wrapper.parentNode) {
                            wrapper.parentNode.insertBefore(tableEl, wrapper);
                            wrapper.remove();
                        }
                    }

                    try {
                        // Remove any rows with colspan before initializing (they cause DataTables errors)
                        const tbody = tableEl.querySelector('tbody');
                        if (tbody) {
                            const colspanRows = tbody.querySelectorAll('tr td[colspan]');
                            colspanRows.forEach(cell => {
                                const row = cell.closest('tr');
                                if (row) row.remove();
                            });
                        }

                        // Double-check one more time that DataTable is not initialized
                        try {
                            const checkTable = DataTable.get(tableEl);
                            if (checkTable) {
                                return; // Already initialized, exit
                            }
                        } catch (e) {
                            // Good, not initialized, continue
                        }

                        // Get the number of columns dynamically
                        const columnCount = tableEl.querySelectorAll('thead tr th').length;
                        const actionsColumnIndex = columnCount - 1; // Last column is always Actions

                        // Initialize new DataTable
                        const dt = new DataTable(tableEl, {
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

                        // Store DataTable instance
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

                    // Filter by status (column index 5)
                    if (status) {
                        const statusCell = row.cells[5];
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
                // Show pending review table by default if user has pending reviews
                @php
                    $isLineManager = auth()->user() && auth()->user()->hasRole('line-manager');
                    $isHecMember =
                        auth()->user() &&
                        auth()
                            ->user()
                            ->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo']);
                    $isProcurementOfficer = auth()->user() && auth()->user()->hasRole('procurement-officer');
                @endphp
                // Initialize DataTable for the visible table based on viewType
                @php
                    $currentViewType = $viewType ?? 'active';
                @endphp

                // Map viewType to table ID
                const viewTypeToTableId = {
                    'active': 'datatable-active',
                    'expiring': 'datatable-expiring',
                    'expired': 'datatable-expired',
                    'pending-line-manager': 'datatable-pending-line-manager',
                    'pending-hec': 'datatable-pending-hec',
                    'pending-procurement': 'datatable-pending-procurement'
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
