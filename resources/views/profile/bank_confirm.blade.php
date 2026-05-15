@extends('layouts.template')
@include('includes.loader')

@section('breadcrumb')
    <style>
        .bank-shell {
            background: #dde1e7;
            min-height: calc(100vh - 60px);
            padding: 1.5rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .bank-card-wrap {
            width: 100%;
            max-width: 820px;
            flex-shrink: 0;
        }
        @media print {
            .no-print, .page-header, .page-sub-header { display: none !important; }
            .bank-shell { background: #fff !important; padding: 0 !important; display: block !important; }
            .bank-card-wrap { max-width: 100% !important; }
            body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
            .main-wrapper, .content-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            @page { size: A4 portrait; margin: 10mm; }
            .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; page-break-inside: avoid; }
            img { max-width: 100% !important; }
        }
    </style>
    <div class="page-header">
        <div class="row">
            <div class="col-sm-12">
                <div class="page-sub-header d-flex justify-content-between align-items-center">
                    <h3 class="page-title d-flex align-items-center mb-0">
                        <i class="fas fa-university me-2" style="color: #007A33;"></i>Bank Details Form Review
                    </h3>
                    <a href="{{ route('requestapprove.index') }}" class="btn btn-sm btn-outline-secondary no-print">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="bank-shell">
        <div class="bank-card-wrap">
                    <!-- Header Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo" class="img-fluid me-3"
                                        style="max-width: 80px;">
                                    <div>
                                        <h4 class="mb-0" style="color: #007A33;">Bank Details Form</h4>
                                        <small class="text-muted">Registration Form - HR.32</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light text-dark">Form ID: #{{ $bankForm->access_id }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Information Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2 text-primary"></i>1. Bank Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="text-muted small mb-1">Bank Name</label>
                                        <p class="mb-0 fw-semibold">{{ $bankForm->bank_name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="text-muted small mb-1">Branch Name</label>
                                        <p class="mb-0 fw-semibold">{{ $bankForm->branch_name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="text-muted small mb-1">Branch Address</label>
                                        <p class="mb-0 fw-semibold">{{ $bankForm->branch_address ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="text-muted small mb-1">Account Number</label>
                                        <p class="mb-0 fw-semibold">{{ $bankForm->account_number ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="text-muted small mb-1">Account Name</label>
                                        <p class="mb-0 fw-semibold">{{ $bankForm->account_name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="text-muted small mb-1">SWIFT Code</label>
                                        <p class="mb-0 fw-semibold">{{ $bankForm->swift_code ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="text-muted small mb-1">Mobile Number</label>
                                        <p class="mb-0 fw-semibold">
                                            <i
                                                class="fas fa-phone me-1 text-muted"></i>{{ $bankForm->bank_mobile_number ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="text-muted small mb-1">Employee Name</label>
                                        <p class="mb-0 fw-semibold">
                                            {{ trim(($bankForm->fname ?? '') . ' ' . ($bankForm->mname ?? '') . ' ' . ($bankForm->lname ?? '')) ?: 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Confirmation Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-check me-2 text-success"></i>2. Employee Confirmation
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-light border">
                                <p class="mb-3">
                                    I,
                                    <strong>{{ trim(($bankForm->fname ?? '') . ' ' . ($bankForm->mname ?? '') . ' ' . ($bankForm->lname ?? '')) }}</strong>,
                                    with my own will, do hereby request CCBRT to pay my eligible payments to the bank
                                    account details as
                                    provided above. I also agree that any delay due to bank processing, costs, or other
                                    issues will be my
                                    responsibility.
                                </p>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Name</label>
                                    <p class="mb-0 fw-semibold">
                                        {{ trim(($bankForm->fname ?? '') . ' ' . ($bankForm->mname ?? '') . ' ' . ($bankForm->lname ?? '')) }}
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Signature</label>
                                    <div>
                                        @if ($bankForm->signature)
                                            <img src="data:image/png;base64,{{ $bankForm->signature }}"
                                                alt="User Signature" class="img-thumbnail"
                                                style="max-width: 120px; height: auto; border: 1px solid #dee2e6;">
                                        @else
                                            <span class="text-muted">No Signature</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">Date</label>
                                    <p class="mb-0 fw-semibold">
                                        {{ \Carbon\Carbon::parse($bankForm->created_at ?? now())->format('d F Y') }}</p>
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
                                            <img src="data:image/png;base64,{{ $HrToApprove->signature }}"
                                                alt="HR Officer Signature" class="img-thumbnail"
                                                style="max-width: 120px; height: auto; border: 1px solid #dee2e6;">
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
                    @if (isset($isApproved) && $isApproved)
                        <div class="alert alert-success mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2 fs-4"></i>
                                <div>
                                    <strong>Approved</strong>
                                    @if ($HrToApprove)
                                        <p class="mb-0 small">Approved by
                                            {{ trim(($HrToApprove->fname ?? '') . ' ' . ($HrToApprove->lname ?? '')) }} on
                                            {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}</p>
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
                                @if ($hasPendingAction)
                                    <div>
                                        <button type="button" class="btn btn-success me-2"
                                            onclick="approveBankForm('{{ $bankForm->access_id }}')">
                                            <i class="fas fa-check-circle me-2"></i>Approve
                                        </button>
                                        <button type="button" class="btn btn-danger"
                                            onclick="rejectBankForm('{{ $bankForm->access_id }}')">
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
        </div>{{-- end bank-card-wrap --}}
    </div>{{-- end bank-shell --}}

    <!-- Script for Actions -->
    <script>
        function approveBankForm(access_id) {
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
                        url: '/bank_form_confirm', // Ensure the endpoint is correct
                        method: 'POST',
                        data: {
                            access_id: access_id
                        },
                        success: function(response) {
                            window.location.href = '/requestapprove';
                        },
                        error: function(xhr, status, error) {
                            console.log('Error confirming:', error);
                        }
                    });
                }
            });
        }

        function rejectBankForm(access_id) {
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
                        url: '/bank_form_reject',
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
