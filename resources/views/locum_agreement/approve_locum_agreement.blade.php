@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    @php
        $user = auth()->user();
        $emp = $agreement->user ?? null;
        $isRequesterLineManager = $emp?->hasRole('line-manager') && !$emp?->hasRole('hr');

        $stageLabel = match ((int) ($agreement->status ?? 0)) {
            0 => 'Pending Line Manager',
            1 => 'Pending HR',
            2 => 'Approved',
            5 => 'Expired',
            default => 'Rejected',
        };
        $stageClass = match ((int) ($agreement->status ?? 0)) {
            0 => 'bg-warning text-dark',
            1 => 'bg-info',
            2 => 'bg-success',
            5 => 'bg-secondary',
            default => 'bg-danger',
        };

        $docStart = $agreement->start_date
            ? \Carbon\Carbon::parse($agreement->start_date)
            : \Carbon\Carbon::parse($agreement->created_at);
        $docEnd = $agreement->end_date
            ? \Carbon\Carbon::parse($agreement->end_date)
            : \Carbon\Carbon::parse($agreement->created_at)->addYear();

        $empName = trim(($emp->fname ?? '') . ' ' . ($emp->lname ?? '')) ?: 'N/A';
        $lmName = isset($linemanager) ? trim(($linemanager->fname ?? '') . ' ' . ($linemanager->lname ?? '')) : null;
        $hrName = isset($Hr) ? trim(($Hr->fname ?? '') . ' ' . ($Hr->lname ?? '')) : null;

        $hasEmpSign = !empty($emp?->signature);
        $hasLmSign = !empty($linemanager?->signature) && ($lmApproved ?? false);
        $hasHrSign = !empty($Hr?->signature) && ($hrApproved ?? false);

        $canLmApprove = !$isRequesterLineManager && $user->hasRole('line-manager') && (int) $agreement->status === 0;
        $canHrApprove = $user->hasRole('hr') && (int) $agreement->status === 1;
        $showActionButtons = $canLmApprove || $canHrApprove;
    @endphp

    <style>
        .approve-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 35px 30px;
            border-radius: 6px;
            font-size: 14px;
            line-height: 1.6;
        }

        .approve-header {
            text-align: center;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .approve-header h1 {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 8px 0 5px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            color: #333;
            margin: 25px 0 12px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 20px;
        }

        .info-table th, .info-table td {
            border: 1px solid #ddd;
            padding: 10px 14px;
            text-align: left;
        }

        .info-table th {
            background: #f8f8f8;
            font-weight: 600;
            width: 30%;
            color: #444;
            font-size: 13px;
        }

        .signature-card {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            background: #fafafa;
        }

        .signature-card .sig-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 6px;
        }

        .signature-card .sig-name {
            font-weight: bold;
            font-size: 13px;
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
            margin: 8px auto 4px;
        }

        .action-bar {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .preamble-text { text-align: justify; margin-bottom: 10px; color: #444; }
        .criteria-list ol { padding-left: 20px; }
        .criteria-list ol li { margin-bottom: 6px; color: #444; }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-0">Review Locum Agreement</h3>
                    </div>
                    <div class="col-auto d-flex gap-2 align-items-center">
                        <span class="badge {{ $stageClass }} fs-6 px-3 py-2">{{ $stageLabel }}</span>
                        @if ($isRequesterLineManager)
                            <span class="badge bg-info fs-6 px-2 py-2">Requester is LM</span>
                        @endif
                        <a href="{{ url('/locum-agreement-show') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="approve-container">
                <!-- Header -->
                <div class="approve-header">
                    <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT" style="max-width: 70px; margin-bottom: 6px;">
                    <h1>Locum Agreement</h1>
                    <p class="mb-0" style="color: #666;">For CCBRT Employees</p>
                </div>

                <!-- Employee Details -->
                <h4 class="section-title">Employee Details</h4>
                <table class="info-table">
                    <tr>
                        <th>Employee Name</th>
                        <td><strong>{{ $empName }}</strong></td>
                    </tr>
                    <tr>
                        <th>CCBRT Code</th>
                        <td>{{ $emp->ccbrt_code ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Department</th>
                        <td>{{ $emp->department->dept_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Education Level</th>
                        <td>{{ $agreement->education_level ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Locum Rate</th>
                        <td>{{ $agreement->locum_rate ? 'TZS ' . number_format($agreement->locum_rate, 0) : 'N/A' }} <span style="font-size: 12px; color: #888;">per day</span></td>
                    </tr>
                    <tr>
                        <th>Contract Period</th>
                        <td><strong>{{ $docStart->format('j F Y') }}</strong> – <strong>{{ $docEnd->format('j F Y') }}</strong></td>
                    </tr>
                    <tr>
                        <th>Submitted On</th>
                        <td>{{ \Carbon\Carbon::parse($agreement->created_at)->format('d M Y \a\t H:i') }}</td>
                    </tr>
                </table>

                <!-- Preamble -->
                <h4 class="section-title">Preamble</h4>
                <p class="preamble-text">
                    This document, dated <strong>{{ $docStart->format('j F Y') }}</strong>, serves as a special agreement in
                    addition to the existing 'Contract of Employment' between
                    <strong>CCBRT (Comprehensive Community Based Rehabilitation in Tanzania)</strong>, P.O. Box 23310,
                    Dar es Salaam, and <strong>{{ $empName }}</strong> residing in Dar es Salaam, hereinafter
                    called the EMPLOYEE.
                </p>
                <p class="preamble-text">
                    The EMPLOYEE voluntarily agrees to enter into this agreement for LOCUM work from
                    <strong>{{ $docStart->format('j F Y') }}</strong>
                    and this agreement will be valid until
                    <strong>{{ $docEnd->format('j F Y') }}</strong>.
                </p>

                <!-- Output Criteria -->
                <div class="criteria-list">
                    <h4 class="section-title">1. Output Criteria</h4>
                    <ol>
                        <li>The Employee contributes positively to service provision of the respective department and
                            performs all required duties as per job/task description provided or additional tasks as
                            demanded in the shift.</li>
                        <li>The Employee will perform duties in their technical capacity.</li>
                        <li>The Employee meets all registration requirements and documentation standards as per CCBRT
                            guidelines and holds a valid license from the respective body (if applicable).</li>
                    </ol>
                </div>

                <!-- Compensation & Payments -->
                <div class="criteria-list">
                    <h4 class="section-title">2. Compensation & Payments</h4>
                    <ol>
                        <li>The locum provision serves as full compensation for the output provided and related transport.</li>
                        <li>The applicable locum rate is based on education level as per CCBRT guidelines.</li>
                        <li>Daily claims must be signed by both Employee and Supervisor.</li>
                        <li>Daily claims are processed to the payroll desk for payment through the payroll.</li>
                        <li>Claims received before the 10th of the next month are processed in that month's payroll.
                            Late submissions are processed the following month.</li>
                        <li>All locum payments are subject to statutory deductions.</li>
                    </ol>
                </div>

                <!-- Signatures -->
                <h4 class="section-title">3. Signatures</h4>
                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px;">
                    <div class="signature-card" style="flex: 1; min-width: 200px;">
                        <div class="sig-label">Employee</div>
                        <div class="sig-name">{{ $empName }}</div>
                        @if ($hasEmpSign)
                            <img src="data:image/png;base64,{{ $emp->signature }}" alt="Employee Signature">
                        @else
                            <div style="height: 50px; display: flex; align-items: center; justify-content: center; color: #adb5bd;">
                                <i class="fas fa-signature" style="font-size: 1.5rem;"></i>
                            </div>
                        @endif
                        <div class="sig-line"></div>
                        <div style="font-size: 11px; color: #999;">{{ $employeeActionAt ?? '—' }}</div>
                    </div>

                    <div class="signature-card" style="flex: 1; min-width: 200px;">
                        <div class="sig-label">Line Manager</div>
                        <div class="sig-name">
                            @if ($isRequesterLineManager)
                                <em style="font-weight: normal; font-size: 12px;">Skipped (requester is LM)</em>
                            @else
                                {{ $lmName ?: '—' }}
                            @endif
                        </div>
                        @if ($isRequesterLineManager)
                            <div style="height: 50px; display: flex; align-items: center; justify-content: center; color: #adb5bd;">
                                <span style="font-size: 0.8rem;">N/A</span>
                            </div>
                        @elseif ($hasLmSign)
                            <img src="data:image/png;base64,{{ $linemanager->signature }}" alt="LM Signature">
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
                        <div style="font-size: 11px; color: #999;">
                            @if ($isRequesterLineManager)
                                Skipped
                            @elseif ($lmApproved && !empty($lmActionAt))
                                {{ $lmActionAt }}
                            @else
                                Pending
                            @endif
                        </div>
                    </div>

                    <div class="signature-card" style="flex: 1; min-width: 200px;">
                        <div class="sig-label">Human Resources</div>
                        <div class="sig-name">{{ $hrName ?: '—' }}</div>
                        @if ($hasHrSign)
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
                        <div style="font-size: 11px; color: #999;">
                            @if ($hrApproved && !empty($hrActionAt))
                                {{ $hrActionAt }}
                            @else
                                Pending
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="action-bar">
                    <a href="{{ url('/locum-agreement-show') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Agreements
                    </a>
                    @if ($showActionButtons)
                        <div class="d-flex gap-2">
                            <a href="{{ route('locum-agreements.approve', $agreement->id) }}"
                                class="btn btn-success">
                                <i class="fas fa-check me-1"></i> Approve
                            </a>
                            <button type="button" class="btn btn-danger" id="rejectButton">
                                <i class="fas fa-times me-1"></i> Reject
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const rejectBtn = document.getElementById('rejectButton');
            if (!rejectBtn) return;

            rejectBtn.addEventListener('click', function() {
                if (typeof Swal === 'undefined' || !Swal.fire) {
                    const reason = prompt('Please provide a reason for rejection:');
                    if (reason && reason.trim()) {
                        const url = @json(route('locum-agreements.reject', ['id' => $agreement->id]));
                        window.location.href = url + '?reason=' + encodeURIComponent(reason.trim());
                    } else if (reason !== null) {
                        alert('Rejection reason is required.');
                    }
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, reject it!',
                    input: 'textarea',
                    inputLabel: 'Please provide a reason for rejection',
                    inputPlaceholder: 'Enter rejection reason...',
                    inputAttributes: { 'aria-label': 'Rejection reason' },
                    preConfirm: (reason) => {
                        if (!reason) Swal.showValidationMessage('Rejection reason is required');
                        return reason;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const url = @json(route('locum-agreements.reject', ['id' => $agreement->id]));
                        window.location.href = url + '?reason=' + encodeURIComponent(result.value);
                    }
                });
            });
        })();
    </script>
@endsection
