<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Access Form – {{ trim(($requestUser->fname ?? '') . ' ' . ($requestUser->lname ?? '')) }}</title>
    <style>
        /* ── Reset ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #fff;
        }

        /* ── Print button (screen only) ── */
        .print-btn-bar {
            position: fixed;
            top: 12px; right: 16px;
            display: flex;
            gap: 8px;
            z-index: 9999;
        }
        .print-btn-bar button {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }
        .btn-print  { background: #007A33; color: #fff; }
        .btn-back   { background: #6c757d; color: #fff; }
        .btn-print:hover { background: #005f27; }
        .btn-back:hover  { background: #545b62; }

        /* ── A4 page wrapper ── */
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 10mm auto;
            padding: 12mm 14mm;
            background: #fff;
            box-shadow: 0 0 12px rgba(0,0,0,.15);
        }

        /* ── Header ── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #007A33;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .doc-header .org-info h1 {
            font-size: 15px;
            color: #007A33;
            font-weight: 700;
            letter-spacing: .3px;
        }
        .doc-header .org-info p {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }
        .doc-header img { height: 52px; width: auto; }

        .form-ref-bar {
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
        .form-ref-bar strong { font-size: 12px; }

        /* ── Section heading ── */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            background: #007A33;
            padding: 5px 10px;
            margin: 14px 0 0;
            border-radius: 2px 2px 0 0;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        /* ── Info tables ── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #c8e6c9;
            margin-bottom: 0;
        }
        .info-table td, .info-table th {
            border: 1px solid #c8e6c9;
            padding: 5px 8px;
            vertical-align: top;
            font-size: 10.5px;
            line-height: 1.4;
        }
        .info-table .lbl {
            background: #f1f8f1;
            width: 32%;
            font-weight: 600;
            color: #2d5a27;
        }
        .info-table .val { color: #222; }
        .info-table .val.na { color: #aaa; font-style: italic; }

        /* ── Access grid ── */
        .access-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #c8e6c9;
            margin-bottom: 0;
        }
        .access-table thead tr {
            background: #e8f5e9;
        }
        .access-table thead th {
            border: 1px solid #c8e6c9;
            padding: 5px 8px;
            font-size: 10px;
            font-weight: 700;
            color: #1b5e20;
            text-align: left;
        }
        .access-table tbody td {
            border: 1px solid #c8e6c9;
            padding: 5px 8px;
            font-size: 10.5px;
            vertical-align: middle;
        }
        .access-table tbody tr:nth-child(even) { background: #fafffe; }
        .access-table .chk { text-align: center; font-size: 13px; }

        /* ── Status badge ── */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
        }
        .badge-approved { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-pending  { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .badge-rejected { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* ── Signature block ── */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #c8e6c9;
        }
        .sig-table td {
            border: 1px solid #c8e6c9;
            padding: 6px 8px;
            font-size: 10px;
            vertical-align: top;
            width: 25%;
        }
        .sig-table .sig-role {
            font-weight: 700;
            color: #007A33;
            font-size: 10.5px;
            background: #f1f8f1;
        }
        .sig-table img {
            max-width: 130px;
            max-height: 50px;
            display: block;
            margin-top: 4px;
        }
        .sig-line {
            border-top: 1px solid #999;
            margin-top: 32px;
            margin-bottom: 3px;
        }
        .sig-label { color: #666; font-size: 9.5px; }

        /* ── Footer ── */
        .doc-footer {
            border-top: 2px solid #007A33;
            margin-top: 16px;
            padding-top: 6px;
            text-align: center;
            font-size: 9px;
            color: #888;
        }

        /* ── Utilities ── */
        .mt-1 { margin-top: 4px; }
        .text-muted { color: #777; }

        /* ── Print rules ── */
        @media print {
            body { margin: 0; }
            .print-btn-bar { display: none !important; }
            .page {
                width: 100%; margin: 0; padding: 10mm 12mm;
                box-shadow: none;
            }
            @page { size: A4 portrait; margin: 0; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .section-title, .sig-table .sig-role { background: #007A33 !important; color: #fff !important; }
            .access-table thead tr { background: #e8f5e9 !important; }
            .info-table .lbl { background: #f1f8f1 !important; }
            .form-ref-bar { background: #007A33 !important; color: #fff !important; }
        }
    </style>
</head>
<body>

{{-- ── Print / Back buttons (hidden on print) ── --}}
<div class="print-btn-bar">
    <button class="btn-back" onclick="window.history.back()">← Back</button>
    <button class="btn-print" onclick="window.print()">⬇ Download PDF</button>
</div>

<div class="page">

    {{-- ══ HEADER ══ --}}
    <div class="doc-header">
        <div class="org-info">
            <h1>CCBRT – ICT Access Request Form</h1>
            <p>Comprehensive Community-Based Rehabilitation in Tanzania</p>
        </div>
        <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo"
             onerror="this.style.display='none'">
    </div>

    {{-- ── Reference bar ── --}}
    <div class="form-ref-bar">
        <span><strong>Form Ref:</strong> ICT-{{ str_pad($ictForm->access_id, 5, '0', STR_PAD_LEFT) }}</span>
        <span><strong>Submitted:</strong> {{ \Carbon\Carbon::parse($ictForm->created_at)->format('d M Y, H:i') }}</span>
        <span>
            <strong>Status:</strong>
            @if ($ictForm->status == 1)
                <span class="badge badge-approved">Approved</span>
            @elseif ($ictForm->status == -1)
                <span class="badge badge-rejected">Rejected</span>
            @else
                <span class="badge badge-pending">Pending</span>
            @endif
        </span>
    </div>

    {{-- ══ 1. REQUEST TYPE ══ --}}
    <div class="section-title">1 &nbsp; Request Type</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Access Action</td>
            <td class="val">{{ $ictForm->access_required ?? 'N/A' }}</td>
            <td class="lbl">Action Type</td>
            <td class="val">{{ $ictForm->action_required ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="lbl">User Type</td>
            <td class="val">{{ $ictForm->user_type ?? 'N/A' }}</td>
            <td class="lbl">Access Period</td>
            <td class="val">
                @if ($ictForm->start_date || $ictForm->end_date)
                    {{ $ictForm->start_date ? \Carbon\Carbon::parse($ictForm->start_date)->format('d M Y') : '—' }}
                    &nbsp;→&nbsp;
                    {{ $ictForm->end_date   ? \Carbon\Carbon::parse($ictForm->end_date)->format('d M Y')   : 'Indefinite' }}
                @else
                    <span class="na">Not specified</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- ══ 2. PERSONAL DETAILS ══ --}}
    <div class="section-title">2 &nbsp; Personal Details</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Full Name</td>
            <td class="val" colspan="3">
                {{ trim(($requestUser->fname ?? '') . ' ' . ($requestUser->mname ?? '') . ' ' . ($requestUser->lname ?? '')) }}
            </td>
        </tr>
        <tr>
            <td class="lbl">Email Address</td>
            <td class="val">{{ $ictForm->user_email ?? $requestUser->email ?? 'N/A' }}</td>
            <td class="lbl">Mobile</td>
            <td class="val">{{ $requestUser->mobile ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="lbl">Department</td>
            <td class="val">{{ $ictForm->dept_name ?? 'N/A' }}</td>
            <td class="lbl">Employment Type</td>
            <td class="val">{{ $ictForm->employment_type ?? 'N/A' }}</td>
        </tr>
        @if ($requestUser->professional_reg_number)
        <tr>
            <td class="lbl">Professional Reg. No.</td>
            <td class="val" colspan="3">{{ $requestUser->professional_reg_number }}</td>
        </tr>
        @endif
    </table>

    {{-- ══ 3. SYSTEM ACCESS ══ --}}
    <div class="section-title">3 &nbsp; System Access Details</div>
    <table class="access-table">
        <thead>
            <tr>
                <th style="width:35%">System / Resource</th>
                <th style="width:15%">Requested</th>
                <th>Access Level / Details</th>
            </tr>
        </thead>
        <tbody>
            {{-- Domain (Active Directory) --}}
            <tr>
                <td><strong>Domain Access (Active Directory)</strong></td>
                <td class="chk">{{ $ictForm->active_drt ? '✔' : '–' }}</td>
                <td>
                    @if ($activeDrtPrivilege)
                        {{ $activeDrtPrivilege->prv_name }}
                    @elseif ($ictForm->active_drt)
                        {{ $ictForm->active_drt }}
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
            </tr>
            {{-- Email --}}
            <tr>
                <td><strong>Email Access (CCBRT Email)</strong></td>
                <td class="chk">{{ $ictForm->email ? '✔' : '–' }}</td>
                <td>
                    @if ($emailPrivilege)
                        {{ $emailPrivilege->prv_name }}
                        @if ($ictForm->requested_email_address)
                            <br><span class="text-muted">Requested: {{ $ictForm->requested_email_address }}</span>
                        @endif
                    @elseif ($ictForm->email)
                        {{ $ictForm->email }}
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
            </tr>
            {{-- Network Folder --}}
            <tr>
                <td><strong>Network Folder Access</strong></td>
                <td class="chk">{{ $ictForm->network_folder ? '✔' : '–' }}</td>
                <td>
                    @if ($ictForm->network_folder)
                        Folder: {{ $ictForm->network_folder }}
                        @if ($folderPrivilege)
                            &nbsp;|&nbsp; Level: {{ $folderPrivilege->prv_name }}
                        @endif
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
            </tr>
            {{-- HMIS --}}
            <tr>
                <td><strong>HealthAI HMIS</strong></td>
                <td class="chk">{{ !empty($hmaccess) && $hmaccess->count() ? '✔' : '–' }}</td>
                <td>
                    @if ($hmaccess && $hmaccess->count())
                        {{ $hmaccess->pluck('names')->implode(', ') }}
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
            </tr>
            {{-- Aruti --}}
            <tr>
                <td><strong>Aruti HR MIS</strong></td>
                <td class="chk">{{ $arutiPrivilege ? '✔' : '–' }}</td>
                <td>
                    @if ($arutiPrivilege)
                        {{ $arutiPrivilege->aruti_name ?? $arutiPrivilege->name ?? $ictForm->aruti }}
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
            </tr>
            {{-- eDocs --}}
            <tr>
                <td><strong>eDocs System</strong></td>
                <td class="chk">{{ $edocsLevels && $edocsLevels->count() ? '✔' : '–' }}</td>
                <td>
                    @if ($edocsLevels && $edocsLevels->count())
                        {{ $edocsLevels->pluck('name')->implode(', ') }}
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
            </tr>
            {{-- VPN --}}
            <tr>
                <td><strong>Network Access (VPN)</strong></td>
                <td class="chk">{{ $vpnPrivilege ? '✔' : '–' }}</td>
                <td>{{ $vpnPrivilege ? $vpnPrivilege->prv_name : '<span class="text-muted">N/A</span>' }}</td>
            </tr>
            {{-- SAP --}}
            <tr>
                <td><strong>SAP ERP</strong></td>
                <td class="chk">{{ $sapLevel ? '✔' : '–' }}</td>
                <td>
                    @if ($sapLevel)
                        {{ $sapLevel->access_name ?? $sapLevel->name ?? $ictForm->ASPId }}
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
            </tr>
            {{-- PABX --}}
            <tr>
                <td><strong>Call Manager (PABX)</strong></td>
                <td class="chk">{{ $pbaxPrivilege ? '✔' : '–' }}</td>
                <td>{{ $pbaxPrivilege ? $pbaxPrivilege->prv_name : '<span class="text-muted">N/A</span>' }}</td>
            </tr>
            {{-- Access Key Cards --}}
            @php
                $accessKeyCardIds = $ictForm->access_key_card_id;
                if (is_string($accessKeyCardIds)) { $accessKeyCardIds = json_decode($accessKeyCardIds, true); }
                if (!is_array($accessKeyCardIds)) { $accessKeyCardIds = []; }
            @endphp
            <tr>
                <td><strong>Physical Access Key Card</strong></td>
                <td class="chk">{{ !empty($accessKeyCardIds) ? '✔' : '–' }}</td>
                <td>
                    @if (!empty($accessKeyCardIds))
                        @foreach ($accessKeyCardIds as $cardId)
                            @php $card = \App\Models\AccessKeyCard::find($cardId); @endphp
                            {{ $card ? ($card->card_number ?? $cardId) : $cardId }}@if (!$loop->last), @endif
                        @endforeach
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ══ 4. HARDWARE REQUEST ══ --}}
    @if ($ictForm->hardware_request)
    <div class="section-title">4 &nbsp; Hardware Request</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Requested Hardware</td>
            <td class="val">{{ $ictForm->hardware_request }}</td>
        </tr>
    </table>
    @endif

    {{-- ══ 5. APPROVAL SIGNATURES ══ --}}
    <div class="section-title">{{ $ictForm->hardware_request ? '5' : '4' }} &nbsp; Approval Signatures</div>
    <table class="sig-table">
        <tr>
            {{-- Requester --}}
            <td>
                <div class="sig-role">Requester</div>
                <div style="margin-top:6px; font-size:10.5px;">
                    {{ trim(($requestUser->fname ?? '') . ' ' . ($requestUser->lname ?? '')) }}
                    @if ($requestUser->jobTitle)
                        <br><span class="text-muted">{{ $requestUser->jobTitle->job_title ?? '' }}</span>
                    @endif
                </div>
                @if ($requestUser->signature)
                    <img src="data:image/png;base64,{{ $requestUser->signature }}" alt="Signature">
                @else
                    <div class="sig-line"></div>
                    <div class="sig-label">Signature</div>
                @endif
                <div class="mt-1 text-muted">{{ \Carbon\Carbon::parse($ictForm->created_at)->format('d M Y') }}</div>
            </td>

            {{-- Line Manager --}}
            <td>
                <div class="sig-role">Line Manager</div>
                @if ($lineManager)
                    <div style="margin-top:6px; font-size:10.5px;">
                        {{ trim(($lineManager->fname ?? '') . ' ' . ($lineManager->lname ?? '')) }}
                    </div>
                    @if ($lineManager->signature)
                        <img src="data:image/png;base64,{{ $lineManager->signature }}" alt="Signature">
                    @else
                        <div class="sig-line"></div>
                        <div class="sig-label">Signature</div>
                    @endif
                    <div class="mt-1 text-muted">{{ \Carbon\Carbon::parse($lineManager->updated_at)->format('d M Y') }}</div>
                @else
                    <div class="sig-line"></div>
                    <div class="sig-label">Signature &amp; Date</div>
                @endif
            </td>

            {{-- HEC / CEO Approver --}}
            @php $hecOrCeo = $approver ?? $ceoApprover; @endphp
            <td>
                <div class="sig-role">{{ $isRequesterHecMember ? 'CEO' : 'HEC Member' }}</div>
                @if ($hecOrCeo)
                    <div style="margin-top:6px; font-size:10.5px;">
                        {{ trim(($hecOrCeo->fname ?? '') . ' ' . ($hecOrCeo->lname ?? '')) }}
                    </div>
                    @if ($hecOrCeo->signature)
                        <img src="data:image/png;base64,{{ $hecOrCeo->signature }}" alt="Signature">
                    @else
                        <div class="sig-line"></div>
                        <div class="sig-label">Signature</div>
                    @endif
                    <div class="mt-1 text-muted">{{ \Carbon\Carbon::parse($hecOrCeo->updated_at)->format('d M Y') }}</div>
                @else
                    <div class="sig-line"></div>
                    <div class="sig-label">Signature &amp; Date</div>
                @endif
            </td>

            {{-- IT Officer --}}
            <td>
                <div class="sig-role">IT Officer</div>
                @if ($itOfficer)
                    <div style="margin-top:6px; font-size:10.5px;">
                        {{ trim(($itOfficer->fname ?? '') . ' ' . ($itOfficer->lname ?? '')) }}
                    </div>
                    @if ($itOfficer->signature)
                        <img src="data:image/png;base64,{{ $itOfficer->signature }}" alt="Signature">
                    @else
                        <div class="sig-line"></div>
                        <div class="sig-label">Signature</div>
                    @endif
                    <div class="mt-1 text-muted">{{ \Carbon\Carbon::parse($itOfficer->updated_at)->format('d M Y') }}</div>
                @else
                    <div class="sig-line"></div>
                    <div class="sig-label">Signature &amp; Date</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ══ FOOTER ══ --}}
    <div class="doc-footer">
        CCBRT ICT Access Request Form &nbsp;|&nbsp;
        Generated: {{ \Carbon\Carbon::now()->format('d M Y, H:i') }} &nbsp;|&nbsp;
        Ref: ICT-{{ str_pad($ictForm->access_id, 5, '0', STR_PAD_LEFT) }}
    </div>

</div>{{-- /.page --}}

<script>
    // Auto-print when opened directly as PDF page
    window.addEventListener('load', function () {
        // Only auto-print if opened via the pdf route (not embedded)
        if (window.location.href.includes('/pdf')) {
            // Small delay to let images render
            setTimeout(function () { window.print(); }, 600);
        }
    });
</script>
</body>
</html>
