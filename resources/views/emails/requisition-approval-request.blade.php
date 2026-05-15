<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisition Request Approval</title>
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
            font-size: 24px;
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
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-new {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-replacement {
            background-color: #cce5ff;
            color: #004085;
        }
        .details-card {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .details-card h4 {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: #007A33;
            font-weight: 600;
        }
        .detail-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-item span.label {
            color: #666;
            font-size: 13px;
        }
        .detail-item span.value {
            display: block;
            color: #333;
            font-weight: 500;
            margin-top: 2px;
        }
        .action-button {
            display: inline-block;
            background-color: #007A33;
            color: #fff !important;
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
        .conditions-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        .conditions-list li {
            padding: 6px 0;
            color: #333;
        }
        .conditions-list li::before {
            content: "✓";
            color: #007A33;
            font-weight: bold;
            margin-right: 8px;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px 15px;
            }
            .header {
                padding: 25px 15px;
            }
            .header h1 {
                font-size: 20px;
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
            <h1>📋 Requisition Request - {{ $stepName }}</h1>
        </div>

        <div class="content">
            @php
                $approverName = trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? ''));
                if ($approverName === '') {
                    $approverName = $approver->username ?? 'Approver';
                }
                $initiatorName = trim(($requisition->user->fname ?? '') . ' ' . ($requisition->user->lname ?? ''));
                if ($initiatorName === '') {
                    $initiatorName = $requisition->user->username ?? 'Initiator';
                }
                $forwardedByName = $forwardedBy ? (trim(($forwardedBy->fname ?? '') . ' ' . ($forwardedBy->lname ?? '')) ?: $forwardedBy->username ?? 'User') : 'System';
                
                // Position type badge
                $positionType = $requisition->position_type ?? 'new_position';
                $positionLabel = match($positionType) {
                    'new_position' => 'New Position',
                    'replacement' => 'Replacement',
                    'contract_renewal' => 'Contract Renewal',
                    default => ucfirst(str_replace('_', ' ', $positionType))
                };
                $positionBadgeClass = match($positionType) {
                    'new_position' => 'badge-new',
                    'replacement' => 'badge-replacement',
                    default => 'badge-pending'
                };
                
                // Contract type
                $contractType = match($requisition->contract_type ?? '') {
                    'minimal_1_year' => 'Minimal 1 year (employment)',
                    'termed_less_1_year' => 'Termed < 1 year (consultant/specific task)',
                    'health_volunteer' => 'Health Volunteer (50% basic, minimal 1 year)',
                    'work_exposure' => 'Work Exposure Placement (no pay, max 2×3 months)',
                    default => 'Not specified'
                };
                
                // Job title
                $jobTitle = $requisition->new_job_title ?? ($requisition->jobTitle->job_title ?? 'Not specified');
            @endphp

            <div class="greeting">
                <p>Dear <strong>{{ $approverName }}</strong>,</p>
            </div>

            <div class="highlight">
                <p style="margin: 0;">
                    <strong>{{ $initiatorName }}</strong> has submitted a Recruitment Requisition that requires your approval at the <strong>{{ $stepName }}</strong> stage.
                </p>
            </div>

            <div class="info-card">
                <h3>Requisition Summary</h3>
                <div class="info-row">
                    <span class="info-label">Requisition ID:</span>
                    <span class="info-value">#{{ $requisition->access_id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Position Type:</span>
                    <span class="info-value"><span class="status-badge {{ $positionBadgeClass }}">{{ $positionLabel }}</span></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Job Title:</span>
                    <span class="info-value">{{ $jobTitle }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department:</span>
                    <span class="info-value">{{ $requisition->dept_name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contract Type:</span>
                    <span class="info-value">{{ $contractType }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Required Start Date:</span>
                    <span class="info-value">{{ $requisition->required_start_date ? \Carbon\Carbon::parse($requisition->required_start_date)->format('d F Y') : 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Submitted by:</span>
                    <span class="info-value">{{ $initiatorName }}</span>
                </div>
                @if ($forwardedBy)
                <div class="info-row">
                    <span class="info-label">Forwarded by:</span>
                    <span class="info-value">{{ $forwardedByName }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Submission Date:</span>
                    <span class="info-value">{{ $requisition->created_at ? \Carbon\Carbon::parse($requisition->created_at)->format('d F Y, H:i') : 'N/A' }}</span>
                </div>
            </div>

            @if(in_array($requisition->position_type, ['replacement', 'contract_renewal']) && $requisition->employee_name)
            <div class="details-card">
                <h4>Employee Details</h4>
                <div class="detail-item">
                    <span class="label">Employee Name:</span>
                    <span class="value">{{ $requisition->employee_name }}</span>
                </div>
                @if($requisition->current_contract_end_date)
                <div class="detail-item">
                    <span class="label">Current Contract End Date:</span>
                    <span class="value">{{ \Carbon\Carbon::parse($requisition->current_contract_end_date)->format('d F Y') }}</span>
                </div>
                @endif
            </div>
            @endif

            <div class="details-card">
                <h4>Conditions Met</h4>
                <ul class="conditions-list">
                    @if($requisition->condition_medical_operational)
                    <li>Medical or operational issue requiring this position</li>
                    @endif
                    @if($requisition->condition_safety_reputational)
                    <li>Safety and reputational issue will arise if not recruited</li>
                    @endif
                    @if($requisition->condition_legal_requirement)
                    <li>Legal requirement to have this position</li>
                    @endif
                    @if($requisition->condition_financial_loss)
                    <li>There will be financial loss without this position</li>
                    @endif
                    @if($requisition->condition_increase_income)
                    <li>This position will increase income</li>
                    @endif
                </ul>
            </div>

            @if($requisition->elaborate_reason)
            <div class="details-card">
                <h4>Justification</h4>
                <p style="margin: 0; color: #333; white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($requisition->elaborate_reason, 300, '...') }}</p>
            </div>
            @endif

            @if($requisition->budget_approved !== null || $requisition->payroll_comment)
            <div class="details-card">
                <h4>Payroll Review</h4>
                @if($requisition->budget_approved !== null)
                <div class="detail-item">
                    <span class="label">Budget Approved:</span>
                    <span class="value">{{ $requisition->budget_approved ? 'Yes' : 'No' }}</span>
                </div>
                @endif
                @if($requisition->max_monthly_budget)
                <div class="detail-item">
                    <span class="label">Max Monthly Budget:</span>
                    <span class="value">TZS {{ number_format($requisition->max_monthly_budget, 2) }}</span>
                </div>
                @endif
                @if($requisition->payroll_comment)
                <div class="detail-item">
                    <span class="label">Payroll Comment:</span>
                    <span class="value">{{ $requisition->payroll_comment }}</span>
                </div>
                @endif
            </div>
            @endif

            @if($requisition->hec_financial_decision)
            <div class="details-card">
                <h4>HEC Review</h4>
                <div class="detail-item">
                    <span class="label">Decision:</span>
                    <span class="value">{{ ucfirst(str_replace('_', ' ', $requisition->hec_financial_decision)) }}</span>
                </div>
                @if($requisition->hec_comment)
                <div class="detail-item">
                    <span class="label">Comment:</span>
                    <span class="value">{{ $requisition->hec_comment }}</span>
                </div>
                @endif
            </div>
            @endif

            @if($requisition->cfo_decision)
            <div class="details-card">
                <h4>CFO Review</h4>
                <div class="detail-item">
                    <span class="label">Decision:</span>
                    <span class="value">{{ ucfirst($requisition->cfo_decision) }}</span>
                </div>
                @if($requisition->cfo_financing_confirmation)
                <div class="detail-item">
                    <span class="label">Financing Confirmation:</span>
                    <span class="value">{{ $requisition->cfo_financing_confirmation }}</span>
                </div>
                @endif
                @if($requisition->cfo_comment)
                <div class="detail-item">
                    <span class="label">Comment:</span>
                    <span class="value">{{ $requisition->cfo_comment }}</span>
                </div>
                @endif
            </div>
            @endif

            @if($requisition->ceo_decision)
            <div class="details-card">
                <h4>CEO Review</h4>
                <div class="detail-item">
                    <span class="label">Decision:</span>
                    <span class="value">{{ ucfirst($requisition->ceo_decision) }}</span>
                </div>
                @if($requisition->ceo_comment)
                <div class="detail-item">
                    <span class="label">Comment:</span>
                    <span class="value">{{ $requisition->ceo_comment }}</span>
                </div>
                @endif
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
            <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk"
                alt="CCBRT Footer">
            <div class="footer-text">Powered by CCBRT e-Docs System</div>
        </div>
    </div>
</body>
</html>
