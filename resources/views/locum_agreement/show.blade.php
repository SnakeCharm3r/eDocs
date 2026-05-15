@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    @php
        $docStart = $agreement->start_date
            ? \Carbon\Carbon::parse($agreement->start_date)
            : \Carbon\Carbon::parse($agreement->created_at);
        $docEnd = $agreement->end_date
            ? \Carbon\Carbon::parse($agreement->end_date)
            : \Carbon\Carbon::parse($agreement->created_at)->addYear();
        $agreementStatus = (int) ($agreement->status ?? 0);
        $statusLabels = [
            0 => ['label' => 'Pending Line Manager Approval', 'class' => 'bg-warning text-dark', 'icon' => 'fas fa-clock'],
            1 => ['label' => 'Pending HR Approval', 'class' => 'bg-info', 'icon' => 'fas fa-hourglass-half'],
            2 => ['label' => 'Approved', 'class' => 'bg-success', 'icon' => 'fas fa-check-circle'],
            3 => ['label' => 'Rejected by Line Manager', 'class' => 'bg-danger', 'icon' => 'fas fa-times-circle'],
            4 => ['label' => 'Rejected by HR', 'class' => 'bg-danger', 'icon' => 'fas fa-times-circle'],
            5 => ['label' => 'Expired', 'class' => 'bg-secondary', 'icon' => 'fas fa-calendar-times'],
        ];
        $currentStatus = $statusLabels[$agreementStatus] ?? ['label' => 'Unknown', 'class' => 'bg-secondary', 'icon' => 'fas fa-question-circle'];
        $isExpired = $docEnd->lt(\Carbon\Carbon::today());
    @endphp

    <style>
        .document-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 40px 35px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333333;
            border-radius: 4px;
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
            margin-bottom: 2px;
            letter-spacing: 2px;
        }

        .document-header .branding-sub {
            font-size: 10pt;
            color: #555555;
            margin-bottom: 12px;
        }

        .document-title {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0 5px;
        }

        .document-subtitle {
            font-size: 13pt;
            color: #4a4a4a;
            margin-bottom: 8px;
        }

        .section-title {
            font-size: 13pt;
            font-weight: bold;
            margin: 25px 0 12px;
            text-transform: uppercase;
            color: #1a1a1a;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .agreement-details p {
            margin-bottom: 12px;
            text-align: justify;
        }

        .agreement-details strong {
            font-weight: bold;
            text-transform: uppercase;
        }

        .criteria-list ol, .compensation-list ol {
            margin: 0 0 15px 25px;
        }

        .criteria-list ol li, .compensation-list ol li {
            margin-bottom: 6px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 20px;
        }

        .info-table th, .info-table td {
            border: 1px solid #ccc;
            padding: 10px 14px;
            text-align: left;
        }

        .info-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10pt;
            width: 30%;
            color: #444;
        }

        .claim-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 20px;
        }

        .claim-table th, .claim-table td {
            border: 1px solid #ccc;
            padding: 10px 14px;
            text-align: left;
        }

        .claim-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10pt;
            color: #444;
        }

        /* Approval progress tracker */
        .approval-tracker {
            display: flex;
            justify-content: space-between;
            margin: 20px 0 30px;
            position: relative;
        }

        .approval-tracker::before {
            content: '';
            position: absolute;
            top: 28px;
            left: 60px;
            right: 60px;
            height: 3px;
            background: #dee2e6;
            z-index: 0;
        }

        .approval-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .step-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 8px;
            border: 3px solid #dee2e6;
            background: #fff;
            transition: all 0.3s;
        }

        .step-icon.completed {
            background: #198754;
            border-color: #198754;
            color: #fff;
        }

        .step-icon.active {
            background: #fff;
            border-color: #0d6efd;
            color: #0d6efd;
            animation: pulse 2s infinite;
        }

        .step-icon.rejected {
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }

        .step-icon.pending {
            background: #fff;
            border-color: #dee2e6;
            color: #adb5bd;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(13, 110, 253, 0); }
        }

        .step-label {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            color: #495057;
            display: block;
        }

        .step-name {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 0.72rem;
            color: #6c757d;
            display: block;
            margin-top: 2px;
        }

        .step-time {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 0.68rem;
            color: #adb5bd;
            display: block;
        }

        /* Signature section */
        .signature-card {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            background: #fafafa;
        }

        .signature-card .sig-label {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .signature-card .sig-name {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 5px;
        }

        .signature-card img {
            max-width: 100px;
            height: auto;
            margin: 5px 0;
        }

        .signature-card .sig-line {
            border-top: 1px solid #999;
            width: 120px;
            margin: 10px auto 5px;
        }

        .document-footer {
            text-align: center;
            font-size: 9pt;
            color: #888;
            margin-top: 35px;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }

        .action-bar {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Status banner */
        .status-banner {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            border-radius: 6px;
            padding: 12px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-banner i {
            font-size: 1.3rem;
        }

        .status-banner .status-text {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .status-banner .status-sub {
            font-size: 0.8rem;
            opacity: 0.85;
        }

        @media print {
            .document-container { box-shadow: none; border: none; padding: 20px; margin: 0; }
            .action-bar, .page-header, .status-banner-wrapper { display: none !important; }
            .page-wrapper, .content { padding: 0; margin: 0; }
        }

        @media (max-width: 768px) {
            .approval-tracker { flex-direction: column; gap: 15px; }
            .approval-tracker::before { display: none; }
            .document-container { padding: 20px 15px; }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-0">Locum Agreement</h3>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <a href="{{ route('locum-agreements.view') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- Agreement Document -->
            <div class="row">
                <div class="col-md-12">
                    <div class="document-container">
                        <!-- Header -->
                        <div class="document-header">
                            <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT" style="max-width: 80px; margin-bottom: 8px;">
                            <h1 class="document-title">Locum Agreement</h1>
                            <p class="document-subtitle mb-0">For CCBRT Employees</p>
                        </div>

                        <!-- Employee Details -->
                        <table class="info-table">
                            <tr>
                                <th>Employee Name</th>
                                <td>{{ trim(($agreement->user->fname ?? '') . ' ' . ($agreement->user->lname ?? '')) ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>CCBRT Code</th>
                                <td>{{ $agreement->user->ccbrt_code ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Department</th>
                                <td>{{ $agreement->user->department->dept_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Education Level</th>
                                <td>{{ $agreement->education_level ?? ($agreement->user->education_level ?? 'N/A') }}</td>
                            </tr>
                            <tr>
                                <th>Locum Rate</th>
                                <td>{{ $agreement->locum_rate ? 'TZS ' . number_format($agreement->locum_rate, 0) : 'N/A' }} <span style="font-size: 9pt; color: #888;">per day</span></td>
                            </tr>
                        </table>

                        <!-- Preamble -->
                        <div class="agreement-details">
                            <h4 class="section-title">Preamble</h4>
                            <p>
                                This document, dated <strong>{{ $docStart->format('j F Y') }}</strong>, serves as a special
                                agreement in addition to the existing 'Contract of Employment' between <strong>CCBRT (Comprehensive
                                Community Based Rehabilitation in Tanzania)</strong>, P.O. Box 23310, Dar es Salaam, and
                                <strong>{{ $agreement->user->fname ?? 'N/A' }} {{ $agreement->user->lname ?? '' }}</strong>
                                residing in Dar es Salaam, hereinafter called the EMPLOYEE.
                            </p>
                            <p>
                                The EMPLOYEE voluntarily agrees to enter into this agreement for LOCUM work from
                                <strong>{{ $docStart->format('j F Y') }}</strong>
                                and this agreement will be valid until
                                <strong>{{ $docEnd->format('j F Y') }}</strong>.
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
                                <li>The locum rate shall be determined based on the Employee's education level, as
                                    established by CCBRT guidelines.</li>
                                <li>Daily claims must be signed by both the Employee and their Supervisor.</li>
                                <li>Daily claims shall be submitted to the payroll desk for processing through the payroll
                                    system.</li>
                                <li>Claims received before the 10th of the following month will be processed in that month's
                                    payroll. Late submissions will be processed in the subsequent month.</li>
                                <li>All locum payments are subject to applicable statutory deductions.</li>
                            </ol>
                        </div>

                        <!-- Signatures -->
                        <h4 class="section-title">3. Signatures</h4>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px;">
                            <div class="signature-card" style="flex: 1; min-width: 200px;">
                                <div class="sig-label">Employee</div>
                                <div class="sig-name">{{ $agreement->user->username ?? 'N/A' }}</div>
                                @if ($agreement->user->signature)
                                    <img src="data:image/png;base64,{{ $agreement->user->signature }}" alt="Employee Signature">
                                @else
                                    <div style="height: 50px; display: flex; align-items: center; justify-content: center; color: #adb5bd;">
                                        <i class="fas fa-signature" style="font-size: 1.5rem;"></i>
                                    </div>
                                @endif
                                <div class="sig-line"></div>
                                <div style="font-size: 8pt; color: #999;">
                                    @if (!empty($employeeActionAt))
                                        {{ $employeeActionAt }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>

                            <div class="signature-card" style="flex: 1; min-width: 200px;">
                                <div class="sig-label">Supervisor / Line Manager</div>
                                <div class="sig-name">{{ $linemanager && $linemanager->username ? $linemanager->username : 'N/A' }}</div>
                                @if ($linemanager && $linemanager->signature && ($lmApproved ?? false))
                                    <img src="data:image/png;base64,{{ $linemanager->signature }}" alt="Supervisor Signature">
                                @elseif ($lmApproved ?? false)
                                    <div style="height: 50px; display: flex; align-items: center; justify-content: center; color: #198754;">
                                        <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                                    </div>
                                @else
                                    <div style="height: 50px; display: flex; align-items: center; justify-content: center; color: #adb5bd;">
                                        <span style="font-size: 0.8rem;">Pending</span>
                                    </div>
                                @endif
                                <div class="sig-line"></div>
                                <div style="font-size: 8pt; color: #999;">
                                    @if ($lmApproved && !empty($lmActionAt))
                                        {{ $lmActionAt }}
                                    @elseif ($lmApproved ?? false)
                                        —
                                    @else
                                        Pending
                                    @endif
                                </div>
                            </div>

                            <div class="signature-card" style="flex: 1; min-width: 200px;">
                                <div class="sig-label">Human Resources</div>
                                <div class="sig-name">{{ $Hr && $Hr->username ? $Hr->username : 'N/A' }}</div>
                                @if ($Hr && $Hr->signature && ($hrApproved ?? false))
                                    <img src="data:image/png;base64,{{ $Hr->signature }}" alt="HR Signature">
                                @elseif ($hrApproved ?? false)
                                    <div style="height: 50px; display: flex; align-items: center; justify-content: center; color: #198754;">
                                        <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                                    </div>
                                @else
                                    <div style="height: 50px; display: flex; align-items: center; justify-content: center; color: #adb5bd;">
                                        <span style="font-size: 0.8rem;">Pending</span>
                                    </div>
                                @endif
                                <div class="sig-line"></div>
                                <div style="font-size: 8pt; color: #999;">
                                    @if ($hrApproved && !empty($hrActionAt))
                                        {{ $hrActionAt }}
                                    @elseif ($hrApproved ?? false)
                                        —
                                    @else
                                        Pending
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="document-footer">
                            <p class="mb-0">CCBRT | P.O. Box 23310, Dar es Salaam, Tanzania</p>
                        </div>

                        <!-- Action Bar -->
                        <div class="action-bar">
                            <a href="{{ route('locum-agreements.view') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i> Back to Agreements
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <br>
        </div>
    </div>
@endsection
