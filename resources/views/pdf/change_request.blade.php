<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Request #{{ $changeRequest->id }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #f0f0f0;
        }

        /* ── Print / Back buttons ── */
        .btn-bar {
            position: fixed;
            top: 12px; right: 16px;
            display: flex; gap: 8px;
            z-index: 9999;
        }
        .btn-bar button {
            padding: 8px 20px;
            border: none; border-radius: 4px;
            cursor: pointer; font-size: 13px; font-weight: 600;
        }
        .btn-print { background: #007A33; color: #fff; }
        .btn-back  { background: #6c757d; color: #fff; }
        .btn-print:hover { background: #005f27; }
        .btn-back:hover  { background: #545b62; }

        /* ── A4 page ── */
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 10mm auto;
            padding: 12mm 14mm;
            background: #fff;
            box-shadow: 0 0 14px rgba(0,0,0,.18);
        }

        /* ── Document header ── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #007A33;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .doc-header .org h1 {
            font-size: 15px; color: #007A33;
            font-weight: 700; letter-spacing: .3px;
        }
        .doc-header .org p { font-size: 10px; color: #555; margin-top: 2px; }
        .doc-header img { height: 52px; width: auto; }

        /* ── Reference bar ── */
        .ref-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #007A33;
            color: #fff;
            padding: 5px 10px;
            border-radius: 3px;
            margin-bottom: 14px;
            font-size: 11px;
        }
        .ref-bar strong { font-size: 12px; }

        /* ── Section title ── */
        .sec-title {
            font-size: 11px; font-weight: 700;
            color: #fff; background: #007A33;
            padding: 5px 10px;
            margin: 14px 0 0;
            border-radius: 2px 2px 0 0;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        /* ── Info table ── */
        .info-table {
            width: 100%; border-collapse: collapse;
            border: 1px solid #c8e6c9; margin-bottom: 0;
        }
        .info-table td, .info-table th {
            border: 1px solid #c8e6c9;
            padding: 5px 8px;
            vertical-align: top;
            font-size: 10.5px; line-height: 1.5;
        }
        .info-table .lbl {
            background: #f1f8f1; width: 28%;
            font-weight: 600; color: #2d5a27;
        }
        .info-table .val { color: #222; }
        .info-table .val.na { color: #aaa; font-style: italic; }

        /* ── Priority badges ── */
        .priority-badge {
            display: inline-block;
            padding: 3px 12px; border-radius: 12px;
            font-weight: 700; font-size: 11px;
        }
        .priority-high   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .priority-medium { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .priority-low    { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        /* ── Status badge ── */
        .badge {
            display: inline-block;
            padding: 2px 8px; border-radius: 10px;
            font-size: 10px; font-weight: 700;
        }
        .badge-price     { background: #cfe2ff; color: #0a3172; border: 1px solid #b6d4fe; }
        .badge-nonprice  { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .badge-approved  { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-pending   { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .badge-rejected  { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* ── Rich-text content area ── */
        .text-content {
            border: 1px solid #c8e6c9;
            padding: 8px 10px;
            background: #fafffe;
            font-size: 10.5px;
            line-height: 1.6;
            min-height: 28px;
        }
        .text-content p { margin: 0 0 4px; }

        /* ── Price comparison table ── */
        .price-table {
            width: 100%; border-collapse: collapse;
            border: 1px solid #c8e6c9;
        }
        .price-table thead tr { background: #e8f5e9; }
        .price-table thead th {
            border: 1px solid #c8e6c9;
            padding: 5px 8px; font-size: 10px;
            font-weight: 700; color: #1b5e20; text-align: left;
        }
        .price-table tbody td {
            border: 1px solid #c8e6c9;
            padding: 5px 8px; font-size: 10.5px;
        }
        .price-table tbody tr:nth-child(even) { background: #fafffe; }
        .diff-up   { color: #155724; font-weight: 600; }
        .diff-down { color: #721c24; font-weight: 600; }

        /* ── Workflow table ── */
        .workflow-table {
            width: 100%; border-collapse: collapse;
            border: 1px solid #c8e6c9;
        }
        .workflow-table thead tr { background: #e8f5e9; }
        .workflow-table thead th {
            border: 1px solid #c8e6c9;
            padding: 5px 8px; font-size: 10px;
            font-weight: 700; color: #1b5e20; text-align: left;
        }
        .workflow-table tbody td {
            border: 1px solid #c8e6c9;
            padding: 6px 8px; font-size: 10.5px;
            vertical-align: middle;
        }
        .workflow-table tbody tr:nth-child(even) { background: #fafffe; }
        .workflow-table img {
            max-width: 100px; max-height: 38px;
            display: block;
            border: 1px solid #ddd;
            padding: 2px; background: #fff;
            border-radius: 2px;
        }
        .step-approved { color: #155724; font-weight: 600; }
        .step-rejected { color: #721c24; font-weight: 600; }
        .step-pending  { color: #856404; }

        /* ── Footer ── */
        .doc-footer {
            border-top: 2px solid #007A33;
            margin-top: 16px; padding-top: 6px;
            text-align: center; font-size: 9px; color: #888;
        }

        /* ── Print rules ── */
        @media print {
            body { margin: 0; background: #fff; }
            .btn-bar { display: none !important; }
            .page { width: 100%; margin: 0; padding: 10mm 12mm; box-shadow: none; }
            @page { size: A4 portrait; margin: 0; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .sec-title   { background: #007A33 !important; color: #fff !important; }
            .ref-bar     { background: #007A33 !important; color: #fff !important; }
            .info-table .lbl   { background: #f1f8f1 !important; }
            .price-table thead tr, .workflow-table thead tr { background: #e8f5e9 !important; }
            .sec-title { page-break-after: avoid; }
            .info-table, .price-table, .workflow-table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

@php
    $changeType   = $changeRequest->change_type ?? 'non_price';
    $isPriceChange = $changeType === 'price';
    $priceChangeType = null;
    if ($isPriceChange) {
        if ($changeRequest->tariff_type)         $priceChangeType = 'tariff';
        elseif ($changeRequest->service_action_type) $priceChangeType = 'service';
        elseif ($changeRequest->price_item_name) $priceChangeType = 'price_change';
    }
    $workflowHistories = $changeRequest->workflow->histories ?? collect();
    $steps = $isPriceChange
        ? ['Line Manager', 'Price Committee', 'HEC Member', 'IT']
        : ['Line Manager', 'HEC Member', 'IT'];
    $isCompleted = $changeRequest->workflow->work_flow_completed ?? 0;
    $wfStatus    = $changeRequest->workflow->work_flow_status    ?? 'Pending';
@endphp

{{-- ── Buttons (screen only) ── --}}
<div class="btn-bar">
    <button class="btn-back" onclick="window.history.back()">← Back</button>
    <button class="btn-print" onclick="window.print()">⬇ Download PDF</button>
</div>

<div class="page">

    {{-- ══ HEADER ══ --}}
    <div class="doc-header">
        <div class="org">
            <h1>CCBRT – Change Request Form</h1>
            <p>Comprehensive Community-Based Rehabilitation in Tanzania</p>
        </div>
        <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT"
             onerror="this.style.display='none'">
    </div>

    {{-- ── Reference bar ── --}}
    <div class="ref-bar">
        <span><strong>Form Ref:</strong> CR-{{ str_pad($changeRequest->id, 5, '0', STR_PAD_LEFT) }}</span>
        <span><strong>Submitted:</strong> {{ \Carbon\Carbon::parse($changeRequest->created_at)->format('d M Y, H:i') }}</span>
        <span>
            <strong>Status:</strong>
            @if ($isCompleted && $wfStatus === 'Approved')
                <span class="badge badge-approved">Approved</span>
            @elseif ($isCompleted && $wfStatus === 'Rejected')
                <span class="badge badge-rejected">Rejected</span>
            @else
                <span class="badge badge-pending">Pending</span>
            @endif
        </span>
    </div>

    {{-- ══ 1. ORIGINATOR DETAILS ══ --}}
    <div class="sec-title">1 &nbsp; Originator Details</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Full Name</td>
            <td class="val">
                {{ trim(($changeRequest->user->fname ?? '') . ' ' . ($changeRequest->user->mname ?? '') . ' ' . ($changeRequest->user->lname ?? '')) }}
            </td>
            <td class="lbl">Department</td>
            <td class="val">{{ $changeRequest->user->department->dept_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="lbl">Change Type</td>
            <td class="val">
                @if ($isPriceChange)
                    <span class="badge badge-price">Price Change</span>
                @else
                    <span class="badge badge-nonprice">Non-Price Change</span>
                @endif
            </td>
            <td class="lbl">Category</td>
            <td class="val">{{ $changeRequest->change_category ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="lbl">Priority</td>
            <td class="val" colspan="3">
                @if ($changeRequest->priority === 'P1-High')
                    <span class="priority-badge priority-high">P1 – High</span>
                @elseif ($changeRequest->priority === 'P2-Medium')
                    <span class="priority-badge priority-medium">P2 – Medium</span>
                @else
                    <span class="priority-badge priority-low">P3 – Low</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- ══ 2. DESCRIPTION OF CHANGE ══ --}}
    <div class="sec-title">2 &nbsp; Description of Change</div>
    <div class="text-content">{!! $changeRequest->description_of_change ?? '<em>—</em>' !!}</div>

    {{-- ══ 3. REASON FOR CHANGE ══ --}}
    <div class="sec-title">3 &nbsp; Reason for Change</div>
    <div class="text-content">{!! $changeRequest->reason_for_change ?? '<em>—</em>' !!}</div>

    {{-- ══ 4. PRICE CHANGE DETAILS (conditional) ══ --}}
    @if ($isPriceChange)
        @if ($priceChangeType === 'tariff')
        <div class="sec-title">4 &nbsp; Tariff Details</div>
        <table class="info-table">
            <tr>
                <td class="lbl">Tariff Action</td>
                <td class="val">
                    {{ $changeRequest->tariff_type === 'new_tariff' ? 'New Tariff' : 'Edit Existing Tariff' }}
                </td>
                <td class="lbl">Tariff Category</td>
                <td class="val">{{ $changeRequest->tariffCategory->name ?? 'N/A' }}</td>
            </tr>
            @if ($changeRequest->tariff_type === 'edit_tariff' && $changeRequest->current_tariff_name)
            <tr>
                <td class="lbl">Current Tariff Name</td>
                <td class="val">{{ $changeRequest->current_tariff_name }}</td>
                <td class="lbl">New Tariff Name</td>
                <td class="val">{{ $changeRequest->tariff_name ?? '—' }}</td>
            </tr>
            @else
            <tr>
                <td class="lbl">Tariff Name</td>
                <td class="val" colspan="3">{{ $changeRequest->tariff_name ?? '—' }}</td>
            </tr>
            @endif
        </table>

        @elseif ($priceChangeType === 'service')
        <div class="sec-title">4 &nbsp; Service Details</div>
        <table class="info-table">
            <tr>
                <td class="lbl">Service Action</td>
                <td class="val">
                    {{ $changeRequest->service_action_type === 'new_service' ? 'New Service' : 'Edit Existing Service' }}
                </td>
                <td class="lbl">Service Category</td>
                <td class="val">{{ $changeRequest->serviceCategory->name ?? 'N/A' }}</td>
            </tr>
            @if ($changeRequest->service_action_type === 'edit_service' && $changeRequest->current_service_name)
            <tr>
                <td class="lbl">Current Service Name</td>
                <td class="val">{{ $changeRequest->current_service_name }}</td>
                <td class="lbl">New Service Name</td>
                <td class="val">{{ $changeRequest->service_name ?? '—' }}</td>
            </tr>
            @else
            <tr>
                <td class="lbl">Service Name</td>
                <td class="val" colspan="3">{{ $changeRequest->service_name ?? '—' }}</td>
            </tr>
            @endif
        </table>
        @php $servicePrices = $changeRequest->service_prices ?? []; @endphp
        @if (!empty($servicePrices))
        <table class="price-table" style="margin-top:4px">
            <thead>
                <tr>
                    <th>Payment Type</th>
                    <th>Price (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($servicePrices as $payType => $price)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $payType)) }}</td>
                    <td>{{ number_format($price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @elseif ($priceChangeType === 'price_change')
        <div class="sec-title">4 &nbsp; Price Change Details</div>
        <table class="info-table">
            <tr>
                <td class="lbl">Item / Service Name</td>
                <td class="val" colspan="3">{{ $changeRequest->price_item_name }}</td>
            </tr>
            @if ($changeRequest->price_change_reason)
            <tr>
                <td class="lbl">Reason for Price Change</td>
                <td class="val" colspan="3">{{ $changeRequest->price_change_reason }}</td>
            </tr>
            @endif
        </table>
        @php
            $currentPrices = $changeRequest->current_price ?? [];
            $newPrices     = $changeRequest->new_price     ?? [];
            $allKeys = array_unique(array_merge(array_keys((array)$currentPrices), array_keys((array)$newPrices)));
        @endphp
        @if (!empty($allKeys))
        <table class="price-table" style="margin-top:4px">
            <thead>
                <tr>
                    <th>Payment Type</th>
                    <th>Current Price (TZS)</th>
                    <th>New Price (TZS)</th>
                    <th>Difference</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($allKeys as $key)
                @php
                    $cur  = (float) ($currentPrices[$key] ?? 0);
                    $nw   = (float) ($newPrices[$key]     ?? 0);
                    $diff = $nw - $cur;
                @endphp
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                    <td>{{ $cur  ? number_format($cur, 2)  : '—' }}</td>
                    <td>{{ $nw   ? number_format($nw,  2)  : '—' }}</td>
                    <td class="{{ $diff > 0 ? 'diff-up' : ($diff < 0 ? 'diff-down' : '') }}">
                        @if ($diff > 0) +{{ number_format($diff, 2) }}
                        @elseif ($diff < 0) {{ number_format($diff, 2) }}
                        @else —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        @endif
    @endif

    {{-- ══ 5. IMPLEMENTATION NOTES ══ --}}
    @if ($changeRequest->implementation_notes)
    <div class="sec-title">{{ $isPriceChange ? '5' : '4' }} &nbsp; Implementation Notes</div>
    <div class="text-content">{!! $changeRequest->implementation_notes !!}</div>
    @endif

    {{-- ══ 6. APPROVAL WORKFLOW ══ --}}
    @php $wfSec = ($isPriceChange ? 5 : 4) + ($changeRequest->implementation_notes ? 1 : 0); @endphp
    <div class="sec-title">{{ $wfSec }} &nbsp; Approval Workflow</div>
    <table class="workflow-table">
        <thead>
            <tr>
                <th style="width:20%">Step</th>
                <th style="width:22%">Approver</th>
                <th style="width:18%">Signature</th>
                <th style="width:12%">Decision</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($steps as $stepName)
            @php
                $stepHistories = $workflowHistories->where('step_name', $stepName);

                // IT: show only the one who approved; others: show all
                if ($stepName === 'IT') {
                    $approved = $stepHistories->where('status', 1)->first();
                    $rows = $approved ? collect([$approved]) : collect([$stepHistories->first()])->filter();
                } else {
                    $rows = $stepHistories;
                }

                // Skip if no history for this step
                if ($rows->isEmpty()) continue;

                $defaultMessages = [
                    'Awaiting Line Manager approval',
                    'Awaiting approval from Price Committee',
                    'Awaiting approval from HEC Member',
                    'Awaiting approval from IT',
                    'Awaiting Price Committee approval',
                    'Awaiting HEC Member approval',
                    'Awaiting HEC Member approval (Line Manager request)',
                    'Awaiting Price Committee approval (Line Manager request)'
                ];
            @endphp
            @foreach ($rows as $history)
            @php
                // Determine person
                if ($history->status == 1 && $history->who_approve && $history->approver) {
                    $person    = $history->approver;
                    $signature = $person->signature ?? null;
                } else {
                    $person    = $history->attendedBy;
                    $signature = null;
                }

                // Determine remark
                if ($history->status == 1)
                    $remark = !empty($history->comments) ? $history->comments : ($history->remark ?? '');
                elseif ($history->status == 2)
                    $remark = $history->rejection_reason ?? ($history->comments ?? ($history->remark ?? ''));
                else
                    $remark = $history->remark ?? '';

                $isDefault = false;
                foreach ($defaultMessages as $dm) {
                    if (str_contains($remark, $dm)) { $isDefault = true; break; }
                }
                if (empty(trim($remark)) || $isDefault) $remark = '—';

                // Step label
                if ($stepName === 'IT' && $history->status == 1 && $person)
                    $stepLabel = 'Approved by ' . trim(($person->fname ?? '') . ' ' . ($person->lname ?? ''));
                else
                    $stepLabel = $stepName;
            @endphp
            <tr>
                <td><strong>{{ $stepLabel }}</strong></td>
                <td>
                    @if ($person)
                        {{ ($person->fname ?? '') . ' ' . ($person->lname ?? '') }}
                        @if ($person->username)
                            <br><span style="color:#777;font-size:9.5px;">{{ $person->username }}</span>
                        @endif
                    @else
                        <span style="color:#aaa">—</span>
                    @endif
                </td>
                <td>
                    @if ($signature && $history->status == 1)
                        <img src="data:image/png;base64,{{ $signature }}" alt="Signature">
                    @else
                        <span style="color:#aaa">—</span>
                    @endif
                </td>
                <td>
                    @if ($history->status == 1)
                        <span class="step-approved">✔ Approved</span>
                    @elseif ($history->status == 2)
                        <span class="step-rejected">✘ Rejected</span>
                    @else
                        <span class="step-pending">⏳ Pending</span>
                    @endif
                </td>
                <td style="font-size:10px;">{{ $remark }}</td>
            </tr>
            @endforeach
            @endforeach
        </tbody>
    </table>

    {{-- ══ FOOTER ══ --}}
    <div class="doc-footer">
        CCBRT Change Request Form &nbsp;|&nbsp;
        Ref: CR-{{ str_pad($changeRequest->id, 5, '0', STR_PAD_LEFT) }} &nbsp;|&nbsp;
        Generated: {{ \Carbon\Carbon::now()->format('d M Y, H:i') }}
    </div>

</div>{{-- /.page --}}

<script>
    window.addEventListener('load', function () {
        if (window.location.href.includes('/pdf')) {
            setTimeout(function () { window.print(); }, 500);
        }
    });
</script>
</body>
</html>
