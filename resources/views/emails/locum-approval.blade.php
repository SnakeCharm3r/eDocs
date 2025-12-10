<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locum Claim Approval Request</title>
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

        .alert-banner {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 4px solid #ffc107;
            border-radius: 8px;
            padding: 18px 20px;
            margin: 25px 0;
            text-align: center;
        }

        .alert-banner strong {
            display: block;
            color: #856404;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .alert-banner span {
            color: #856404;
            font-size: 14px;
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
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            flex: 1;
        }

        .info-value {
            color: #333;
            flex: 1;
            text-align: right;
            font-weight: 500;
        }

        .highlight {
            background-color: #e7f3ff;
            border-left: 3px solid #007A33;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .highlight strong {
            color: #007A33;
        }

        .action-button {
            display: inline-block;
            background-color: #007A33;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 35px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 25px 0;
            transition: background-color 0.3s;
            box-shadow: 0 2px 4px rgba(0, 122, 51, 0.2);
        }

        .action-button:hover {
            background-color: #005a25;
            box-shadow: 0 4px 8px rgba(0, 122, 51, 0.3);
        }

        .button-container {
            text-align: center;
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
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

        .urgency-note {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 12px 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #856404;
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
                flex-direction: column;
            }

            .info-value {
                text-align: left;
                margin-top: 5px;
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
            <h1>⏰ Locum Claim Awaiting Approval</h1>
        </div>

        <div class="content">
            @php
                $approverName = trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) ?: $approver->username ?? 'Approver';
                $submitterName = trim(($submitter->fname ?? '') . ' ' . ($submitter->lname ?? '')) ?: $submitter->username ?? 'Requester';
                $submissionDate = optional($locumRequest->created_at)->timezone('Africa/Dar_es_Salaam')->format('d M Y, H:i');
                $year = $locumRequest->locum_year ?? optional($locumRequest->created_at)->format('Y');
            @endphp

            <div class="greeting">
                <p>Dear <strong>{{ $approverName }}</strong>,</p>
            </div>

            <div class="alert-banner">
                <strong>🔔 New Locum Claim Requires Your Review</strong>
                <span>Please review and take action on this pending request</span>
            </div>

            <div class="highlight">
                <p style="margin: 0;">
                    <strong>{{ $submitterName }}</strong> has submitted a locum claim for 
                    <strong>{{ $locumRequest->locum_month }} {{ $year }}</strong>
                </p>
            </div>

            <div class="info-card">
                <h3>Claim Summary</h3>
                <div class="info-row">
                    <span class="info-label">Submitted by:</span>
                    <span class="info-value">{{ $submitterName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Period:</span>
                    <span class="info-value">{{ $locumRequest->locum_month }} {{ $year }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Submission Date:</span>
                    <span class="info-value">{{ $submissionDate }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Days:</span>
                    <span class="info-value">{{ $locumRequest->number_of_days }} day(s)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Hours:</span>
                    <span class="info-value">{{ number_format((float) $locumRequest->total_hours, 2) }} hours</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value" style="color: #007A33; font-weight: 600;">
                        TZS {{ number_format((float) $locumRequest->total_amount_payable, 2) }}
                    </span>
                </div>
                @if (optional($locumRequest->locumAgreement)->locum_rate)
                <div class="info-row">
                    <span class="info-label">Locum Rate:</span>
                    <span class="info-value">TZS {{ number_format((float) $locumRequest->locumAgreement->locum_rate, 2) }} / day</span>
                </div>
                @endif
            </div>

            <div class="urgency-note">
                <strong>⏱️ Action Required:</strong> This request is pending your approval. Please review the details and take appropriate action.
            </div>

            <div class="button-container">
                <a href="{{ $approvalUrl }}" class="action-button" target="_blank">
                    👉 Review & Approve in e-Docs
                </a>
            </div>

            <div class="signature">
                <p>Best regards,</p>
                <p><strong>CCBRT e-Docs System</strong></p>
            </div>

            <div class="footer-text" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e9ecef; font-size: 12px; color: #888;">
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
