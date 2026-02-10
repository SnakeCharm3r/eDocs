@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">
                            <i class="fas fa-file-contract me-2"></i>Contract Details
                        </h3>

                    </div>
                    <div class="col-auto">
                        <a href="{{ route('procurements.contracts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                        @php
                            $user = auth()->user();
                            // Only Procurement Officers, HR, and Super Admins can edit contracts
                            // Line Managers and HEC members are NOT allowed to edit
                            $canEdit = $user && $user->hasAnyRole(['procurement-officer', 'hr', 'super-admin']);
                        @endphp
                        @if ($canEdit)
                            <a href="{{ route('procurements.contracts.edit', $contract->id) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Status Overview Card -->
                <div class="col-lg-12 mb-4">
                    <div class="card shadow-sm border-0 bg-white">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h3 class="mb-2 text-dark">
                                        <i class="fas fa-file-contract me-2 text-muted"></i>{{ $contract->title }}
                                    </h3>
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-hashtag me-1"></i>Contract
                                        #{{ $contract->contract_number ?? 'N/A' }}
                                    </p>
                                    @if ($contract->parent_contract_id)
                                        <p class="mb-0 mt-2">
                                            <span class="badge bg-secondary">Renewal Term
                                                {{ $contract->renewal_term_number ?? 2 }}</span>
                                            @if ($contract->parentContract)
                                                <a href="{{ route('procurements.contracts.show', $contract->parent_contract_id) }}"
                                                    class="text-decoration-none ms-2">
                                                    <i class="fas fa-link me-1"></i>View First Contract
                                                </a>
                                            @endif
                                        </p>
                                    @elseif($contract->renewals && $contract->renewals->count() > 0)
                                        <p class="mb-0 mt-2">
                                            <span class="badge bg-secondary">First Contract</span>
                                            <span class="text-muted ms-2">{{ $contract->renewals->count() }}
                                                renewal(s)</span>
                                        </p>
                                    @endif
                                </div>
                                <div class="col-md-4 text-end">
                                    @php
                                        $statusLabel = match ($contract->status ?? 'draft') {
                                            'soonToExpire' => 'Soon To Expire',
                                            default => ucfirst($contract->status ?? 'Draft'),
                                        };
                                    @endphp
                                    <span class="badge bg-secondary text-white fs-6 px-3 py-2 mb-2 d-inline-block">
                                        {{ $statusLabel }}
                                    </span>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            @if ($contract->end_date)
                                                @php
                                                    $endDate = \Carbon\Carbon::parse($contract->end_date);
                                                    $today = \Carbon\Carbon::now();
                                                    $daysRemaining = $today->diffInDays($endDate, false);
                                                @endphp
                                                @if ($daysRemaining < 0)
                                                    Expired {{ abs($daysRemaining) }} days ago
                                                @elseif($daysRemaining <= 30)
                                                    Expires in {{ $daysRemaining }} days
                                                @else
                                                    {{ $endDate->format('M d, Y') }}
                                                @endif
                                            @else
                                                No end date set
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar with Quick Stats -->
                <div class="col-lg-4 mb-4">
                    <!-- Quick Stats Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 text-dark">
                                <i class="fas fa-chart-bar me-2"></i>Quick Stats
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <small class="text-muted d-block">Contract Value</small>
                                    <strong class="text-dark">{{ number_format($contract->cost ?? 0, 0) }}
                                        {{ $contract->currency ?? 'TZS' }}</strong>
                                </div>
                                <i class="fas fa-money-bill-wave fa-2x text-muted opacity-25"></i>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <small class="text-muted d-block">Duration</small>
                                    <strong class="text-dark">{{ $contract->duration_months ?? 'N/A' }} months</strong>
                                </div>
                                <i class="fas fa-calendar-alt fa-2x text-muted opacity-25"></i>
                            </div>
                            @if ($contract->end_date)
                                @php
                                    $endDate = \Carbon\Carbon::parse($contract->end_date);
                                    $today = \Carbon\Carbon::now();
                                    $daysRemaining = $today->diffInDays($endDate, false);
                                @endphp
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block">Days Remaining</small>
                                        <strong class="text-dark">
                                            {{ $daysRemaining < 0 ? abs($daysRemaining) . ' days ago' : $daysRemaining . ' days' }}
                                        </strong>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-muted opacity-25"></i>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 text-dark">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('procurements.contracts.index') }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i> Back to List
                                </a>
                                @php
                                    $user = auth()->user();
                                    $canEdit = $user && $user->hasAnyRole(['procurement-officer', 'hr', 'super-admin']);
                                @endphp
                                @if ($canEdit)
                                    <a href="{{ route('procurements.contracts.edit', $contract->id) }}"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-edit me-1"></i> Edit Contract
                                    </a>
                                @endif
                                @if (auth()->user()->hasRole('super-admin'))
                                    <form action="{{ route('procurements.contracts.destroy', $contract->id) }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this contract? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm w-100">
                                            <i class="fas fa-trash me-1"></i> Delete Contract
                                        </button>
                                    </form>
                                @endif
                                @if ($contract->file_path || $contract->signed_contract_path)
                                    <a href="{{ route('procurements.contracts.document', $contract->id) }}?type=file_path"
                                        target="_blank" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-file-pdf me-1"></i> View Document
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Contract Information Section -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-info-circle me-2"></i>Contract Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-tag me-1"></i>Contract Type
                                        </label>
                                        <div class="fw-bold">
                                            @if ($contract->contract_type)
                                                <span class="badge bg-secondary fs-6 px-3 py-2">
                                                    {{ $contract->contract_type }}
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-sync-alt me-1"></i>Renewal Status
                                        </label>
                                        <div class="fw-bold text-dark">
                                            {{ ucfirst(str_replace('_', ' ', $contract->renewal_status ?? 'N/A')) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-layer-group me-1"></i>Lifecycle Stage
                                        </label>
                                        <div class="fw-bold text-dark">
                                            {{ ucfirst($contract->lifecycle_stage ?? 'Drafting') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-clock me-1"></i>Duration
                                        </label>
                                        <div class="fw-bold">
                                            @if ($contract->duration_months)
                                                <strong>{{ $contract->duration_months }} months</strong>
                                                @if ($contract->start_date && $contract->end_date)
                                                    @php
                                                        $start = \Carbon\Carbon::parse($contract->start_date);
                                                        $end = \Carbon\Carbon::parse($contract->end_date);
                                                        $actualMonths = $start->diffInMonths($end);
                                                    @endphp
                                                    <small class="text-muted ms-2">(Actual: {{ $actualMonths }}
                                                        months)</small>
                                                @endif
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-calendar-check me-1"></i>Start Date
                                        </label>
                                        <div class="fw-bold">
                                            @if ($contract->start_date)
                                                @php
                                                    $startDate = \Carbon\Carbon::parse($contract->start_date);
                                                    $today = \Carbon\Carbon::now();
                                                    $daysUntilStart = $today->diffInDays($startDate, false);
                                                @endphp
                                                <strong>{{ $startDate->format('M d, Y') }}</strong>
                                                @if ($daysUntilStart > 0)
                                                    <small class="text-muted ms-2">(Starts in {{ $daysUntilStart }}
                                                        days)</small>
                                                @elseif($daysUntilStart == 0)
                                                    <small class="text-muted ms-2">(Starts today)</small>
                                                @else
                                                    <small class="text-muted ms-2">(Active)</small>
                                                @endif
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-calendar-times me-1"></i>End Date
                                        </label>
                                        <div class="fw-bold">
                                            @if ($contract->end_date)
                                                @php
                                                    $endDate = \Carbon\Carbon::parse($contract->end_date);
                                                    $today = \Carbon\Carbon::now();
                                                    $daysRemaining = $today->diffInDays($endDate, false);
                                                @endphp
                                                <strong>{{ $endDate->format('M d, Y') }}</strong>
                                                @if ($daysRemaining < 0)
                                                    <small class="text-muted ms-2">(Expired {{ abs($daysRemaining) }}
                                                        days ago)</small>
                                                @elseif($daysRemaining <= 30)
                                                    <small class="text-muted ms-2">(Expires in {{ $daysRemaining }}
                                                        days)</small>
                                                @elseif($daysRemaining <= 90)
                                                    <small class="text-muted ms-2">(Expires in {{ $daysRemaining }}
                                                        days)</small>
                                                @else
                                                    <small class="text-muted ms-2">(Active)</small>
                                                @endif
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-calendar-plus me-1"></i>Record Created
                                        </label>
                                        <div class="fw-bold">
                                            @if ($contract->created_at)
                                                <strong>{{ $contract->created_at->format('M d, Y H:i') }}</strong>
                                                <small
                                                    class="text-muted ms-2">({{ $contract->created_at->diffForHumans() }})</small>
                                                <br><small class="text-muted">This is when the contract record was created
                                                    in the system</small>
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contract Chain & History Section -->
                    @if (isset($contractChain) && $contractChain->count() > 0)
                        <div class="card shadow-sm mb-4 border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0 text-dark">
                                    <i class="fas fa-history me-2"></i>Contract History & Financial Details
                                </h5>
                            </div>
                            <div class="card-body">
                                @php
                                    $currentContractId = $contract->id;
                                @endphp

                                @foreach ($contractChain as $contractItem)
                                    @php
                                        $isCurrent = $contractItem->id == $currentContractId;
                                        $termNumber = $contractItem->renewal_term_number ?? 1;
                                        $isFirst = $termNumber == 1;
                                    @endphp

                                    <div
                                        class="contract-term-card mb-4 {{ $isCurrent ? 'border-primary border-2' : 'border' }} rounded p-4 bg-white">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="mb-1 text-dark">
                                                    @if ($isFirst)
                                                        <i class="fas fa-star me-2 text-muted"></i>First Contract
                                                    @else
                                                        <i class="fas fa-sync-alt me-2 text-muted"></i>Renewal Term
                                                        {{ $termNumber }}
                                                    @endif
                                                    @if ($isCurrent)
                                                        <span class="badge bg-secondary ms-2">Current</span>
                                                    @endif
                                                </h6>
                                                <p class="mb-0 text-muted">
                                                    <strong>{{ $contractItem->title }}</strong>
                                                    @if ($contractItem->contract_number)
                                                        <br><small>#{{ $contractItem->contract_number }}</small>
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="text-end">
                                                <a href="{{ route('procurements.contracts.show', $contractItem->id) }}"
                                                    class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-2">
                                            <div class="col-md-3">
                                                <div class="info-item p-2 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-calendar-plus me-1"></i>Created
                                                    </label>
                                                    <div class="fw-bold text-dark">
                                                        @if ($contractItem->created_at)
                                                            {{ \Carbon\Carbon::parse($contractItem->created_at)->format('M d, Y') }}
                                                            <br><small
                                                                class="text-muted">{{ \Carbon\Carbon::parse($contractItem->created_at)->diffForHumans() }}</small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="info-item p-2 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-calendar-check me-1"></i>Start Date
                                                    </label>
                                                    <div class="fw-bold text-dark">
                                                        @if ($contractItem->start_date)
                                                            {{ \Carbon\Carbon::parse($contractItem->start_date)->format('M d, Y') }}
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="info-item p-2 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-calendar-times me-1"></i>End Date
                                                    </label>
                                                    <div class="fw-bold text-dark">
                                                        @if ($contractItem->end_date)
                                                            {{ \Carbon\Carbon::parse($contractItem->end_date)->format('M d, Y') }}
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="info-item p-2 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-clock me-1"></i>Duration
                                                    </label>
                                                    <div class="fw-bold text-dark">
                                                        @if ($contractItem->duration_months)
                                                            {{ $contractItem->duration_months }} months
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="info-item p-2 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-money-bill-wave me-1"></i>Contract Value
                                                    </label>
                                                    <div class="fw-bold text-dark">
                                                        <strong>{{ number_format($contractItem->cost ?? 0, 2) }}</strong>
                                                        <small
                                                            class="text-muted">{{ $contractItem->currency ?? 'TZS' }}</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="info-item p-2 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-tag me-1"></i>Contract Type
                                                    </label>
                                                    <div class="fw-bold">
                                                        <span
                                                            class="badge bg-secondary">{{ $contractItem->contract_type ?? 'N/A' }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="info-item p-2 bg-light rounded">
                                                    <label class="text-muted small mb-1 d-block">
                                                        <i class="fas fa-info-circle me-1"></i>Status
                                                    </label>
                                                    <div class="fw-bold">
                                                        <span
                                                            class="badge bg-secondary">{{ ucfirst($contractItem->status ?? 'N/A') }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($contractItem->vendor)
                                                <div class="col-md-6">
                                                    <div class="info-item p-2 bg-light rounded">
                                                        <label class="text-muted small mb-1 d-block">
                                                            <i class="fas fa-building me-1"></i>Vendor
                                                        </label>
                                                        <div class="fw-bold text-dark">
                                                            {{ $contractItem->vendor->name }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($contractItem->evaluation_score)
                                                <div class="col-md-6">
                                                    <div class="info-item p-2 bg-light rounded">
                                                        <label class="text-muted small mb-1 d-block">
                                                            <i class="fas fa-star me-1"></i>Evaluation Score
                                                        </label>
                                                        <div class="fw-bold text-dark">
                                                            {{ number_format($contractItem->evaluation_score, 2) }}/5
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if (!$loop->last)
                                        <div class="text-center my-3">
                                            <i class="fas fa-arrow-down text-muted fa-2x"></i>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Contract Parties Section -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-users me-2"></i>Contract Parties
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-building me-1"></i>Vendor / Contractor
                                        </label>
                                        <div class="fw-bold">
                                            @if ($contract->vendor)
                                                <a href="{{ route('procurements.vendors.show', $contract->vendor->id) }}"
                                                    class="text-decoration-none">
                                                    {{ $contract->vendor->name }}
                                                    <i class="fas fa-external-link-alt ms-1 small"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">No vendor assigned</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-sitemap me-1"></i>Entity
                                        </label>
                                        <div class="fw-bold">{{ $contract->division->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-briefcase me-1"></i>Department
                                        </label>
                                        <div class="fw-bold">{{ $contract->department->dept_name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-user-tie me-1"></i>Contract Owner (Line Manager)
                                        </label>
                                        <div class="fw-bold">
                                            @if ($contract->contractManager)
                                                {{ $contract->contractManager->fname }}
                                                {{ $contract->contractManager->mname }}
                                                {{ $contract->contractManager->lname }}
                                            @else
                                                <span class="text-muted">Not assigned</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-user-plus me-1"></i>Created By
                                        </label>
                                        <div class="fw-bold">
                                            @php
                                                $creator = $contract->creator;
                                            @endphp
                                            @if ($creator)
                                                <strong>{{ trim(($creator->fname ?? '') . ' ' . ($creator->mname ?? '') . ' ' . ($creator->lname ?? '')) ?: $creator->username ?? 'Unknown User' }}</strong>
                                                @if ($creator->email)
                                                    <br><small class="text-muted">{{ $creator->email }}</small>
                                                @endif
                                            @elseif($contract->created_by)
                                                @php
                                                    $user = \App\Models\User::find($contract->created_by);
                                                @endphp
                                                @if ($user)
                                                    <strong>{{ trim(($user->fname ?? '') . ' ' . ($user->mname ?? '') . ' ' . ($user->lname ?? '')) ?: $user->username ?? 'Unknown User' }}</strong>
                                                    @if ($user->email)
                                                        <br><small class="text-muted">{{ $user->email }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">User ID: {{ $contract->created_by }} (User
                                                        not found)</span>
                                                @endif
                                            @else
                                                <span class="text-muted">System</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Details Section -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-money-bill-wave me-2"></i>Financial Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="info-item p-4 bg-light rounded">
                                        <label class="text-muted small mb-2 d-block">
                                            <i class="fas fa-money-bill-wave me-1"></i>Contract Value
                                        </label>
                                        <div class="display-6 fw-bold text-dark">
                                            {{ number_format($contract->cost ?? 0, 2, '.', ',') }}
                                            <span class="fs-4">{{ $contract->currency ?? 'TZS' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Risk Assessment Section (if available) -->
                    @if ($contract->likelihood_rating || $contract->impact_if_not_requested || $contract->overall_risk)
                        <div class="card shadow-sm mb-4 border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0 text-dark">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Risk Assessment
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @if ($contract->likelihood_rating)
                                        <div class="col-md-4">
                                            <div class="info-item p-3 bg-light rounded">
                                                <label class="text-muted small mb-1 d-block">
                                                    <i class="fas fa-chart-line me-1"></i>Likelihood Rating
                                                </label>
                                                <div class="fw-bold text-dark">
                                                    {{ $contract->likelihood_rating }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($contract->impact_if_not_requested)
                                        <div class="col-md-4">
                                            <div class="info-item p-3 bg-light rounded">
                                                <label class="text-muted small mb-1 d-block">
                                                    <i class="fas fa-exclamation-circle me-1"></i>Impact if Not Requested
                                                </label>
                                                <div class="fw-bold text-dark">
                                                    {{ $contract->impact_if_not_requested }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($contract->overall_risk)
                                        <div class="col-md-4">
                                            <div class="info-item p-3 bg-light rounded">
                                                <label class="text-muted small mb-1 d-block">
                                                    <i class="fas fa-shield-alt me-1"></i>Overall Risk
                                                </label>
                                                <div class="fw-bold text-dark">
                                                    {{ $contract->overall_risk }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Documents & Attachments Section -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-paperclip me-2"></i>Documents & Attachments
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                @if ($contract->file_path)
                                    <div class="col-md-6">
                                        <a href="{{ route('procurements.contracts.document', $contract->id) }}?type=file_path"
                                            target="_blank"
                                            class="document-card d-block p-3 bg-light rounded text-decoration-none text-dark border hover-shadow">
                                            <div class="d-flex align-items-center">
                                                <div class="document-icon me-3">
                                                    <i class="fas fa-file-pdf fa-2x text-muted"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">Contract Document</div>
                                                    <small class="text-muted">View PDF</small>
                                                </div>
                                                <i class="fas fa-external-link-alt text-muted"></i>
                                            </div>
                                        </a>
                                    </div>
                                @endif
                                @if ($contract->signed_contract_path)
                                    <div class="col-md-6">
                                        <a href="{{ route('procurements.contracts.document', $contract->id) }}?type=signed_contract_path"
                                            target="_blank"
                                            class="document-card d-block p-3 bg-light rounded text-decoration-none text-dark border hover-shadow">
                                            <div class="d-flex align-items-center">
                                                <div class="document-icon me-3">
                                                    <i class="fas fa-file-signature fa-2x text-muted"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">Signed Contract</div>
                                                    <small class="text-muted">View PDF</small>
                                                </div>
                                                <i class="fas fa-external-link-alt text-muted"></i>
                                            </div>
                                        </a>
                                    </div>
                                @endif
                                @if ($contract->terms_conditions_path)
                                    <div class="col-md-6">
                                        <a href="{{ route('procurements.contracts.document', $contract->id) }}?type=terms_conditions_path"
                                            target="_blank"
                                            class="document-card d-block p-3 bg-light rounded text-decoration-none text-dark border hover-shadow">
                                            <div class="d-flex align-items-center">
                                                <div class="document-icon me-3">
                                                    <i class="fas fa-file-contract fa-2x text-muted"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">Terms & Conditions</div>
                                                    <small class="text-muted">View PDF</small>
                                                </div>
                                                <i class="fas fa-external-link-alt text-muted"></i>
                                            </div>
                                        </a>
                                    </div>
                                @endif
                                @if ($contract->sla_document_path)
                                    <div class="col-md-6">
                                        <a href="{{ route('procurements.contracts.document', $contract->id) }}?type=sla_document_path"
                                            target="_blank"
                                            class="document-card d-block p-3 bg-light rounded text-decoration-none text-dark border hover-shadow">
                                            <div class="d-flex align-items-center">
                                                <div class="document-icon me-3">
                                                    <i class="fas fa-file-alt fa-2x text-muted"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">SLA Document</div>
                                                    <small class="text-muted">View PDF</small>
                                                </div>
                                                <i class="fas fa-external-link-alt text-muted"></i>
                                            </div>
                                        </a>
                                    </div>
                                @endif
                                @if ($contract->terms_of_reference_path)
                                    <div class="col-md-6">
                                        <a href="{{ route('procurements.contracts.document', $contract->id) }}?type=terms_of_reference_path"
                                            target="_blank"
                                            class="document-card d-block p-3 bg-light rounded text-decoration-none text-dark border hover-shadow">
                                            <div class="d-flex align-items-center">
                                                <div class="document-icon me-3">
                                                    <i class="fas fa-file-contract fa-2x text-muted"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">Terms of Reference (TOR)</div>
                                                    <small class="text-muted">View Document</small>
                                                </div>
                                                <i class="fas fa-external-link-alt text-muted"></i>
                                            </div>
                                        </a>
                                    </div>
                                @endif
                                @if (
                                    !$contract->file_path &&
                                        !$contract->signed_contract_path &&
                                        !$contract->terms_conditions_path &&
                                        !$contract->sla_document_path &&
                                        !$contract->terms_of_reference_path)
                                    <div class="col-12">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>No documents uploaded
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>


                    @php
                        $user = auth()->user();
                        $isHecMember = $user && $user->hasAnyRole(['coo', 'cfo', 'cms', 'crhdo']);

                        // Direct check for HEC approval - simplified and reliable
                        $canHecApprove =
                            $isHecMember &&
                            $contract->approval_stage === 'hec' &&
                            $contract->current_approver_id == $user->id &&
                            !in_array(strtolower($contract->status ?? ''), ['terminated', 'rejected']);
                    @endphp


                    @if ($canHecApprove)
                        <!-- HEC Member Approval Section -->
                        <div class="mb-4">
                            <div class="card border-0">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0 text-dark">
                                        <i class="fas fa-user-check me-2"></i>HEC Member Review & Approval
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        $isRenewal =
                                            $contract->renewal_status === 'pending' ||
                                            $contract->lifecycle_stage === 'renewal';
                                    @endphp
                                    <div class="alert alert-light border">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Action Required:</strong> This contract{{ $isRenewal ? ' renewal' : '' }}
                                        has been
                                        forwarded to you for review and rating. Please rate the contract and approve or
                                        reject it.
                                        @if ($isRenewal)
                                            <br><small><strong>Note:</strong> If you reject this renewal, the contract will
                                                be
                                                terminated with the reason you provide.</small>
                                        @endif
                                    </div>

                                    <form action="{{ route('procurements.contracts.approve', $contract->id) }}"
                                        method="POST" id="hecApprovalForm">
                                        @csrf

                                        @if ($contract->line_manager_rating)
                                            <div class="mb-4 p-3 bg-light rounded border">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <label class="text-muted small mb-1 d-block">
                                                            <i class="fas fa-user-tie me-1"></i>Line Manager Rating
                                                        </label>
                                                        <div class="d-flex align-items-center">
                                                            <div class="rating-display me-2">
                                                                @for ($i = 1; $i <= 5; $i++)
                                                                    @if ($i <= $contract->line_manager_rating)
                                                                        <i class="fas fa-star text-muted"></i>
                                                                    @else
                                                                        <i class="far fa-star text-muted"></i>
                                                                    @endif
                                                                @endfor
                                                            </div>
                                                            <strong
                                                                class="text-dark">{{ number_format($contract->line_manager_rating, 1) }}/5</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="mb-4">
                                            <label for="hec_rating" class="form-label mb-3">
                                                <strong class="d-block mb-1">Contract Rating <span
                                                        class="text-danger">*</span></strong>
                                                <small class="text-muted">Rate the performance of this contract (1-5
                                                    scale)</small>
                                            </label>

                                            <div class="rating-container p-4 bg-light rounded border">
                                                <div class="star-rating mb-3" data-rating-id="hec_rating">
                                                    <input type="hidden" id="hec_rating" name="hec_rating"
                                                        value="" required>
                                                    <div class="stars d-flex justify-content-center gap-2">
                                                        <span class="star" data-value="1" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Poor">
                                                            <i class="far fa-star fa-2x"></i>
                                                        </span>
                                                        <span class="star" data-value="2" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Adequate">
                                                            <i class="far fa-star fa-2x"></i>
                                                        </span>
                                                        <span class="star" data-value="3" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Good">
                                                            <i class="far fa-star fa-2x"></i>
                                                        </span>
                                                        <span class="star" data-value="4" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Very Good">
                                                            <i class="far fa-star fa-2x"></i>
                                                        </span>
                                                        <span class="star" data-value="5" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Excellent">
                                                            <i class="far fa-star fa-2x"></i>
                                                        </span>
                                                    </div>
                                                    <div class="rating-text text-center mt-3">
                                                        <small class="text-muted">Click on a star to select your
                                                            rating</small>
                                                    </div>
                                                </div>
                                                @error('hec_rating')
                                                    <div class="text-danger text-center mt-2">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="hec_comments" class="form-label">
                                                <strong>Comments</strong>
                                                <small class="text-muted">(Optional)</small>
                                            </label>
                                            <textarea class="form-control" id="hec_comments" name="hec_comments" rows="4"
                                                placeholder="Add any comments about your review...">{{ old('hec_comments') }}</textarea>
                                            @error('hec_comments')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-secondary">
                                                <i class="fas fa-check-circle me-1"></i> Approve & Forward to Procurement
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#rejectContractModal">
                                                <i class="fas fa-times-circle me-1"></i>
                                                {{ $isRenewal ? 'Reject Renewal (Terminate)' : 'Reject Contract' }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Reject Contract Modal -->
                        <div class="modal fade" id="rejectContractModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-white border-bottom">
                                        <h5 class="modal-title text-dark">
                                            <i class="fas fa-times-circle me-2"></i>Reject Contract
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="{{ route('procurements.contracts.reject', $contract->id) }}"
                                        method="POST">
                                        @csrf
                                        <div class="modal-body">
                                            @php
                                                $isRenewal =
                                                    $contract->renewal_status === 'pending' ||
                                                    $contract->lifecycle_stage === 'renewal';
                                            @endphp
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Warning:</strong> Rejecting this
                                                {{ $isRenewal ? 'renewal' : 'contract' }}
                                                will
                                                {{ $isRenewal ? 'terminate the renewal and mark the contract as terminated' : 'terminate the contract' }}.
                                                This action cannot be undone.
                                            </div>
                                            <div class="mb-3">
                                                <label for="rejection_reason" class="form-label">
                                                    <strong>{{ $isRenewal ? 'Rejection/Termination' : 'Rejection' }} Reason
                                                        <span class="text-danger">*</span></strong>
                                                </label>
                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required
                                                    placeholder="Please provide a reason for {{ $isRenewal ? 'rejecting this renewal' : 'rejecting this contract' }}...">{{ old('rejection_reason') }}</textarea>
                                                @error('rejection_reason')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-outline-secondary">
                                                <i class="fas fa-times-circle me-1"></i> Confirm Rejection
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    @php
                        $user = auth()->user();
                        $isLineManager = $user && $user->hasRole('line-manager');

                        // Check if contract is pending Line Manager review
                        $canLineManagerApprove = false;
                        if ($isLineManager && $user) {
                            $canLineManagerApprove =
                                $contract->approval_stage === 'line_manager' &&
                                $contract->current_approver_id == $user->id &&
                                !in_array(strtolower($contract->status ?? ''), ['terminated', 'rejected']);
                        }
                    @endphp

                    @if ($canLineManagerApprove)
                        <!-- Line Manager Approval Section -->
                        <div class="mb-4">
                            <div class="card border-0">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0 text-dark">
                                        <i class="fas fa-user-check me-2"></i>Line Manager Review & Approval
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        $isRenewal =
                                            $contract->renewal_status === 'pending' ||
                                            $contract->lifecycle_stage === 'renewal';
                                    @endphp
                                    <div class="alert alert-light border">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Action Required:</strong> This contract{{ $isRenewal ? ' renewal' : '' }}
                                        has been
                                        forwarded to you for review and rating. Please rate the contract and choose an
                                        action (Renew,
                                        Terminate, or Hold).
                                    </div>

                                    <form action="{{ route('procurements.contracts.approve', $contract->id) }}"
                                        method="POST" id="lineManagerApprovalForm">
                                        @csrf

                                        <div class="mb-4">
                                            <label for="contract_rating" class="form-label mb-3">
                                                <strong class="d-block mb-1">Contract Rating <span
                                                        class="text-danger">*</span></strong>
                                                <small class="text-muted">Rate the performance of this contract (1-5
                                                    scale)</small>
                                            </label>

                                            <div class="rating-container p-4 bg-light rounded border">
                                                <div class="star-rating mb-3" data-rating-id="contract_rating">
                                                    <input type="hidden" id="contract_rating" name="contract_rating"
                                                        value="" required>
                                                    <div class="stars d-flex justify-content-center gap-2">
                                                        <span class="star" data-value="1" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Poor">
                                                            <i class="far fa-star fa-2x"></i>
                                                        </span>
                                                        <span class="star" data-value="2" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Adequate">
                                                            <i class="far fa-star fa-2x"></i>
                                                        </span>
                                                        <span class="star" data-value="3" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Good">
                                                            <i class="far fa-star fa-2x"></i>
                                                        </span>
                                                        <span class="star" data-value="4" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Very Good">
                                                            <i class="far fa-star fa-2x"></i>
                                                        </span>
                                                        <span class="star" data-value="5" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="Excellent">
                                                            <i class="far fa-star fa-2x"></i>
                                                        </span>
                                                    </div>
                                                    <div class="rating-text text-center mt-3">
                                                        <small class="text-muted">Click on a star to select your
                                                            rating</small>
                                                    </div>
                                                </div>
                                                @error('contract_rating')
                                                    <div class="text-danger text-center mt-2">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="contract_action" class="form-label">
                                                <strong>Action <span class="text-danger">*</span></strong>
                                            </label>
                                            <select class="form-control" id="contract_action" name="contract_action"
                                                required>
                                                <option value="">Select Action</option>
                                                <option value="renew">Renew Contract</option>
                                                <option value="terminate">Terminate Contract</option>
                                                <option value="hold">Hold Contract</option>
                                            </select>
                                            @error('contract_action')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="remark" class="form-label">
                                                <strong>Comments</strong>
                                                <small class="text-muted">(Optional)</small>
                                            </label>
                                            <textarea class="form-control" id="remark" name="remark" rows="3"
                                                placeholder="Add any comments about your review...">{{ old('remark') }}</textarea>
                                            @error('remark')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-secondary">
                                                <i class="fas fa-check-circle me-1"></i> Submit Review
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    @php
                        $user = auth()->user();
                        $isProcurementOfficer = $user && $user->hasRole('procurement-officer');

                        // Check if contract is pending Procurement processing
                        $canProcurementApprove = false;
                        if ($isProcurementOfficer && $user) {
                            $canProcurementApprove =
                                $contract->approval_stage === 'procurement' &&
                                $contract->current_approver_id == $user->id &&
                                !in_array(strtolower($contract->status ?? ''), ['terminated', 'rejected']);
                        }
                    @endphp

                    @if ($canProcurementApprove)
                        <!-- Procurement Officer Processing Section -->
                        <div class="mb-4">
                            <div class="card border-0">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0 text-dark">
                                        <i class="fas fa-tasks me-2"></i>Procurement Officer - Finalize Contract
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        $isRenewal =
                                            $contract->renewal_status === 'pending' ||
                                            $contract->lifecycle_stage === 'renewal';
                                    @endphp
                                    <div class="alert alert-light border">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Ready to Process:</strong> This contract{{ $isRenewal ? ' renewal' : '' }}
                                        has been
                                        approved by Line Manager and HEC. Please finalize the processing to mark it as
                                        Active.
                                    </div>

                                    @if ($contract->line_manager_rating)
                                        <div class="mb-2">
                                            <strong>Line Manager Rating:</strong>
                                            <span
                                                class="badge bg-secondary ms-2">{{ number_format($contract->line_manager_rating, 1) }}/5</span>
                                        </div>
                                    @endif

                                    @if ($contract->hec_rating)
                                        <div class="mb-3">
                                            <strong>HEC Rating:</strong>
                                            <span
                                                class="badge bg-secondary ms-2">{{ number_format($contract->hec_rating, 1) }}/5</span>
                                        </div>
                                    @endif

                                    @if ($contract->evaluation_score)
                                        <div class="mb-3">
                                            <strong>Overall Evaluation Score:</strong>
                                            <span
                                                class="badge bg-secondary ms-2">{{ number_format($contract->evaluation_score, 2) }}/5</span>
                                        </div>
                                    @endif

                                    <form action="{{ route('procurements.contracts.approve', $contract->id) }}"
                                        method="POST" id="procurementApprovalForm" enctype="multipart/form-data">
                                        @csrf

                                        <h6 class="text-dark border-bottom pb-2 mb-3">
                                            <i class="fas fa-edit me-2"></i>Update Contract Details
                                        </h6>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="procurement_start_date" class="form-label">
                                                    <strong>Start Date</strong>
                                                </label>
                                                <input type="date" class="form-control" id="procurement_start_date"
                                                    name="start_date"
                                                    value="{{ old('start_date', $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '') }}">
                                                @error('start_date')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="procurement_end_date" class="form-label">
                                                    <strong>End Date</strong>
                                                </label>
                                                <input type="date" class="form-control" id="procurement_end_date"
                                                    name="end_date"
                                                    value="{{ old('end_date', $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '') }}">
                                                @error('end_date')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="procurement_duration_months" class="form-label">
                                                    <strong>Duration (Months)</strong>
                                                </label>
                                                <input type="number" class="form-control"
                                                    id="procurement_duration_months" name="duration_months"
                                                    min="1"
                                                    value="{{ old('duration_months', $contract->duration_months ?? '') }}">
                                                @error('duration_months')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="procurement_cost" class="form-label">
                                                    <strong>Cost</strong>
                                                </label>
                                                <input type="number" class="form-control" id="procurement_cost"
                                                    name="cost" step="0.01" min="0"
                                                    value="{{ old('cost', $contract->cost ?? '') }}">
                                                @error('cost')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="procurement_currency" class="form-label">
                                                    <strong>Currency</strong>
                                                </label>
                                                <select class="form-control" id="procurement_currency" name="currency">
                                                    <option value="TZS"
                                                        {{ old('currency', $contract->currency ?? 'TZS') == 'TZS' ? 'selected' : '' }}>
                                                        TZS</option>
                                                    <option value="UGX"
                                                        {{ old('currency', $contract->currency ?? 'TZS') == 'UGX' ? 'selected' : '' }}>
                                                        UGX</option>
                                                    <option value="USD"
                                                        {{ old('currency', $contract->currency ?? 'TZS') == 'USD' ? 'selected' : '' }}>
                                                        USD</option>
                                                    <option value="EUR"
                                                        {{ old('currency', $contract->currency ?? 'TZS') == 'EUR' ? 'selected' : '' }}>
                                                        EUR</option>
                                                </select>
                                                @error('currency')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="procurement_vendor_id" class="form-label">
                                                    <strong>Vendor</strong>
                                                </label>
                                                <select class="form-control" id="procurement_vendor_id" name="vendor_id">
                                                    <option value="">Select Vendor</option>
                                                    @foreach ($vendors ?? [] as $vendor)
                                                        <option value="{{ $vendor->id }}"
                                                            {{ old('vendor_id', $contract->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                                            {{ $vendor->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('vendor_id')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="procurement_contract_number" class="form-label">
                                                    <strong>Contract Number</strong>
                                                </label>
                                                <input type="text" class="form-control"
                                                    id="procurement_contract_number" name="contract_number"
                                                    value="{{ old('contract_number', $contract->contract_number ?? '') }}">
                                                @error('contract_number')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="procurement_contract_type" class="form-label">
                                                    <strong>Contract Type</strong>
                                                </label>
                                                <select class="form-control" id="procurement_contract_type"
                                                    name="contract_type">
                                                    <option value="">Select Contract Type</option>
                                                    <option value="Services"
                                                        {{ old('contract_type', $contract->contract_type ?? '') == 'Services' ? 'selected' : '' }}>
                                                        Services
                                                    </option>
                                                    <option value="Goods"
                                                        {{ old('contract_type', $contract->contract_type ?? '') == 'Goods' ? 'selected' : '' }}>
                                                        Goods
                                                    </option>
                                                    <option value="Services and Goods"
                                                        {{ old('contract_type', $contract->contract_type ?? '') == 'Services and Goods' ? 'selected' : '' }}>
                                                        Services and Goods
                                                    </option>
                                                    <option value="Consultants"
                                                        {{ old('contract_type', $contract->contract_type ?? '') == 'Consultants' ? 'selected' : '' }}>
                                                        Consultants
                                                    </option>
                                                </select>
                                                @error('contract_type')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="terms_of_reference" class="form-label">
                                                <strong>Terms of Reference (TOR)</strong>
                                                <small class="text-muted">(Required for Final Approval)</small>
                                            </label>
                                            <input type="file" class="form-control" id="terms_of_reference"
                                                name="terms_of_reference" accept=".pdf,.doc,.docx">
                                            <small class="form-text text-muted">
                                                Upload the Terms of Reference document. This will be used as a reference for
                                                the new agreement and final approval.
                                                Max file size: 5MB. Accepted formats: PDF, DOC, DOCX
                                            </small>
                                            @if ($contract->terms_of_reference_path)
                                                <div class="mt-2">
                                                    <small class="text-muted">Current TOR: </small>
                                                    <a href="{{ route('procurements.contracts.document', $contract->id) }}?type=terms_of_reference_path"
                                                        target="_blank" class="text-decoration-none">
                                                        <i class="fas fa-file-pdf me-1"></i>View Current TOR
                                                    </a>
                                                </div>
                                            @endif
                                            @error('terms_of_reference')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="procurement_remark" class="form-label">
                                                <strong>Processing Notes</strong>
                                                <small class="text-muted">(Optional)</small>
                                            </label>
                                            <textarea class="form-control" id="procurement_remark" name="remark" rows="3"
                                                placeholder="Add any notes about the processing...">{{ old('remark') }}</textarea>
                                            @error('remark')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-secondary">
                                                <i class="fas fa-check-circle me-1"></i> Finalize & Mark as Active
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    @push('styles')
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

            /* Star Rating Styles */
            .star-rating {
                margin: 10px 0;
            }

            .star-rating .stars {
                display: flex;
                gap: 8px;
            }

            .star-rating .star {
                cursor: pointer;
                color: #6c757d;
                transition: all 0.2s ease;
            }

            .star-rating .star:hover {
                color: #495057;
                transform: scale(1.1);
            }

            .star-rating .star i {
                transition: all 0.2s ease;
            }

            .star-rating .star:hover i {
                transform: scale(1.15);
            }

            .rating-text {
                font-size: 0.875rem;
            }

            /* Rating Container Styles */
            .rating-container {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
            }

            .rating-display {
                font-size: 1.2rem;
            }

            .rating-display .fa-star {
                margin-right: 2px;
            }

            /* Modern Card Styles */
            .card {
                transition: transform 0.2s, box-shadow 0.2s;
            }

            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            }

            .info-item {
                transition: all 0.3s ease;
            }

            .info-item:hover {
                background-color: #f8f9fa !important;
                transform: translateX(5px);
            }

            /* Document Card Styles */
            .document-card {
                transition: all 0.3s ease;
            }

            .document-card:hover {
                background-color: #f8f9fa !important;
                transform: translateY(-3px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
                border-color: #6c757d !important;
            }

            .hover-shadow:hover {
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            }

            /* Timeline Styles */
            .timeline {
                position: relative;
                padding-left: 0;
            }

            .timeline-item {
                position: relative;
            }

            .timeline-item:not(:last-child)::after {
                content: '';
                position: absolute;
                left: 15px;
                top: 50px;
                width: 2px;
                height: calc(100% + 1rem);
                background: linear-gradient(to bottom, #dee2e6, transparent);
            }

            .timeline-marker {
                position: relative;
                z-index: 1;
            }

            .timeline-badge {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
                transition: transform 0.3s ease;
            }

            .timeline-badge:hover {
                transform: scale(1.1);
            }


            /* Status Badge Enhancements */
            .badge {
                font-weight: 500;
                padding: 0.5em 0.75em;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .timeline-item::after {
                    display: none;
                }

                .document-card {
                    margin-bottom: 1rem;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize star rating for HEC approval
                const hecRatingContainer = document.querySelector('.star-rating[data-rating-id="hec_rating"]');
                if (hecRatingContainer) {
                    initStarRating(hecRatingContainer);
                }

                // Initialize star rating for Line Manager approval
                const lineManagerRatingContainer = document.querySelector(
                    '.star-rating[data-rating-id="contract_rating"]');
                if (lineManagerRatingContainer) {
                    initStarRating(lineManagerRatingContainer);
                }

                function initStarRating(container) {
                    const stars = container.querySelectorAll('.star');
                    const hiddenInput = container.querySelector('input[type="hidden"]');
                    const ratingText = container.querySelector('.rating-text');
                    const ratingLabels = {
                        1: 'Poor',
                        2: 'Adequate',
                        3: 'Good',
                        4: 'Very Good',
                        5: 'Excellent'
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

                // Form validation for HEC approval
                const hecApprovalForm = document.getElementById('hecApprovalForm');
                if (hecApprovalForm) {
                    hecApprovalForm.addEventListener('submit', function(e) {
                        const ratingInput = document.getElementById('hec_rating');
                        if (!ratingInput || !ratingInput.value) {
                            e.preventDefault();
                            alert('Please select a contract rating before submitting.');
                            return false;
                        }
                    });
                }

                // Form validation for Line Manager approval
                const lineManagerApprovalForm = document.getElementById('lineManagerApprovalForm');
                if (lineManagerApprovalForm) {
                    lineManagerApprovalForm.addEventListener('submit', function(e) {
                        const ratingInput = document.getElementById('contract_rating');
                        const actionInput = document.getElementById('contract_action');
                        if (!ratingInput || !ratingInput.value) {
                            e.preventDefault();
                            alert('Please select a contract rating before submitting.');
                            return false;
                        }
                        if (!actionInput || !actionInput.value) {
                            e.preventDefault();
                            alert('Please select an action (Renew, Terminate, or Hold) before submitting.');
                            return false;
                        }
                    });
                }

                // Auto-calculate duration_months when start_date or end_date changes in Procurement form
                const procurementStartDate = document.getElementById('procurement_start_date');
                const procurementEndDate = document.getElementById('procurement_end_date');
                const procurementDurationMonths = document.getElementById('procurement_duration_months');

                if (procurementStartDate && procurementEndDate && procurementDurationMonths) {
                    function calculateDuration() {
                        const startDate = procurementStartDate.value;
                        const endDate = procurementEndDate.value;

                        if (startDate && endDate) {
                            const start = new Date(startDate);
                            const end = new Date(endDate);

                            if (end >= start) {
                                // Calculate difference in months
                                const years = end.getFullYear() - start.getFullYear();
                                const months = end.getMonth() - start.getMonth();
                                const totalMonths = years * 12 + months;

                                // If duration is empty or user hasn't manually changed it, auto-calculate
                                if (!procurementDurationMonths.dataset.manual || procurementDurationMonths.dataset
                                    .manual === 'false') {
                                    procurementDurationMonths.value = totalMonths > 0 ? totalMonths : '';
                                }
                            }
                        }
                    }

                    procurementStartDate.addEventListener('change', calculateDuration);
                    procurementEndDate.addEventListener('change', calculateDuration);

                    // Track manual changes to duration
                    procurementDurationMonths.addEventListener('input', function() {
                        this.dataset.manual = 'true';
                    });
                }
            });
        </script>
    @endpush
@endsection
