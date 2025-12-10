@extends('layouts.template2')
@include('includes.loader')

@section('breadcrumb')
    <br><br><br><br>
    <div class="content container-fluid" style="background-color: #ffffff; max-width:1000px; margin: auto;">
        <br>
        <style>
            @media (max-width: 768px) {
                .fw-bold {
                    font-size: 1.5rem !important;
                }
            }

            body {
                font-family: Arial, sans-serif;
            }

            .container {
                width: 90%;
                max-width: 800px;
                margin: auto;
            }

            .section {
                border: 1px solid #afa6a6;
                padding: 15px;
                margin-bottom: 20px;
            }

            .section h2 {
                margin-top: 0;
            }

            .input-field {
                width: 100%;
                padding: 8px;
                margin-top: 5px;
            }

            .button {
                background-color: #4CAF50;
                color: white;
                padding: 10px 20px;
                border: none;
                cursor: pointer;
            }

            .custom-bullets {
                list-style-type: disc;
                margin-left: 20px;
            }

            .section h4 {
                font-size: 1.25rem;
                color: #333;
                margin-bottom: 10px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            table td {
                padding: 10px;
                vertical-align: top;
                border: 1px solid #ddd;
            }

            table td strong {
                color: #333;
            }

            .btn-container {
                margin-top: 20px;
                display: flex;
                justify-content: space-between;
                gap: 10px;
            }

            .btn {
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-weight: bold;
            }

            .btn-success {
                background-color: #28a745;
                color: white;
            }

            .btn-danger {
                background-color: #dc3545;
                color: white;
            }
        </style>

        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="text-center d-flex justify-content-between align-items-center border p-2">
                        <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Stamp" class="img-fluid"
                            style="max-width: 80px;">
                        <div>
                            <h3 class="fw-bold" style="color: #459c51;">NHIF (National Health Insurance Fund)</h3>
                            <p class="mb-0 text-muted" style="font-style: italic;">REGISTRATION FORM</p>
                        </div>
                        <div class="fw-bold" style="font-size: 1.5rem; color: grey;">HR.32</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <h4>1. Terms of the NHIF</h4>
            <ul class="custom-bullets">
                <li>Contribution per month: 3% from employer and 3% from employee's basic salary.</li>
                <li>No refund if funds are unused due to termination, resignation, retrenchment, or retirement.</li>
                <li>NHIF health service terms are available at the HR office or NHIF website.</li>
                <li>Effective from January 1, 2011, continuation depends on employer's contribution availability.</li>
                <li>CCBRT will provide 4 weeks' notice if unable to finance NHIF.</li>
                <li>Employees must complete the NHIF application form with accurate information.</li>
                <li>Ensure data accuracy with HR assistance.</li>
                <li>Processing takes 3-4 weeks, with cards issued via the HR office.</li>
            </ul>
        </div>

        <div class="section">
            <h4>2. Employee voluntary registration for NHIF</h4>
            <table>
                <tbody>
                    <tr>
                        <td><strong>Employee Name:</strong> {{ $nhifForm->fname }} {{ $nhifForm->mname }}
                            {{ $nhifForm->lname }}</td>
                        <td>
                            <strong>Employee Signature:</strong>
                            @if ($nhifForm->signature)
                                <img src="data:image/png;base64,{{ $nhifForm->signature }}" alt="User Signature"
                                    style="max-width: 100px; height: auto; vertical-align: middle;">
                            @else
                                <span>No Signature</span>
                            @endif
                        </td>
                        <td><strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d, F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>HR Officer Name:</strong>
                            {{ trim(($HrToApprove->fname ?? '') . ' ' . ($HrToApprove->lname ?? '')) }}</td>
                        <td>
                            <strong>HR Signature:</strong>
                            @if ($HrToApprove)
                                <img src="data:image/png;base64,{{ $HrToApprove->signature }}" alt="HR Officer Signature"
                                    style="max-width: 10%; height: 5%;">
                            @endif
                        </td>
                        <td>
                            <strong>Date:</strong>
                            @if ($nhifForm->status == 0 && $HrToApprove && $HrToApprove->updated_at)
                                {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
                            @elseif ($nhifForm->status == 1 && $HrToApprove && $HrToApprove->updated_at)
                                {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="btn-container">
            <button type="button" class="btn btn-secondary" onclick="window.history.back()">← Back</button>

            <button type="button" class="btn btn-success"
                onclick="approveNhifForm('{{ $nhifForm->access_id }}')">Confirm</button>
            <button type="button" class="btn btn-danger"
                onclick="rejectNhifForm('{{ $nhifForm->access_id }}')">Reject</button>
        </div>
        <br>
    </div>
    <br>
@endsection

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
