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
                        <i class="fas fa-hospital me-2" style="color: #007A33;"></i>NHIF Registration Form Review
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
                                        <h4 class="mb-0" style="color: #007A33;">NHIF (National Health Insurance Fund)</h4>
                                        <small class="text-muted">Registration Form - HR.32</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light text-dark">Form ID: #{{ $nhifForm->access_id }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-contract me-2 text-info"></i>1. Terms of the NHIF
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Contribution per month: 3% from employer and 3% from employee's basic salary.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>No refund if funds are unused due to termination, resignation, retrenchment, or retirement.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>NHIF health service terms are available at the HR office or NHIF website.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Effective from January 1, 2011, continuation depends on employer's contribution availability.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>CCBRT will provide 4 weeks' notice if unable to finance NHIF.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Employees must complete the NHIF application form with accurate information.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Ensure data accuracy with HR assistance.</li>
                                <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Processing takes 3-4 weeks, with cards issued via the HR office.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Employee Confirmation Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-check me-2 text-success"></i>2. Employee Voluntary Registration for NHIF
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-light border">
                                <p class="mb-3">
                                    I, <strong>{{ trim(($nhifForm->fname ?? '') . ' ' . ($nhifForm->mname ?? '') . ' ' . ($nhifForm->lname ?? '')) }}</strong>,
                                    voluntarily request CCBRT to register me and my eligible dependents for NHIF health insurance
                                    in accordance with the terms and conditions stated above.
                                </p>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Employee Name</label>
                                    <p class="mb-0 fw-semibold">
                                        {{ trim(($nhifForm->fname ?? '') . ' ' . ($nhifForm->mname ?? '') . ' ' . ($nhifForm->lname ?? '')) }}
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Employee Signature</label>
                                    <div>
                                        @if ($nhifForm->signature)
                                            <img src="data:image/png;base64,{{ $nhifForm->signature }}" alt="User Signature"
                                                class="img-thumbnail" style="max-width: 120px; height: auto; border: 1px solid #dee2e6;">
                                        @else
                                            <span class="text-muted">No Signature</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Date</label>
                                    <p class="mb-0 fw-semibold">{{ \Carbon\Carbon::parse($nhifForm->created_at ?? now())->format('d F Y') }}</p>
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
                                        {{ $HrToApprove ? trim(($HrToApprove->fname ?? '') . ' ' . ($HrToApprove->lname ?? '')) : 'Pending' }}
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">HR Signature</label>
                                    <div>
                                        @if ($HrToApprove && $HrToApprove->signature)
                                            <img src="data:image/png;base64,{{ $HrToApprove->signature }}" alt="HR Officer Signature"
                                                class="img-thumbnail" style="max-width: 120px; height: auto; border: 1px solid #dee2e6;">
                                        @else
                                            <span class="text-muted">No Signature</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Date</label>
                                    <p class="mb-0 fw-semibold">
                                        @if ($HrToApprove && $HrToApprove->updated_at)
                                            {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
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
                    @endif

                    <!-- Action Buttons -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('requestapprove.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </a>
                                @php
                                    $hasPendingAction = !$isApproved && \App\Models\WorkFlowHistory::where('work_flow_id', $workflow->id)
                                        ->where('attended_by', Auth::id())
                                        ->where('status', 0)
                                        ->exists();
                                @endphp
                                @if($hasPendingAction)
                                    <div>
                                        <button type="button" class="btn btn-success me-2"
                                            onclick="approveNhifForm('{{ $nhifForm->access_id }}')">
                                            <i class="fas fa-check-circle me-2"></i>Approve
                                        </button>
                                        <button type="button" class="btn btn-danger"
                                            onclick="rejectNhifForm('{{ $nhifForm->access_id }}')">
                                            <i class="fas fa-times-circle me-2"></i>Reject
                                        </button>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success fs-6 px-3 py-2">
                                            <i class="fas fa-check-circle me-1"></i>Already Approved
                                        </span>
                                        <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                                            <i class="fas fa-file-pdf me-1"></i> Download PDF
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    function approveNhifForm(access_id) {
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
                    url: '/nhif_form_confirm',
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

    function rejectNhifForm(access_id) {
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
                    url: '/nhif_form_reject',
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
