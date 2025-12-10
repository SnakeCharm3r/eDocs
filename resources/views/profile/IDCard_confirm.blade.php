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
                border: 1px solid #ddd;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 8px;
            }

            .section h2,
            .section h4,
            .section h5 {
                margin-top: 0;
                font-size: 1.5rem;
                color: #333;
            }

            .input-field {
                width: 100%;
                padding: 10px;
                margin-top: 5px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }

            .input-field:focus {
                border-color: #4CAF50;
                outline: none;
            }

            .button {
                background-color: #4CAF50;
                color: white;
                padding: 10px 20px;
                border: none;
                cursor: pointer;
                border-radius: 5px;
                font-size: 1rem;
            }

            .button:hover {
                background-color: #45a049;
            }

            .custom-bullets {
                list-style-type: disc;
                margin-left: 20px;
            }

            .form-table td {
                padding: 10px;
            }

            .form-table {
                width: 100%;
                margin-top: 15px;
                border-collapse: collapse;
            }

            .form-table td {
                border-bottom: 1px solid #ccc;
            }

            .form-table th {
                text-align: left;
                font-weight: bold;
                padding-bottom: 10px;
                padding-top: 10px;
            }

            .form-table td p {
                margin: 0;
            }

            /* Button Styling */
            .action-buttons button {
                width: 48%;
                padding: 10px 15px;
                border-radius: 5px;
            }

            .mt-3 {
                margin-top: 20px;
            }
        </style>

        <!-- Page Header -->
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="text-center d-flex justify-content-between align-items-center border p-2">
                        <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Stamp" class="img-fluid"
                            style="max-width: 80px;">
                        <div>
                            <h3 class="fw-bold" style="color: #459c51;">ID Card Form</h3>
                        </div>
                        <div class="fw-bold" style="font-size: 1.5rem; color: grey;">HR.32</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ID Card Information Section -->
        <div class="section">
            <h4>ID Card Information</h4>
            <div class="mb-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 10%;">No</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>All CCBRT Team members (employees/consultants) are required to identify themselves while on
                                duty
                                and for security reasons are required to wear their ID card (version 2018) visible at all
                                times at
                                CCBRT premises. <strong>Volunteers/Interns are required to wear name tags.</strong></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>CCBRT provides a standard CCBRT ID card (version 2018) once to the team members free of
                                charge.</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>For employees, this is issued after completion of the probation period. Upon management
                                approval,
                                a card might be issued during the probation period. Non-staff members will be issued as per
                                contract.</td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>CCBRT management has the right to add/withdraw features assigned to the respective card
                                (access
                                rights, credits, etc.) at any time.</td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>Old CCBRT ID cards are no longer valid and should be returned to the CCBRT HR office; these
                                cards
                                are no longer authorized by CCBRT as a valid identification method.</td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>Staff will be required to wear their name tag (if provided) visible as well.</td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td>Loss or theft of the ID card needs to be reported within 12 hours to the CCBRT security
                                department. Theft also needs to be reported to the police and a copy of the report to be
                                provided to
                                the CCBRT Security office.</td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td>Replacement costs need to be paid by the team member.</td>
                        </tr>
                        <tr>
                            <td>9</td>
                            <td>Any wear & tear of the card or malfunctioning of its features should be reported to security
                                immediately. Replacement costs due to wear & tear are not for the cost of the team member.
                                Replacement costs of the card due to negligence are for the team member.</td>
                        </tr>
                        <tr>
                            <td>10</td>
                            <td>Negligence might also result in disciplinary action.</td>
                        </tr>
                        <tr>
                            <td>11</td>
                            <td>The ID card remains the property of CCBRT and should be returned to CCBRT at the end of the
                                employment/contract.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Declaration Section -->
        <div class="section">
            <h4>Declaration</h4>
            <table class="form-table">
                <tbody>
                    <tr>
                        <td>Name of staff:</td>
                        <td><strong>{{ $idForm->fname }} {{ $idForm->mname }} {{ $idForm->lname }}</strong></td>
                    </tr>
                    <tr>
                        <td>Staff Signature:</td>
                        <td>
                            @if ($idForm->signature)
                                <img src="data:image/png;base64,{{ $idForm->signature }}" alt="User Signature"
                                    style="max-width: 100px; height: auto;">
                            @else
                                <span>No Signature</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Date:</td>
                        <td><strong>{{ \Carbon\Carbon::parse($idForm->created_at)->format('d, F Y') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- HR Confirmation Section -->
        <div class="section">
            <h5>HR Confirmation</h5>
            <table class="form-table">
                <tbody>
                    <tr>
                        <td>HR Officer Name:</td>
                        <td>{{ trim(($HrToApprove->fname ?? '') . ' ' . ($HrToApprove->lname ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td>HR Signature:</td>
                        <td>
                            @if ($HrToApprove)
                                <img src="data:image/png;base64,{{ $HrToApprove->signature }}" alt="HR Officer Signature"
                                    style="max-width: 100px; height: auto;">
                            @else
                                <span>No Signature</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Date:</td>
                        <td>
                            @if ($idForm->status == 0 && $HrToApprove && $HrToApprove->updated_at)
                                {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
                            @elseif ($idForm->status == 1 && $HrToApprove && $HrToApprove->updated_at)
                                {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="section">
            <h5>IT Confirmation </h5>
            <table class="form-table">
                <tbody>
                    <tr>
                        <td>IT Officer Name:</td>
                        <td>{{ trim(($ItToApprove->fname ?? '') . ' ' . ($ItToApprove->lname ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td>IT Signature:</td>
                        <td>
                            @if ($ItToApprove)
                                <img src="data:image/png;base64,{{ $ItToApprove->signature }}" alt="HR Officer Signature"
                                    style="max-width: 100px; height: auto;">
                            @else
                                <span>No Signature</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Date:</td>
                        <td>
                            @if ($idForm->status == 0 && $ItToApprove && $ItToApprove->updated_at)
                                {{ \Carbon\Carbon::parse($ItToApprove->updated_at)->format('d F Y') }}
                            @elseif ($idForm->status == 1 && $ItToApprove && $ItToApprove->updated_at)
                                {{ \Carbon\Carbon::parse($ItToApprove->updated_at)->format('d F Y') }}
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Action Buttons -->
        <div class="mt-3 d-flex justify-content-start">
            <button type="button" class="btn btn-secondary action-buttons me-2"
                onclick="window.history.back()">Back</button>
            <button type="button" class="btn btn-success action-buttons me-2"
                onclick="approveIdForm('{{ $idForm->access_id }}')">Confirm</button>
            <button type="button" class="btn btn-danger action-buttons"
                onclick="rejectIdForm('{{ $idForm->access_id }}')">Reject</button>
        </div>
        <br>
    </div>
    <br>
@endsection

<script>
    function approveIdForm(access_id) {
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
                    url: '/id_form_confirm',
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

    function rejectIdForm(access_id) {
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
                    url: '/id_form_reject',
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
@include('includes.loader')
