<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Access Request Approval</title>
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
        <div class="content">
            @php
                $approverName = trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) ?: $approver->username ?? 'Approver';
                $requesterName = $requestDetails['forwarded_by'] ?? 'Requester';
                $requestDate = $requestDetails['requestDate'] ?? now()->format('d M Y');
                $requestType = $requestDetails['request'] ?? 'ICT Access Form';
            @endphp

            <div class="greeting">
                <p>Dear <strong>{{ $approverName }}</strong>,</p>
            </div>

            <div class="highlight">
                <p style="margin: 0;">
                    <strong>{{ $requesterName }}</strong> has submitted an ICT Access Request that requires your approval.
                </p>
            </div>

            <div class="info-card">
                <h3>Request Summary</h3>
                <div class="info-row">
                    <span class="info-label">Submitted by:</span>
                    <span class="info-value">{{ $requesterName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Submission Date:</span>
                    <span class="info-value">{{ $requestDate }}</span>
                </div>
                @if(isset($requestDetails['user_type']))
                <div class="info-row">
                    <span class="info-label">User Type:</span>
                    <span class="info-value">{{ $requestDetails['user_type'] }}</span>
                </div>
                @endif
                @if(isset($requestDetails['access_required']))
                <div class="info-row">
                    <span class="info-label">Access Action:</span>
                    <span class="info-value">{{ $requestDetails['access_required'] }}</span>
                </div>
                @endif
            </div>

            @if(isset($requestDetails['request_summary']) && !empty($requestDetails['request_summary']))
            <div class="info-card" style="margin-top: 20px;">
                <h3>Requested Access Details</h3>
                <div style="padding: 10px 0;">
                    @foreach($requestDetails['request_summary'] as $item)
                        <div style="padding: 8px 0; border-bottom: 1px solid #e9ecef;">
                            <span style="color: #007A33; font-weight: 600;">✓</span>
                            <span style="margin-left: 8px; color: #333;">{{ $item }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="button-container">
                <a href="{{ route('requestapprove.index') }}" class="action-button" target="_blank">
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
