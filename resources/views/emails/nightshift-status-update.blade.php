<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Night Allowance Claim Status Update</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .header {
            background: linear-gradient(135deg, #007A33 0%, #005a25 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 600;
            color: #fff;
            letter-spacing: 0.5px;
        }

        .content {
            padding: 30px 25px;
        }

        .greeting {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
        }

        .status-banner {
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .status-icon {
            font-size: 24px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .info-card {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }

        .info-card h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #007A33;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #007A33;
            padding-bottom: 8px;
        }

        .info-row {
            display: table;
            width: 100%;
            table-layout: fixed;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }

        .info-value {
            color: #333;
            display: table-cell;
            width: 60%;
            text-align: right;
            vertical-align: top;
            font-weight: 500;
        }

        .rejection-reason {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }

        .rejection-reason strong {
            display: block;
            color: #856404;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .rejection-reason p {
            margin: 0;
            color: #856404;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .action-button {
            display: inline-block;
            background-color: #007A33;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 25px 0;
            transition: background-color 0.3s;
        }

        .action-button:hover {
            background-color: #005a25;
        }

        .button-container {
            text-align: center;
        }

        .signature {
            margin-top: 25px;
            color: #333;
        }

        .signature strong {
            color: #007A33;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 25px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer img {
            max-width: 100%;
            height: auto;
            margin-bottom: 15px;
        }

        .footer-text {
            font-size: 12px;
            color: #888;
            margin-top: 10px;
        }

        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px 15px;
            }

            .header {
                padding: 25px 15px;
            }

            .header h1 {
                font-size: 22px;
            }

            .info-row {
                display: block;
            }

            .info-value {
                display: block;
                width: 100%;
                text-align: left;
                margin-top: 4px;
            }

            .action-button {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>🌙 Night Allowance Claim Status Update</h1>
        </div>

        <div class="content">
            @php
                $requester = $nightShiftClaim->submitter ?? null;
                $requesterName = trim(($requester->fname ?? '') . ' ' . ($requester->lname ?? ''));
                if ($requesterName === '') {
                    $requesterName = $requester->username ?? 'Requester';
                }

                $approverName = $approver
                    ? (trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) ?: $approver->username ?? 'Approver')
                    : 'Approver';

                $ratePerDay = (float) \App\Http\Controllers\SettingsController::getSetting('night_allowance_amount_per_day', 0);
                $totalAmount = $nightShiftClaim->total_days_worked * $ratePerDay;
            @endphp

            <div class="greeting">
                <p>Dear <strong>{{ $requesterName }}</strong>,</p>
            </div>

            <div class="status-banner {{ $status === 'approved' ? 'status-approved' : 'status-rejected' }}">
                @if ($status === 'approved')
                    <span class="status-icon">✅</span>
                    <strong>Your night allowance claim has been APPROVED{{ $approvalStep ? ' by ' . $approvalStep : '' }}</strong>
                @else
                    <span class="status-icon">❌</span>
                    <strong>Your night allowance claim has been REJECTED</strong>
                @endif
            </div>

            <div class="info-card">
                <h3>Claim Details</h3>
                <div class="info-row">
                    <span class="info-label">Period:</span>
                    <span class="info-value">{{ $nightShiftClaim->month }} {{ $nightShiftClaim->year }}</span>
                </div>
                @if ($approver)
                <div class="info-row">
                    <span class="info-label">Action by:</span>
                    <span class="info-value">{{ $approverName }}</span>
                </div>
                @endif
                @if ($approvalStep)
                <div class="info-row">
                    <span class="info-label">Approval Level:</span>
                    <span class="info-value">{{ $approvalStep }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Type:</span>
                    <span class="info-value">{{ $nightShiftClaim->type_of_allowance ?? 'Night Allowance' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Days:</span>
                    <span class="info-value">{{ $nightShiftClaim->total_days_worked }} day(s)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value" style="color: #007A33; font-weight: 600;">
                        TZS {{ number_format($totalAmount, 2) }}
                    </span>
                </div>
            </div>

            @if ($status === 'rejected' && !empty($reason))
                <div class="rejection-reason">
                    <strong>Reason for Rejection:</strong>
                    <p>{{ $reason }}</p>
                </div>
            @endif

            <div class="button-container">
                <a href="{{ $portalUrl }}" class="action-button" target="_blank">
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
            <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk"
                alt="CCBRT Footer">
            <div class="footer-text">Powered by CCBRT e-Docs System</div>
        </div>
    </div>
</body>

</html>
