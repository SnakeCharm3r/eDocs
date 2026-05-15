@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
@endsection
@push('styles')
<style>
    .contract-banner { border-left: 5px solid #dee2e6; }
    .contract-banner.status-active    { border-left-color: #28a745; }
    .contract-banner.status-expired   { border-left-color: #dc3545; }
    .contract-banner.status-expiring  { border-left-color: #ffc107; }
    .contract-banner.status-draft     { border-left-color: #6c757d; }
    .contract-banner.status-terminated{ border-left-color: #343a40; }
    .stat-pill { background: #f8f9fa; border-radius: 0.5rem; padding: 0.6rem 1rem; }
    .info-item { border-radius: 0.5rem; }
    .section-card { border:0; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.07); margin-bottom:.75rem; }
    .section-card .card-header { background:#f8fdf9; border-bottom:1px solid #d1e7dd; border-radius:10px 10px 0 0!important; padding:.5rem 1rem; }
    .section-card .card-header h6 { margin:0; font-size:.82rem; font-weight:600; color:#198754; }
</style>
@endpush
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @php
                $user         = auth()->user();
                $canEdit      = $user && $user->hasAnyRole(['procurement-officer', 'hr', 'super-admin']);
                $st           = $contract->status ?? 'draft';
                $bannerClass  = match(true) {
                    $st === 'active'                                       => 'status-active',
                    in_array($st, ['expired','terminated','rejected'])     => 'status-expired',
                    $st === 'soonToExpire'                                 => 'status-expiring',
                    $st === 'draft'                                        => 'status-draft',
                    default                                                => '',
                };
                $stColors  = ['active'=>'success','expired'=>'danger','soonToExpire'=>'warning','draft'=>'secondary','in_progress'=>'info','renewed'=>'primary','terminated'=>'dark'];
                $stColor   = $stColors[$st] ?? 'secondary';
                $stLabel   = $st === 'soonToExpire' ? 'Soon to Expire' : ucfirst(str_replace('_',' ',$st));

                $vEnd  = $contract->end_date  ? \Carbon\Carbon::parse($contract->end_date)  : null;
                $vStart= $contract->start_date? \Carbon\Carbon::parse($contract->start_date): null;
                $vDays = $vEnd ? \Carbon\Carbon::now()->diffInDays($vEnd, false) : null;
                if ($vDays !== null) {
                    if ($vDays < 0)       { $vTxt = 'Expired '.abs($vDays).' days ago';   $vCls = 'danger'; }
                    elseif ($vDays == 0)  { $vTxt = 'Expires today';                       $vCls = 'danger'; }
                    elseif ($vDays <= 30) { $vTxt = $vDays.' days left';                   $vCls = 'warning'; }
                    elseif ($vDays <= 365){ $vTxt = round($vDays/30).' months left';       $vCls = 'success'; }
                    else { $vYr=floor($vDays/365); $vMo=round(($vDays%365)/30); $vTxt=$vYr.'y '.($vMo>0?$vMo.'m ':'').'left'; $vCls='success'; }
                } else { $vTxt = 'No end date'; $vCls = 'secondary'; }
            @endphp

            {{-- Header Card --}}
            <div class="card shadow-sm border-0 mb-3 contract-banner {{ $bannerClass }}">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start gap-2 mb-1 flex-wrap">
                        <span class="badge bg-{{ $stColor }}">{{ $stLabel }}</span>
                        @if ($contract->parent_contract_id)
                            <span class="badge bg-secondary">Renewal Term {{ $contract->renewal_term_number ?? 2 }}</span>
                        @elseif($contract->renewals && $contract->renewals->count() > 0)
                            <span class="badge bg-secondary">First Contract</span>
                            <span class="text-muted small">{{ $contract->renewals->count() }} renewal(s)</span>
                        @endif
                    </div>
                    <h6 class="mb-1 text-dark fw-semibold" style="font-size:.92rem;">{{ $contract->title }}</h6>
                    <div class="d-flex flex-wrap gap-3 text-muted" style="font-size:.78rem;" class="mb-2">
                        @if ($contract->contract_number)
                            <span><i class="fas fa-hashtag me-1"></i>#{{ $contract->contract_number }}</span>
                        @endif
                        @if ($contract->division)
                            <span><i class="fas fa-sitemap me-1"></i>{{ $contract->division->name }}</span>
                        @endif
                        @if ($contract->department)
                            <span><i class="fas fa-briefcase me-1"></i>{{ $contract->department->dept_name }}</span>
                        @endif
                        @if ($contract->vendor)
                            <span><i class="fas fa-building me-1"></i>{{ $contract->vendor->name }}</span>
                        @endif
                    </div>
                    {{-- Compact stats inline --}}
                    <div class="d-flex flex-wrap gap-3" style="font-size:.8rem;">
                        <span class="text-muted"><i class="fas fa-money-bill-wave me-1"></i><strong class="text-dark">{{ number_format($contract->cost ?? 0, 0, '.', ',') }}</strong> {{ $contract->currency ?? 'TZS' }}</span>
                        <span class="text-muted"><i class="fas fa-calendar-check me-1"></i>{{ $vStart ? $vStart->format('d M Y') : '—' }} → {{ $vEnd ? $vEnd->format('d M Y') : '—' }}</span>
                        <span class="text-{{ $vCls }} fw-semibold"><i class="fas fa-clock me-1"></i>{{ $vTxt }}</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8 order-lg-1 mb-4">
                    <div>{{-- inner wrapper for seamless content flow --}}</div>
                    <!-- Contract Information Section -->
                    <div class="card section-card">
                        <div class="card-header">
                            <h6><i class="fas fa-info-circle me-2"></i>Contract Information</h6>
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
                        <div class="card section-card">
                            <div class="card-header">
                                <h6><i class="fas fa-history me-2"></i>Contract History &amp; Financial Details</h6>
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
                                                            {{ number_format(min((float) $contractItem->evaluation_score, 5), 2) }}/5
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        @php
                                            $prevItem = null;
                                            if (isset($contractChain) && method_exists($contractChain, 'values')) {
                                                $idx = $loop->index ?? null;
                                                $prevItem = is_int($idx) && $idx > 0 ? ($contractChain->values()[$idx - 1] ?? null) : null;
                                            }

                                            $fmtDate = function ($d) {
                                                return $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '-';
                                            };
                                            $fmtMoney = function ($v, $cur) {
                                                return number_format((float) ($v ?? 0), 2) . ' ' . ($cur ?? 'TZS');
                                            };
                                            $vendorName = function ($c) {
                                                return $c && $c->vendor ? ($c->vendor->name ?? '-') : '-';
                                            };
                                            $docVal = function ($p) {
                                                return !empty($p) ? 'Yes' : 'No';
                                            };

                                            $changes = [];
                                            if ($prevItem) {
                                                $changes = [
                                                    'Vendor' => [$vendorName($prevItem), $vendorName($contractItem)],
                                                    'Contract type' => [$prevItem->contract_type ?? '-', $contractItem->contract_type ?? '-'],
                                                    'Start date' => [$fmtDate($prevItem->start_date ?? null), $fmtDate($contractItem->start_date ?? null)],
                                                    'End date' => [$fmtDate($prevItem->end_date ?? null), $fmtDate($contractItem->end_date ?? null)],
                                                    'Duration (months)' => [$prevItem->duration_months ?? '-', $contractItem->duration_months ?? '-'],
                                                    'Value' => [$fmtMoney($prevItem->cost ?? 0, $prevItem->currency ?? 'TZS'), $fmtMoney($contractItem->cost ?? 0, $contractItem->currency ?? 'TZS')],
                                                    'Main document' => [$docVal($prevItem->file_path ?? null), $docVal($contractItem->file_path ?? null)],
                                                    'Signed contract' => [$docVal($prevItem->signed_contract_path ?? null), $docVal($contractItem->signed_contract_path ?? null)],
                                                    'TOR document' => [$docVal($prevItem->terms_of_reference_path ?? null), $docVal($contractItem->terms_of_reference_path ?? null)],
                                                    'SLA document' => [$docVal($prevItem->sla_document_path ?? null), $docVal($contractItem->sla_document_path ?? null)],
                                                    'Terms & conditions' => [$docVal($prevItem->terms_conditions_path ?? null), $docVal($contractItem->terms_conditions_path ?? null)],
                                                ];
                                            }
                                        @endphp

                                        @if ($prevItem)
                                            <div class="mt-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="text-dark">Changes from previous term</strong>
                                                    <small class="text-muted">Compare Term {{ $prevItem->renewal_term_number ?? 1 }} → Term {{ $termNumber }}</small>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 30%;">Field</th>
                                                                <th style="width: 35%;">Previous</th>
                                                                <th style="width: 35%;">New</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($changes as $label => $vals)
                                                                @php
                                                                    $oldVal = (string) ($vals[0] ?? '-');
                                                                    $newVal = (string) ($vals[1] ?? '-');
                                                                    $changed = trim($oldVal) !== trim($newVal);
                                                                @endphp
                                                                <tr class="{{ $changed ? 'table-warning' : '' }}">
                                                                    <td class="fw-semibold">{{ $label }}</td>
                                                                    <td>{{ $oldVal }}</td>
                                                                    <td>{{ $newVal }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        Tip: use this to track what was updated during renewal (dates, value, vendor, documents).
                                                    </small>
                                                </div>
                                            </div>
                                        @endif
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
                    <div class="card section-card">
                        <div class="card-header">
                            <h6><i class="fas fa-users me-2"></i>Contract Parties</h6>
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
                    <div class="card section-card">
                        <div class="card-header">
                            <h6><i class="fas fa-coins me-2"></i>Financial Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="info-item p-4 bg-light rounded">
                                        <label class="text-muted small mb-2 d-block">
                                            <i class="fas fa-money-bill-wave me-1"></i>Contract Value
                                        </label>
                                        <div class="fw-bold text-dark" style="font-size:1.3rem;">
                                            {{ number_format($contract->cost ?? 0, 2, '.', ',') }}
                                            <span class="text-muted" style="font-size:.9rem;">{{ $contract->currency ?? 'TZS' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Risk Assessment Section (if available) -->
                    @if ($contract->likelihood_rating || $contract->impact_if_not_requested || $contract->overall_risk)
                        <div class="card section-card">
                            <div class="card-header">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Risk Assessment</h6>
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
                    <div class="card section-card">
                        <div class="card-header">
                            <h6><i class="fas fa-paperclip me-2"></i>Documents &amp; Attachments</h6>
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
                                                    <i class="fas fa-file-pdf fa-lg text-muted"></i>
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
                                                    <i class="fas fa-file-signature fa-lg text-muted"></i>
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
                                                    <i class="fas fa-file-contract fa-lg text-muted"></i>
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
                                                    <i class="fas fa-file-alt fa-lg text-muted"></i>
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
                                                    <i class="fas fa-file-contract fa-lg text-muted"></i>
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
                        $isHecMember = $user && $user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro']);

                        // Direct check for HEC approval - simplified and reliable
                        $canHecApprove =
                            $isHecMember &&
                            $contract->approval_stage === 'hec' &&
                            $contract->current_approver_id == $user->id &&
                            !in_array(strtolower($contract->status ?? ''), ['terminated', 'rejected']);
                    @endphp


                    @if ($canHecApprove)
                        <!-- HEC Member Approval Section -->
                        <div class="mb-4" id="approvalSection">
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

                                        // Latest Line Manager workflow remark (includes comments if provided)
                                        $lmLastRemark = null;
                                        try {
                                            $histories = null;
                                            if (method_exists($contract, 'workflow') && $contract->relationLoaded('workflow') && $contract->workflow && $contract->workflow->relationLoaded('histories')) {
                                                $histories = $contract->workflow->histories;
                                            } elseif (method_exists($contract, 'workflows') && $contract->relationLoaded('workflows') && $contract->workflows) {
                                                $histories = $contract->workflows->flatMap(fn ($wf) => $wf->histories ?? collect());
                                            }

                                            if ($histories) {
                                                $lmLastRemark = $histories
                                                    ->where('step_name', 'Line Manager')
                                                    ->sortByDesc('created_at')
                                                    ->first()
                                                    ?->remark;
                                            }
                                        } catch (\Throwable $e) {
                                            $lmLastRemark = null;
                                        }
                                    @endphp
                                    <div class="alert alert-light border py-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Rate this contract{{ $isRenewal ? ' renewal' : '' }} and <strong>Approve</strong> or <strong>Reject</strong> it.
                                        @if (!empty($lmLastRemark))
                                            <div class="mt-2 p-2 bg-white border rounded small text-dark">
                                                <span class="text-muted fw-semibold">LM note:</span> {{ $lmLastRemark }}
                                            </div>
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
                                                                @php
                                                                    $lmRating = (int) ($contract->line_manager_rating ?? 0);
                                                                @endphp
                                                                @for ($i = 1; $i <= 5; $i++)
                                                                    @if ($i <= min($lmRating, 5))
                                                                        <i class="fas fa-star text-muted"></i>
                                                                    @else
                                                                        <i class="far fa-star text-muted"></i>
                                                                    @endif
                                                                @endfor
                                                            </div>
                                                            <strong
                                                                class="text-dark">{{ number_format(min($lmRating, 5), 1) }}/5</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

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
                        <div class="mb-4" id="approvalSection">
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
                                                        value="{{ old('contract_rating') }}" required>
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
                                                        <small class="text-muted">Select 1–5 stars to set your rating.</small>
                                                    </div>
                                                    <div class="text-center mt-2">
                                                        <small class="text-muted">1 Poor • 2 Adequate • 3 Good • 4 Very Good • 5 Excellent</small>
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
                        <div class="mb-4" id="approvalSection">
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

                                        // Latest Line Manager workflow remark (reuse if available)
                                        $lmLastRemark = $lmLastRemark ?? null;
                                        $hecLastRemark = null;
                                        try {
                                            $histories = null;
                                            if (method_exists($contract, 'workflow') && $contract->relationLoaded('workflow') && $contract->workflow && $contract->workflow->relationLoaded('histories')) {
                                                $histories = $contract->workflow->histories;
                                            } elseif (method_exists($contract, 'workflows') && $contract->relationLoaded('workflows') && $contract->workflows) {
                                                $histories = $contract->workflows->flatMap(fn ($wf) => $wf->histories ?? collect());
                                            }
                                            if ($histories) {
                                                if ($lmLastRemark === null) {
                                                    $lmLastRemark = $histories->where('step_name', 'Line Manager')->sortByDesc('created_at')->first()?->remark;
                                                }
                                                $hecLastRemark = $histories->where('step_name', 'HEC Member')->sortByDesc('created_at')->first()?->remark;
                                            }
                                        } catch (\Throwable $e) {
                                            $lmLastRemark = null;
                                            $hecLastRemark = null;
                                        }
                                    @endphp
                                    <div class="alert alert-light border">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Ready to Process:</strong> This contract{{ $isRenewal ? ' renewal' : '' }}
                                        has been
                                        approved by Line Manager and HEC. Please finalize the processing to mark it as
                                        Active.
                                    </div>

                                    @if (!empty($lmLastRemark))
                                        <div class="mb-2">
                                            <strong class="d-block small text-muted">Line Manager:</strong>
                                            <div class="small text-dark mt-1 p-2 bg-light border rounded">
                                                {{ $lmLastRemark }}
                                            </div>
                                        </div>
                                    @endif

                                    @if (!empty($hecLastRemark))
                                        <div class="mb-3">
                                            <strong class="d-block small text-muted">HEC Member:</strong>
                                            <div class="small text-dark mt-1 p-2 bg-light border rounded">
                                                {{ $hecLastRemark }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($contract->line_manager_rating)
                                        <div class="mb-2">
                                            <strong>Line Manager Rating:</strong>
                                            <span
                                                class="badge bg-secondary ms-2">{{ number_format(min((float) $contract->line_manager_rating, 5), 1) }}/5</span>
                                        </div>
                                    @endif

                                    @if ($contract->hec_rating)
                                        <div class="mb-3">
                                            <strong>HEC Rating:</strong>
                                            <span
                                                class="badge bg-secondary ms-2">{{ number_format(min((float) $contract->hec_rating, 5), 1) }}/5</span>
                                        </div>
                                    @endif

                                    @if ($contract->evaluation_score)
                                        <div class="mb-3">
                                            <strong>Overall Evaluation Score:</strong>
                                            <span
                                                class="badge bg-secondary ms-2">{{ number_format(min((float) $contract->evaluation_score, 5), 2) }}/5</span>
                                        </div>
                                    @endif

                                    <form action="{{ route('procurements.contracts.approve', $contract->id) }}"
                                        method="POST" id="procurementApprovalForm" enctype="multipart/form-data">
                                        @csrf

                                        @php
                                            $procIsRenewal = $contract->renewal_status === 'pending' || $contract->lifecycle_stage === 'renewal';
                                            $suggestedStart = $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->addDay()->format('Y-m-d') : '';
                                            $suggestedEnd   = $contract->end_date && $contract->duration_months
                                                ? \Carbon\Carbon::parse($contract->end_date)->addMonths($contract->duration_months)->format('Y-m-d')
                                                : ($contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->addYear()->format('Y-m-d') : '');
                                        @endphp

                                        @if ($procIsRenewal)
                                        <div class="alert alert-info py-2 px-3 mb-3 small">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Enter the <strong>new contract details</strong> for this renewal. Start date is pre-set to the day after the previous contract ends.
                                        </div>
                                        @endif

                                        <h6 class="text-dark border-bottom pb-2 mb-3">
                                            <i class="fas fa-{{ $procIsRenewal ? 'redo' : 'edit' }} me-2"></i>
                                            {{ $procIsRenewal ? 'New Contract Details (Renewal)' : 'Finalize Contract Details' }}
                                        </h6>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="procurement_start_date" class="form-label">
                                                    <strong>Start Date</strong>
                                                </label>
                                                <input type="date" class="form-control" id="procurement_start_date"
                                                    name="start_date"
                                                    value="{{ old('start_date', $procIsRenewal ? $suggestedStart : ($contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '')) }}">
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
                                                    value="{{ old('end_date', $procIsRenewal ? $suggestedEnd : ($contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '')) }}">
                                                @error('end_date')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="procurement_duration_months" class="form-label d-flex justify-content-between align-items-center">
                                                    <strong>Duration (Months)</strong>
                                                    <span id="durationAutoLabel" class="badge bg-light text-success border border-success" style="font-size:.7rem;cursor:pointer" onclick="resetDurationAuto()" title="Click to recalculate from dates">&#8635; Auto</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control"
                                                        id="procurement_duration_months" name="duration_months"
                                                        min="1"
                                                        value="{{ old('duration_months', $procIsRenewal ? ($contract->duration_months ?? '') : ($contract->duration_months ?? '')) }}">
                                                    <span class="input-group-text text-muted small" id="durationHint" style="font-size:.8rem;">months</span>
                                                </div>
                                                <div id="durationPreview" class="text-muted mt-1" style="font-size:.78rem;"></div>
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
                                                    placeholder="{{ $procIsRenewal ? 'Enter new contract cost' : '' }}"
                                                    value="{{ old('cost', $procIsRenewal ? '' : ($contract->cost ?? '')) }}">
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
                                                    placeholder="{{ $procIsRenewal ? 'New contract number (auto-generated if blank)' : '' }}"
                                                    value="{{ old('contract_number', $procIsRenewal ? '' : ($contract->contract_number ?? '')) }}">
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
                    {{-- Contract Chain / History Section --}}
                    @if (($contractChain ?? collect())->count() > 1 || $contract->parent_contract_id)
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                            <i class="fas fa-history text-primary"></i>
                            <h6 class="mb-0 fw-semibold text-dark">Contract History</h6>
                            <span class="badge bg-primary ms-1">{{ ($contractChain ?? collect())->count() }} version{{ ($contractChain ?? collect())->count() > 1 ? 's' : '' }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:90px">Version</th>
                                            <th>Title</th>
                                            <th>Contract No.</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Duration</th>
                                            <th>Cost</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($contractChain ?? collect() as $chainContract)
                                        @php
                                            $isCurrent  = $chainContract->id === $contract->id;
                                            $isActive   = ($chainContract->status ?? '') === 'active';
                                            $cSt = $chainContract->status ?? 'draft';
                                            $cStColor = ['active'=>'success','expired'=>'danger','soonToExpire'=>'warning','draft'=>'secondary','in_progress'=>'info','terminated'=>'dark','renewed'=>'primary'][$cSt] ?? 'secondary';
                                            $cLabel = $cSt === 'soonToExpire' ? 'Soon to Expire' : ucfirst(str_replace('_',' ',$cSt));
                                        @endphp
                                        <tr class="{{ $isActive ? 'table-success' : ($isCurrent ? 'table-light' : '') }}">
                                            <td class="text-nowrap">
                                                @if (!$chainContract->parent_contract_id)
                                                    <span class="badge bg-dark">Original</span>
                                                @else
                                                    <span class="badge bg-secondary">Term {{ $chainContract->renewal_term_number }}</span>
                                                @endif
                                                @if ($isActive)
                                                    <span class="badge bg-success ms-1" style="font-size:.65rem">● Active</span>
                                                @endif
                                                @if ($isCurrent && !$isActive)
                                                    <span class="badge bg-light text-muted border ms-1" style="font-size:.65rem">Viewing</span>
                                                @endif
                                            </td>
                                            <td class="small fw-semibold">{{ Str::limit($chainContract->title, 35) }}</td>
                                            <td class="small text-muted">{{ $chainContract->contract_number ?? '—' }}</td>
                                            <td class="small">{{ $chainContract->start_date ? \Carbon\Carbon::parse($chainContract->start_date)->format('d M Y') : '—' }}</td>
                                            <td class="small">{{ $chainContract->end_date ? \Carbon\Carbon::parse($chainContract->end_date)->format('d M Y') : '—' }}</td>
                                            <td class="small text-muted">{{ $chainContract->duration_months ? $chainContract->duration_months.' mo' : '—' }}</td>
                                            <td class="small">
                                                @if ($chainContract->cost)
                                                    {{ number_format($chainContract->cost, 0) }}
                                                    <span class="text-muted">{{ $chainContract->currency ?? 'TZS' }}</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td><span class="badge bg-{{ $cStColor }}">{{ $cLabel }}</span></td>
                                            <td>
                                                @if (!$isCurrent)
                                                    <a href="{{ route('procurements.contracts.show', $chainContract->id) }}" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:.75rem;">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                @else
                                                    <span class="text-muted small">Viewing</span>
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

                </div>{{-- /col-lg-8 main content --}}

                <!-- Quick Actions Sidebar (right) -->
                <div class="col-lg-4 order-lg-2 mb-4">
                    <div class="card shadow-sm border-0 sticky-top" style="top:80px">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 text-dark fw-semibold">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            @php
                                $sbUser      = auth()->user();
                                $sbStatus    = $contract->status ?? 'draft';
                                $sbStage     = $contract->approval_stage ?? '';
                                $sbApprover  = $contract->current_approver_id == $sbUser->id;
                                $canEditSb   = $sbUser->hasAnyRole(['procurement-officer','hr','super-admin']);
                                $isLmSb      = $sbUser->hasRole('line-manager');
                                $isProcSb    = $sbUser->hasRole('procurement-officer');
                                $isHecSb     = $sbUser->hasAnyRole(['coo','cfo','cms','ccdro']);

                                // Can take approval action (current approver, not terminated/rejected)
                                // HEC members use the inline form — no Quick Action buttons needed
                                $canActSb = $sbApprover
                                    && in_array($sbStage, ['line_manager','procurement'])
                                    && !in_array($sbStatus, ['terminated','rejected']);

                                // Can initiate renewal (active/expiring, line manager of dept OR procurement officer)
                                $canRenewSb = in_array($sbStatus, ['active','soonToExpire','expired'])
                                    && $contract->renewal_status !== 'pending'
                                    && ($isProcSb || ($isLmSb && $sbUser->deptId == ($contract->department->id ?? null)));

                                // Can file (procurement officer, contract expired, not archived)
                                $canFileSb = $isProcSb
                                    && $sbStatus === 'expired'
                                    && !in_array($sbStatus, ['archived','terminated']);

                                // Can send reminder (procurement officer, active/expiring)
                                $canRemindSb = $isProcSb
                                    && in_array($sbStatus, ['active','soonToExpire']);
                            @endphp
                            <div class="d-grid gap-2">
                                {{-- Approval actions (for current approver) --}}
                                @if ($canActSb)
                                    <a href="#approvalSection" class="btn btn-success btn-sm"
                                        onclick="document.getElementById('approvalSection')?.scrollIntoView({behavior:'smooth'});return false;">
                                        <i class="fas fa-check-circle me-1"></i>
                                        @if ($sbStage === 'line_manager') Review & Approve
                                        @elseif ($sbStage === 'hec') Rate & Approve
                                        @else Finalize Contract
                                        @endif
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#rejectContractModal">
                                        <i class="fas fa-times-circle me-1"></i> Reject
                                    </button>
                                    <hr class="my-1">
                                @endif

                                {{-- Renewal --}}
                                @if ($canRenewSb)
                                    <button type="button" class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#renewalModal">
                                        <i class="fas fa-redo me-1"></i> Initiate Renewal
                                    </button>
                                @endif

                                {{-- File (archive) expired contract --}}
                                @if ($canFileSb)
                                    <form action="{{ route('procurements.contracts.file', $contract->id) }}" method="POST"
                                        onsubmit="return confirm('File this contract as archived?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                            <i class="fas fa-archive me-1"></i> File / Archive
                                        </button>
                                    </form>
                                @endif

                                {{-- Send Reminder --}}
                                @if ($canRemindSb)
                                    <form action="{{ route('procurements.contracts.send-reminder', $contract->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning btn-sm w-100 text-dark">
                                            <i class="fas fa-bell me-1"></i> Send Reminder
                                        </button>
                                    </form>
                                @endif

                                <hr class="my-1">

                                <a href="{{ route('procurements.contracts.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i> Back to List
                                </a>
                                @if ($canEditSb)
                                    <a href="{{ route('procurements.contracts.edit', $contract->id) }}" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-edit me-1"></i> Edit Contract
                                    </a>
                                @endif
                                @if ($contract->signed_contract_path)
                                    <a href="{{ route('procurements.contracts.document', $contract->id) }}?type=signed_contract_path"
                                        target="_blank" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-file-signature me-1"></i> Signed Contract
                                    </a>
                                @endif
                                @if ($sbUser->hasRole('super-admin'))
                                    <hr class="my-1">
                                    <form action="{{ route('procurements.contracts.destroy', $contract->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Delete this contract? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                            <i class="fas fa-trash me-1"></i> Delete Contract
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- Contract meta info --}}
                        <div class="card-footer bg-light border-top px-3 py-3">
                            <div class="small text-muted mb-2 fw-semibold text-uppercase" style="font-size:0.7rem;letter-spacing:.05em">Contract Info</div>
                            <div class="d-flex flex-column gap-2">
                                @if ($contract->contract_type)
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small"><i class="fas fa-tag me-1"></i>Type</span>
                                    <span class="badge bg-secondary">{{ $contract->contract_type }}</span>
                                </div>
                                @endif
                                @if ($contract->lifecycle_stage)
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small"><i class="fas fa-layer-group me-1"></i>Stage</span>
                                    <span class="text-dark small fw-semibold">{{ ucfirst($contract->lifecycle_stage) }}</span>
                                </div>
                                @endif
                                @if ($contract->duration_months)
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small"><i class="fas fa-clock me-1"></i>Duration</span>
                                    <span class="text-dark small fw-semibold">{{ $contract->duration_months }} months</span>
                                </div>
                                @endif
                                @if ($contract->parent_contract_id && $contract->parentContract)
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small"><i class="fas fa-link me-1"></i>First Contract</span>
                                    <a href="{{ route('procurements.contracts.show', $contract->parent_contract_id) }}" class="small text-decoration-none">View</a>
                                </div>
                                @endif
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small"><i class="fas fa-calendar-plus me-1"></i>Created</span>
                                    <span class="text-dark small">{{ $contract->created_at?->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>{{-- /col-lg-4 sidebar --}}

            </div>{{-- /row --}}
        </div>{{-- /content --}}
    </div>{{-- /page-wrapper --}}

    @push('styles')
        <style>
            /* Green accent for section-card header icons only */
            .section-card .card-header .fas,
            .section-card .card-header .fa {
                color: #198754;
            }
            .info-item .fas,
            .info-item .fa {
                color: #198754;
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
                        4: 'Excellent'
                    };
                    const maxValue = stars.length;

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
                                    `<small class="text-muted">Selected: ${value}/${maxValue} - ${ratingLabels[value] ?? ''}</small>`;
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

                    // Initialize from old() value (after validation errors)
                    const initialValue = parseInt(hiddenInput.value) || 0;
                    if (initialValue > 0) {
                        updateStars(stars, initialValue);
                        if (ratingText) {
                            ratingText.innerHTML =
                                `<small class="text-muted">Selected: ${initialValue}/${maxValue} - ${ratingLabels[initialValue] ?? ''}</small>`;
                        }
                    }
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
                const procurementStartDate   = document.getElementById('procurement_start_date');
                const procurementEndDate     = document.getElementById('procurement_end_date');
                const procurementDurationMonths = document.getElementById('procurement_duration_months');
                const durationPreview        = document.getElementById('durationPreview');

                function calcMonths(start, end) {
                    let y = end.getFullYear() - start.getFullYear();
                    let m = end.getMonth() - start.getMonth();
                    let total = y * 12 + m;
                    if (end.getDate() < start.getDate()) total--; // partial month
                    return Math.max(total, 0);
                }

                function updateDurationPreview(total) {
                    if (!durationPreview) return;
                    if (total <= 0) { durationPreview.textContent = ''; return; }
                    const yrs = Math.floor(total / 12);
                    const mos = total % 12;
                    let txt = '';
                    if (yrs) txt += yrs + ' year' + (yrs > 1 ? 's' : '');
                    if (mos) txt += (txt ? ' ' : '') + mos + ' month' + (mos > 1 ? 's' : '');
                    durationPreview.textContent = '≈ ' + txt;
                }

                function calculateDuration() {
                    if (!procurementStartDate || !procurementEndDate || !procurementDurationMonths) return;
                    const sv = procurementStartDate.value;
                    const ev = procurementEndDate.value;
                    if (sv && ev) {
                        const start = new Date(sv);
                        const end   = new Date(ev);
                        if (end > start) {
                            const total = calcMonths(start, end);
                            procurementDurationMonths.value = total || '';
                            updateDurationPreview(total);
                        } else {
                            procurementDurationMonths.value = '';
                            if (durationPreview) durationPreview.textContent = 'End date must be after start date';
                        }
                    }
                }

                function resetDurationAuto() {
                    if (procurementDurationMonths) {
                        procurementDurationMonths.dataset.manual = 'false';
                        calculateDuration();
                    }
                }

                if (procurementStartDate && procurementEndDate) {
                    procurementStartDate.addEventListener('change', calculateDuration);
                    procurementEndDate.addEventListener('change', calculateDuration);
                    // Run on load to show preview for existing dates
                    calculateDuration();
                }
            });
        </script>
    @endpush

    {{-- Renewal Modal --}}
    @if ($canRenewSb ?? false)
    <div class="modal fade" id="renewalModal" tabindex="-1" aria-labelledby="renewalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="renewalModalLabel">
                        <i class="fas fa-redo me-2"></i>Initiate Contract Renewal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('procurements.contracts.renewal.initiate', $contract->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @php $renewIsProcSb = auth()->user()->hasRole('procurement-officer'); @endphp

                        @if ($renewIsProcSb)
                            {{-- Procurement officer: set new dates --}}
                            <div class="alert alert-info small mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Set the new contract period and submit. The renewal workflow will start with the Line Manager.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">New Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="new_start_date" class="form-control"
                                    value="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->addDay()->format('Y-m-d') : '' }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">New End Date <span class="text-danger">*</span></label>
                                <input type="date" name="new_end_date" class="form-control"
                                    value="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->addYear()->format('Y-m-d') : '' }}" required>
                            </div>
                        @else
                            {{-- Line manager / HEC: just rate and add notes --}}
                            <div class="alert alert-info small mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Rate this contract and add any notes. It will be forwarded to HEC for approval.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Contract Rating <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-center mt-1">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <label class="d-flex align-items-center gap-1 mb-0" style="cursor:pointer">
                                            <input type="radio" name="contract_rating" value="{{ $i }}" required style="display:none">
                                            <i class="far fa-star fa-lg renewal-star" data-val="{{ $i }}" style="color:#ccc;cursor:pointer"></i>
                                        </label>
                                    @endfor
                                    <span class="small text-muted ms-2 renewal-rating-label">Select rating</span>
                                </div>
                                <input type="hidden" name="contract_rating" id="renewalRatingInput">
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes <small class="text-muted fw-normal">(Optional)</small></label>
                            <textarea name="renewal_notes" class="form-control" rows="3"
                                placeholder="Add any comments or reason for renewal..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-redo me-1"></i>
                            {{ $renewIsProcSb ?? false ? 'Initiate Renewal' : 'Rate & Forward to HEC' }}
                        </button>
                    </div>
                </form>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const stars = document.querySelectorAll('.renewal-star');
                        const ratingInput = document.getElementById('renewalRatingInput');
                        const label = document.querySelector('.renewal-rating-label');
                        const labels = ['','Poor','Adequate','Good','Very Good','Excellent'];
                        stars.forEach(function(star) {
                            star.addEventListener('click', function() {
                                const val = parseInt(this.dataset.val);
                                if (ratingInput) ratingInput.value = val;
                                if (label) label.textContent = labels[val] || '';
                                stars.forEach(function(s) {
                                    s.className = parseInt(s.dataset.val) <= val
                                        ? 'fas fa-star fa-lg renewal-star'
                                        : 'far fa-star fa-lg renewal-star';
                                    s.style.color = parseInt(s.dataset.val) <= val ? '#ffc107' : '#ccc';
                                });
                            });
                        });
                    });
                </script>
            </div>
        </div>
    </div>
    @endif

@endsection
