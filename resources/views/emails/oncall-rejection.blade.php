<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>On-Call Claim Rejected</title>
  <style>
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#333;background:#f5f5f5;margin:0;padding:0;line-height:1.6}
    .email-wrapper{max-width:600px;margin:0 auto;background:#fff}
    .header{background:linear-gradient(135deg,#007A33 0%,#005a25 100%);color:#fff;padding:30px 20px;text-align:center}
    .header h1{margin:0;font-size:26px;font-weight:600;color:#fff;letter-spacing:0.5px}
    .content{padding:30px 25px}
    .greeting{font-size:16px;color:#333;margin-bottom:20px}
    .status-banner{padding:20px;border-radius:8px;margin:25px 0;text-align:center;font-size:18px;font-weight:600}
    .status-rejected{background-color:#f8d7da;color:#721c24;border-left:4px solid #dc3545}
    .status-icon{font-size:24px;margin-right:8px;vertical-align:middle}
    .info-card{background-color:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:20px;margin:25px 0}
    .info-card h3{margin:0 0 15px 0;font-size:16px;color:#007A33;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #007A33;padding-bottom:8px}
    .info-row{display:table;width:100%;table-layout:fixed;padding:10px 0;border-bottom:1px solid #e9ecef}
    .info-row:last-child{border-bottom:none}
    .info-label{font-weight:600;color:#555;display:table-cell;width:40%;vertical-align:top}
    .info-value{color:#333;display:table-cell;width:60%;text-align:right;font-weight:500;vertical-align:top}
    .rejection-reason{background-color:#fff3cd;border:1px solid #ffc107;border-left:4px solid #ffc107;border-radius:6px;padding:15px;margin:20px 0}
    .rejection-reason strong{display:block;color:#856404;margin-bottom:8px;font-size:14px;text-transform:uppercase;letter-spacing:0.5px}
    .rejection-reason p{margin:0;color:#856404;white-space:pre-wrap;word-wrap:break-word}
    .action-button{display:inline-block;background-color:#007A33;color:#fff!important;text-decoration:none;padding:12px 30px;border-radius:6px;font-weight:600;font-size:16px;text-align:center;margin:25px 0;transition:background-color 0.3s}
    .action-button:hover{background-color:#005a25}
    .button-container{text-align:center}
    .signature{margin-top:25px;color:#333}
    .signature strong{color:#007A33}
    .footer{background-color:#f8f9fa;padding:25px;text-align:center;border-top:1px solid #e9ecef}
    .footer img{max-width:100%;height:auto;margin-bottom:15px}
    .footer-text{font-size:12px;color:#888;margin-top:10px}
    @media (max-width:600px){.content{padding:20px 15px}.header{padding:25px 15px}.header h1{font-size:22px}.info-row{display:block}.info-value{display:block;width:100%;text-align:left;margin-top:4px}.action-button{display:block;width:100%;box-sizing:border-box}}
  </style>
</head>
<body>
  <div class="email-wrapper">
    <div class="header">
      <h1>📋 On-Call Claim Rejected</h1>
    </div>

    <div class="content">
      @php
        $requester = $onCallRequest->user ?? null;
        $requesterName = trim(($requester->fname ?? '').' '.($requester->lname ?? ''));
        if ($requesterName === '') {
            $requesterName = $requester->username ?? 'Requester';
        }

        $rejectedByName = $rejectedBy
            ? (trim(($rejectedBy->fname ?? '').' '.($rejectedBy->lname ?? '')) ?: $rejectedBy->username ?? 'Approver')
            : 'Approver';

        $year = optional($onCallRequest->created_at)->format('Y');
        if (property_exists($onCallRequest, 'locum_year') && !empty($onCallRequest->locum_year)) {
            $year = $onCallRequest->locum_year;
        }
      @endphp

      <div class="greeting">
        <p>Dear <strong>{{ $requesterName }}</strong>,</p>
      </div>

      <div class="status-banner status-rejected">
        <span class="status-icon">❌</span>
        <strong>Your on-call claim has been REJECTED</strong>
      </div>

      <div class="info-card">
        <h3>Claim Details</h3>
        <div class="info-row">
          <span class="info-label">Period:</span>
          <span class="info-value">{{ $onCallRequest->locum_month }} {{ $year }}</span>
        </div>
        @if ($rejectedBy)
        <div class="info-row">
          <span class="info-label">Rejected by:</span>
          <span class="info-value">{{ $rejectedByName }}</span>
        </div>
        @endif
        <div class="info-row">
          <span class="info-label">Total Days:</span>
          <span class="info-value">{{ $onCallRequest->number_of_days }} day(s)</span>
        </div>
        <div class="info-row">
          <span class="info-label">Total Hours:</span>
          <span class="info-value">{{ number_format((float)$onCallRequest->total_hours, 2) }} hours</span>
        </div>
        <div class="info-row">
          <span class="info-label">Total Amount:</span>
          <span class="info-value" style="color: #007A33; font-weight: 600;">
            TZS {{ number_format((float)$onCallRequest->total_amount_payable, 2) }}
          </span>
        </div>
      </div>

      @if (!empty($rejectionReason))
        <div class="rejection-reason">
          <strong>Reason for Rejection:</strong>
          <p>{{ $rejectionReason }}</p>
        </div>
      @endif

      <div class="button-container">
        <a href="{{ $viewUrl }}" class="action-button" target="_blank">
          View Full Details in e-Docs
        </a>
      </div>

      <div class="signature">
        <p>Best regards,</p>
        <p><strong>CCBRT e-Docs System</strong></p>
      </div>

      <div class="footer-text" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; color: #666; font-size: 14px;">
        <p>This is an automated notification. Please do not reply to this email.</p>
      </div>
    </div>

    <div class="footer">
      <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk" alt="CCBRT Footer">
      <div class="footer-text">Powered by CCBRT e-Docs System</div>
    </div>
  </div>
</body>
</html>
