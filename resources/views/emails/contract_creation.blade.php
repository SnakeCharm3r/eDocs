<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Contract Action Required</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .body { padding: 32px 36px; color: #333; }
        .body p { margin: 0 0 14px; line-height: 1.6; }
        .greeting { font-size: 16px; margin-bottom: 6px; }
        .role-label { font-size: 13px; color: #666; margin-bottom: 20px; }
        .info-box { background: #f8f9fa; border-left: 4px solid #28a745; border-radius: 4px; padding: 14px 18px; margin: 18px 0; }
        .info-box table { width: 100%; border-collapse: collapse; }
        .info-box td { padding: 6px 0; font-size: 14px; vertical-align: top; }
        .info-box td:first-child { color: #666; width: 42%; }
        .info-box td:last-child { font-weight: 600; color: #222; }
        .message-box { background: #fff8e1; border-left: 4px solid #f59e0b; border-radius: 4px; padding: 12px 16px; margin: 14px 0; font-size: 14px; color: #555; }
        .btn { display: inline-block; background: #28a745; color: #ffffff !important; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; margin-top: 12px; font-size: 15px; }
        .footer { background: #f8f9fa; padding: 16px 32px; text-align: center; font-size: 12px; color: #aaa; border-top: 1px solid #eee; }
        .divider { border: none; border-top: 1px solid #eee; margin: 24px 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="body">
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

        @if(isset($customMessage) && $customMessage)
            <div class="message-box">{{ $customMessage }}</div>
        @endif

        <p>The following contract requires your attention. Please log in to the system to review and take the necessary action.</p>

        <div class="info-box">
            <table>
                <tr><td>Contract Title:</td><td>{{ $contract->title ?? 'N/A' }}</td></tr>
                <tr><td>Contract Number:</td><td>{{ $contract->contract_number ?? 'Pending' }}</td></tr>
                <tr><td>Department:</td><td>{{ optional($contract->department)->dept_name ?? optional($contract->department)->name ?? 'N/A' }}</td></tr>
                @if($contract->vendor)
                <tr><td>Vendor:</td><td>{{ optional($contract->vendor)->name ?? 'N/A' }}</td></tr>
                @endif
                @if($contract->start_date)
                <tr><td>Start Date:</td><td>{{ \Carbon\Carbon::parse($contract->start_date)->format('d M Y') }}</td></tr>
                @endif
                @if($contract->end_date)
                <tr><td>End Date:</td><td>{{ \Carbon\Carbon::parse($contract->end_date)->format('d M Y') }}</td></tr>
                @endif
                <tr><td>Status:</td><td>{{ ucfirst(str_replace('_',' ', $contract->status ?? 'N/A')) }}</td></tr>
                <tr><td>Stage:</td><td>{{ ucfirst(str_replace('_',' ', $contract->approval_stage ?? 'N/A')) }}</td></tr>
            </table>
        </div>

        <a href="{{ url('/procurements/contracts/' . $contract->id) }}" class="btn" style="color:#ffffff !important;">View Contract</a>

        <hr class="divider">
        <p style="font-size:13px;color:#888;margin:0;">Best regards,<br><strong style="color:#333;">CCBRT e-Docs System</strong></p>
    </div>
    <div class="footer">
        This is an automated notification from the CCBRT Document Management System. Please do not reply to this email.
    </div>
</div>
</body>
</html>
