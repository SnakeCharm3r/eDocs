<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recruitment Requisition Form (HR.01) - RR-{{ str_pad($requisition->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #f0f0f0;
            padding: 8px;
            font-weight: bold;
            border: 1px solid #000;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table td {
            padding: 8px;
            border: 1px solid #000;
        }
        table td:first-child {
            font-weight: bold;
            width: 30%;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 10px;
        }
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-box {
            display: inline-block;
            width: 45%;
            margin: 10px 2%;
            border: 1px solid #000;
            padding: 10px;
            min-height: 80px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CCBRT RECRUITMENT REQUISITION FORM (HR.01)</h1>
        <p>Reference Number: RR-{{ str_pad($requisition->id, 6, '0', STR_PAD_LEFT) }}</p>
        <p>Date: {{ $requisition->created_at->format('d M Y') }}</p>
    </div>

    <div class="section">
        <div class="section-title">SECTION 1: POSITION DETAILS</div>
        <table>
            <tr>
                <td>Job Title</td>
                <td>{{ $requisition->job_title }}</td>
            </tr>
            <tr>
                <td>Background</td>
                <td>{{ ucfirst(str_replace('_', ' ', $requisition->background)) }}</td>
            </tr>
            @if($requisition->background === 'contract_renewal_extension')
            <tr>
                <td>Employee Name</td>
                <td>{{ $requisition->employee_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Current Contract End Date</td>
                <td>{{ $requisition->current_contract_end_date ? $requisition->current_contract_end_date->format('d M Y') : 'N/A' }}</td>
            </tr>
            @endif
            <tr>
                <td>Department</td>
                <td>{{ $requisition->department->dept_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Responsibility Centre</td>
                <td>{{ $requisition->responsibility_centre }}</td>
            </tr>
            <tr>
                <td>Reports To Position</td>
                <td>{{ $requisition->reports_to_position }}</td>
            </tr>
            <tr>
                <td>Contract Type</td>
                <td>{{ ucfirst(str_replace('_', ' ', $requisition->contract_type)) }}</td>
            </tr>
            <tr>
                <td>Position Approved in Budget</td>
                <td>{{ $requisition->position_approved_in_budget ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td>Funding Available</td>
                <td>{{ $requisition->funding_available ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td>Max Monthly Budget</td>
                <td>{{ $requisition->max_monthly_budget ? number_format($requisition->max_monthly_budget, 2) : 'N/A' }}</td>
            </tr>
            <tr>
                <td>Donor Code</td>
                <td>{{ $requisition->donor_code ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Activity Code</td>
                <td>{{ $requisition->activity_code ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Required Starting Date</td>
                <td>{{ $requisition->required_starting_date->format('d M Y') }}</td>
            </tr>
            @if($requisition->payrollAccountant)
            <tr>
                <td>Payroll Accountant</td>
                <td>{{ $requisition->payrollAccountant->name }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">SECTION 2: CONDITIONS & JUSTIFICATION</div>
        <table>
            <tr>
                <td>Selected Conditions</td>
                <td>
                    @foreach($requisition->conditions ?? [] as $condition)
                        {{ $condition }}. 
                        @if($condition == 1) Overwhelming medical/operational imperatives
                        @elseif($condition == 2) Safety or reputational risks
                        @elseif($condition == 3) Legal requirement to fill the post
                        @elseif($condition == 4) Evidence of demonstrable financial loss if not filled
                        @elseif($condition == 5) Post is necessary to increase income significantly
                        @endif
                        <br>
                    @endforeach
                </td>
            </tr>
            <tr>
                <td>Justification</td>
                <td>{{ $requisition->justification_text }}</td>
            </tr>
            <tr>
                <td>HOD</td>
                <td>{{ $requisition->hod->name ?? 'N/A' }}</td>
            </tr>
            @if($requisition->hod_signed_at)
            <tr>
                <td>HOD Signed At</td>
                <td>{{ $requisition->hod_signed_at->format('d M Y H:i') }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if($requisition->hecReviewer)
    <div class="section">
        <div class="section-title">HEC REVIEW</div>
        <table>
            <tr>
                <td>Reviewed By</td>
                <td>{{ $requisition->hecReviewer->name }}</td>
            </tr>
            <tr>
                <td>Decision</td>
                <td>{{ ucfirst(str_replace('_', ' ', $requisition->hec_decision ?? 'N/A')) }}</td>
            </tr>
            @if($requisition->hec_comments)
            <tr>
                <td>Comments</td>
                <td>{{ $requisition->hec_comments }}</td>
            </tr>
            @endif
            @if($requisition->hec_reviewed_at)
            <tr>
                <td>Reviewed At</td>
                <td>{{ $requisition->hec_reviewed_at->format('d M Y H:i') }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    @if($requisition->cfoReviewer)
    <div class="section">
        <div class="section-title">CFO FINANCE REVIEW</div>
        <table>
            <tr>
                <td>Reviewed By</td>
                <td>{{ $requisition->cfoReviewer->name }}</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>{{ $requisition->status === 'cfo_finance_confirmed' ? 'Confirmed' : 'Rejected' }}</td>
            </tr>
            @if($requisition->cfo_financing_confirmation)
            <tr>
                <td>Financing Confirmation</td>
                <td>{{ $requisition->cfo_financing_confirmation }}</td>
            </tr>
            @endif
            @if($requisition->cfo_reviewed_at)
            <tr>
                <td>Reviewed At</td>
                <td>{{ $requisition->cfo_reviewed_at->format('d M Y H:i') }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    @if($requisition->ceoReviewer)
    <div class="section">
        <div class="section-title">CEO DECISION</div>
        <table>
            <tr>
                <td>Reviewed By</td>
                <td>{{ $requisition->ceoReviewer->name }}</td>
            </tr>
            <tr>
                <td>Decision</td>
                <td>{{ ucfirst(str_replace('_', ' ', $requisition->ceo_decision ?? 'N/A')) }}</td>
            </tr>
            @if($requisition->ceo_comment)
            <tr>
                <td>Comments</td>
                <td>{{ $requisition->ceo_comment }}</td>
            </tr>
            @endif
            @if($requisition->ceo_reviewed_at)
            <tr>
                <td>Reviewed At</td>
                <td>{{ $requisition->ceo_reviewed_at->format('d M Y H:i') }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    <div class="footer">
        <p><strong>Status:</strong> {{ $requisition->getStatusLabel() }}</p>
        <p><strong>Generated:</strong> {{ now()->format('d M Y H:i:s') }}</p>
    </div>
</body>
</html>




