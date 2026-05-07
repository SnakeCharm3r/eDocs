<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Clearance Form Status Update</title>
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
            border-radius: 8px;
            padding: 18px 20px;
            margin: 25px 0;
            text-align: center;
        }

        .status-banner.approved {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 4px solid #28a745;
        }

        .status-banner.pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 4px solid #ffc107;
        }

        .status-banner.rejected {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left: 4px solid #dc3545;
        }

        .status-banner strong {
            display: block;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .status-banner.approved strong {
            color: #155724;
        }

        .status-banner.pending strong {
            color: #856404;
        }

        .status-banner.rejected strong {
            color: #721c24;
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

        .message-box {
            background-color: #e7f3ff;
            border-left: 3px solid #007A33;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
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
            <h1>
                @if($status === 'approved')
                    ✅ Clearance Form Approved
                @elseif($status === 'rejected')
                    ❌ Clearance Form Rejected
                @else
                    📋 Clearance Form Status Update
                @endif
            </h1>
        </div>

        <div class="content">
            @php
                $submitterName = trim(($submitter->fname ?? '') . ' ' . ($submitter->lname ?? '')) ?: $submitter->username ?? 'Employee';
                $approverName = $approver ? trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) : 'System';
            @endphp

            <div class="greeting">
                <p>Dear <strong>{{ $submitterName }}</strong>,</p>
            </div>

            <div class="status-banner {{ $status }}">
                @if($status === 'approved')
                    <strong>✅ Your Clearance Form Has Been Approved</strong>
                    <span style="color: #155724;">The form has been approved and forwarded to the next step in the approval process.</span>
                @elseif($status === 'rejected')
                    <strong>❌ Your Clearance Form Has Been Rejected</strong>
                    <span style="color: #721c24;">Please review the rejection reason and resubmit if necessary.</span>
                @else
                    <strong>📋 Clearance Form Status Update</strong>
                    <span style="color: #856404;">Your clearance form status has been updated.</span>
                @endif
            </div>

            @if($message)
            <div class="message-box">
                <p style="margin: 0;"><strong>Message:</strong> {{ $message }}</p>
            </div>
            @endif

            <div class="info-card">
                <h3>Form Details</h3>
                <div class="info-row">
                    <span class="info-label">Form ID:</span>
                    <span class="info-value">#{{ $clearanceForm->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Current Status:</span>
                    <span class="info-value" style="color: #007A33; font-weight: 600;">
                        {{ $workflow->work_flow_status ?? 'Pending' }}
                    </span>
                </div>
                @if($approver)
                <div class="info-row">
                    <span class="info-label">Approved by:</span>
                    <span class="info-value">{{ $approverName }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Update Date:</span>
                    <span class="info-value">{{ now()->timezone('Africa/Dar_es_Salaam')->format('d M Y, H:i') }}</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $viewUrl }}" class="action-button" target="_blank">
                    👉 View Clearance Form
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
            <div class="footer-text">Powered by CCBRT e-Docs System</div>
        </div>
    </div>
</body>

</html>

