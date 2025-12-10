@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        /* Document Styling */
        .document-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cccccc;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            padding: 40px 30px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333333;
        }

        .document-header {
            text-align: center;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .document-header .branding {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .document-header .branding-sub {
            font-size: 10pt;
            color: #555555;
            margin-bottom: 10px;
        }

        .document-title {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
        }

        .document-subtitle {
            font-size: 14pt;
            color: #4a4a4a;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 25px 0 15px;
            text-transform: uppercase;
            color: #1a1a1a;
        }

        .agreement-details p {
            margin-bottom: 15px;
            text-align: justify;
        }

        .agreement-details strong {
            font-weight: bold;
            text-transform: uppercase;
        }

        .criteria-list ol,
        .compensation-list ol {
            margin: 0 0 15px 25px;
        }

        .criteria-list ol li,
        .compensation-list ol li {
            margin-bottom: 8px;
        }

        .claim-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 25px;
        }

        .claim-table th,
        .claim-table td {
            border: 1px solid #1a1a1a;
            padding: 12px;
            text-align: left;
        }

        .claim-table th {
            background-color: #e6e6e6;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11pt;
        }

        .approval-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .approval-table td {
            border: none;
            padding: 10px 0;
            vertical-align: top;
        }

        .signature-line {
            border-top: 1px solid #1a1a1a;
            width: 180px;
            margin-top: 30px;
        }

        .document-footer {
            text-align: center;
            font-size: 10pt;
            color: #666666;
            margin-top: 40px;
            border-top: 1px solid #cccccc;
            padding-top: 10px;
        }

        .back-button {
            margin-top: 20px;
            text-align: right;
        }

        /* Print Styles */
        @media print {
            .document-container {
                box-shadow: none;
                border: none;
                padding: 20px;
                margin: 0;
            }

            .back-button {
                display: none;
            }

            .page-wrapper,
            .content {
                padding: 0;
                margin: 0;
            }

            .page-header {
                display: none;
            }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Locum Agreement</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agreement Document -->
            <div class="row">
                <div class="col-md-12">
                    <div class="document-container">
                        <!-- Header -->
                        <div class="document-header">
                            {{-- <div class="branding">CCBRT</div> --}}
                            <h1 class="document-title">Locum Agreement</h1>
                            <h3 class="document-subtitle">For CCBRT Employees</h3>
                        </div>

                        <!-- Agreement Details -->
                        <div class="agreement-details">
                            <p>
                                THIS AGREEMENT is made between <strong>CCBRT</strong> (Comprehensive Community Based
                                Rehabilitation in Tanzania), located at P.O. Box 23310, Dar es Salaam, hereinafter referred
                                to as the <strong>EMPLOYER</strong> and
                                <strong>{{ $agreement->user->fname ?? 'N/A' }} {{ $agreement->user->lname ?? '' }}
                                </strong>Residing at Dar es Salaam, hereinafter referred to as the
                                <strong>EMPLOYEE</strong>.
                            </p>
                            <p>
                                WHEREAS the <strong>EMPLOYEE</strong>, a qualified professional, voluntarily agrees to
                                undertake <strong>LOCUM</strong> work for the <strong>EMPLOYER</strong>, commencing on
                                <strong>{{ $agreement->start_date ? \Carbon\Carbon::parse($agreement->start_date)->format('F j, Y') : '1st July 2025' }}</strong>
                                and concluding on
                                <strong>{{ $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date)->format('F j, Y') : '30th June 2026' }}</strong>.
                            </p>
                        </div>

                        <!-- Output Criteria -->
                        <div class="criteria-list">
                            <h4 class="section-title">1. Output Criteria</h4>
                            <ol>
                                <li>The Employee shall contribute positively to the service provision of their assigned
                                    department, fulfilling all duties as outlined in the job/task description or any
                                    additional tasks required during the shift.</li>
                                <li>The Employee shall perform duties in accordance with their professional technical
                                    capacity.</li>
                                <li>The Employee shall comply with all CCBRT registration requirements, documentation
                                    standards, and maintain a valid license from the respective professional body, if
                                    applicable.</li>
                            </ol>
                        </div>

                        <!-- Compensation & Payments -->
                        <div class="compensation-list">
                            <h4 class="section-title">2. Compensation & Payments</h4>
                            <ol>
                                <li>The locum provision constitutes full compensation for services rendered, including any
                                    associated transport costs.</li>
                                <li>The locum rate shall be determined based on the Employee’s education level, as
                                    established by CCBRT guidelines.</li>
                                <li>Daily claims must be signed by both the Employee and their Supervisor.</li>
                                <li>Daily claims shall be submitted to the payroll desk for processing through the payroll
                                    system.</li>
                                <li>Claims received before the 10th of the following month will be processed in that month’s
                                    payroll. Late submissions will be processed in the subsequent month.</li>
                                <li>All locum payments are subject to applicable statutory deductions.</li>
                            </ol>
                        </div>

                        <!-- Claim Section -->
                        <div class="claim-section">
                            <h4 class="section-title">3. Claim</h4>
                            <table class="claim-table">
                                <thead>
                                    <tr>
                                        <th>Locum Rate</th>
                                        <th>Education Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $agreement->locum_rate ? 'TZS ' . number_format($agreement->locum_rate, 2) : 'N/A' }}
                                        </td>
                                        <td>{{ $agreement->education_level ?? ($user->education_level ?? 'N/A') }}</td>

                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Approval Section -->
                        <div class="approval-section">
                            <h4 class="section-title">4. Approval</h4>
                            <style>
                                .approval-table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    margin: 20px 0;
                                }

                                .approval-table td {
                                    padding: 10px;
                                    vertical-align: top;
                                    border: 1px solid #ddd;
                                }

                                .signature-img {
                                    width: 90px;
                                    max-width: 100%;
                                    height: auto;
                                    display: inline-block;
                                    vertical-align: middle;
                                    margin: 0 5px;
                                }

                                .signature-label {
                                    display: inline-block;
                                    vertical-align: middle;
                                }

                                /* .signature-line {
                                                                                        border-top: 1px solid #000;
                                                                                        width: 90px;
                                                                                        /* Match signature image width */
                                /* margin-top: 5px;
                                                                                    } */

                                @media (max-width: 600px) {
                                    .signature-img {
                                        width: 70px;
                                        /* Smaller size for mobile */
                                    }


                                }
                            </style>
                            <table class="approval-table">
                                <tr>
                                    <td>
                                        <p><strong>Employee Name:</strong> {{ $agreement->user->username ?? 'N/A' }}</p>
                                        <p>
                                            <strong class="signature-label">Signature:</strong>
                                            @if ($agreement->user->signature)
                                                <img src="data:image/png;base64,{{ $agreement->user->signature }}"
                                                    alt="Employee Signature" class="signature-img">
                                            @else
                                                <span class="signature-label">Pending</span>
                                            @endif
                                        </p>
                                    </td>
                                    <td>
                                        <p><strong>Supervisor Name:</strong> {{ $linemanager->username ?? 'N/A' }}</p>
                                        <p>
                                            <strong class="signature-label">Signature:</strong>
                                            @if ($linemanager && $linemanager->signature && ($lmApproved ?? false))
                                                <img src="data:image/png;base64,{{ $linemanager->signature }}"
                                                    alt="Supervisor Signature" class="signature-img">
                                            @else
                                                <span class="signature-label">Pending</span>
                                            @endif
                                        </p>
                                        <p class="text-muted mb-0">
                                            <strong>Action Time:</strong>
                                            @if ($lmApproved && !empty($lmActionAt))
                                                {{ $lmActionAt }}
                                            @else
                                                Pending
                                            @endif
                                        </p>
                                    </td>
                                    <td>
                                        <p><strong>HR Name:</p> {{ $Hr->username ?? 'N/A' }}</p>
                                        <p>
                                            <strong class="signature-label">Signature:</strong>
                                            @if ($Hr && $Hr->signature && ($hrApproved ?? false))
                                                <img src="data:image/png;base64,{{ $Hr->signature }}" alt="HR Signature"
                                                    class="signature-img">
                                            @else
                                                <span class="signature-label">Pending</span>
                                            @endif
                                        </p>
                                        <p class="text-muted mb-0">
                                            <strong>Action Time:</strong>
                                            @if ($hrApproved && !empty($hrActionAt))
                                                {{ $hrActionAt }}
                                            @else
                                                Pending
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Footer -->
                        <div class="document-footer">
                            <p>CCBRT | P.O. Box 23310, Dar es Salaam, Tanzania</p>
                            <p>Page 1 of 1</p>
                        </div>

                        <!-- Back Button -->
                        <div class="back-button">
                            <a href="{{ route('locum-agreements.view') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to Locum Agreements
                            </a>
                            {{-- <a href="#" class="btn btn-sm btn-outline-success" title="Download">
                                <i class="fas fa-download"></i>
                            </a> --}}
                        </div>
                    </div>
                </div>
            </div>
            <br>
        </div>
    </div>
@endsection
