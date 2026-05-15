@extends('layouts.template')

@push('styles')
<style>
.ns-page { max-width: 960px; margin: 0 auto; }

/* ── Page header ── */
.page-title { font-size: 1.2rem; font-weight: 700; color: #343a40; }
.page-header { padding-bottom: 0.5rem; border-bottom: 1px solid #f0f0f0; margin-bottom: 1.25rem !important; }

/* ── Cards ── */
.card { border-radius: 10px; border: 1px solid #e9ecef; }
.card-header { font-weight: 600; border-bottom: 1px solid #e9ecef; background-color: #f8f9fa !important; }
.card.shadow-sm { box-shadow: 0 1px 4px rgba(0,0,0,.07) !important; }

/* ── Status pills ── */
.status-pill { border-radius: 20px; padding: 4px 12px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
.status-pending  { background: #fff3cd; color: #856404; }
.status-approved { background: #d1e7dd; color: #0f5132; }
.status-rejected { background: #f8d7da; color: #842029; }

/* ── Step badges ── */
.step-badge { font-size: 0.72rem; padding: 4px 10px; border-radius: 20px; display: inline-block; }
.section-badge { width: 24px; height: 24px; border-radius: 50%; background: #61ce70; color: #fff;
                 font-size: 0.68rem; font-weight: 700; display: inline-flex; align-items: center;
                 justify-content: center; flex-shrink: 0; }

/* ── Summary stat bar ── */
.summary-stat {
    display: flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 20px;
    cursor: default; user-select: none;
}
.summary-stat:hover { background: #f0f0f0; }
.summary-stat-count { font-size: 1.1rem; font-weight: 700; line-height: 1; }
.summary-stat-label { font-size: 0.75rem; color: #6c757d; white-space: nowrap; }

/* ── Info grid ── */
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.info-item { padding: 10px 16px; border-bottom: 1px solid #f0f0f0; }
.info-item:nth-child(odd) { border-right: 1px solid #f0f0f0; }
.info-label { font-size: 0.69rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; margin-bottom: 2px; }
.info-value { font-size: 0.85rem; font-weight: 500; color: #222; }

/* ── Table ── */
table.dataTable tbody tr:hover { background-color: #f0f7f8 !important; }

/* ── Timeline ── */
.wf-timeline { position: relative; padding-left: 28px; }
.wf-step { position: relative; padding-bottom: 20px; }
.wf-step:last-child { padding-bottom: 0; }
.wf-step::before { content: ''; position: absolute; left: -20px; top: 28px; bottom: 0;
                    width: 2px; background: #e9ecef; }
.wf-step:last-child::before { display: none; }
.wf-dot { position: absolute; left: -26px; top: 6px; width: 14px; height: 14px;
           border-radius: 50%; border: 2px solid #e9ecef; background: #fff; }
.wf-dot-approved { background: #198754; border-color: #198754; }
.wf-dot-rejected { background: #dc3545; border-color: #dc3545; }
.wf-dot-pending  { background: #ffc107; border-color: #ffc107; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(255,193,7,.4); } 50% { box-shadow: 0 0 0 6px rgba(255,193,7,0); } }

@media print {
    .no-print { display: none !important; }
    .ns-page { max-width: 100%; }
    .card { box-shadow: none !important; border: 1px solid #ddd; }
}
</style>
@endpush

@section('content')
@php
    $ratePerDay = (float) \App\Http\Controllers\SettingsController::getSetting('night_allowance_amount_per_day', 0);
    $totalAmount = $nightShift->total_days_worked * $ratePerDay;
    $wf = $nightShift->workflow;
    $histories = $wf ? $wf->histories->sortBy('id') : collect();
    $pendingStep = $histories->firstWhere('status', 0);
@endphp
<div class="page-wrapper">
<div class="content container-fluid ns-page">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" style="font-size:0.85rem;">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Page Header --}}
    <div class="page-header mb-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-moon text-info me-2"></i>Night Allowances Claim #{{ $nightShift->id }}
                        </h3>
                        <span class="status-pill status-{{ $nightShift->status }}">
                            <i class="fas fa-{{ $nightShift->status === 'approved' ? 'check-circle' : ($nightShift->status === 'rejected' ? 'times-circle' : 'clock') }} me-1"></i>
                            {{ ucfirst($nightShift->status) }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-1" style="font-size:0.78rem; color:#6c757d;">
                        <span><i class="fas fa-calendar me-1"></i>{{ $nightShift->month }} {{ $nightShift->year }}</span>
                        <span><i class="fas fa-building me-1"></i>{{ $nightShift->department?->dept_name ?? '—' }}</span>
                        <span><i class="fas fa-clock me-1"></i>Submitted {{ $nightShift->created_at->format('d M Y') }} ({{ $nightShift->created_at->diffForHumans() }})</span>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 no-print">
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    {{-- Summary stat bar --}}
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-1">
                    <i class="fas fa-chart-bar text-muted me-1" style="font-size:0.85rem;"></i>
                    <small class="text-muted fw-semibold">Summary</small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <div class="summary-stat">
                        <span class="summary-stat-count" style="color:#0d6efd;">{{ $nightShift->number_of_employees }}</span>
                        <span class="summary-stat-label"><i class="fas fa-users me-1"></i>Employees</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-stat-count" style="color:#fd7e14;">{{ $nightShift->total_days_worked }}</span>
                        <span class="summary-stat-label"><i class="fas fa-calendar-day me-1"></i>Days Worked</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-stat-count" style="color:#6f42c1;">{{ $nightShift->employees->sum('hours') }}</span>
                        <span class="summary-stat-label"><i class="fas fa-clock me-1"></i>Total Hours</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-stat-count" style="color:#198754;">{{ number_format($totalAmount, 0) }}</span>
                        <span class="summary-stat-label"><i class="fas fa-money-bill-wave me-1"></i>TZS</span>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-stat-count" style="color:#20c997;">{{ $nightShift->type_of_allowance }}</span>
                        <span class="summary-stat-label"><i class="fas fa-tag me-1"></i>Type</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section A: Claim Information --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2 d-flex align-items-center gap-2">
            <span class="section-badge">A</span>
            <h6 class="mb-0 fw-bold">Claim Information</h6>
        </div>
        <div class="card-body p-0">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Department</div>
                    <div class="info-value">{{ $nightShift->department?->dept_name ?? '—' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Type of Allowance</div>
                    <div class="info-value">{{ $nightShift->type_of_allowance }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Claim Period</div>
                    <div class="info-value"><strong>{{ $nightShift->month }} {{ $nightShift->year }}</strong></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Rate per Day</div>
                    <div class="info-value">TZS {{ number_format($ratePerDay, 2) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Submitted By (Line Manager)</div>
                    <div class="info-value">
                        {{ trim($nightShift->submitter->fname.' '.($nightShift->submitter->mname??'').' '.$nightShift->submitter->lname) }}
                        @if($nightShift->submitter->ccbrt_code)
                            <br><small class="text-muted">{{ $nightShift->submitter->ccbrt_code }}</small>
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">LM's Signature</div>
                    <div class="info-value">
                        @php $sig = $nightShift->submitter->signature; @endphp
                        @if(!empty($sig))
                        <div style="border:1px solid #e9ecef; border-radius:6px; padding:4px 8px; background:#fff; display:inline-block;">
                            <img src="data:image/png;base64,{{ $sig }}" style="max-height:44px; max-width:120px; object-fit:contain;">
                        </div>
                        @else <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date Submitted</div>
                    <div class="info-value">{{ $nightShift->created_at->format('d M Y, H:i') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total Amount (TZS)</div>
                    <div class="info-value"><strong style="color:#198754;">TZS {{ number_format($totalAmount, 2) }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section B: Night Duty Days --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="section-badge">B</span>
                <h6 class="mb-0 fw-bold">Night Duty Days</h6>
            </div>
            <span class="step-badge" style="background:#e2e3e5; color:#41464b;">
                <i class="fas fa-moon me-1" style="font-size:0.65rem;"></i>{{ $nightShift->employees->count() }} night(s)
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:2rem; padding:8px 14px;">#</th>
                            <th style="padding:8px 14px;">Date</th>
                            <th style="padding:8px 14px;">Day</th>
                            <th style="padding:8px 14px;">Platform</th>
                            <th style="padding:8px 14px;">Unit</th>
                            <th class="text-center" style="padding:8px 14px;">Hours</th>
                            <th class="text-end" style="padding:8px 14px;">Amount (TZS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nightShift->employees->sortBy('date') as $emp)
                        <tr>
                            <td class="text-muted small" style="padding:8px 14px;">{{ $loop->iteration }}</td>
                            <td style="padding:8px 14px; font-weight:500;">
                                @if($emp->date)
                                    {{ \Carbon\Carbon::parse($emp->date)->format('d M Y') }}
                                @else —
                                @endif
                            </td>
                            <td style="padding:8px 14px; color:#6c757d; font-size:0.82rem;">
                                @if($emp->date)
                                    {{ \Carbon\Carbon::parse($emp->date)->format('l') }}
                                @endif
                            </td>
                            <td style="padding:8px 14px;">{{ $emp->platform?->name ?? '—' }}</td>
                            <td style="padding:8px 14px;">{{ $emp->unit?->name ?? '—' }}</td>
                            <td class="text-center" style="padding:8px 14px;">{{ $emp->hours ? $emp->hours.' hrs' : '—' }}</td>
                            <td class="text-end fw-semibold" style="padding:8px 14px;">{{ number_format($ratePerDay, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8f9fa; font-weight:700; font-size:0.85rem;">
                            <td colspan="5" class="text-end" style="padding:10px 14px;">Total</td>
                            <td class="text-center" style="padding:10px 14px;">
                                {{ $nightShift->employees->count() }} nights / {{ $nightShift->employees->sum('hours') }} hrs
                            </td>
                            <td class="text-end" style="padding:10px 14px; color:#198754;">
                                TZS {{ number_format($totalAmount, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Section C: Approval Workflow --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="section-badge">C</span>
                <h6 class="mb-0 fw-bold">Approval Workflow</h6>
            </div>
            @if($nightShift->status === 'approved')
                <span class="step-badge" style="background:#d1e7dd; color:#0f5132;">
                    <i class="fas fa-check-circle me-1" style="font-size:0.65rem;"></i>Completed
                </span>
            @elseif($nightShift->status === 'rejected')
                <span class="step-badge" style="background:#f8d7da; color:#842029;">
                    <i class="fas fa-times-circle me-1" style="font-size:0.65rem;"></i>Rejected
                </span>
            @else
                <span class="step-badge" style="background:#fff3cd; color:#856404;">
                    <i class="fas fa-clock me-1" style="font-size:0.65rem;"></i>In Progress
                </span>
            @endif
        </div>

        {{-- Pending info strip --}}
        @if($pendingStep)
        <div style="padding:10px 16px; border-bottom:1px solid #e9ecef; background:#fffbeb;">
            <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size:0.82rem;">
                <i class="fas fa-hourglass-half text-warning"></i>
                <span class="text-muted">Pending with:</span>
                <strong>{{ $pendingStep->attendedBy ? trim($pendingStep->attendedBy->fname.' '.$pendingStep->attendedBy->lname) : '—' }}</strong>
                <span class="text-muted ms-2">Step:</span>
                <span class="step-badge" style="background:#fff3cd; color:#856404;">
                    <i class="fas fa-user-shield me-1" style="font-size:0.6rem;"></i>{{ $pendingStep->step_name }}
                </span>
            </div>
        </div>
        @elseif($nightShift->status === 'rejected')
            @php $rejStep = $histories->lastWhere('status', 2); @endphp
            @if($rejStep && $rejStep->rejection_reason)
            <div style="padding:10px 16px; border-bottom:1px solid #e9ecef; background:#fff5f5;">
                <div class="d-flex align-items-start gap-2" style="font-size:0.82rem;">
                    <i class="fas fa-exclamation-triangle text-danger mt-1"></i>
                    <div>
                        <span class="text-muted">Rejection reason:</span>
                        <span style="color:#842029; font-style:italic;">{{ $rejStep->rejection_reason }}</span>
                    </div>
                </div>
            </div>
            @endif
        @endif

        {{-- Timeline --}}
        <div class="card-body">
            @if($histories->count())
            <div class="wf-timeline">
                @foreach($histories as $step)
                @php
                    $st = (int) $step->status;
                    $dotCls = match($st) { 1 => 'wf-dot-approved', 2 => 'wf-dot-rejected', default => 'wf-dot-pending' };
                    $statusLabel = match($st) { 1 => 'Approved', 2 => 'Rejected', default => 'Pending' };
                    $statusColors = match($st) {
                        1 => ['bg' => '#d1e7dd', 'text' => '#0f5132', 'icon' => 'check-circle'],
                        2 => ['bg' => '#f8d7da', 'text' => '#842029', 'icon' => 'times-circle'],
                        default => ['bg' => '#fff3cd', 'text' => '#856404', 'icon' => 'clock'],
                    };
                    $stepColors = [
                        'Incharge Approval'          => ['bg' => '#fff3cd', 'text' => '#856404', 'icon' => 'user-shield'],
                        'Platform Manager Approval'  => ['bg' => '#cfe2ff', 'text' => '#084298', 'icon' => 'sitemap'],
                        'Line Manager Approval'      => ['bg' => '#d1e7dd', 'text' => '#0f5132', 'icon' => 'user-tie'],
                        'HEC Approval'               => ['bg' => '#e2d9f3', 'text' => '#4a1d91', 'icon' => 'landmark'],
                        'HR Approval'                => ['bg' => '#f8d7da', 'text' => '#842029', 'icon' => 'users'],
                    ];
                    $sc = $stepColors[$step->step_name] ?? ['bg' => '#e2e3e5', 'text' => '#41464b', 'icon' => 'tasks'];
                @endphp
                <div class="wf-step">
                    <div class="wf-dot {{ $dotCls }}"></div>
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <span class="step-badge" style="background:{{ $sc['bg'] }}; color:{{ $sc['text'] }};">
                                    <i class="fas fa-{{ $sc['icon'] }} me-1" style="font-size:0.6rem;"></i>{{ $step->step_name }}
                                </span>
                                <span class="step-badge" style="background:{{ $statusColors['bg'] }}; color:{{ $statusColors['text'] }};">
                                    <i class="fas fa-{{ $statusColors['icon'] }} me-1" style="font-size:0.6rem;"></i>{{ $statusLabel }}
                                </span>
                            </div>
                            <div style="font-size:0.82rem;">
                                @if($step->attendedBy)
                                    <span class="fw-semibold">{{ trim($step->attendedBy->fname.' '.$step->attendedBy->lname) }}</span>
                                    @if($st === 1 && !empty($step->attendedBy->signature))
                                    <div style="border:1px solid #e9ecef; border-radius:4px; padding:2px 5px; background:#fff; display:inline-block; margin-left:8px;">
                                        <img src="data:image/png;base64,{{ $step->attendedBy->signature }}"
                                             style="max-height:24px; max-width:80px; object-fit:contain; display:block;">
                                    </div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                            @if($step->forwardedBy)
                            <div style="font-size:0.75rem; color:#6c757d; margin-top:2px;">
                                <i class="fas fa-share me-1" style="font-size:0.6rem;"></i>Forwarded by: {{ trim($step->forwardedBy->fname.' '.$step->forwardedBy->lname) }}
                            </div>
                            @endif
                            @if($step->rejection_reason)
                            <div style="font-size:0.78rem; color:#842029; font-style:italic; margin-top:4px;">
                                <i class="fas fa-comment-dots me-1"></i>{{ $step->rejection_reason }}
                            </div>
                            @elseif($step->remark)
                            <div style="font-size:0.78rem; color:#555; margin-top:4px;">
                                <i class="fas fa-comment me-1" style="font-size:0.65rem;"></i>{{ $step->remark }}
                            </div>
                            @endif
                        </div>
                        <div style="font-size:0.75rem; color:#6c757d; text-align:right; white-space:nowrap;">
                            @if($step->attend_date)
                                {{ \Carbon\Carbon::parse($step->attend_date)->format('d M Y, H:i') }}
                                <br><small>{{ \Carbon\Carbon::parse($step->attend_date)->diffForHumans() }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No workflow steps yet</p>
            </div>
            @endif
        </div>
    </div>

</div>
</div>
@endsection
