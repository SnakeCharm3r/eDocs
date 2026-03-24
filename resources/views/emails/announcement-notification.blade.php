<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $isNew ? 'New' : 'Updated' }} Announcement</title>
  <style>
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#333;background:#f5f5f5;margin:0;padding:0;line-height:1.6}
    .email-wrapper{max-width:600px;margin:0 auto;background:#fff}
    .header{background:linear-gradient(135deg,#007A33 0%,#005a25 100%);color:#fff;padding:30px 20px;text-align:center}
    .header h1{margin:0;font-size:26px;font-weight:600;color:#fff;letter-spacing:0.5px}
    .content{padding:30px 25px}
    .greeting{font-size:16px;color:#333;margin-bottom:20px}
    .info-card{background-color:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:20px;margin:25px 0}
    .info-card h3{margin:0 0 15px 0;font-size:16px;color:#007A33;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #007A33;padding-bottom:8px}
    .info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #e9ecef}
    .info-row:last-child{border-bottom:none}
    .info-label{font-weight:600;color:#555;flex:1}
    .info-value{color:#333;flex:1;text-align:right;font-weight:500}
    .content-preview{color:#555;font-size:14px;line-height:1.6;margin-top:10px}
    .action-button{display:inline-block;background-color:#007A33;color:#fff!important;text-decoration:none;padding:12px 30px;border-radius:6px;font-weight:600;font-size:16px;text-align:center;margin:25px 0;transition:background-color 0.3s}
    .action-button:hover{background-color:#005a25}
    .button-container{text-align:center}
    .signature{margin-top:25px;color:#333}
    .signature strong{color:#007A33}
    .footer{background-color:#f8f9fa;padding:25px;text-align:center;border-top:1px solid #e9ecef}
    .footer-text{font-size:12px;color:#888;margin-top:10px}
    @media (max-width:600px){.content{padding:20px 15px}.header{padding:25px 15px}.header h1{font-size:22px}.info-row{flex-direction:column}.info-value{text-align:left;margin-top:5px}.action-button{display:block;width:100%;box-sizing:border-box}}
  </style>
</head>
<body>
  <div class="email-wrapper">
    <div class="header">
      <h1>📢 {{ $isNew ? 'New' : 'Updated' }} Announcement</h1>
    </div>

    <div class="content">
      @php
        $recipientName = trim(($recipient->fname ?? '').' '.($recipient->lname ?? ''));
        if ($recipientName === '') {
            $recipientName = $recipient->username ?? 'User';
        }
      @endphp

      <div class="greeting">
        <p>Dear <strong>{{ $recipientName }}</strong>,</p>
        <p>{{ $isNew ? 'A new announcement has been posted and is available for you to view.' : 'An announcement has been updated.' }}</p>
      </div>

      <div class="info-card">
        <h3>Announcement Details</h3>
        <div class="info-row">
          <span class="info-label">Title:</span>
          <span class="info-value">{{ $announcement->title }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Posted by:</span>
          <span class="info-value">{{ $announcement->user->username ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">{{ $isNew ? 'Published' : 'Updated' }}:</span>
          <span class="info-value">{{ $isNew ? ($announcement->created_at?->format('d F Y, H:i') ?? 'N/A') : ($announcement->updated_at?->format('d F Y, H:i') ?? 'N/A') }}</span>
        </div>
      </div>
      @if ($announcement->content)
      <div class="content-preview">
        {!! \Illuminate\Support\Str::limit(strip_tags($announcement->content), 200) !!}
      </div>
      @endif

      <div class="button-container">
        <a href="{{ $viewUrl }}" class="action-button" target="_blank">
          View Announcements in e-Docs
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
      <div class="footer-text">Powered by CCBRT e-Docs System</div>
    </div>
  </div>
</body>
</html>
