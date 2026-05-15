@extends('layouts.template')

@push('styles')
<style>
    .detail-label { font-size:.74rem; color:#6c757d; font-weight:600; margin-bottom:2px; text-transform:uppercase; letter-spacing:.03em; }
    .detail-value { font-size:.86rem; color:#212529; font-weight:500; }
    .section-card { border:0; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.07); margin-bottom:.85rem; }
    .section-card .card-header { background:#f8fdf9; border-bottom:1px solid #d1e7dd; border-radius:10px 10px 0 0 !important; padding:.55rem 1rem; }
    .section-card .card-header h6 { margin:0; font-size:.82rem; font-weight:600; color:#198754; }
    .doc-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border:1px solid #dee2e6; border-radius:20px; font-size:.78rem; color:#495057; text-decoration:none; background:#fff; transition:.2s; }
    .doc-pill:hover { background:#198754; color:#fff; border-color:#198754; }
    .doc-pill i { color:#dc3545; font-size:.8rem; }
    .doc-pill:hover i { color:#fff; }

    /* Quick stat cards */
    .qs-card { border-radius:8px; padding:.7rem .8rem; text-align:center; position:relative; overflow:hidden; }
    .qs-card .qs-icon { font-size:1.1rem; margin-bottom:.25rem; }
    .qs-card .qs-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.03em; font-weight:600; opacity:.75; margin-bottom:1px; }
    .qs-card .qs-value { font-size:.92rem; font-weight:600; line-height:1.2; }

    /* Renewal modal */
    .renew-modal-section { border-left:3px solid #198754; padding-left:12px; margin-bottom:1.25rem; }
    .renew-modal-section-title { font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; font-weight:700; color:#198754; margin-bottom:.75rem; }
    #renewModal .modal-body { max-height:70vh; overflow-y:auto; }

    /* Vendor highlight */
    .vendor-badge { display:inline-flex; align-items:center; gap:4px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:5px; padding:2px 8px; font-size:.76rem; color:#0369a1; }

    /* Action buttons */
    .action-bar .btn { font-size:.78rem; }
</style>
@endpush

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
@php
    $today = \Carbon\Carbon::today();
    $endDate   = $contract->end_date;
    $startDate = $contract->start_date;
    $isExp  = $endDate && $endDate->lt($today);
    $isSoon = $endDate && !$isExp && $endDate->diffInDays($today) <= 30;

    if (in_array($contract->status, ['renewed','terminated','archived','draft'])) {
        $displayStatus = $contract->status;
    } elseif ($isExp) {
        $displayStatus = 'expired';
    } elseif ($isSoon) {
        $displayStatus = 'soonToExpire';
    } else {
        $displayStatus = $contract->status;
    }

    $statusColors = [
        'active'=>'success','expired'=>'danger','soonToExpire'=>'warning',
        'draft'=>'secondary','in_progress'=>'info','renewed'=>'primary',
        'terminated'=>'dark','archived'=>'secondary'
    ];
    $color = $statusColors[$displayStatus] ?? 'secondary';
    $displayLabel = $displayStatus === 'soonToExpire' ? 'Soon to Expire' : ucfirst(str_replace('_',' ',$displayStatus));

    // Timeline calculations
    $totalDays   = ($startDate && $endDate) ? max($startDate->diffInDays($endDate), 1) : 0;
    $elapsedDays = ($startDate && $totalDays) ? max(0, $startDate->diffInDays($today)) : 0;
    $daysLeft    = ($endDate) ? (int) $today->diffInDays($endDate, false) : null;
    $pct = ($totalDays > 0) ? min(100, max(0, round(($elapsedDays / $totalDays) * 100))) : 0;
    if ($isExp) $pct = 100;

    $showCanRenew = $isManager
        && (!$contract->renewals || $contract->renewals->isEmpty())
        && (in_array($contract->status, ['expired','soonToExpire'])
            || ($contract->end_date && $contract->end_date->diffInDays($today, false) >= -30));

    // Contract owner (non-manager) can also see the renew button
    if (!$showCanRenew && $contract->contract_owner_id === Auth::id()
        && (!$contract->renewals || $contract->renewals->isEmpty())
        && (in_array($contract->status, ['expired','soonToExpire'])
            || ($contract->end_date && $contract->end_date->diffInDays($today, false) >= -30))) {
        $showCanRenew = true;
    }
@endphp

<div class="page-wrapper">
    <div class="content container-fluid">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Page header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <h6 class="mb-0 text-dark fw-semibold" style="font-size:.92rem;"><i class="fas fa-file-contract me-1 text-success"></i>{{ $contract->title }}</h6>
                    <span class="badge bg-{{ $color }} px-2 py-1" style="font-size:.72rem;">{{ $displayLabel }}</span>
                </div>
                <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.76rem;">
                    <span><i class="fas fa-hashtag me-1"></i>{{ $contract->contract_number ?? 'No Number' }}</span>
                    @if($contract->contract_type)
                    <span><i class="fas fa-tag me-1"></i>{{ $contract->contract_type }}</span>
                    @endif
                    @if($contract->vendor)
                    <span class="vendor-badge"><i class="fas fa-building"></i>{{ $contract->vendor->name }}</span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap action-bar">
                <a href="{{ route('hec-contracts.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                @if($isManager)
                <a href="{{ route('hec-contracts.edit', $contract->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit me-1"></i> Edit
                </a>
                @endif
                @if($showCanRenew)
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#renewModal">
                    <i class="fas fa-redo me-1"></i> Renew
                </button>
                @elseif($contract->renewals && $contract->renewals->isNotEmpty())
                <span class="btn btn-sm btn-success disabled"><i class="fas fa-check me-1"></i> Renewed</span>
                @endif
            </div>
        </div>

        {{-- Lineage alerts --}}
        @if($contract->parentContract)
        <div class="alert alert-info py-2 d-flex align-items-center gap-2 mb-2" style="border-radius:8px;">
            <i class="fas fa-link text-info"></i>
            <span class="small">Renewal of <a href="{{ route('hec-contracts.show', $contract->parentContract->id) }}" class="fw-semibold">{{ $contract->parentContract->contract_number }}</a> — {{ $contract->parentContract->title }}
            <span class="badge bg-secondary ms-1">{{ ucfirst($contract->parentContract->status) }}</span></span>
        </div>
        @endif
        @if($contract->renewals && $contract->renewals->isNotEmpty())
        <div class="alert alert-success py-2 d-flex align-items-center gap-2 mb-2" style="border-radius:8px;">
            <i class="fas fa-redo text-success"></i>
            <span class="small">Renewed as: @foreach($contract->renewals as $r)
                <a href="{{ route('hec-contracts.show', $r->id) }}" class="fw-semibold ms-1">{{ $r->contract_number }}</a>
                <span class="badge bg-success ms-1">{{ ucfirst($r->status) }}</span>
            @endforeach</span>
        </div>
        @endif

        {{-- Quick stat cards --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="qs-card bg-white shadow-sm border">
                    <div class="qs-icon text-success"><i class="fas fa-coins"></i></div>
                    <div class="qs-label">Contract Value</div>
                    <div class="qs-value text-success">{{ number_format($contract->cost ?? 0, 2) }} <span style="font-size:.72rem;font-weight:400;color:#6c757d;">{{ $contract->currency ?? 'TZS' }}</span></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="qs-card bg-white shadow-sm border">
                    <div class="qs-icon text-primary"><i class="fas fa-clock"></i></div>
                    <div class="qs-label">Duration</div>
                    <div class="qs-value">{{ $contract->duration_months ?? '—' }} <span style="font-size:.72rem;font-weight:400;color:#6c757d;">months</span></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="qs-card bg-white shadow-sm border">
                    @if($isExp)
                    <div class="qs-icon text-danger"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="qs-label">Expired</div>
                    <div class="qs-value text-danger">
                        @php
                            $expMonths = abs($today->diffInMonths($endDate));
                            $expDays   = abs($today->diffInDays($endDate));
                        @endphp
                        {{ $expMonths >= 1 ? $expMonths.' mo'.($expMonths>1?'s':'') : $expDays.'d' }} <span style="font-size:.72rem;font-weight:400;">ago</span>
                    </div>
                    @elseif($daysLeft !== null)
                    <div class="qs-icon {{ $isSoon ? 'text-warning' : 'text-info' }}"><i class="fas fa-hourglass-half"></i></div>
                    <div class="qs-label">Days Left</div>
                    <div class="qs-value {{ $isSoon ? 'text-warning' : '' }}">
                        @if($daysLeft > 60)
                            {{ floor($daysLeft/30) }} <span style="font-size:.72rem;font-weight:400;color:#6c757d;">months</span>
                        @else
                            {{ $daysLeft }} <span style="font-size:.72rem;font-weight:400;color:#6c757d;">days</span>
                        @endif
                    </div>
                    @else
                    <div class="qs-icon text-muted"><i class="fas fa-hourglass-half"></i></div>
                    <div class="qs-label">Days Left</div>
                    <div class="qs-value text-muted">—</div>
                    @endif
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="qs-card bg-white shadow-sm border">
                    <div class="qs-icon text-secondary"><i class="fas fa-user-tie"></i></div>
                    <div class="qs-label">Owner</div>
                    <div class="qs-value" style="font-size:.82rem;">
                        @if($contract->contractOwner)
                            {{ $contract->contractOwner->fname }} {{ $contract->contractOwner->lname }}
                        @elseif($contract->owner_email)
                            <span class="small">{{ $contract->owner_email }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Left column --}}
            <div class="col-lg-8">

                {{-- Description --}}
                @if($contract->description)
                <div class="card section-card">
                    <div class="card-header"><h6><i class="fas fa-align-left me-2"></i>Description</h6></div>
                    <div class="card-body py-3 px-4">
                        <p class="mb-0" style="font-size:.9rem; line-height:1.6; color:#333;">{{ $contract->description }}</p>
                    </div>
                </div>
                @endif

                {{-- Contract Period & Financials --}}
                <div class="card section-card">
                    <div class="card-header"><h6><i class="fas fa-calendar-alt me-2"></i>Period &amp; Financial Details</h6></div>
                    <div class="card-body py-3 px-4">
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="detail-label">Start Date</div>
                                <div class="detail-value">{{ $contract->start_date?->format('d M Y') ?? '—' }}</div>
                            </div>
                            <div class="col-sm-4">
                                <div class="detail-label">End Date</div>
                                <div class="detail-value {{ $isExp ? 'text-danger' : '' }}">
                                    {{ $contract->end_date?->format('d M Y') ?? '—' }}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="detail-label">Duration</div>
                                <div class="detail-value">{{ $contract->duration_months ?? '—' }} months</div>
                            </div>
                        </div>
                        <hr class="my-3" style="opacity:.08;">
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="detail-label">Contract Value</div>
                                <div class="detail-value text-success" style="font-weight:600;">{{ number_format($contract->cost ?? 0, 2) }} {{ $contract->currency ?? 'TZS' }}</div>
                            </div>
                            <div class="col-sm-4">
                                <div class="detail-label">Impact if Not Requested</div>
                                <div class="detail-value">
                                    @php $imp = $contract->impact_if_not_requested ?? null; @endphp
                                    @if($imp)
                                    <span class="badge bg-{{ $imp==='High'?'danger':($imp==='Medium'?'warning text-dark':'success') }}">{{ $imp }}</span>
                                    @else <span class="text-muted">—</span> @endif
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="detail-label">Likelihood Rating</div>
                                <div class="detail-value">
                                    @php $lk = $contract->likelihood_rating ?? null; @endphp
                                    @if($lk)
                                    <span class="badge bg-{{ $lk==='High'?'danger':($lk==='Medium'?'warning text-dark':'success') }}">{{ $lk }}</span>
                                    @else <span class="text-muted">—</span> @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Documents --}}
                <div class="card section-card">
                    <div class="card-header"><h6><i class="fas fa-file-pdf me-2 text-danger"></i>Documents</h6></div>
                    <div class="card-body py-3 px-4">
                        @php $hasDocs = $contract->file_path || $contract->signed_contract_path || $contract->terms_conditions_path || $contract->sla_document_path; @endphp
                        @if($hasDocs)
                        <div class="d-flex flex-wrap gap-2">
                            @if($contract->file_path)
                            <a href="{{ asset('storage/'.$contract->file_path) }}" target="_blank" class="doc-pill">
                                <i class="fas fa-file-pdf"></i> Contract Document
                            </a>
                            @endif
                            @if($contract->signed_contract_path)
                            <a href="{{ asset('storage/'.$contract->signed_contract_path) }}" target="_blank" class="doc-pill">
                                <i class="fas fa-file-pdf"></i> Signed Contract
                            </a>
                            @endif
                            @if($contract->terms_conditions_path)
                            <a href="{{ asset('storage/'.$contract->terms_conditions_path) }}" target="_blank" class="doc-pill">
                                <i class="fas fa-file-pdf"></i> Terms &amp; Conditions
                            </a>
                            @endif
                            @if($contract->sla_document_path)
                            <a href="{{ asset('storage/'.$contract->sla_document_path) }}" target="_blank" class="doc-pill">
                                <i class="fas fa-file-pdf"></i> SLA Document
                            </a>
                            @endif
                        </div>
                        @else
                        <p class="text-muted small mb-0"><i class="fas fa-exclamation-circle me-1"></i>No documents uploaded.</p>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Right column --}}
            <div class="col-lg-4">

                {{-- Contract Owner & Entity --}}
                <div class="card section-card">
                    <div class="card-header"><h6><i class="fas fa-user-tie me-2"></i>Contract Owner</h6></div>
                    <div class="card-body py-3 px-4">
                        @if($contract->contractOwner)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px;min-width:36px;">
                                <i class="fas fa-user text-success" style="font-size:.85rem;"></i>
                            </div>
                            <div>
                                <div class="detail-value" style="line-height:1.2;">{{ $contract->contractOwner->fname }} {{ $contract->contractOwner->mname }} {{ $contract->contractOwner->lname }}</div>
                                <div class="text-muted" style="font-size:.78rem;">{{ $contract->contractOwner->email }}</div>
                            </div>
                        </div>
                        @else
                        <div class="text-muted small mb-2"><i class="fas fa-user-slash me-1"></i>No HEC member assigned</div>
                        @endif

                        @if($contract->owner_email && (!$contract->contractOwner || $contract->owner_email !== $contract->contractOwner->email))
                        <div class="detail-label mt-2">Notification Email</div>
                        <div class="detail-value small">{{ $contract->owner_email }}</div>
                        @endif

                        <hr class="my-2" style="opacity:.15;">

                        <div class="detail-label">CCBRT Entity</div>
                        <div class="detail-value small mb-2">
                            @if($contract->division)
                                {{ $contract->division->name }}@if($contract->division->code) <span class="text-muted">({{ $contract->division->code }})</span>@endif
                            @else <span class="text-muted">—</span>
                            @endif
                        </div>

                        @if($contract->vendor)
                        <div class="detail-label">Vendor</div>
                        <div class="detail-value small">
                            <i class="fas fa-building me-1 text-muted"></i>{{ $contract->vendor->name }}
                        </div>
                        @endif

                        <div class="detail-label mt-2">Contract Source</div>
                        <span class="badge {{ $contract->contract_source === 'existing' ? 'bg-info' : 'bg-primary' }}" style="font-size:.74rem;">
                            {{ $contract->contract_source === 'existing' ? 'Existing Contract' : 'New Contract' }}
                        </span>
                    </div>
                </div>

                {{-- Audit Trail --}}
                <div class="card section-card">
                    <div class="card-header"><h6><i class="fas fa-history me-2"></i>Audit Trail</h6></div>
                    <div class="card-body py-3 px-4">
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;margin-top:1px;">
                                <i class="fas fa-plus text-secondary" style="font-size:.65rem;"></i>
                            </div>
                            <div>
                                <div class="small fw-semibold">Created</div>
                                <div class="text-muted" style="font-size:.78rem;">
                                    {{ $contract->creator->fname ?? '' }} {{ $contract->creator->lname ?? '' }}
                                    <br>{{ $contract->created_at->format('d M Y, H:i') }}
                                </div>
                            </div>
                        </div>

                        @if($contract->updated_at && $contract->updated_at->ne($contract->created_at))
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;margin-top:1px;">
                                <i class="fas fa-edit text-info" style="font-size:.65rem;"></i>
                            </div>
                            <div>
                                <div class="small fw-semibold">Last Updated</div>
                                <div class="text-muted" style="font-size:.78rem;">{{ $contract->updated_at->format('d M Y, H:i') }}</div>
                            </div>
                        </div>
                        @endif

                        @if($contract->renewed_at)
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;margin-top:1px;">
                                <i class="fas fa-redo text-success" style="font-size:.65rem;"></i>
                            </div>
                            <div>
                                <div class="small fw-semibold text-success">Renewed On</div>
                                <div class="text-muted" style="font-size:.78rem;">{{ $contract->renewed_at->format('d M Y, H:i') }}</div>
                            </div>
                        </div>
                        @endif

                        @if($contract->parentContract)
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;margin-top:1px;">
                                <i class="fas fa-link text-primary" style="font-size:.65rem;"></i>
                            </div>
                            <div>
                                <div class="small fw-semibold">Renewal Of</div>
                                <div style="font-size:.78rem;">
                                    <a href="{{ route('hec-contracts.show', $contract->parentContract->id) }}">{{ $contract->parentContract->contract_number }}</a>
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

{{-- Renewal Modal --}}
@if($showCanRenew)
<div class="modal fade" id="renewModal" tabindex="-1" aria-labelledby="renewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header py-2" style="background:linear-gradient(135deg,#198754,#157347);border:0;">
                <h5 class="modal-title fw-bold text-white" id="renewModalLabel" style="font-size:.95rem;">
                    <i class="fas fa-redo me-2"></i>Renew HEC Contract
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('hec-contracts.renew', $contract->id) }}" method="POST" enctype="multipart/form-data" id="renewForm">
                @csrf
                <div class="modal-body px-4 py-3">

                    {{-- Info banner --}}
                    <div class="alert alert-info py-2 d-flex gap-2 mb-3" style="border-radius:8px;">
                        <i class="fas fa-info-circle mt-1 flex-shrink-0"></i>
                        <div class="small">A <strong>new contract</strong> will be created with the details below. The original
                            (<strong>{{ $contract->contract_number }}</strong>) will be marked as <strong>Renewed</strong> and remain unchanged.</div>
                    </div>

                    {{-- Original summary --}}
                    <div class="rounded p-3 mb-4" style="background:#f8f9fa;border:1px solid #dee2e6;">
                        <div class="small fw-semibold text-muted mb-2" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Original Contract Summary</div>
                        <div class="row g-2 small">
                            <div class="col-6 col-md-4"><span class="text-muted">Contract #:</span><br><strong>{{ $contract->contract_number }}</strong></div>
                            <div class="col-6 col-md-4"><span class="text-muted">Title:</span><br><strong>{{ Str::limit($contract->title,35) }}</strong></div>
                            <div class="col-6 col-md-4"><span class="text-muted">Owner:</span><br><strong>{{ $contract->contractOwner?->fname.' '.$contract->contractOwner?->lname ?? $contract->owner_email ?? '—' }}</strong></div>
                            <div class="col-6 col-md-4"><span class="text-muted">Period:</span><br>{{ $contract->start_date?->format('d M Y') ?? '—' }} — {{ $contract->end_date?->format('d M Y') ?? '—' }}</div>
                            <div class="col-6 col-md-4"><span class="text-muted">Value:</span><br><strong class="text-success">{{ number_format($contract->cost??0,2) }} {{ $contract->currency }}</strong></div>
                            @if($contract->vendor)
                            <div class="col-6 col-md-4"><span class="text-muted">Vendor:</span><br><strong>{{ $contract->vendor->name }}</strong></div>
                            @endif
                        </div>
                    </div>

                    {{-- Section 1: Period --}}
                    <div class="renew-modal-section">
                        <div class="renew-modal-section-title"><i class="fas fa-calendar-alt me-1"></i>New Period</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="new_start_date" id="newStartDate" class="form-control form-control-sm" required
                                    value="{{ $contract->end_date ? $contract->end_date->addDay()->format('Y-m-d') : '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="new_end_date" id="newEndDate" class="form-control form-control-sm" required>
                                <div class="form-text">Auto-fills from start + duration</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Duration (months) <span class="text-danger">*</span></label>
                                <input type="number" name="new_duration_months" id="newDurationMonths" class="form-control form-control-sm"
                                    value="{{ $contract->duration_months ?? 12 }}" min="1" required>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Financial --}}
                    <div class="renew-modal-section">
                        <div class="renew-modal-section-title"><i class="fas fa-coins me-1"></i>Financial</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Contract Value <span class="text-danger">*</span></label>
                                <input type="text" id="renewCostDisplay" class="form-control form-control-sm"
                                    placeholder="Enter new value" required
                                    value="{{ $contract->cost ? number_format($contract->cost, 2, '.', ',') : '' }}">
                                <input type="hidden" name="new_cost" id="renewCostHidden"
                                    value="{{ $contract->cost ?? '' }}">
                                <div class="form-text">Pre-filled from original contract</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Currency <span class="text-danger">*</span></label>
                                <select name="new_currency" class="form-select form-select-sm">
                                    @foreach(['TZS','USD','EUR','GBP','KES'] as $cur)
                                        <option value="{{ $cur }}" {{ ($contract->currency??'TZS')===$cur?'selected':'' }}>{{ $cur }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Contract Type</label>
                                <input type="text" name="new_contract_type" class="form-control form-control-sm"
                                    value="{{ $contract->contract_type ?? '' }}" readonly style="background:#f8f9fa;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Impact if Not Requested</label>
                                <select name="new_impact" class="form-select form-select-sm">
                                    @foreach(['Low','Medium','High'] as $opt)
                                        <option value="{{ $opt }}" {{ ($contract->impact_if_not_requested??'')===$opt?'selected':'' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Likelihood Rating</label>
                                <select name="new_likelihood" class="form-select form-select-sm">
                                    @foreach(['Low','Medium','High'] as $opt)
                                        <option value="{{ $opt }}" {{ ($contract->likelihood_rating??'')===$opt?'selected':'' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Description --}}
                    <div class="renew-modal-section">
                        <div class="renew-modal-section-title"><i class="fas fa-align-left me-1"></i>Description</div>
                        <textarea name="new_description" class="form-control form-control-sm" rows="2" placeholder="Optional — will carry over from original if blank">{{ $contract->description }}</textarea>
                    </div>

                    {{-- Section 4: Documents --}}
                    <div class="renew-modal-section mb-0">
                        <div class="renew-modal-section-title"><i class="fas fa-file-pdf me-1"></i>Documents <span class="fw-normal text-muted">(PDF, max 10MB — optional, originals won't carry over)</span></div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Contract Document</label>
                                <input type="file" name="new_file_path" class="form-control form-control-sm" accept=".pdf">
                                @if($contract->file_path)<div class="form-text text-success"><i class="fas fa-check-circle me-1"></i>Original has this file</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Signed Contract</label>
                                <input type="file" name="new_signed_contract" class="form-control form-control-sm" accept=".pdf">
                                @if($contract->signed_contract_path)<div class="form-text text-success"><i class="fas fa-check-circle me-1"></i>Original has this file</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Terms &amp; Conditions</label>
                                <input type="file" name="new_terms_conditions" class="form-control form-control-sm" accept=".pdf">
                                @if($contract->terms_conditions_path)<div class="form-text text-success"><i class="fas fa-check-circle me-1"></i>Original has this file</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">SLA Document</label>
                                <input type="file" name="new_sla_document" class="form-control form-control-sm" accept=".pdf">
                                @if($contract->sla_document_path)<div class="form-text text-success"><i class="fas fa-check-circle me-1"></i>Original has this file</div>@endif
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer py-2" style="background:#f8fdf9;border-top:1px solid #d1e7dd;">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success px-4" id="renewSubmitBtn">
                        <i class="fas fa-redo me-1"></i> Confirm Renewal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const startInput    = document.getElementById('newStartDate');
    const durationInput = document.getElementById('newDurationMonths');
    const endInput      = document.getElementById('newEndDate');
    if (!startInput || !durationInput || !endInput) return;

    function calcEnd() {
        if (!startInput.value || !durationInput.value) return;
        const d = new Date(startInput.value);
        d.setMonth(d.getMonth() + parseInt(durationInput.value));
        endInput.value = d.toISOString().split('T')[0];
    }

    function calcDuration() {
        if (!startInput.value || !endInput.value) { durationInput.value = ''; return; }
        const start = new Date(startInput.value);
        const end = new Date(endInput.value);
        if (end <= start) { durationInput.value = ''; return; }
        let months = (end.getFullYear() - start.getFullYear()) * 12 + end.getMonth() - start.getMonth();
        if (end.getDate() < start.getDate()) months--;
        durationInput.value = months || 1;
    }

    startInput.addEventListener('change', calcEnd);
    durationInput.addEventListener('input', calcEnd);
    endInput.addEventListener('change', calcDuration);

    // Auto-calculate end date on modal open
    calcEnd();

    // Cost comma formatting in renewal modal
    const costDisplay = document.getElementById('renewCostDisplay');
    const costHidden  = document.getElementById('renewCostHidden');
    if (costDisplay && costHidden) {
        costDisplay.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
            if (parts.length === 2 && parts[1].length > 2) value = parts[0] + '.' + parts[1].substring(0, 2);
            costHidden.value = value;
            if (value) {
                const num = parseFloat(value);
                if (!isNaN(num)) {
                    e.target.value = num.toLocaleString('en-US', {
                        minimumFractionDigits: value.includes('.') ? 2 : 0,
                        maximumFractionDigits: 2
                    });
                }
            }
        });
        costDisplay.addEventListener('blur', function(e) {
            if (costHidden.value) {
                const num = parseFloat(costHidden.value);
                if (!isNaN(num)) {
                    e.target.value = num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            }
        });
    }

    // Prevent double-submit
    document.getElementById('renewForm').addEventListener('submit', function() {
        var btn = document.getElementById('renewSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing…';
    });
})();
</script>
@endpush
@endif

@endsection
