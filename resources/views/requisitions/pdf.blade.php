<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recruitment Requisition Form - {{ $requisition->access_id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            margin: 0;
            padding: 15pt;
            color: #000000;
        }
        .header {
            text-align: center;
            margin-bottom: 20pt;
            border-bottom: 2pt solid #459c51;
            padding-bottom: 10pt;
        }
        .header h1 {
            color: #459c51;
            font-size: 16pt;
            font-weight: bold;
            margin: 5pt 0;
        }
        .header .subtitle {
            font-style: italic;
            font-size: 9pt;
            color: #666666;
            margin: 2pt 0;
        }
        .form-code {
            float: right;
            font-size: 12pt;
            font-weight: bold;
            color: #666666;
            margin-top: -30pt;
        }
        .section {
            margin-bottom: 20pt;
        }
        .section-title {
            background-color: #459c51;
            color: #ffffff;
            padding: 6pt 8pt;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 10pt;
            border-radius: 2pt;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12pt;
            font-size: 9pt;
        }
        .data-table th {
            background-color: #f8f9fa;
            border: 1pt solid #cccccc;
            padding: 8pt 6pt;
            text-align: left;
            font-weight: bold;
            width: 35%;
            vertical-align: top;
        }
        .data-table td {
            border: 1pt solid #cccccc;
            padding: 8pt 6pt;
            vertical-align: top;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12pt;
            font-size: 9pt;
        }
        .signature-table th {
            background-color: #e9ecef;
            border: 1pt solid #cccccc;
            padding: 6pt;
            text-align: left;
            font-weight: bold;
            width: 25%;
        }
        .signature-table td {
            border: 1pt solid #cccccc;
            padding: 6pt;
            vertical-align: middle;
        }
        .text-content {
            background-color: #f8f9fa;
            border: 1pt solid #cccccc;
            padding: 8pt;
            margin: 8pt 0;
            border-radius: 2pt;
            font-size: 9pt;
            line-height: 1.4;
        }
        .signature-img {
            max-width: 120pt;
            max-height: 40pt;
            border: 1pt solid #cccccc;
        }
        .page-break {
            page-break-before: always;
            padding-top: 15pt;
        }
        .footer {
            margin-top: 20pt;
            padding-top: 8pt;
            border-top: 1pt solid #cccccc;
            font-size: 8pt;
            color: #666666;
            text-align: center;
        }
        ul {
            margin: 3pt 0;
            padding-left: 15pt;
        }
        li {
            margin-bottom: 2pt;
        }
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .compact-section {
            margin-bottom: 12pt;
        }
        .compact-title {
            font-weight: bold;
            margin-bottom: 4pt;
            color: #459c51;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="form-code">HR.01</div>
        <h1>RECRUITMENT REQUISITION FORM</h1>
        <p class="subtitle">For new employment positions, replacement and renewal/extension</p>
        <p class="subtitle">And for temporary staff, Health Volunteers and Work Exposure Placements</p>
    </div>

    <!-- Section 1: Position Details -->
    <div class="section">
        <div class="section-title">1. POSITION DETAILS (To be filled by Head of Department)</div>

        <table class="data-table">
            <tr>
                @if (!empty($requisition->jobTitle->job_title))
                    <th>JOB TITLE</th>
                    <td>{{ $requisition->jobTitle->job_title }}</td>
                @elseif(!empty($requisition->new_job_title))
                    <th>NEW JOB TITLE</th>
                    <td>{{ $requisition->new_job_title }}</td>
                @else
                    <th>JOB TITLE</th>
                    <td><em>No title provided</em></td>
                @endif
            </tr>

            <tr>
                <th>BACKGROUND</th>
                <td>
                    @php
                        $backgrounds = [
                            'new_position' => 'New position',
                            'replacement' => 'Replacement',
                            'contract_renewal' => 'Contract renewal/Extension',
                        ];
                    @endphp
                    {{ $backgrounds[$requisition->background] ?? '' }}
                </td>
            </tr>

            @if ($requisition->background == 'contract_renewal')
            <tr>
                <th>FOR CONTRACT RENEWAL/EXTENSION</th>
                <td>
                    <strong>Employee's Name:</strong> {{ $requisition->employee->fname ?? '' }} {{ $requisition->employee->mname ?? '' }} {{ $requisition->employee->lname ?? '' }}<br>
                    <strong>Current contract end date:</strong> {{ $requisition->contract_end_date ? \Carbon\Carbon::parse($requisition->contract_end_date)->format('Y-m-d') : '' }}
                </td>
            </tr>
            @endif

            <tr>
                <th>CCBRT HIRING DEPARTMENT</th>
                <td>{{ $requisition->department->dept_name ?? '' }}</td>
            </tr>

            <tr>
                <th>CCBRT RESPONSIBILITY CENTRE</th>
                <td>{{ $requisition->responsibility_centre ?? '' }}</td>
            </tr>

            <tr>
                <th>REPORTING LINE</th>
                <td>{{ $requisition->reporting_line ?? '' }}</td>
            </tr>

            <tr>
                <th>CONTRACT TYPE</th>
                <td>
                    @php
                        $contractTypes = [
                            'minimal_1_year' => 'Minimal 1 year (employment)',
                            'termed_less_1_year' => 'Termed < 1 year: consultant / specific task',
                            'health_volunteer' => 'Health Volunteer (50% basic, minimal 1 year)',
                            'work_exposure' => 'Work Exposure placement (no pay, max 2x3 months)',
                        ];
                    @endphp
                    {{ $contractTypes[$requisition->contract_type] ?? '' }}
                </td>
            </tr>

            <tr>
                <th>REQUIRED STARTING DATE</th>
                <td>{{ $requisition->required_start_date ? \Carbon\Carbon::parse($requisition->required_start_date)->format('Y-m-d') : '' }}</td>
            </tr>

            <tr>
                <th>DOCUMENTS TO BE ATTACHED</th>
                <td>
                    Updated Job/Task description:
                    @if ($requisition->job_description_file)
                        <span class="status-approved">[FILE ATTACHED]</span>
                    @else
                        <span class="status-rejected">[NO FILE ATTACHED]</span>
                    @endif
                </td>
            </tr>

            <tr>
                <th>REASONS/CONDITIONS</th>
                <td>
                    @if (is_array($requisition->conditions) && !empty($requisition->conditions))
                        <ul>
                            @foreach ($requisition->conditions as $condition)
                                <li>{{ is_scalar($condition) ? $condition : json_encode($condition) }}</li>
                            @endforeach
                        </ul>
                    @else
                        {{ is_scalar($requisition->conditions) ? $requisition->conditions ?? 'Not specified' : json_encode($requisition->conditions) }}
                    @endif
                </td>
            </tr>
        </table>

        <div class="compact-section">
            <div class="compact-title">REASONING & BUSINESS IMPACT:</div>
            <div class="text-content">
                {{ $requisition->reasoning ?? 'No reasoning provided' }}
            </div>
        </div>

        <!-- HOD Signature -->
        <table class="signature-table">
            <tr>
                <th>HEAD OF DEPARTMENT NAME</th>
                <td>{{ $requisition->fname }} {{ $requisition->lname }}</td>
            </tr>
            <tr>
                <th>SIGNATURE</th>
                <td>
                    @if ($requisition->signature)
                        <img src="data:image/png;base64,{{ $requisition->signature }}" class="signature-img" alt="HOD Signature">
                    @else
                        <span style="color: #999; font-style: italic;">No signature provided</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>DATE</th>
                <td>{{ $requisition->created_at ? \Carbon\Carbon::parse($requisition->created_at)->format('d F Y') : '' }}</td>
            </tr>
        </table>
    </div>

    <!-- Page Break for Next Section -->
    <div class="page-break"></div>

    <!-- Section 2: Budget Allocation -->
    <div class="section">
        <div class="section-title">2. BUDGET ALLOCATION (To be checked with Payroll Accountant)</div>

        <table class="data-table">
            <tr>
                <th>POSITION APPROVED IN BUDGET</th>
                <td>
                    @if($requisition->budget_approved)
                        <span class="status-approved">YES</span>
                    @else
                        <span class="status-rejected">NO</span>
                    @endif
                </td>
            </tr>

            <tr>
                <th>MAX MONTHLY BUDGET</th>
                <td>
                    @if($requisition->max_monthly_budget)
                        {{ number_format($requisition->max_monthly_budget, 2) }} TZS
                    @else
                        Not specified
                    @endif
                </td>
            </tr>

            <tr>
                <th>FUNDING AVAILABLE</th>
                <td>
                    @if($requisition->funding_available)
                        <span class="status-approved">YES</span>
                    @else
                        <span class="status-rejected">NO</span>
                    @endif
                </td>
            </tr>

            <tr>
                <th>DONOR CODE</th>
                <td>{{ $requisition->donor_code ?? 'Not specified' }}</td>
            </tr>

            <tr>
                <th>ACTIVITY CODE</th>
                <td>{{ $requisition->activity_code ?? 'Not specified' }}</td>
            </tr>
        </table>

        @if(isset($payroll_accountant))
        <table class="signature-table">
            <tr>
                <th>PAYROLL ACCOUNTANT NAME</th>
                <td>{{ $payroll_accountant->fname ?? '' }} {{ $payroll_accountant->lname ?? '' }}</td>
            </tr>
            <tr>
                <th>SIGNATURE</th>
                <td>
                    @if ($payroll_accountant->signature)
                        <img src="data:image/png;base64,{{ $payroll_accountant->signature }}" class="signature-img" alt="Payroll Signature">
                    @else
                        <span style="color: #999; font-style: italic;">No signature provided</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>DATE</th>
                <td>{{ $payroll_accountant->updated_at ? \Carbon\Carbon::parse($payroll_accountant->updated_at)->format('d F Y') : 'N/A' }}</td>
            </tr>
        </table>
        @endif
    </div>

    <!-- Page Break for Next Section -->
    <div class="page-break"></div>

    <!-- Section 3: HEC Review -->
    @if($requisition->hec_objection || $requisition->hec_member_comment || $requisition->hec_justification || $requisition->hec_proposed_funding || isset($approver))
    <div class="section">
        <div class="section-title">3. REVIEW BY HOSPITAL EXECUTIVE COMMITTEE MEMBER</div>

        <table class="data-table">
            <tr>
                <th>FUNDING STATUS</th>
                <td>
                    @if($requisition->budget_approved == 1)
                        <span class="status-approved">WITH FUNDS</span>
                    @else
                        <span class="status-rejected">NO FUNDS</span>
                    @endif
                </td>
            </tr>

            @if($requisition->hec_objection)
            <tr>
                <th>HEC DECISION</th>
                <td>
                    @php
                        $objectionText = [
                            'in_budget_no_objection' => 'NO OBJECTION to start recruitment/renewal',
                            'in_budget_with_objection' => 'OBJECTION to start recruitment/renewal',
                            'Objection to start' => 'OBJECTION to start recruitment/renewal',
                            'No objection to start' => 'NO OBJECTION to start recruitment/renewal',
                        ];
                    @endphp
                    <strong>{{ $objectionText[$requisition->hec_objection] ?? $requisition->hec_objection }}</strong>
                </td>
            </tr>
            @endif

            @if($requisition->hec_member_comment)
            <tr>
                <th>COMMENTS</th>
                <td>{{ $requisition->hec_member_comment }}</td>
            </tr>
            @endif

            @if($requisition->hec_justification)
            <tr>
                <th>JUSTIFICATION</th>
                <td>{{ $requisition->hec_justification }}</td>
            </tr>
            @endif

            @if($requisition->hec_proposed_funding)
            <tr>
                <th>PROPOSED FUNDING</th>
                <td>{{ $requisition->hec_proposed_funding }}</td>
            </tr>
            @endif
        </table>

        @if(isset($approver))
        <table class="signature-table">
            <tr>
                <th>HEC MEMBER NAME</th>
                <td>{{ $approver->fname ?? '' }} {{ $approver->lname ?? '' }}</td>
            </tr>
            <tr>
                <th>SIGNATURE</th>
                <td>
                    @if ($approver->signature)
                        <img src="data:image/png;base64,{{ $approver->signature }}" class="signature-img" alt="HEC Signature">
                    @else
                        <span style="color: #999; font-style: italic;">No signature provided</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>DATE</th>
                <td>{{ $approver->updated_at ? \Carbon\Carbon::parse($approver->updated_at)->format('d F Y') : '' }}</td>
            </tr>
        </table>
        @endif
    </div>
    @endif

    <!-- Section 4: CFO Review -->
    @if($requisition->cfo_financing_confirmation || $requisition->cfo_financing_code || $requisition->cfo_comment || isset($cfoToApprove))
    <div class="section">
        <div class="section-title">4. REVIEW BY CHIEF FINANCIAL OFFICER</div>

        <table class="data-table">
            @if($requisition->cfo_financing_confirmation)
            <tr>
                <th>FINANCING CONFIRMATION</th>
                <td>{{ $requisition->cfo_financing_confirmation }}</td>
            </tr>
            @endif

            @if($requisition->cfo_financing_code)
            <tr>
                <th>FINANCING CODE</th>
                <td>{{ $requisition->cfo_financing_code }}</td>
            </tr>
            @endif

            @if($requisition->cfo_comment)
            <tr>
                <th>COMMENT</th>
                <td>{{ $requisition->cfo_comment }}</td>
            </tr>
            @endif
        </table>

        @if(isset($cfoToApprove))
        <table class="signature-table">
            <tr>
                <th>CFO NAME</th>
                <td>{{ $cfoToApprove->fname ?? '' }} {{ $cfoToApprove->lname ?? '' }}</td>
            </tr>
            <tr>
                <th>SIGNATURE</th>
                <td>
                    @if (isset($cfoToApprove->signature) && $cfoToApprove->signature)
                        <img src="data:image/png;base64,{{ $cfoToApprove->signature }}" class="signature-img" alt="CFO Signature">
                    @else
                        <span style="color: #999; font-style: italic;">No signature provided</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>DATE</th>
                <td>{{ $cfoToApprove->updated_at ? \Carbon\Carbon::parse($cfoToApprove->updated_at)->format('d F Y') : '' }}</td>
            </tr>
        </table>
        @endif
    </div>
    @endif

    <!-- Section 5: CEO Decision -->
    @if($requisition->ceo_decision || $requisition->ceo_comment || isset($ceoToApprove))
    <div class="section">
        <div class="section-title">5. CEO DECISION</div>

        <table class="data-table">
            @if($requisition->ceo_decision)
            <tr>
                <th>CEO DECISION</th>
                <td>
                    @php
                        $decisionText = [
                            'approved' => 'APPROVED',
                            'declined' => 'DECLINED',
                            'needs_further_information' => 'NEEDS FURTHER INFORMATION',
                        ];
                    @endphp
                    <strong class="
                        @if($requisition->ceo_decision == 'approved') status-approved
                        @elseif($requisition->ceo_decision == 'declined') status-rejected
                        @else status-pending @endif
                    ">
                        {{ $decisionText[$requisition->ceo_decision] ?? strtoupper($requisition->ceo_decision) }}
                    </strong>
                </td>
            </tr>
            @endif

            @if($requisition->ceo_comment)
            <tr>
                <th>CEO COMMENT</th>
                <td>{{ $requisition->ceo_comment }}</td>
            </tr>
            @endif
        </table>

        @if(isset($ceoToApprove))
        <table class="signature-table">
            <tr>
                <th>CEO NAME</th>
                <td>{{ $ceoToApprove->fname ?? '' }} {{ $ceoToApprove->lname ?? '' }}</td>
            </tr>
            <tr>
                <th>SIGNATURE</th>
                <td>
                    @if (isset($ceoToApprove->signature) && $ceoToApprove->signature)
                        <img src="data:image/png;base64,{{ $ceoToApprove->signature }}" class="signature-img" alt="CEO Signature">
                    @else
                        <span style="color: #999; font-style: italic;">No signature provided</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>DATE</th>
                <td>{{ $ceoToApprove->updated_at ? \Carbon\Carbon::parse($ceoToApprove->updated_at)->format('d F Y') : '' }}</td>
            </tr>
        </table>
        @endif
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p><strong>Requisition ID:</strong> {{ $requisition->access_id }} | <strong>Generated on:</strong> {{ now()->format('d F Y H:i:s') }}</p>
        <p><strong>Version:</strong> September 2024 | CCBRT Recruitment Requisition Form</p>
    </div>
</body>
</html>
