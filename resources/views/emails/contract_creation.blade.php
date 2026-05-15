<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Action Required</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #1a6e35; padding: 20px 32px; }
        .header-table { display: table; width: 100%; }
        .header-icon-cell { display: table-cell; width: 54px; vertical-align: middle; }
        .header-text-cell { display: table-cell; vertical-align: middle; }
        .header-icon { width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.2); text-align: center; line-height: 40px; }
        .header-title { color: #ffffff; font-size: 16px; font-weight: bold; margin: 0; }
        .header-subtitle { color: rgba(255,255,255,0.75); font-size: 12px; margin: 2px 0 0; }
        .body { padding: 32px 36px; color: #333; }
        .body p { margin: 0 0 14px; line-height: 1.6; }
        .greeting { font-size: 16px; margin-bottom: 4px; }
        .role-label { font-size: 13px; color: #666; margin-bottom: 20px; }
        .message-box { background: #fff8e1; border-left: 4px solid #f59e0b; border-radius: 0; padding: 12px 16px; margin: 0 0 18px; font-size: 14px; color: #7c5a00; }
        .info-box { background: #f8f9fa; border-left: 4px solid #1a6e35; border-radius: 0; padding: 14px 18px; margin: 18px 0; }
        .info-box table { width: 100%; border-collapse: collapse; }
        .info-box td { padding: 6px 0; font-size: 14px; vertical-align: top; }
        .info-box td:first-child { color: #666; width: 42%; }
        .info-box td:last-child { font-weight: 600; color: #222; }
        .pill { display: inline-block; font-size: 12px; padding: 2px 10px; border-radius: 20px; font-weight: 600; }
        .pill-success { background: #d1fae5; color: #065f46; }
        .pill-warning { background: #fef3c7; color: #92400e; }
        .pill-info { background: #dbeafe; color: #1e40af; }
        .pill-danger { background: #fee2e2; color: #991b1b; }
        .notice-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 12px 16px; margin: 18px 0; font-size: 13px; color: #166534; line-height: 1.6; }
        .btn { display: inline-block; background: #1a6e35; color: #ffffff !important; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; margin-top: 12px; font-size: 15px; }
        .divider { border: none; border-top: 1px solid #eee; margin: 24px 0; }
        .footer { background: #f8f9fa; padding: 16px 32px; text-align: center; font-size: 12px; color: #aaa; border-top: 1px solid #eee; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <div class="header-table">
            <div class="header-icon-cell">
                <div class="header-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
            </div>
            <div class="header-text-cell">
                <p class="header-title">CCBRT e-Docs System</p>
                <p class="header-subtitle">Document Management System</p>
            </div>
        </div>
    </div>

    <div class="body">

        {{-- Greeting --}}
        @php
            $recipientName = $recipient->name ?? null;
            $recipientRole = $recipient ? ucfirst(str_replace('-', ' ', $recipient->getRoleNames()->first() ?? '')) : null;
        @endphp

        @if($recipientName)
            <p class="greeting">Dear <strong>{{ $recipientName }}</strong>,</p>
            @if($recipientRole)
                <p class="role-label">{{ $recipientRole }}</p>
            @endif
        @else
            <p class="greeting">Dear Team,</p>
        @endif

        {{-- Custom message --}}
        @if(isset($customMessage) && $customMessage)
            <div class="message-box">{{ $customMessage }}</div>
        @endif

        <p>The following contract requires your attention. Please review the details carefully and log in to the system to take the necessary action.</p>

        {{-- Contract details --}}
        <div class="info-box">
            <table>
                <tr>
                    <td>Contract Title</td>
                    <td>{{ $contract->title ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Contract Number</td>
                    <td>{{ $contract->contract_number ?? 'Pending' }}</td>
                </tr>
                <tr>
                    <td>Department</td>
                    <td>{{ optional($contract->department)->dept_name ?? optional($contract->department)->name ?? 'N/A' }}</td>
                </tr>
                @if($contract->vendor)
                    <tr>
                        <td>Vendor</td>
                        <td>{{ optional($contract->vendor)->name ?? 'N/A' }}</td>
                    </tr>
                @endif
                @if($contract->start_date)
                    <tr>
                        <td>Start Date</td>
                        <td>{{ \Carbon\Carbon::parse($contract->start_date)->format('d.m.Y') }}</td>
                    </tr>
                @endif
                @if($contract->end_date)
                    <tr>
                        <td>End Date</td>
                        <td>{{ \Carbon\Carbon::parse($contract->end_date)->format('d.m.Y') }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Status</td>
                    <td>
                        <span class="pill pill-success">{{ ucfirst(str_replace('_', ' ', $contract->status ?? 'N/A')) }}</span>
                    </td>
                </tr>
                <tr>
                    <td>Stage</td>
                    <td>
                        <span class="pill pill-info">{{ ucfirst(str_replace('_', ' ', $contract->approval_stage ?? 'N/A')) }}</span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Informative notice --}}
        <div class="notice-box">
            You are receiving this notification because this contract is currently at the
            <strong>{{ ucfirst(str_replace('_', ' ', $contract->approval_stage ?? 'review')) }}</strong> stage
            and requires your action. Please log in and either approve, reject, or escalate as appropriate.
            If you believe you have received this in error, please contact your system administrator.
        </div>

        <a href="{{ url('/procurements/contracts/' . $contract->id) }}" class="btn" style="color:#ffffff !important;">View Contract</a>

        <hr class="divider">
        <p style="font-size:13px; color:#888; margin:0;">Best regards,<br><strong style="color:#333;">CCBRT e-Docs System @ {{ \Carbon\Carbon::now()->format('Y') }} </strong></p>

    </div>

    <div class="footer">
        This is an automated notification from the CCBRT Document Management System. Please do not reply to this email.
    </div>

</div>
</body>
</html>