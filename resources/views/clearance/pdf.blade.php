<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Clearance Form - {{ $clearance->access_id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            margin: 0;
            padding: 15pt;
            color: #000000;
        }
        .header {
            text-align: center;
            margin-bottom: 20pt;
            border-bottom: 2pt solid #007A33;
            padding-bottom: 10pt;
        }
        .header h1 {
            color: #007A33;
            font-size: 18pt;
            font-weight: bold;
            margin: 5pt 0;
        }
        .header .subtitle {
            font-size: 10pt;
            color: #666666;
            margin: 2pt 0;
        }
        .form-code {
            float: right;
            font-size: 11pt;
            font-weight: bold;
            color: #666666;
            margin-top: -30pt;
        }
        .section {
            margin-bottom: 15pt;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #007A33;
            color: #ffffff;
            padding: 6pt 10pt;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 8pt;
            border-radius: 3pt;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 8pt;
        }
        .info-item {
            display: table-cell;
            width: 50%;
            padding: 4pt 8pt;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #666666;
            font-size: 9pt;
            margin-bottom: 2pt;
        }
        .info-value {
            font-size: 10pt;
            color: #000000;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
        }
        .table th {
            background-color: #f5f5f5;
            padding: 6pt;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            border: 1pt solid #dddddd;
        }
        .table td {
            padding: 6pt;
            font-size: 9pt;
            border: 1pt solid #dddddd;
        }
        .signature-section {
            margin-top: 15pt;
            padding: 8pt;
            background-color: #f8f9fa;
            border-left: 3pt solid #007A33;
        }
        .signature-section p {
            margin: 3pt 0;
            font-size: 9pt;
        }
        .signature-img {
            max-width: 150pt;
            max-height: 50pt;
            height: auto;
            margin-top: 5pt;
            border: 1pt solid #cccccc;
            display: block;
        }
        .footer {
            margin-top: 30pt;
            padding-top: 10pt;
            border-top: 2pt solid #007A33;
            font-size: 8pt;
            color: #666666;
            text-align: center;
            page-break-inside: avoid;
        }
        .page-break {
            page-break-before: always;
        }
        @page {
            margin-bottom: 50pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>EMPLOYEE CLEARANCE FORM</h1>
        <div class="subtitle">CCBRT - Comprehensive Community Based Rehabilitation in Tanzania</div>
        <div class="form-code">Form ID: {{ $clearance->access_id }}</div>
    </div>

    <!-- Request Summary -->
    <div class="section">
        <div class="section-title">Request Summary</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Submitted Date:</div>
                <div class="info-value">{{ $submittedDate }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status:</div>
                <div class="info-value">Completed and Approved</div>
            </div>
        </div>
    </div>

    <!-- Staff Details -->
    <div class="section">
        <div class="section-title">Staff Details</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Full Name:</div>
                <div class="info-value">{{ $clearance->fname }} {{ $clearance->mname ?? '' }} {{ $clearance->lname }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">CCBRT Code:</div>
                <div class="info-value">{{ $clearance->ccbrt_code ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Username:</div>
                <div class="info-value">{{ $clearance->username ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $clearance->email ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Mobile Number:</div>
                <div class="info-value">{{ $clearance->mobile ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Department:</div>
                <div class="info-value">{{ $clearance->dept_name ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Job Title:</div>
                <div class="info-value">{{ $clearance->job_title ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Employment Dates -->
    <div class="section">
        <div class="section-title">Employment Dates</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Date of Hire:</div>
                <div class="info-value">{{ $dateOfHire }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Last Working Day:</div>
                <div class="info-value">{{ $lastWorkingDay }}</div>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">End of Contract:</div>
                <div class="info-value">{{ $endOfContract }}</div>
            </div>
        </div>
    </div>

    <!-- Line Manager Section -->
    <div class="section">
        <div class="section-title">Line Manager Section - Items Collected by Last Day of Work</div>
        <table class="table">
            <tr>
                <th style="width: 60%;">Item</th>
                <th style="width: 40%;">Status</th>
            </tr>
            <tr>
                <td>Changing Room Keys</td>
                <td>{{ $clearance->changing_room_keys ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Office Keys</td>
                <td>{{ $clearance->office_keys ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Mobile Phone</td>
                <td>{{ $clearance->mobile_phone ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Camera</td>
                <td>{{ $clearance->camera ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>CCBRT Uniforms</td>
                <td>{{ $clearance->ccbrt_uniforms ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Office Car Keys</td>
                <td>{{ $clearance->office_car_keys ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Any other CCBRT items given</td>
                <td>{{ $clearance->other_items ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Line Manager Review Section -->
    <div class="section">
        <div class="section-title">Line Manager Review Section</div>
        <table class="table">
            <tr>
                <th style="width: 60%;">Item</th>
                <th style="width: 40%;">Status</th>
            </tr>
            <tr>
                <td>Handover Report Received</td>
                <td>
                    @if ($clearance->handover_report_received == 1 || $clearance->handover_report_received === 'Yes')
                        Yes
                    @elseif ($clearance->handover_report_received == 0 || $clearance->handover_report_received === 'No')
                        No
                    @elseif ($clearance->handover_report_received === 'N/A' || $clearance->handover_report_received === 'Not Applicable')
                        N/A
                    @else
                        {{ $clearance->handover_report_received ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Work Responsibilities Transferred</td>
                <td>
                    @if ($clearance->work_responsibilities_transferred == 1 || $clearance->work_responsibilities_transferred === 'Yes')
                        Yes
                    @elseif ($clearance->work_responsibilities_transferred == 0 || $clearance->work_responsibilities_transferred === 'No')
                        No
                    @elseif ($clearance->work_responsibilities_transferred === 'N/A' || $clearance->work_responsibilities_transferred === 'Not Applicable')
                        N/A
                    @else
                        {{ $clearance->work_responsibilities_transferred ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Projects/Tasks Closed</td>
                <td>
                    @if ($clearance->projects_tasks_closed == 1 || $clearance->projects_tasks_closed === 'Yes')
                        Yes
                    @elseif ($clearance->projects_tasks_closed == 0 || $clearance->projects_tasks_closed === 'No')
                        No
                    @elseif ($clearance->projects_tasks_closed === 'N/A' || $clearance->projects_tasks_closed === 'Not Applicable')
                        N/A
                    @else
                        {{ $clearance->projects_tasks_closed ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Comments/Notes</td>
                <td>{{ $clearance->line_manager_comments ?? 'N/A' }}</td>
            </tr>
        </table>
        @php
            $lmHistory = $allApproversWithDetails->firstWhere('step_name', 'Line Manager');
        @endphp
        @if ($lmHistory)
            <div class="signature-section">
                <p><strong>I confirm receiving the above items.</strong></p>
                <p><strong>Approved by:</strong> {{ $lmHistory['approver_name'] ?? 'N/A' }}</p>
                @if ($lmHistory['signature'] ?? null)
                    <p><strong>Signature:</strong></p>
                    <img src="data:image/png;base64,{{ $lmHistory['signature'] }}" alt="Line Manager Signature" class="signature-img">
                @endif
                <p><strong>Date:</strong> {{ $lmHistory['approval_date'] ? \Carbon\Carbon::parse($lmHistory['approval_date'])->format('d F Y') : 'N/A' }}</p>
                @if (!empty($clearance->line_manager_comments))
                    <p><strong>Comments:</strong> {{ $clearance->line_manager_comments }}</p>
                @endif
            </div>
        @endif
    </div>

    <!-- Finance Officer Section -->
    <div class="section">
        <div class="section-title">Finance Officer Section - Finance Confirmation</div>
        <table class="table">
            <tr>
                <th style="width: 60%;">Item</th>
                <th style="width: 40%;">Status</th>
            </tr>
            <tr>
                <td>Repaid advance on Salary?</td>
                <td>{{ $clearance->repaid_salary_advance ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Staff informed Finance of outstanding loan balances?</td>
                <td>{{ $clearance->loan_balances_informed ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Repaid any outstanding imprest?</td>
                <td>{{ $clearance->repaid_outstanding_imprest ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Finance Officer Review Section -->
    <div class="section">
        <div class="section-title">Finance Officer Review Section</div>
        <table class="table">
            <tr>
                <th style="width: 60%;">Item</th>
                <th style="width: 40%;">Status</th>
            </tr>
            <tr>
                <td>All Salary Advances Cleared</td>
                <td>
                    @if ($clearance->all_salary_advances_cleared == 1 || $clearance->all_salary_advances_cleared === 'Yes')
                        Yes
                    @elseif ($clearance->all_salary_advances_cleared == 0 || $clearance->all_salary_advances_cleared === 'No')
                        No
                    @elseif ($clearance->all_salary_advances_cleared === 'N/A' || $clearance->all_salary_advances_cleared === 'Not Applicable')
                        N/A
                    @else
                        {{ $clearance->all_salary_advances_cleared ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Allowances Reconciled</td>
                <td>
                    @if ($clearance->allowances_reconciled == 1 || $clearance->allowances_reconciled === 'Yes')
                        Yes
                    @elseif ($clearance->allowances_reconciled == 0 || $clearance->allowances_reconciled === 'No')
                        No
                    @elseif ($clearance->allowances_reconciled === 'N/A' || $clearance->allowances_reconciled === 'Not Applicable')
                        N/A
                    @else
                        {{ $clearance->allowances_reconciled ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Pending Claims Settled</td>
                <td>
                    @if ($clearance->pending_claims_settled == 1 || $clearance->pending_claims_settled === 'Yes')
                        Yes
                    @elseif ($clearance->pending_claims_settled == 0 || $clearance->pending_claims_settled === 'No')
                        No
                    @elseif ($clearance->pending_claims_settled === 'N/A' || $clearance->pending_claims_settled === 'Not Applicable')
                        N/A
                    @else
                        {{ $clearance->pending_claims_settled ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Outstanding Loans Recovered</td>
                <td>
                    @if ($clearance->outstanding_loans_recovered == 1 || $clearance->outstanding_loans_recovered === 'Yes')
                        Yes
                    @elseif ($clearance->outstanding_loans_recovered == 0 || $clearance->outstanding_loans_recovered === 'No')
                        No
                    @elseif ($clearance->outstanding_loans_recovered === 'N/A' || $clearance->outstanding_loans_recovered === 'Not Applicable')
                        N/A
                    @else
                        {{ $clearance->outstanding_loans_recovered ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Employee Eligible for Final Payment</td>
                <td>
                    @if ($clearance->employee_eligible_for_final_payment == 1 || $clearance->employee_eligible_for_final_payment === 'Yes')
                        Yes
                    @elseif ($clearance->employee_eligible_for_final_payment == 0 || $clearance->employee_eligible_for_final_payment === 'No')
                        No
                    @elseif ($clearance->employee_eligible_for_final_payment === 'N/A' || $clearance->employee_eligible_for_final_payment === 'Not Applicable')
                        N/A
                    @else
                        {{ $clearance->employee_eligible_for_final_payment ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Comments/Notes</td>
                <td>{{ $clearance->finance_comments ?? 'N/A' }}</td>
            </tr>
        </table>
        @php
            $foHistory = $allApproversWithDetails->firstWhere('step_name', 'Finance Officer');
        @endphp
        @if ($foHistory)
            <div class="signature-section">
                <p><strong>I confirm the above finance details.</strong></p>
                <p><strong>Approved by:</strong> {{ $foHistory['approver_name'] ?? 'N/A' }}</p>
                @if ($foHistory['signature'] ?? null)
                    <p><strong>Signature:</strong></p>
                    <img src="data:image/png;base64,{{ $foHistory['signature'] }}" alt="Finance Officer Signature" class="signature-img">
                @endif
                <p><strong>Date:</strong> {{ $foHistory['approval_date'] ? \Carbon\Carbon::parse($foHistory['approval_date'])->format('d F Y') : 'N/A' }}</p>
                @if (!empty($clearance->finance_comments))
                    <p><strong>Comments:</strong> {{ $clearance->finance_comments }}</p>
                @endif
            </div>
        @endif
    </div>

    <!-- IT Officer Section -->
    <div class="section">
        <div class="section-title">IT Officer Section - ICT Checklist</div>
        <table class="table">
            <tr>
                <th style="width: 60%;">Item</th>
                <th style="width: 40%;">Status</th>
            </tr>
            <tr>
                <td>Keyboard & Mouse Returned?</td>
                <td>{{ $clearance->keyboard_mouse_returned ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Phone Returned?</td>
                <td>{{ $clearance->phone_returned ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>ID Card Returned?</td>
                <td>{{ $clearance->id_card_returned ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Laptop/iPad & Accessories & Gate Pass Returned?</td>
                <td>{{ $clearance->laptop_returned ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Access Card Returned?</td>
                <td>{{ $clearance->access_card_returned ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- ICT Systems -->
    <div class="section">
        <div class="section-title">ICT Systems</div>
        <table class="table">
            <tr>
                <th style="width: 60%;">System</th>
                <th style="width: 40%;">Status</th>
            </tr>
            <tr>
                <td>Domain Account Disabled?</td>
                <td>{{ $clearance->domain_account_disabled ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Email Account Disabled?</td>
                <td>{{ $clearance->email_account_disabled ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Telephone Pin Code Disabled?</td>
                <td>{{ $clearance->telephone_pin_disabled ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Health AI Disabled?</td>
                <td>{{ $clearance->health_ai_disabled ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>SAP Account Disabled?</td>
                <td>{{ $clearance->sap_account_disabled ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Aruti Account Disabled?</td>
                <td>{{ $clearance->aruti_account_disabled ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- IT Officer Review Section -->
    <div class="section">
        <div class="section-title">IT Officer Review Section</div>
        <table class="table">
            <tr>
                <th style="width: 60%;">Item</th>
                <th style="width: 40%;">Value</th>
            </tr>
            <tr>
                <td>IT Ticket No</td>
                <td>{{ $clearance->it_ticket_no ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Comments/Notes</td>
                <td>{{ $clearance->it_comments ?? 'N/A' }}</td>
            </tr>
        </table>
        @php
            $itHistory = $allApproversWithDetails->firstWhere('step_name', 'IT Officer');
        @endphp
        @if ($itHistory)
            <div class="signature-section">
                <p><strong>I confirm the above ICT items have been processed.</strong></p>
                <p><strong>Approved by:</strong> {{ $itHistory['approver_name'] ?? 'N/A' }}</p>
                @if ($itHistory['signature'] ?? null)
                    <p><strong>Signature:</strong></p>
                    <img src="data:image/png;base64,{{ $itHistory['signature'] }}" alt="IT Officer Signature" class="signature-img">
                @endif
                <p><strong>Date:</strong> {{ $itHistory['approval_date'] ? \Carbon\Carbon::parse($itHistory['approval_date'])->format('d F Y') : 'N/A' }}</p>
                @if (!empty($clearance->it_comments))
                    <p><strong>Comments:</strong> {{ $clearance->it_comments }}</p>
                @endif
            </div>
        @endif
    </div>

    <!-- HR Section -->
    <div class="section">
        <div class="section-title">HR Section - HR Confirmation</div>
        <table class="table">
            <tr>
                <th style="width: 60%;">Item</th>
                <th style="width: 40%;">Status</th>
            </tr>
            <tr>
                <td>Resignation Letter Received</td>
                <td>
                    @if ($clearance->resignation_letter_received == 1 || $clearance->resignation_letter_received === 'Yes')
                        Yes
                    @elseif ($clearance->resignation_letter_received == 0 || $clearance->resignation_letter_received === 'No')
                        No
                    @else
                        {{ $clearance->resignation_letter_received ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Exit Interview Completed</td>
                <td>
                    @if ($clearance->exit_interview_completed == 1 || $clearance->exit_interview_completed === 'Yes')
                        Yes
                    @elseif ($clearance->exit_interview_completed == 0 || $clearance->exit_interview_completed === 'No')
                        No
                    @else
                        {{ $clearance->exit_interview_completed ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Leave Balance Confirmed</td>
                <td>
                    @if ($clearance->leave_balance_confirmed == 1 || $clearance->leave_balance_confirmed === 'Yes')
                        Yes
                    @elseif ($clearance->leave_balance_confirmed == 0 || $clearance->leave_balance_confirmed === 'No')
                        No
                    @else
                        {{ $clearance->leave_balance_confirmed ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Contract File Reviewed</td>
                <td>
                    @if ($clearance->contract_file_reviewed == 1 || $clearance->contract_file_reviewed === 'Yes')
                        Yes
                    @elseif ($clearance->contract_file_reviewed == 0 || $clearance->contract_file_reviewed === 'No')
                        No
                    @else
                        {{ $clearance->contract_file_reviewed ?? 'N/A' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>CCBRT Identification Card</td>
                <td>{{ $clearance->ccbrt_id_card ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>CCBRT Name Tag</td>
                <td>{{ $clearance->ccbrt_name_tag ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>NHIF Cards (Including dependents' cards)</td>
                <td>{{ $clearance->nhif_cards ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Work Permit Cancelled (for non-Tanzanians)</td>
                <td>{{ $clearance->work_permit_cancelled ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Residence Permit Cancelled (for non-Tanzanians)</td>
                <td>{{ $clearance->residence_permit_cancelled ?? 'N/A' }}</td>
            </tr>
        </table>
        @php
            $hrHistory = $allApproversWithDetails->firstWhere('step_name', 'HR Officer');
        @endphp
        @if ($hrHistory)
            <div class="signature-section">
                <p><strong>I confirm the above HR items have been processed.</strong></p>
                <p><strong>Approved by:</strong> {{ $hrHistory['approver_name'] ?? 'N/A' }}</p>
                @if ($hrHistory['signature'] ?? null)
                    <p><strong>Signature:</strong></p>
                    <img src="data:image/png;base64,{{ $hrHistory['signature'] }}" alt="HR Officer Signature" class="signature-img">
                @endif
                <p><strong>Date:</strong> {{ $hrHistory['approval_date'] ? \Carbon\Carbon::parse($hrHistory['approval_date'])->format('d F Y') : 'N/A' }}</p>
                @if (!empty($clearance->hr_manager_comments))
                    <p><strong>Comments:</strong> {{ $clearance->hr_manager_comments }}</p>
                @endif
            </div>
        @endif
    </div>

    <!-- Exit Interview Questions -->
    @php
        $exitReasonArray = [];
        if (!empty($clearance->exit_reason)) {
            if (is_string($clearance->exit_reason)) {
                $exitReasonArray = json_decode($clearance->exit_reason, true) ?? [];
            } else {
                $exitReasonArray = is_array($clearance->exit_reason) ? $clearance->exit_reason : [];
            }
        }
    @endphp
    @if (!empty($exitReasonArray) || !empty($clearance->exit_explanation) || !empty($clearance->suggestions) || !empty($clearance->would_recommend))
        <div class="section">
            <div class="section-title">Exit Interview Questions</div>
            @if (!empty($exitReasonArray))
                <div style="margin-bottom: 8pt;">
                    <div class="info-label">Primary Reason for Exit:</div>
                    <div class="info-value">
                        <ul style="margin: 4pt 0; padding-left: 20pt;">
                            @foreach ($exitReasonArray as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @if (!empty($clearance->exit_explanation))
                <div style="margin-bottom: 8pt;">
                    <div class="info-label">Additional Details:</div>
                    <div class="info-value">{{ $clearance->exit_explanation }}</div>
                </div>
            @endif
            @if (!empty($clearance->suggestions))
                <div style="margin-bottom: 8pt;">
                    <div class="info-label">Suggestions for Improvement:</div>
                    <div class="info-value">{{ $clearance->suggestions }}</div>
                </div>
            @endif
            @if (!empty($clearance->would_recommend))
                <div style="margin-bottom: 8pt;">
                    <div class="info-label">Would Recommend CCBRT:</div>
                    <div class="info-value">{{ $clearance->would_recommend }}</div>
                </div>
            @endif
        </div>
    @endif

    <!-- Final Approval Section -->
    <div class="section">
        <div class="section-title">Final Approval Details</div>
        @if ($workflow && $workflow->work_flow_completed == 1)
            <div class="signature-section">
                <p><strong>Status:</strong> Form Completed and Approved</p>
                <p><strong>Completed Date:</strong> {{ $workflow->updated_at ? \Carbon\Carbon::parse($workflow->updated_at)->format('d F Y, H:i') : 'N/A' }}</p>
            </div>
        @endif
    </div>

    <!-- Footer - Positioned at the end -->
    <div style="page-break-inside: avoid; margin-top: 30pt;">
        <div class="footer">
            <p style="margin: 5pt 0;"><strong>This document was generated on {{ date('d F Y, H:i') }}</strong></p>
            <p style="margin: 5pt 0;">CCBRT - Comprehensive Community Based Rehabilitation in Tanzania</p>
            <p style="margin: 5pt 0; font-size: 7pt; color: #999999;">This is a system-generated document. All approvals and signatures are verified and recorded in the system.</p>
        </div>
    </div>
</body>
</html>

