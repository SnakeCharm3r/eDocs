@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Fix overlap with page header */
        .page-wrapper > .content { position: relative; z-index: 1; background: #f8f9fa; }
        .page-header { position: relative; z-index: 2; }
        .card.shadow-sm { position: relative; z-index: 1; }

        /* Override global .card-title font-size */
        .card .card-header .card-title.section-title { font-size: 1rem !important; font-weight: 600 !important; color: #333 !important; margin-bottom: 0 !important; }
        .card .card-header .card-title.section-title i { color: #007A33; }
        .info-label { font-size: 0.78rem; color: #6c757d; font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; }
        .info-value { font-size: 0.92rem; color: #212529; font-weight: 500; }
        .cr-content-box {
            background: #fafafa; border-left: 4px solid #007A33;
            padding: .75rem 1rem; border-radius: 0 3px 3px 0;
            font-size: .88rem; line-height: 1.7; color: #212529;
        }
        .cr-priority-row { display: flex; gap: .75rem; }
        .cr-priority-pill {
            flex: 1; text-align: center; padding: .55rem .5rem;
            border-radius: 4px; border: 2px solid #e9ecef; background: #f8f9fa;
            font-size: .82rem; font-weight: 600; color: #adb5bd;
        }
        .cr-priority-pill.active-low { border-color: #28a745; background: #d4edda; color: #155724; }
        .cr-priority-pill.active-medium { border-color: #ffc107; background: #fff3cd; color: #856404; }
        .cr-priority-pill.active-high { border-color: #dc3545; background: #f8d7da; color: #721c24; }
        .cr-tbl { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .cr-tbl th {
            background: #f1f3f5; font-size: .75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .04em; color: #495057;
            padding: .5rem .7rem; border: 1px solid #dee2e6;
        }
        .cr-tbl td { padding: .48rem .7rem; border: 1px solid #dee2e6; vertical-align: middle; }
        .cr-tbl tbody tr:nth-child(even) td { background: #fafafa; }
        .cr-wf-step {
            display: flex; align-items: center; gap: .75rem;
            padding: .55rem .7rem; border: 1px solid #e9ecef; margin-top: -1px;
        }
        .cr-wf-icon {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .7rem; flex-shrink: 0;
        }
        .cr-wf-icon.approved { background: #d4edda; color: #155724; }
        .cr-wf-icon.rejected { background: #f8d7da; color: #721c24; }
        .cr-wf-icon.pending  { background: #fff3cd; color: #856404; }
        .cr-wf-info { flex: 1; min-width: 0; }
        .cr-wf-role { font-size: .82rem; font-weight: 600; color: #212529; }
        .cr-wf-person { font-size: .78rem; color: #6c757d; }
        .cr-wf-remark { font-size: .76rem; color: #868e96; font-style: italic; margin-top: .1rem; }
        .cr-wf-badge { font-size: .7rem; font-weight: 600; padding: .2rem .5rem; border-radius: 3px; }
        .cr-wf-badge.approved { background: #d4edda; color: #155724; }
        .cr-wf-badge.rejected { background: #f8d7da; color: #721c24; }
        .cr-wf-badge.pending  { background: #fff3cd; color: #856404; }
        .cr-wf-sig { flex-shrink: 0; }
        .cr-wf-sig img { height: 32px; border: 1px solid #dee2e6; border-radius: 3px; padding: 2px; background: #fff; }
        .cr-att-chip {
            display: inline-flex; align-items: center; gap: .35rem;
            border: 1px solid #c8e6c9; background: #f1f8e9; color: #33691e;
            border-radius: 3px; padding: .28rem .65rem; font-size: .8rem; font-weight: 500;
            text-decoration: none;
        }
        .cr-att-chip:hover { background: #dcedc8; color: #1b5e20; }
        @media print {
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 12mm; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>

    @php
        $changeType = $changeRequest->change_type ?? 'non_price';
        $isPriceChange = $changeType === 'price';
        $wfStatus = strtolower($changeRequest->workflow->work_flow_status ?? 'pending');
        $isCompleted = $changeRequest->workflow->work_flow_completed ?? 0;
        $isApproved = $isCompleted == 1 && !str_contains($wfStatus, 'rejected');
        $isRejected = str_contains($wfStatus, 'rejected') || $isCompleted == 2;
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">
            <br>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title" style="font-weight:300;">Change Request Form</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">

                    {{-- Alerts --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size:.85rem;">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size:.85rem;">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Request Summary Card --}}
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-info-circle me-2"></i>Request Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="info-label">Reference</label>
                                        <p class="mb-0 info-value">CR-{{ str_pad($changeRequest->id, 4, '0', STR_PAD_LEFT) }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="info-label">Submitted Date</label>
                                        <p class="mb-0 info-value">{{ $changeRequest->created_at->format('d M Y, h:i A') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="info-label">Change Type</label>
                                        <p class="mb-0">
                                            <span style="background:#007A33;color:#fff;padding:.2rem .55rem;border-radius:3px;font-size:.78rem;font-weight:600;">
                                                {{ $isPriceChange ? 'Price Change' : 'Non-Price Change' }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="info-label">Status</label>
                                        <p class="mb-0">
                                            @if ($isApproved)
                                                <span class="badge bg-success" style="font-size:.82rem;padding:.35em .65em;">Approved</span>
                                            @elseif ($isRejected)
                                                <span class="badge bg-danger" style="font-size:.82rem;padding:.35em .65em;">Rejected</span>
                                            @else
                                                <span class="badge bg-warning text-dark" style="font-size:.82rem;padding:.35em .65em;">Pending</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Originator Details Card --}}
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-user me-2"></i>Originator Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="info-label">Full Name</label>
                                        <p class="mb-0 info-value">
                                            @if ($changeRequest->user)
                                                {{ $changeRequest->user->fname ?? '' }} {{ $changeRequest->user->mname ?? '' }} {{ $changeRequest->user->lname ?? '' }}
                                            @else
                                                N/A
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="info-label">Job Title</label>
                                        <p class="mb-0 info-value">{{ $changeRequest->user->jobTitle->name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="info-label">Department</label>
                                        <p class="mb-0 info-value">{{ $changeRequest->user->department->dept_name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                @if ($changeRequest->change_category)
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="info-label">Change Category</label>
                                            @php
                                                $categoryLabels = [
                                                    'non_price' => 'Non-Price',
                                                    'price' => 'Price',
                                                    'Insurance' => 'Insurance',
                                                    'Procedure' => 'Procedure',
                                                    'Price Change' => 'Price Change',
                                                    'Cash' => 'Cash',
                                                    'Donor' => 'Donor',
                                                ];
                                            @endphp
                                            <p class="mb-0 info-value">{{ $categoryLabels[$changeRequest->change_category] ?? ucwords(str_replace('_', ' ', $changeRequest->change_category)) }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Priority Level Card --}}
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-flag me-2"></i>Priority Level
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="cr-priority-row">
                                <div class="cr-priority-pill {{ $changeRequest->priority === 'P3-Low' ? 'active-low' : '' }}">
                                    @if ($changeRequest->priority === 'P3-Low') <i class="fas fa-check-circle"></i> @endif
                                    P3 — Low
                                </div>
                                <div class="cr-priority-pill {{ $changeRequest->priority === 'P2-Medium' ? 'active-medium' : '' }}">
                                    @if ($changeRequest->priority === 'P2-Medium') <i class="fas fa-check-circle"></i> @endif
                                    P2 — Medium
                                </div>
                                <div class="cr-priority-pill {{ $changeRequest->priority === 'P1-High' ? 'active-high' : '' }}">
                                    @if ($changeRequest->priority === 'P1-High') <i class="fas fa-check-circle"></i> @endif
                                    P1 — High
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Description of Change Card --}}
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-file-alt me-2"></i>Description of Change(s)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="cr-content-box">
                                {!! nl2br(e($changeRequest->description_of_change ?? 'N/A')) !!}
                            </div>
                        </div>
                    </div>

                    {{-- Reason for Change Card --}}
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-question-circle me-2"></i>Reason for Change
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="cr-content-box">
                                {!! nl2br(e($changeRequest->reason_for_change ?? 'N/A')) !!}
                            </div>
                        </div>
                    </div>

                    {{-- Tariff Details Card (price changes) --}}
                    @if ($isPriceChange && $changeRequest->tariff_type)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-tags me-2"></i>Tariff Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="info-label">Tariff Type</label>
                                            <p class="mb-0">
                                                <span style="background:#007A33;color:#fff;padding:.15rem .5rem;border-radius:3px;font-size:.78rem;font-weight:600;">
                                                    {{ $changeRequest->tariff_type === 'new_tariff' ? 'New Tariff' : 'Edit Tariff' }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    @if ($changeRequest->tariff_category_id)
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="info-label">Tariff Category</label>
                                                <p class="mb-0 info-value">{{ $changeRequest->tariffCategory->name ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($changeRequest->tariff_type === 'edit_tariff' && $changeRequest->current_tariff_name)
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="info-label">Current Tariff Name</label>
                                                <p class="mb-0 info-value">{{ $changeRequest->current_tariff_name }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($changeRequest->tariff_name)
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="info-label">{{ $changeRequest->tariff_type === 'new_tariff' ? 'Tariff Name' : 'New Tariff Name' }}</label>
                                                <p class="mb-0 info-value">{{ $changeRequest->tariff_name }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Service Details Card (price changes) --}}
                    @if ($isPriceChange && $changeRequest->service_action_type)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-concierge-bell me-2"></i>Service Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="info-label">Service Action Type</label>
                                            <p class="mb-0">
                                                <span style="background:#007A33;color:#fff;padding:.15rem .5rem;border-radius:3px;font-size:.78rem;font-weight:600;">
                                                    {{ $changeRequest->service_action_type === 'new_service' ? 'New Service' : 'Edit Service' }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    @if ($changeRequest->service_category_id)
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="info-label">Service Category</label>
                                                <p class="mb-0 info-value">{{ $changeRequest->serviceCategory->name ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($changeRequest->service_action_type === 'edit_service' && $changeRequest->current_service_name)
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="info-label">Current Service Name</label>
                                                <p class="mb-0 info-value">{{ $changeRequest->current_service_name }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($changeRequest->service_name)
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="info-label">{{ $changeRequest->service_action_type === 'new_service' ? 'Service Name' : 'New Service Name' }}</label>
                                                <p class="mb-0 info-value">{{ $changeRequest->service_name }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @if ($changeRequest->service_action_type === 'new_service' && $changeRequest->service_prices)
                                    @php
                                        $servicePrices = is_array($changeRequest->service_prices) ? $changeRequest->service_prices : json_decode($changeRequest->service_prices, true);
                                    @endphp
                                    @if ($servicePrices && is_array($servicePrices))
                                        <table class="cr-tbl mt-3">
                                            <thead>
                                                <tr><th>Payment Type</th><th style="text-align:right">Price</th></tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($servicePrices as $type => $price)
                                                    <tr>
                                                        <td><strong>{{ ucfirst(str_replace('_', ' ', $type)) }}</strong></td>
                                                        <td style="text-align:right">{{ number_format($price, 2) }} TZS</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Price Change Details Card --}}
                    @if ($isPriceChange && $changeRequest->price_item_name)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-money-bill-wave me-2"></i>Price Change Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="info-label">Item / Service Name</label>
                                        <p class="mb-0 info-value" style="font-weight:600;">{{ $changeRequest->price_item_name }}</p>
                                    </div>
                                    @if ($changeRequest->price_change_reason)
                                        <div class="col-md-6">
                                            <label class="info-label">Price Change Reason</label>
                                            <p class="mb-0 info-value">{{ $changeRequest->price_change_reason }}</p>
                                        </div>
                                    @endif
                                </div>
                                @php
                                    $currentPrices = is_array($changeRequest->current_price) ? $changeRequest->current_price : (is_string($changeRequest->current_price) ? json_decode($changeRequest->current_price, true) : []);
                                    $newPrices = is_array($changeRequest->new_price) ? $changeRequest->new_price : (is_string($changeRequest->new_price) ? json_decode($changeRequest->new_price, true) : []);
                                @endphp
                                @if (!empty($currentPrices) || !empty($newPrices))
                                    <table class="cr-tbl">
                                        <thead>
                                            <tr><th>Payment Type</th><th style="text-align:right">Current Price</th><th style="text-align:right">New Price</th><th style="text-align:right">Difference</th></tr>
                                        </thead>
                                        <tbody>
                                            @php $allTypes = array_unique(array_merge(array_keys($currentPrices ?? []), array_keys($newPrices ?? []))); @endphp
                                            @foreach ($allTypes as $type)
                                                @php
                                                    $current = $currentPrices[$type] ?? 0;
                                                    $new = $newPrices[$type] ?? 0;
                                                    $difference = $new - $current;
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ ucfirst(str_replace('_', ' ', $type)) }}</strong></td>
                                                    <td style="text-align:right">{{ number_format($current, 2) }} TZS</td>
                                                    <td style="text-align:right">{{ number_format($new, 2) }} TZS</td>
                                                    <td style="text-align:right;color:{{ $difference > 0 ? '#28a745' : ($difference < 0 ? '#dc3545' : '#6c757d') }};font-weight:600;">
                                                        {{ $difference > 0 ? '+' : '' }}{{ number_format($difference, 2) }} TZS
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Implementation Notes Card --}}
                    @if ($changeRequest->implementation_notes)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-sticky-note me-2"></i>Implementation Notes
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="cr-content-box">
                                    {!! nl2br(e($changeRequest->implementation_notes)) !!}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Supporting Document Card --}}
                    @if ($changeRequest->supporting_document)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-paperclip me-2"></i>Supporting Document
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <a href="{{ asset('storage/' . $changeRequest->supporting_document) }}" target="_blank" class="cr-att-chip">
                                        <i class="fas fa-download"></i> Download Document
                                    </a>
                                </div>
                                <div style="border:1px solid #dee2e6;border-radius:3px;overflow:hidden;">
                                    <iframe src="{{ asset('storage/' . $changeRequest->supporting_document) }}" width="100%" height="500px" style="border:none;"></iframe>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Approval Workflow Card --}}
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-project-diagram me-2"></i>Approval Workflow
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            @php
                                $workflowHistories = $changeRequest->workflow->histories ?? collect();
                                $steps = $isPriceChange
                                    ? ['Line Manager', 'Price Committee', 'HEC Member', 'IT']
                                    : ['Line Manager', 'HEC Member', 'IT'];
                                $stepGroups = [];
                                foreach ($steps as $stepName) {
                                    $hists = $workflowHistories->where('step_name', $stepName);
                                    if ($hists->count() > 0) $stepGroups[$stepName] = $hists;
                                }
                            @endphp

                            @foreach ($stepGroups as $stepName => $histories)
                                @php
                                    if ($stepName === 'IT') {
                                        $approvedHistory = $histories->where('status', 1)->first();
                                        $iterableHistories = $approvedHistory ? collect([$approvedHistory]) : collect([$histories->first()]);
                                    } else {
                                        $iterableHistories = $histories;
                                    }
                                @endphp
                                @foreach ($iterableHistories as $history)
                                    @php
                                        $person = null; $signature = null;
                                        if ($history->status == 1 && $history->who_approve && $history->approver) {
                                            $person = $history->approver;
                                            $signature = $person->signature ?? null;
                                        } elseif ($history->status == 2) {
                                            $person = $history->attendedBy;
                                        } else {
                                            $person = $history->attendedBy;
                                        }

                                        if ($history->status == 1) {
                                            $remark = !empty($history->comments) ? $history->comments : ($history->remark ?? '');
                                        } elseif ($history->status == 2) {
                                            $remark = $history->rejection_reason ?? ($history->comments ?? ($history->remark ?? ''));
                                        } else {
                                            $remark = $history->remark ?? '';
                                        }
                                        $defaultMessages = ['Awaiting Line Manager approval','Awaiting approval from Price Committee','Awaiting approval from HEC Member','Awaiting approval from IT','Awaiting Price Committee approval','Awaiting HEC Member approval','Awaiting HEC Member approval (Line Manager request)','Awaiting Price Committee approval (Line Manager request)'];
                                        $isDefaultMsg = false;
                                        foreach ($defaultMessages as $dm) { if (str_contains($remark, $dm)) { $isDefaultMsg = true; break; } }
                                        if (empty(trim($remark)) || $isDefaultMsg) $remark = '';

                                        $sClass = $history->status == 1 ? 'approved' : ($history->status == 2 ? 'rejected' : 'pending');
                                        $sText = $history->status == 1 ? 'Approved' : ($history->status == 2 ? 'Rejected' : 'Pending');
                                        $sIcon = $history->status == 1 ? 'fa-check' : ($history->status == 2 ? 'fa-times' : 'fa-clock');
                                    @endphp
                                    <div class="cr-wf-step">
                                        <div class="cr-wf-icon {{ $sClass }}"><i class="fas {{ $sIcon }}"></i></div>
                                        <div class="cr-wf-info">
                                            <div class="cr-wf-role">
                                                {{ $stepName }}
                                                <span class="cr-wf-badge {{ $sClass }} ms-2">{{ $sText }}</span>
                                            </div>
                                            <div class="cr-wf-person">
                                                @if ($person)
                                                    {{ $person->fname ?? '' }} {{ $person->lname ?? '' }}
                                                    @if ($person->jobTitle)
                                                        — {{ $person->jobTitle->name ?? '' }}
                                                    @endif
                                                @else
                                                    Awaiting assignment
                                                @endif
                                            </div>
                                            @if ($remark)
                                                <div class="cr-wf-remark">"{{ $remark }}"</div>
                                            @endif
                                        </div>
                                        <div class="cr-wf-sig">
                                            @if ($signature && $history->status == 1)
                                                <img src="data:image/png;base64,{{ $signature }}" alt="Signature">
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>

                    {{-- Action Bar Card --}}
                    @php
                        $currentPendingHistory = $changeRequest->workflow->histories
                            ->where('attended_by', Auth::user()->id)
                            ->where('status', 0)
                            ->first();
                    @endphp

                    <div class="card shadow-sm mb-3 no-print">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div style="font-size:.85rem;color:#6c757d;">
                                    @if (auth()->user()->can('view change management') || auth()->user()->hasAnyRole(['price_committee','cms','cfo','coo','it','super-admin']))
                                        <strong>{{ $changeRequest->user->fname ?? '' }} {{ $changeRequest->user->lname ?? '' }}</strong>
                                        &mdash; {{ $changeRequest->user->department->dept_name ?? 'N/A' }}
                                        @if ($isApproved)
                                            <span class="badge bg-success ms-1" style="font-size:.72rem;">Approved</span>
                                        @elseif ($isRejected)
                                            <span class="badge bg-danger ms-1" style="font-size:.72rem;">Rejected</span>
                                        @else
                                            <span class="badge bg-warning text-dark ms-1" style="font-size:.72rem;">Pending</span>
                                        @endif
                                    @endif
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" id="backButton">
                                        <i class="fas fa-arrow-left me-1"></i> Back
                                    </button>
                                    @if ($isCompleted)
                                        <a href="{{ route('change_request.pdf', $changeRequest->id) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </a>
                                    @endif
                                    @if ($currentPendingHistory && !$isCompleted)
                                        <button class="btn btn-sm btn-success" id="approveBtn">
                                            <i class="fas fa-check me-1"></i> Approve
                                        </button>
                                        <button class="btn btn-sm btn-danger" id="rejectBtn">
                                            <i class="fas fa-times me-1"></i> Reject
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var backBtn = document.getElementById('backButton');
        if (backBtn) {
            backBtn.addEventListener('click', function() {
                if (document.referrer && window.history.length > 1) {
                    window.history.back();
                } else {
                    window.location.href = '{{ route('change_request.index') }}';
                }
            });
        }

        @if ($currentPendingHistory && !$isCompleted)
        document.getElementById('approveBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'Approve Change Request',
                html: '<div class="mb-3"><label for="swal-approval-comments" class="form-label text-start d-block mb-2"><strong>Comments (Optional)</strong></label><textarea id="swal-approval-comments" class="form-control" rows="4" placeholder="Add any comments or notes..." style="min-height:100px;resize:vertical;"></textarea></div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check-circle me-1"></i> Approve',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#007A33',
                preConfirm: () => document.getElementById('swal-approval-comments').value.trim()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Approving...', html: '<div class="text-center"><div class="spinner-border text-success"></div><p class="mt-3">Please wait...</p></div>', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    if (result.value) formData.append('comments', result.value);
                    fetch('{{ route('change_request.approve', $changeRequest->id) }}', { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                    .then(async r => { if (!r.headers.get('content-type')?.includes('application/json')) throw new Error('Non-JSON response'); if (!r.ok) throw await r.json(); return r.json(); })
                    .then(d => { Swal.fire({ icon: d.icon||'success', title: d.title||'Success', text: d.text||d.message||'Approved.', timer: 2000, showConfirmButton: false }).then(() => window.location.reload()); })
                    .catch(e => { Swal.fire({ icon: 'error', title: 'Error', text: 'Failed: '+(e.text||e.message||'Unknown error') }); });
                }
            });
        });

        document.getElementById('rejectBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'Reject Change Request',
                html: '<div class="mb-3"><label for="swal-rejection-reason" class="form-label text-start d-block mb-2"><strong>Reason for Rejection <span class="text-danger">*</span></strong></label><textarea id="swal-rejection-reason" class="form-control" rows="4" required placeholder="Please provide a reason..." style="min-height:100px;resize:vertical;"></textarea></div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-times-circle me-1"></i> Reject',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                preConfirm: () => { const r = document.getElementById('swal-rejection-reason').value.trim(); if (!r) { Swal.showValidationMessage('Please provide a reason.'); return false; } return r; }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({ title: 'Rejecting...', html: '<div class="text-center"><div class="spinner-border text-danger"></div><p class="mt-3">Please wait...</p></div>', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    formData.append('rejection_reason', result.value);
                    fetch('{{ route('change_request.reject', $changeRequest->id) }}', { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                    .then(async r => { if (!r.headers.get('content-type')?.includes('application/json')) throw new Error('Non-JSON response'); if (!r.ok) throw await r.json(); return r.json(); })
                    .then(d => { Swal.fire({ icon: d.icon||'success', title: d.title||'Success', text: d.text||d.message||'Rejected.', timer: 2000, showConfirmButton: false }).then(() => window.location.reload()); })
                    .catch(e => { Swal.fire({ icon: 'error', title: 'Error', text: 'Failed: '+(e.text||e.message||'Unknown error') }); });
                }
            });
        });
        @endif
    });
    </script>
@endsection
