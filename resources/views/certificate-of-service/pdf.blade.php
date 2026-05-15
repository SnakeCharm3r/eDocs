<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 16px; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DejaVu Sans', sans-serif; font-size:12pt; color:#121212; padding:12px; }
        .sheet { border:3px solid #2f7d3a; padding:18px 22px 40px; }
        .logo { text-align:center; margin-top:6px; }
        .logo img { height:75px; width:auto; }
        .strip-wrap { border:1px solid #b6c5b6; padding:4px; margin:18px 0 48px; }
        .strip { width:100%; border-collapse:collapse; table-layout:fixed; }
        .strip td { height:20px; }
        .title { text-align:center; font-weight:700; font-size:14pt; text-transform:uppercase; margin-bottom:8px; }
        .subtitle { text-align:center; font-weight:700; font-size:13pt; text-transform:uppercase; margin-bottom:24px; }
        .body-text { font-size:11.5pt; line-height:1.75; margin-bottom:5px; }
        .details { margin:6px 0 20px; padding:0; list-style:none; }
        .details li { margin-bottom:2px; }
        .closing { margin-top:14px; }
        .signoff-table { width:100%; border-collapse:collapse; margin-top:52px; }
        .signoff-table td { border:none; padding:0; vertical-align:bottom; }
        .td-sig { width:40%; }
        .td-stamp { width:25%; text-align:center; }
        .td-date { width:35%; text-align:right; vertical-align:bottom; padding-bottom:4px; }
        .sig-img { max-height:52px; max-width:200px; display:block; margin-bottom:4px; }
        .sig-line { width:130px; border-top:2px dotted #333; margin-bottom:8px; }
        .sig-name { font-weight:700; font-size:11pt; line-height:1.4; }
        .sig-title { font-size:11pt; line-height:1.4; }
        .stamp-img { max-height:90px; max-width:95px; display:block; margin:0 auto; }
        .issue-date { font-size:11pt; line-height:1.4; }
    </style>
</head>
<body>
    @php
        // $cooSignatureSrc, $approverSignatureSrc, $cooName, $cooTitle resolved by controller
        $signerName  = $templateSettings['signer_name']  ?: ($cooName  ?? 'Authorised Signatory');
        $signerTitle = $templateSettings['signer_title'] ?: ($cooTitle ?? 'Chief Operating Officer');
    @endphp
    <div class="sheet">
        <div class="logo">
            @if($certificateLogoSrc)
                <img src="{{ $certificateLogoSrc }}" alt="CCBRT Logo">
            @endif
        </div>

        <div class="strip-wrap">
            <table class="strip">
                <tr>
                    <td style="background:#0f6f2f;width:17%;"></td>
                    <td style="background:#1d7d35;width:5%;"></td>
                    <td style="background:#3d8f42;width:29%;"></td>
                    <td style="background:#79bf3c;width:18%;"></td>
                    <td style="background:#9ec347;width:5%;"></td>
                    <td style="background:#b7c945;width:13%;"></td>
                    <td style="background:#b8d83e;width:9%;"></td>
                    <td style="background:#8bc34a;width:4%;"></td>
                </tr>
            </table>
        </div>

        <div class="title">{{ $templateSettings['title'] }}</div>
        <div class="subtitle">{{ $templateSettings['subtitle'] }}</div>

        <p class="body-text">
            {!! $renderTemplateText($templateSettings['intro_text']) !!}
        </p>

        <ul class="details">
            <li class="body-text">{{ $templateSettings['start_date_label'] }}: {{ $certificate->date_of_joining?->format('jS F Y') }}</li>
            <li class="body-text">{{ $templateSettings['position_label'] }}: {{ $certificate->position_held }}</li>
            <li class="body-text">{{ $templateSettings['end_date_label'] }}: {{ $certificate->last_working_day?->format('jS F Y') }}</li>
        </ul>

        @if($certificate->remarks)
            <p class="body-text">{{ $certificate->remarks }}</p>
        @endif

        <p class="body-text closing">
            {{ $renderTemplateText($templateSettings['closing_text']) }}
        </p>

        @php $stampCenter = !empty($certificateStampSrc) && ($stampPosition ?? 'right') === 'center'; @endphp

        @if($stampCenter)
        {{-- Center: stamp overlaps signature area, date on right --}}
        <table class="signoff-table">
            <tr>
                <td style="width:55%;vertical-align:bottom;border:none;padding:0;">
                    {{-- Signature + stamp side-by-side in same cell --}}
                    <table style="border-collapse:collapse;border:none;">
                        <tr>
                            <td style="border:none;padding:0;vertical-align:bottom;">
                                @if($cooSignatureSrc)
                                    <img class="sig-img" src="{{ $cooSignatureSrc }}" alt="COO Signature">
                                @elseif($approverSignatureSrc)
                                    <img class="sig-img" src="{{ $approverSignatureSrc }}" alt="Approver Signature">
                                @else
                                    <div style="height:52px;"></div>
                                @endif
                            </td>
                            <td style="border:none;padding:0 0 0 10px;vertical-align:bottom;">
                                <img src="{{ $certificateStampSrc }}" alt="Official Stamp" style="max-height:85px;max-width:85px;display:block;margin-bottom:-4px;">
                            </td>
                        </tr>
                    </table>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $signerName }}</div>
                    <div class="sig-title">{{ $signerTitle }}</div>
                </td>
                <td style="width:45%;vertical-align:bottom;text-align:right;border:none;padding:0;padding-bottom:4px;">
                    <div class="issue-date">Date of Issue: {{ $certificate->issue_date?->format('jS F Y') }}</div>
                </td>
            </tr>
        </table>
        @else
        {{-- Right: signature LEFT | stamp CENTER | date RIGHT --}}
        <table class="signoff-table">
            <tr>
                <td class="td-sig">
                    @if($cooSignatureSrc)
                        <img class="sig-img" src="{{ $cooSignatureSrc }}" alt="COO Signature">
                    @elseif($approverSignatureSrc)
                        <img class="sig-img" src="{{ $approverSignatureSrc }}" alt="Approver Signature">
                    @else
                        <div style="height:52px;"></div>
                    @endif
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $signerName }}</div>
                    <div class="sig-title">{{ $signerTitle }}</div>
                </td>
                <td class="td-stamp">
                    @if(!empty($certificateStampSrc))
                        <img class="stamp-img" src="{{ $certificateStampSrc }}" alt="Official Stamp">
                    @endif
                </td>
                <td class="td-date">
                    <div class="issue-date">Date of Issue: {{ $certificate->issue_date?->format('jS F Y') }}</div>
                </td>
            </tr>
        </table>
        @endif
    </div>
</body>
</html>
