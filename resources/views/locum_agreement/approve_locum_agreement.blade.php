@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .pdf-like-container {
            max-width: 1200px;
            margin: 0 auto;
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            border: 1px solid #ddd;
            padding: 30px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
            border-radius: 8px;
        }

        .pdf-header {
            margin-bottom: 20px;
        }

        .pdf-header h3 {
            font-size: 24px;
            font-weight: bold;
            color: #459c51;
            margin: 0;
        }

        .pdf-header p {
            font-style: italic;
            color: #6c757d;
            margin: 0;
        }

        .pdf-section {
            margin-bottom: 30px;
        }

        .pdf-section h5 {
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #459c51;
            padding-bottom: 5px;
        }

        .pdf-section p {
            margin-bottom: 12px;
            color: #444;
        }

        .pdf-section ol {
            padding-left: 20px;
            margin-bottom: 0;
        }

        .pdf-section ol li {
            margin-bottom: 10px;
            color: #444;
        }

        .claim-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .claim-table th,
        .claim-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .claim-table th {
            width: 30%;
            font-weight: 600;
            background: #f8f8f8;
            color: #333;
        }

        .claim-table td {
            background: #fff;
        }

        .alert {
            font-size: 14px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .btn-primary,
        .btn-secondary {
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 4px;
            transition: background-color .3s;
        }

        .btn-primary {
            background-color: #459c51;
            border-color: #459c51;
        }

        .btn-primary:hover {
            background-color: #3a8043;
            border-color: #3a8043;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }

        .stage-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
        }

        .stage-lm {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .stage-hr {
            background: #e2f0e9;
            color: #196b3b;
            border: 1px solid #c7e6d7;
        }

        .stage-approved {
            background: #e9f7ef;
            color: #1e7e34;
            border: 1px solid #d4edda;
        }

        .stage-rejected {
            background: #fdecea;
            color: #a71d2a;
            border: 1px solid #f5c6cb;
        }
    </style>

    @php
        $user = auth()->user();
        $rolesForIndexBtn = ['hr', 'Admin', 'super-admin'];

        // Defensive accessors
        $emp = $agreement->user ?? null;

        // Determine if the REQUESTER is a Line Manager (and not HR)
        $isRequesterLineManager = $emp?->hasRole('line-manager') && !$emp?->hasRole('hr');

        // Stage helper from agreement->status
        // 0 => LM stage, 1 => HR stage, 2 => Approved, 3/4/5 => Rejected/Closed (adjust to your map)
        $stageLabel = match ((int) ($agreement->status ?? 0)) {
            0 => 'Pending Line Manager',
            1 => 'Pending HR',
            2 => 'Approved',
            default => 'Closed / Rejected',
        };
        $stageClass = match ((int) ($agreement->status ?? 0)) {
            0 => 'stage-lm',
            1 => 'stage-hr',
            2 => 'stage-approved',
            default => 'stage-rejected',
        };

        // Dates for preamble
        $start = optional($agreement->start_date)->format('j F Y');
        $end = optional($agreement->end_date)->format('j F Y');
        $preambleStart = $start ?: '1st July 2025';
        $preambleEnd = $end ?: '30th June 2026';

        // Quick helpers for names & signatures
        $empName = trim(($emp->fname ?? '') . ' ' . ($emp->lname ?? '')) ?: 'N/A';

        $lmName = isset($linemanager) ? trim(($linemanager->fname ?? '') . ' ' . ($linemanager->lname ?? '')) : null;

        $hrName = isset($Hr) ? trim(($Hr->fname ?? '') . ' ' . ($Hr->lname ?? '')) : null;

        $hasEmpSign = !empty($emp?->signature);
        $hasLmSign = !empty($linemanager?->signature) && ($lmApproved ?? false);
        $hasHrSign = !empty($Hr?->signature) && ($hrApproved ?? false);
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="pdf-like-container">
                <!-- Header -->
                <div class="pdf-header text-center border-bottom pb-3 mb-4">
                    <div class="d-flex justify-content-center align-items-center">
                        <div>
                            <h3>Locum Agreement</h3>
                            <p>For CCBRT Employees</p>
                            <div class="mt-2">
                                <span class="stage-badge {{ $stageClass }}">{{ $stageLabel }}</span>
                                @if ($isRequesterLineManager)
                                    <span class="stage-badge stage-hr ms-1">Requester is Line Manager
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Oops! There were some problems:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($user && $user->hasAnyRole($rolesForIndexBtn))
                    <div class="mb-4 text-end">
                        <a href="{{ route('locum-requests.index') }}" class="btn btn-info">
                            <i class="fas fa-list"></i> My Locum Requests
                        </a>
                    </div>
                @endif

                <!-- Preamble -->
                <div class="pdf-section">
                    <h5>Preamble</h5>
                    <p>This document, dated <strong>{{ now()->format('d F Y') }}</strong>, serves as a special agreement in
                        addition to the existing ‘Contract of Employment’ between
                        <strong>CCBRT (Comprehensive Community Based Rehabilitation in Tanzania)</strong>, P.O. Box 23310,
                        Dar es Salaam, and <strong>{{ $empName }}</strong> residing in Dar es Salaam, hereinafter
                        called the EMPLOYEE.
                    </p>
                    <p>The EMPLOYEE voluntarily agrees to enter into this agreement for LOCUM work from
                        <strong>{{ $preambleStart }}</strong> to <strong>{{ $preambleEnd }}</strong>.
                    </p>
                </div>

                <!-- Output Criteria -->
                <div class="pdf-section">
                    <h5>Output Criteria</h5>
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
                <div class="pdf-section">
                    <h5>Compensation & Payments</h5>
                    <ol>
                        <li>The locum provision serves as full compensation for the output provided and related
                            transport.</li>
                        <li>The applicable locum rate is based on education level as per CCBRT guidelines.</li>
                        <li>Daily claims must be signed by both Employee and Supervisor.</li>
                        <li>Daily claims are processed to the payroll desk for payment through the payroll.</li>
                        <li>Claims received before the 10th of the next month are processed in that month’s payroll.
                            Late submissions are processed the following month.</li>
                        <li>All locum payments are subject to statutory deductions.</li>
                    </ol>
                </div>

                <!-- Claim -->
                <div class="pdf-section">
                    <h5>Claim</h5>
                    <table class="claim-table">
                        <tr>
                            <th>Employee Name</th>
                            <td>{{ $empName }}</td>
                        </tr>
                        <tr>
                            <th>Employee Signature</th>
                            <td>
                                @if ($hasEmpSign)
                                    <img src="data:image/png;base64,{{ $emp->signature }}" alt="User Signature"
                                        style="max-width: 20%; margin-right: 10px;">
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Education Level</th>
                            <td>{{ $agreement->education_level ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Locum Rate</th>
                            <td>
                                @if ($agreement->locum_rate)
                                    TZS {{ number_format($agreement->locum_rate, 2) }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>

                        {{-- Line Manager rows adapt if requester IS LM --}}
                        <tr>
                            <th>Line Manager</th>
                            <td>
                                @if ($isRequesterLineManager)
                                    <em>— Requester is the Line Manager.</em>
                                @else
                                    {{ $lmName ?: '—' }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Line Manager Signature</th>
                            <td>
                                @if ($isRequesterLineManager)
                                    <em>— Skipped (sent directly to HR).</em>
                                @else
                                    @if ($hasLmSign)
                                        <img src="data:image/png;base64,{{ $linemanager->signature }}"
                                            alt="Line Manager Signature" style="max-width: 20%; margin-right: 10px;">
                                    @else
                                        —
                                    @endif
                                @endif
                                <div class="text-muted small mt-1">
                                    <strong>Action Time:</strong>
                                    @if ($lmApproved && !empty($lmActionAt))
                                        {{ $lmActionAt }}
                                    @else
                                        Pending
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- HR rows --}}
                        <tr>
                            <th>HR</th>
                            <td>{{ $hrName ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>HR Signature</th>
                            <td>
                                @if ($hasHrSign)
                                    <img src="data:image/png;base64,{{ $Hr->signature }}" alt="HR Signature"
                                        style="max-width: 20%; margin-right: 10px;">
                                @else
                                    —
                                @endif
                                <div class="text-muted small mt-1">
                                    <strong>Action Time:</strong>
                                    @if ($hrApproved && !empty($hrActionAt))
                                        {{ $hrActionAt }}
                                    @else
                                        Pending
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>

                    {{-- Approve / Reject controls --}}
                    @php
                        // Visibility rules:
                        // - If requester IS LM: only HR can act when status == 1
                        // - Else: LM can act when status == 0; HR when status == 1 (original behavior)
                        $canLmApprove =
                            !$isRequesterLineManager &&
                            $user->hasRole('line-manager') &&
                            (int) $agreement->status === 0;
                        $canHrApprove = $user->hasRole('hr') && (int) $agreement->status === 1;
                        $showActionButtons = $canLmApprove || $canHrApprove;
                    @endphp

                    <div class="d-flex gap-2">
                        @if ($showActionButtons)
                            <a href="{{ route('locum-agreements.approve', $agreement->id) }}"
                                class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Approve
                            </a>
                            <button type="button" class="btn btn-danger btn-sm" id="rejectButton">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        @endif
                        <a href="{{ url('/locum-agreement-show') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Safe guard: only run if inputs exist (this page is a show page, not a form) --}}
    <script>
        (function() {
            const edu = document.getElementById('education_level');
            const rate = document.getElementById('locum_rate');
            if (!edu || !rate) return;

            const recompute = () => {
                let v = 0;
                switch (edu.value) {
                    case 'Certificate':
                        v = 50000;
                        break;
                    case 'Enrolled_Certificate':
                        v = 60000;
                        break;
                    case 'Diploma':
                        v = 80000;
                        break;
                    case 'Degree':
                        v = 100000;
                        break;
                    case 'Masters':
                        v = 200000;
                        break;
                    default:
                        v = 0;
                }
                rate.value = v;
            };
            edu.addEventListener('change', recompute);
            recompute();
        })();
    </script>

    <script>
        (function() {
            const rejectBtn = document.getElementById('rejectButton');
            if (!rejectBtn) return;

            rejectBtn.addEventListener('click', function() {
                // Check if SweetAlert2 is available
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
                    inputAttributes: {
                        'aria-label': 'Rejection reason'
                    },
                    preConfirm: (reason) => {
                        if (!reason) {
                            Swal.showValidationMessage('Rejection reason is required');
                        }
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
