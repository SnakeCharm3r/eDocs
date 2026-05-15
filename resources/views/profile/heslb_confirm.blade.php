@extends('layouts.template')
@include('includes.loader')

@section('breadcrumb')
    <style>
        @media print {
            .header, .sidebar, .footer, .fixed-bottom,
            .page-header, .page-sub-header,
            .btn, button, .button-group,
            #loader, .loader, .swal2-container,
            nav, .navbar, .no-print { display: none !important; }

            body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
            .main-wrapper, .content-wrapper {
                margin: 0 !important; padding: 0 !important;
                width: 100% !important; float: none !important;
            }
            .page-wrapper { padding: 0 !important; margin: 0 !important; }
            .content.container-fluid {
                padding: 0 !important; margin: 0 !important;
                max-width: 100% !important; width: 100% !important;
            }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            @page { size: A4 portrait; margin: 10mm; }
            .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; page-break-inside: avoid; }
            tr { page-break-inside: avoid; }
            img { max-width: 100% !important; }
        }
    </style>
    <div class="page-header">
        <div class="row">
            <div class="col-sm-12">
                <div class="page-sub-header">
                    <h3 class="page-title d-flex align-items-center">
                        <i class="fas fa-graduation-cap me-2" style="color: #007A33;"></i>HESLB Loan Declaration Form Review
                    </h3>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">
                    <!-- Header Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo" class="img-fluid me-3" style="max-width: 80px;">
                                    <div>
                                        <h4 class="mb-0" style="color: #007A33;">Higher Education Students Loans Board</h4>
                                        <small class="text-muted">Employee Declaration - HR.32</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light text-dark">Form ID: #{{ $heslbForm->access_id }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HESLB Information Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2 text-info"></i>1. HESLB Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>CCBRT as an employer is legally required to ensure the recovery of outstanding HESLB loans from its employees.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Employees of CCBRT must declare whether they have an outstanding HESLB loan.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>CCBRT will confirm outstanding obligations with HESLB for its employees.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>In accordance with the law, monthly deductions are made as per the percentage specified in the HESLB Act through payroll, with CCBRT submitting the deductions to HESLB on behalf of the employee.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>HESLB deductions take precedence over all other loan deductions applicable to an employee.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Declaration Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-signature me-2 text-success"></i>2. Employee Declaration
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-light border">
                                <p class="mb-3">
                                    I, <strong>{{ trim(($heslbForm->fname ?? '') . ' ' . ($heslbForm->mname ?? '') . ' ' . ($heslbForm->lname ?? '')) }}</strong>,
                                    hereby confirm the following:
                                </p>
                                @if($heslbForm->has_loan == 'Yes')
                                    <p class="mb-0">
                                        I have obtained a loan from the HESLB loans board under Form IV Index No.
                                        <strong>{{ $heslbForm->form_iv_index ?? 'N/A' }}</strong>, which is still to be paid back,
                                        and I hereby instruct CCBRT to deduct from my monthly salary as per the guidelines from the HESLB.
                                        Deductions will start in the next upcoming payroll.
                                    </p>
                                @else
                                    <p class="mb-0">
                                        I have no outstanding loan from the HESLB loans board.
                                    </p>
                                @endif
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Employee Name</label>
                                    <p class="mb-0 fw-semibold">
                                        {{ trim(($heslbForm->fname ?? '') . ' ' . ($heslbForm->mname ?? '') . ' ' . ($heslbForm->lname ?? '')) }}
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Form IV Index No.</label>
                                    <p class="mb-0 fw-semibold">
                                        {{ $heslbForm->form_iv_index ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Loan Status</label>
                                    <p class="mb-0">
                                        @if($heslbForm->has_loan == 'Yes')
                                            <span class="badge bg-warning">Has Outstanding Loan</span>
                                        @else
                                            <span class="badge bg-success">No Outstanding Loan</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Employee Signature</label>
                                    <div>
                                        @if ($heslbForm->signature)
                                            <img src="data:image/png;base64,{{ $heslbForm->signature }}" alt="User Signature"
                                                class="img-thumbnail" style="max-width: 120px; height: auto; border: 1px solid #dee2e6;">
                                        @else
                                            <span class="text-muted">No Signature</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Date</label>
                                    <p class="mb-0 fw-semibold">{{ \Carbon\Carbon::parse($heslbForm->created_at ?? now())->format('d F Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HR Confirmation Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clipboard-check me-2 text-info"></i>3. HR Confirmation Receipt
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">HR Officer Name</label>
                                    <p class="mb-0 fw-semibold">
                                        @if ($isApproved && $HrToApprove)
                                            {{ trim(($HrToApprove->fname ?? '') . ' ' . ($HrToApprove->lname ?? '')) }}
                                        @elseif ($isRejected && $rejectedBy)
                                            {{ trim(($rejectedBy->fname ?? '') . ' ' . ($rejectedBy->lname ?? '')) }}
                                        @elseif ($HrToApprove)
                                            {{ trim(($HrToApprove->fname ?? '') . ' ' . ($HrToApprove->lname ?? '')) }}
                                        @else
                                            <span class="text-muted">Not Assigned</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">HR Signature</label>
                                    <div>
                                        @if ($isApproved && $HrToApprove && $HrToApprove->signature)
                                            <img src="data:image/png;base64,{{ $HrToApprove->signature }}" alt="HR Officer Signature"
                                                class="img-thumbnail" style="max-width: 120px; height: auto; border: 1px solid #dee2e6;">
                                        @elseif ($isRejected && $rejectedBy && $rejectedBy->signature)
                                            <img src="data:image/png;base64,{{ $rejectedBy->signature }}" alt="HR Officer Signature"
                                                class="img-thumbnail" style="max-width: 120px; height: auto; border: 1px solid #dee2e6;">
                                        @else
                                            <span class="text-muted">{{ ($isApproved || $isRejected) ? 'No Signature' : 'Pending' }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Date</label>
                                    <p class="mb-0 fw-semibold">
                                        @if ($isApproved && $HrToApprove && $HrToApprove->updated_at)
                                            {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
                                        @elseif ($isRejected && $rejectedAt)
                                            {{ \Carbon\Carbon::parse($rejectedAt)->format('d F Y') }}
                                        @else
                                            <span class="text-muted">Pending</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    @if(isset($isApproved) && $isApproved)
                        <div class="alert alert-success mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2 fs-4"></i>
                                <div>
                                    <strong>Approved</strong>
                                    @if($HrToApprove)
                                        <p class="mb-0 small">Approved by {{ trim(($HrToApprove->fname ?? '') . ' ' . ($HrToApprove->lname ?? '')) }} on {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @elseif(isset($isRejected) && $isRejected)
                        <div class="alert alert-danger mb-4" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-times-circle me-2 fs-4 mt-1"></i>
                                <div class="flex-grow-1">
                                    <strong>Rejected</strong>
                                    @if($rejectedBy)
                                        <p class="mb-1 small">Rejected by {{ trim(($rejectedBy->fname ?? '') . ' ' . ($rejectedBy->lname ?? '')) }} 
                                            @if($rejectedAt)
                                                on {{ \Carbon\Carbon::parse($rejectedAt)->format('d F Y') }}
                                            @endif
                                        </p>
                                    @endif
                                    @if($rejectionReason)
                                        <div class="mt-2">
                                            <strong class="small">Rejection Reason:</strong>
                                            <p class="mb-0 small">{{ $rejectionReason }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('requestapprove.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </a>
                                @php
                                    $hasPendingAction = !$isApproved && !$isRejected && \App\Models\WorkFlowHistory::where('work_flow_id', $workflow->id)
                                        ->where('attended_by', Auth::id())
                                        ->where('status', 0)
                                        ->exists();
                                @endphp
                                @if($hasPendingAction)
                                    <div>
                                        <button type="button" class="btn btn-success me-2"
                                            onclick="approveHeslbForm('{{ $heslbForm->access_id }}')">
                                            <i class="fas fa-check-circle me-2"></i>Approve
                                        </button>
                                        <button type="button" class="btn btn-danger"
                                            onclick="rejectHeslbForm('{{ $heslbForm->access_id }}')">
                                            <i class="fas fa-times-circle me-2"></i>Reject
                                        </button>
                                    </div>
                                @elseif(isset($isApproved) && $isApproved)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success fs-6 px-3 py-2">
                                            <i class="fas fa-check-circle me-1"></i>Already Approved
                                        </span>
                                        <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                                            <i class="fas fa-file-pdf me-1"></i> Download PDF
                                        </button>
                                    </div>
                                @elseif(isset($isRejected) && $isRejected)
                                    <div>
                                        <span class="badge bg-danger fs-6 px-3 py-2">
                                            <i class="fas fa-times-circle me-1"></i>Rejected
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script for Actions -->
    <script>
        function approveHeslbForm(access_id) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to confirm this request?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, confirm it!',
                cancelButtonText: 'No, cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/heslb_form_confirm',
                        method: 'POST',
                        data: {
                            access_id: access_id
                        },
                        success: function(response) {
                            window.location.href = '/requestapprove';
                        },
                        error: function(xhr, status, error) {
                            Swal.fire('Error!', 'There was an error confirming the form.', 'error');
                        }
                    });
                }
            });
        }

        function rejectHeslbForm(access_id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You are about to reject this submission.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                cancelButtonText: 'Cancel',
                input: 'textarea',
                inputPlaceholder: 'Please provide a rejection reason...',
                inputAttributes: {
                    'aria-label': 'Type your rejection reason here'
                },
                showLoaderOnConfirm: true,
                preConfirm: (comment) => {
                    if (!comment) {
                        Swal.showValidationMessage('Please provide a rejection reason');
                        return false;
                    }
                    return comment;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    var comment = result.value;

                    $.ajax({
                        url: '/heslb_form_reject',
                        method: 'POST',
                        data: {
                            access_id: access_id,
                            status: 'rejected',
                            comment: comment,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire('Rejected!', response.message, 'success')
                                .then(() => {
                                    window.location.href = '/requestapprove';
                                });
                        },
                        error: function(xhr, status, error) {
                            Swal.fire('Error!', 'There was an error rejecting the submission.',
                                'error');
                        }
                    });
                }
            });
        }
    </script>
@endsection
