<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Staff Contract Expiry Reminder</title>
  <style>
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#333;background:#f5f5f5;margin:0;padding:0;line-height:1.6}
    .email-wrapper{max-width:650px;margin:0 auto;background:#fff}
    .header{background:linear-gradient(135deg,#007A33 0%,#005a25 100%);color:#fff;padding:30px 20px;text-align:center}
    .header h1{margin:0;font-size:24px;font-weight:600;color:#fff;letter-spacing:0.5px}
    .header p{margin:6px 0 0;opacity:.8;font-size:13px}
    .content{padding:30px 25px}
    .greeting{font-size:16px;color:#333;margin-bottom:20px}
    .status-banner{padding:18px;border-radius:8px;margin:20px 0;text-align:center;font-size:16px;font-weight:600}
    .status-urgent{background-color:#f8d7da;color:#721c24;border-left:4px solid #dc3545}
    .status-warning{background-color:#fff3cd;color:#856404;border-left:4px solid #ffc107}
    .status-info{background-color:#d4edda;color:#155724;border-left:4px solid #28a745}
    .status-icon{font-size:20px;margin-right:8px;vertical-align:middle}
    .info-card{background-color:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:20px;margin:25px 0}
    .info-card h3{margin:0 0 15px 0;font-size:16px;color:#007A33;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #007A33;padding-bottom:8px}
    .info-row{display:table;width:100%;table-layout:fixed;padding:10px 0;border-bottom:1px solid #e9ecef}
    .info-row:last-child{border-bottom:none}
    .info-label{font-weight:600;color:#555;display:table-cell;width:40%;vertical-align:top}
    .info-value{color:#333;display:table-cell;width:60%;text-align:right;font-weight:500;vertical-align:top}
    .staff-table{width:100%;border-collapse:collapse;margin:20px 0;font-size:13px}
    .staff-table th{background:#f8f9fa;padding:10px 8px;text-align:left;border-bottom:2px solid #dee2e6;font-weight:600;color:#555;font-size:12px;text-transform:uppercase;letter-spacing:.3px}
    .staff-table td{padding:10px 8px;border-bottom:1px solid #eee;vertical-align:top}
    .staff-table tr:last-child td{border-bottom:none}
    .badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600}
    .badge-danger{background:#f8d7da;color:#721c24}
    .badge-warning{background:#fff3cd;color:#856404}
    .badge-info{background:#d1ecf1;color:#0c5460}
    .badge-success{background:#d4edda;color:#155724}
    .requisition-note{background-color:#f8f9fa;border:1px solid #e9ecef;border-radius:6px;padding:14px;margin:15px 0;font-size:13px}
    .requisition-note .note-success{color:#155724}
    .requisition-note .note-danger{color:#721c24}
    .action-button{display:inline-block;background-color:#007A33;color:#fff!important;text-decoration:none;padding:12px 30px;border-radius:6px;font-weight:600;font-size:16px;text-align:center;margin:25px 0;transition:background-color 0.3s}
    .action-button:hover{background-color:#005a25}
    .button-container{text-align:center}
    .signature{margin-top:25px;color:#333}
    .signature strong{color:#007A33}
    .footer{background-color:#f8f9fa;padding:25px;text-align:center;border-top:1px solid #e9ecef}
    .footer img{max-width:100%;height:auto;margin-bottom:15px}
    .footer-text{font-size:12px;color:#888;margin-top:10px}
    .staff-name{font-weight:600;color:#333}
    .staff-meta{font-size:11px;color:#888;margin-top:2px}
    @media (max-width:600px){.content{padding:20px 15px}.header{padding:25px 15px}.header h1{font-size:20px}.info-row{display:block}.info-value{display:block;width:100%;text-align:left;margin-top:4px}.action-button{display:block;width:100%;box-sizing:border-box}}
  </style>
</head>
<body>
  <div class="email-wrapper">
    <div class="header">
      @if($milestone === 'day_of')
        <h1>⚠️ URGENT: Staff Contracts Expiring Today</h1>
      @elseif($milestone === '1_week')
        <h1>🔔 Staff Contracts Expiring in {{ $milestoneLabel }}</h1>
      @else
        <h1>📋 Staff Contract Expiry Reminder</h1>
      @endif
      <p>{{ now()->format('d F Y') }} &bull; CCBRT e-DOCS</p>
    </div>

    <div class="content">
      <div class="greeting">
        <p>Dear <strong>HR Team</strong>,</p>
      </div>

      @if($milestone === 'day_of')
        <div class="status-banner status-urgent">
          <span class="status-icon">🚨</span>
          <strong>{{ $staffList->count() }} staff contract(s) expiring TODAY — immediate action required</strong>
        </div>
      @elseif($milestone === '1_week')
        <div class="status-banner status-warning">
          <span class="status-icon">⏰</span>
          <strong>{{ $staffList->count() }} staff contract(s) expiring in 1 week</strong>
        </div>
      @else
        <div class="status-banner status-info">
          <span class="status-icon">📌</span>
          <strong>{{ $staffList->count() }} staff contract(s) expiring in {{ $milestoneLabel }}</strong>
        </div>
      @endif

      <div class="info-card">
        <h3>Summary</h3>
        <div class="info-row">
          <span class="info-label">Milestone:</span>
          <span class="info-value">{{ $milestoneLabel }} before expiry</span>
        </div>
        <div class="info-row">
          <span class="info-label">Staff Affected:</span>
          <span class="info-value">{{ $staffList->count() }} member(s)</span>
        </div>
        <div class="info-row">
          <span class="info-label">Report Date:</span>
          <span class="info-value">{{ now()->format('d F Y, H:i') }}</span>
        </div>
      </div>

      <div class="info-card">
        <h3>Affected Staff</h3>
        <table class="staff-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Staff</th>
              <th>Contract Type</th>
              <th>End Date</th>
              <th>Time Left</th>
            </tr>
          </thead>
          <tbody>
            @foreach($staffList as $i => $staff)
              @php
                $daysLeft = (int) now()->startOfDay()->diffInDays($staff->ending_date, false);
                $months = intdiv($daysLeft, 30);
                $remainDays = $daysLeft % 30;
                if ($daysLeft <= 0) {
                    $daysLabel = 'Today';
                    $badgeClass = 'badge-danger';
                } elseif ($daysLeft == 1) {
                    $daysLabel = '1 day';
                    $badgeClass = 'badge-danger';
                } elseif ($daysLeft < 30) {
                    $daysLabel = $daysLeft . ' days';
                    $badgeClass = $daysLeft <= 7 ? 'badge-danger' : 'badge-warning';
                } elseif ($remainDays == 0) {
                    $daysLabel = $months . ' month' . ($months > 1 ? 's' : '');
                    $badgeClass = 'badge-info';
                } else {
                    $daysLabel = $months . ' month' . ($months > 1 ? 's' : '') . ', ' . $remainDays . ' day' . ($remainDays > 1 ? 's' : '');
                    $badgeClass = 'badge-info';
                }
              @endphp
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                  <div class="staff-name">{{ $staff->fname }} {{ $staff->lname }}</div>
                  <div class="staff-meta">{{ $staff->ccbrt_code ?? '—' }} &middot; {{ optional($staff->department)->dept_name ?? '—' }}</div>
                </td>
                <td>{{ optional($staff->employmentType)->employment_type ?? '—' }}</td>
                <td>{{ \Carbon\Carbon::parse($staff->ending_date)->format('d M Y') }}</td>
                <td><span class="badge {{ $badgeClass }}">{{ $daysLabel }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @if($staffList->count() > 0)
        @php
          $hasRequisition = $staffList->filter(fn($s) => $s->has_active_requisition ?? false);
          $noRequisition = $staffList->filter(fn($s) => !($s->has_active_requisition ?? false));
        @endphp
        <div class="requisition-note">
          @if($hasRequisition->count() > 0)
            <p class="note-success" style="margin:0 0 6px">
              <strong>✅ {{ $hasRequisition->count() }} staff member(s)</strong> already have an active recruitment requisition linked.
            </p>
          @endif
          @if($noRequisition->count() > 0)
            <p class="note-danger" style="margin:0">
              <strong>⚠️ {{ $noRequisition->count() }} staff member(s)</strong> do NOT have an active recruitment requisition. Please initiate one if renewal is needed.
            </p>
          @endif
        </div>
      @endif

      <p style="color:#555;font-size:14px">Please log in to e-DOCS to review these contracts and take the appropriate action (renew, extend, or plan for transition).</p>

      <div class="button-container">
        <a href="{{ url('/staff-details') }}" class="action-button" target="_blank">
          View Staff Details in e-Docs
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
