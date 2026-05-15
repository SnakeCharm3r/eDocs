<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Certificate of Service Required</title>
  <style>
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#333;background:#f5f5f5;margin:0;padding:0;line-height:1.6}
    .email-wrapper{max-width:600px;margin:0 auto;background:#fff}
    .header{background:linear-gradient(135deg,#007A33 0%,#005a25 100%);color:#fff;padding:30px 20px;text-align:center}
    .header h1{margin:0;font-size:26px;font-weight:600;color:#fff;letter-spacing:0.5px}
    .content{padding:30px 25px}
    .greeting{font-size:16px;color:#333;margin-bottom:20px}
    .status-banner{padding:20px;border-radius:8px;margin:25px 0;text-align:center;font-size:18px;font-weight:600;background-color:#d4edda;color:#155724;border-left:4px solid #28a745}
    .status-icon{font-size:24px;margin-right:8px;vertical-align:middle}
    .info-card{background-color:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:20px;margin:25px 0}
    .info-card h3{margin:0 0 15px 0;font-size:16px;color:#007A33;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #007A33;padding-bottom:8px}
    .info-row{display:table;width:100%;table-layout:fixed;padding:10px 0;border-bottom:1px solid #e9ecef}
    .info-row:last-child{border-bottom:none}
    .info-label{font-weight:600;color:#555;display:table-cell;width:40%;vertical-align:top}
    .info-value{color:#333;display:table-cell;width:60%;text-align:right;font-weight:500;vertical-align:top}
    .notice-box{background-color:#fff8e1;border:1px solid #ffc107;border-left:4px solid #ffc107;border-radius:6px;padding:15px;margin:20px 0;color:#856404;font-size:14px}
    .action-button{display:inline-block;background-color:#007A33;color:#fff!important;text-decoration:none;padding:12px 30px;border-radius:6px;font-weight:600;font-size:16px;text-align:center;margin:25px 0}
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
      <h1>🎖️ Certificate of Service Required</h1>
    </div>

    <div class="content">
      @php
        $staffName   = trim(($staff->fname ?? '') . ' ' . ($staff->lname ?? ''));
        $notifierName = trim(($notifiedBy->fname ?? '') . ' ' . ($notifiedBy->lname ?? '')) ?: ($notifiedBy->username ?? 'HR Officer');
      @endphp

      <div class="greeting">
        <p>Dear COO,</p>
        <p>
          HR has confirmed that <strong>{{ $staffName }}</strong> has successfully completed their
          employee clearance process. A <strong>Certificate of Service</strong> now needs to be
          created for this staff member.
        </p>
      </div>

      <div class="status-banner">
        <span class="status-icon">✅</span>
        <strong>Clearance Completed — Certificate of Service Required</strong>
      </div>

      <div class="info-card">
        <h3>Staff Details</h3>
        <div class="info-row">
          <span class="info-label">Staff Name:</span>
          <span class="info-value">{{ $staffName }}</span>
        </div>
        @if ($staff->ccbrt_code)
        <div class="info-row">
          <span class="info-label">Staff Code:</span>
          <span class="info-value">{{ $staff->ccbrt_code }}</span>
        </div>
        @endif
        @if ($staff->email)
        <div class="info-row">
          <span class="info-label">Email:</span>
          <span class="info-value">{{ $staff->email }}</span>
        </div>
        @endif
        @if ($clearance->last_working_day)
        <div class="info-row">
          <span class="info-label">Last Working Day:</span>
          <span class="info-value">{{ \Carbon\Carbon::parse($clearance->last_working_day)->format('d F Y') }}</span>
        </div>
        @endif
        @if ($clearance->reason_for_leaving)
        <div class="info-row">
          <span class="info-label">Reason for Leaving:</span>
          <span class="info-value">{{ $clearance->reason_for_leaving }}</span>
        </div>
        @endif
        <div class="info-row">
          <span class="info-label">Clearance Completed:</span>
          <span class="info-value">{{ $clearance->updated_at ? \Carbon\Carbon::parse($clearance->updated_at)->format('d F Y') : 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Notified By:</span>
          <span class="info-value">{{ $notifierName }} (HR)</span>
        </div>
        <div class="info-row">
          <span class="info-label">Notification Sent:</span>
          <span class="info-value">{{ \Carbon\Carbon::now()->format('d F Y, H:i') }}</span>
        </div>
      </div>

      <div class="notice-box">
        <strong>⚠ Action Required:</strong> Please log in to e-Docs and create the Certificate of Service
        for this staff member. The staff member is already listed as eligible in the certificate creation form.
      </div>

      <div class="button-container">
        <a href="{{ $createUrl }}" class="action-button" target="_blank">
          Create Certificate of Service
        </a>
      </div>

      <div class="signature">
        <p>Best regards,</p>
        <p><strong>CCBRT e-Docs System</strong></p>
      </div>

      <div style="margin-top:30px;padding-top:20px;border-top:1px solid #e9ecef;color:#666;font-size:14px;">
        <p>This is an automated notification sent by HR. Please do not reply to this email.</p>
      </div>
    </div>

    <div class="footer">
      <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk" alt="CCBRT Footer">
      <div class="footer-text">Powered by CCBRT e-Docs System</div>
    </div>
  </div>
</body>
</html>
