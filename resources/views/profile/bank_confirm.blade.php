@extends('layouts.template2')
@include('includes.loader')

@section('breadcrumb')
    <br><br><br>
    <div class="content container-fluid" style="background-color: #ffffff; max-width:1000px; margin: auto;">
        <style>
            /* Responsive Styling */
            @media (max-width: 768px) {
                .fw-bold {
                    font-size: 1.5rem !important;
                }

                /* Stack columns vertically on small screens */
                .page-header .row {
                    flex-direction: column;
                    align-items: center;
                }

                .page-header .col {
                    text-align: center;
                    margin-bottom: 10px;
                }

                .page-header img {
                    max-width: 70px;
                }

                .table {
                    font-size: 0.9rem;
                    width: 100%;
                    margin: 0;
                }

                .table td,
                .table th {
                    padding: 6px !important;
                    font-size: 0.9rem !important;
                }

                .table-container {
                    padding: 0 10px;
                }

                /* Adjust header styles */
                .page-header .fw-bold {
                    font-size: 1.2rem !important;
                }

                .page-header h3 {
                    font-size: 1.5rem;
                    text-align: center;
                }

                .page-header p {
                    font-size: 1rem;
                    margin-top: 5px;
                }

                /* Action buttons */
                .mt-3.d-flex {
                    flex-direction: column;
                    align-items: center;
                }

                .mt-3.d-flex button {
                    width: 100%;
                    margin: 5px 0;
                }
            }

            /* Container and page-header styling */
            .page-header .container {
                max-width: 1000px;
                margin: auto;
                padding: 0;
            }

            /* Flexbox layout for header */
            .page-header .row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                flex-wrap: wrap;
            }

            /* Adjust image sizing */
            .page-header .col-auto img {
                max-width: 80px;
                height: auto;
            }

            /* Make sure the middle section (text) is centered */
            .page-header .col.text-center {
                text-align: center;
                flex: 1;
            }

            /* The right column (HR.32) should stay aligned */
            .page-header .col-auto {
                flex: 0 1 auto;
            }

            /* Text and font styling */
            .page-header .fw-bold {
                font-size: 1.5rem;
            }

            .page-header h3 {
                font-size: 1.8rem;
                color: #459c51;
            }

            .page-header p {
                font-style: italic;
                color: #6c757d;
                margin: 0;
            }

            /* Table styling */
            .table-container {
                width: 100%;
                max-width: 1000px;
                margin: auto;
            }

            .table {
                width: 100%;
                table-layout: fixed;
                border-collapse: collapse;
            }

            .table td,
            .table th {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }

            .table th {
                background-color: #f8f9fa;
                font-weight: bold;
            }

            /* Styling for action buttons */
            .action-buttons button {
                width: 48%;
            }
        </style>

        <!-- Page Header in Table Format -->
        <div class="page-header">
            <div class="container">
                <br>
                <table class="table" style="border: none; width: 95%; margin: 0 auto; padding: 0;">
                    <tr>
                        <!-- Left Image -->
                        <td style="width: 10%; padding: 0;">
                            <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Stamp" class="img-fluid"
                                style="max-width: 80px;">
                        </td>

                        <!-- Middle Text Block -->
                        <td class="text-center" style="width: 80%; padding: 0;">
                            <h3 class="fw-bold" style="color: #459c51;">Bank Information</h3>
                            <p class="mb-0 text-muted" style="font-style: italic;">REGISTRATION FORM</p>
                        </td>
                        <!-- Right HR.32 -->
                        <td class="fw-bold text-secondary" style="width: 10%; font-size: 1.5rem; padding: 0;">
                            HR.32
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container my-5">
            <!-- Bank Information Section -->
            <div class="border p-4 mb-4 table-container">
                <h4 class="section-title mb-3 text-secondary" style="font-size: 1.5rem;">1. Bank Information</h4>
                <table class="table">
                    <tr>
                        <td><strong>Bank Name:</strong> <span class="text-muted">{{ $bankForm->bank_name }}</span></td>
                        <td><strong>Branch Name:</strong> <span
                                class="text-muted">{{ $bankForm->branch_name ?? 'N/A' }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Bank Branch Address:</strong> <span
                                class="text-muted">{{ $bankForm->branch_address ?? 'N/A' }}</span></td>
                        <td><strong>Bank Account Number:</strong> <span
                                class="text-muted">{{ $bankForm->account_number ?? 'N/A' }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Account Name:</strong> <span
                                class="text-muted">{{ $bankForm->account_name ?? 'N/A' }}</span></td>
                        <td><strong>Swift Code:</strong> <span
                                class="text-muted">{{ $bankForm->swift_code ?? 'N/A' }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>First Name:</strong> <span class="text-muted">{{ $bankForm->fname ?? 'N/A' }}</span>
                        </td>
                        <td><strong>Last Name:</strong> <span class="text-muted">{{ $bankForm->lname ?? 'N/A' }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Mobile Number:</strong> <span
                                class="text-muted">{{ $bankForm->bank_mobile_number ?? 'N/A' }}</span></td>
                    </tr>
                </table>
            </div>

            <!-- Employee Confirmation Section -->
            <div class="border p-4 mb-4 table-container">
                <h4 class="section-title mb-3 text-secondary" style="font-size: 1.5rem;">2. Employee Confirmation</h4>
                <p>
                    I, <span class="fw-bold">{{ $bankForm->fname }} {{ $bankForm->mname }} {{ $bankForm->lname }}</span>,
                    with my own will, do hereby request CCBRT to pay my eligible payments to the bank account details as
                    provided above. I also agree that any delay due to bank processing, costs, or other issues will be my
                    responsibility.
                </p>
                <table class="table">
                    <tr>
                        <td><strong>Name:</strong> <span>{{ $bankForm->fname }} {{ $bankForm->mname }}
                                {{ $bankForm->lname }}</span></td>
                        <td>
                            <strong>Signature:</strong>
                            @if ($bankForm->signature)
                                <img src="data:image/png;base64,{{ $bankForm->signature }}" alt="User Signature"
                                    style="max-width: 100px; height: auto;">
                            @else
                                <span>No Signature</span>
                            @endif
                        </td>
                        <td><strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d, F Y') }}</td>
                    </tr>
                </table>
            </div>

            <!-- HR Confirmation Section -->
            <div class="border p-4 mb-4 table-container">
                <h4 class="section-title mb-3 text-secondary" style="font-size: 1.5rem;">3. HR Confirmation Receipt</h4>
                <table class="table">
                    <tr>
                        <td><strong>HR Officer Name:</strong>
                            {{ trim(($HrToApprove->fname ?? '') . ' ' . ($HrToApprove->lname ?? '')) }}</td>
                        <td>
                            <strong>HR Signature:</strong>
                            @if ($HrToApprove)
                                <img src="data:image/png;base64,{{ $HrToApprove->signature }}" alt="HR Officer Signature"
                                    style="max-width: 100px; height: auto;">
                            @else
                                <span>No Signature</span>
                            @endif
                        </td>
                        <td>
                            <strong>Date:</strong>
                            @if ($bankForm->status == 0 && $HrToApprove && $HrToApprove->updated_at)
                                {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
                            @elseif ($bankForm->status == 1 && $HrToApprove && $HrToApprove->updated_at)
                                {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Action Buttons Section -->
            <div class="mt-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" onclick="window.history.back()">← Back</button>
                <button type="button" class="btn btn-success"
                    onclick="approveBankForm('{{ $bankForm->access_id }}')">Confirm</button>
                <button type="button" class="btn btn-danger"
                    onclick="rejectBankForm('{{ $bankForm->access_id }}')">Reject</button>
            </div>
            <br>
        </div>

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
