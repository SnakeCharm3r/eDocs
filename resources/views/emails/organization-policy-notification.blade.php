<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $isNew ? 'New' : 'Updated' }} Organization Policy</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #333; background: #f0f2f5; margin: 0; padding: 0; line-height: 1.6; }
    .email-outer { padding: 32px 16px; }
    .email-wrapper { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .email-header { background-color: #007A33; padding: 28px 30px; text-align: center; }
    .email-header .badge { display: inline-block; background: rgba(255,255,255,0.18); color: #fff; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding: 4px 12px; border-radius: 20px; margin-bottom: 10px; }
    .email-header h1 { margin: 0; color: #fff; font-size: 20px; font-weight: 700; }
    .email-header p { margin: 6px 0 0; color: rgba(255,255,255,0.85); font-size: 13px; }
    .content { padding: 32px 30px; }
    .greeting p { margin: 0 0 10px; font-size: 15px; color: #333; }
    .info-card { background: #f8faf9; border: 1px solid #d8ede1; border-radius: 8px; margin: 24px 0; overflow: hidden; }
    .info-card-header { background: #007A33; padding: 10px 18px; }
    .info-card-header span { color: #fff; font-size: 13px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; }
    .info-row { display: flex; justify-content: space-between; padding: 11px 18px; border-bottom: 1px solid #e4ede8; }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-weight: 600; color: #555; font-size: 14px; }
    .info-value { color: #222; font-size: 14px; text-align: right; max-width: 60%; word-break: break-word; }
    .button-container { text-align: center; margin: 28px 0 8px; }
    .action-button { display: inline-block; background-color: #007A33; color: #fff !important; text-decoration: none; padding: 13px 34px; border-radius: 6px; font-weight: 700; font-size: 15px; }
    .signature { margin-top: 28px; padding-top: 20px; border-top: 1px solid #e9ecef; color: #555; font-size: 14px; }
    .signature strong { color: #007A33; }
    @media (max-width: 600px) {
      .content { padding: 24px 18px; }
      .email-header { padding: 22px 18px; }
      .info-row { flex-direction: column; gap: 4px; }
      .info-value { text-align: left; max-width: 100%; }
      .action-button { display: block; text-align: center; }
    }
  </style>
</head>
<body>
  <div class="email-outer">
  <div class="email-wrapper">

    {{-- Header --}}
    <div class="email-header">
      <div class="badge">{{ $isNew ? 'New Policy' : 'Policy Updated' }}</div>
      <h1>Organization Policy</h1>
      <p>CCBRT e-Docs</p>
    </div>

    {{-- Body --}}
    <div class="content">
      @php
        $recipientName = trim(($recipient->fname ?? '') . ' ' . ($recipient->lname ?? ''));
        if ($recipientName === '') {
            $recipientName = $recipient->username ?? 'Staff Member';
        }
      @endphp

      <div class="greeting">
        <p>Dear <strong>{{ $recipientName }}</strong>,</p>
        <p>An organization policy has been <strong>{{ $isNew ? 'published' : 'updated' }}</strong> and is now available for you to view in the e-Docs system.</p>
      </div>

      <div class="info-card">
        <div class="info-card-header"><span>Policy Details</span></div>

        <div class="info-row">
          <span class="info-label">Title</span>
          <span class="info-value">{{ $policy->title }}</span>
        </div>

        @if ($policy->document_code)
        <div class="info-row">
          <span class="info-label">Document Code</span>
          <span class="info-value">{{ $policy->document_code }}</span>
        </div>
        @endif

        <div class="info-row">
          <span class="info-label">Entity</span>
          <span class="info-value">
            @if ($policy->division)
              {{ $policy->division->name }}
            @elseif ($policy->is_global)
              All Entities
            @else
              —
            @endif
          </span>
        </div>

        <div class="info-row">
          <span class="info-label">Scope</span>
          <span class="info-value">
            @if ($policy->is_global)
              All Users
            @elseif ($policy->departments && $policy->departments->isNotEmpty())
              {{ $policy->departments->pluck('dept_name')->join(', ') }}
            @else
              Division-wide
            @endif
          </span>
        </div>

        <div class="info-row">
          <span class="info-label">{{ $isNew ? 'Published' : 'Updated' }}</span>
          <span class="info-value">
            {{ $isNew
              ? ($policy->created_at?->format('d F Y, H:i') ?? '—')
              : ($policy->updated_at?->format('d F Y, H:i') ?? '—') }}
          </span>
        </div>
      </div>

      <div class="button-container">
        <a href="{{ $viewUrl }}" class="action-button" target="_blank">View in e-Docs</a>
      </div>

      <div class="signature">
        <p>Best regards,<br><strong>CCBRT e-Docs</strong></p>
      </div>
    </div>

  </div>
  </div>
</body>
</html>
