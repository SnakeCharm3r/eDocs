@extends('layouts.template2')
@section('breadcrumb')
@include('includes.loader')

    <br>
    <br>
    <br>
    <br>
    <div class="content container-fluid" style="background-color: #ffffff; max-width:1300px; margin: auto;">
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
        </style>

        {{-- <div class="section"> --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="text-center d-flex justify-content-between align-items-center border p-2">
                        <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Stamp" class="img-fluid"
                            style="max-width: 80px;">
                        <div>
                            {{-- <h3 class="fw-bold mb-0">NHIF (National Health Insurance Fund)</h3> --}}
                            <h3 class="fw-bold" style="color: #459c51;">CCBRT ID Card Request Form</h3>
                            <p class="mb-0 text-muted" style="font-style: italic;">REGISTRATION FORM</p>
                        </div>
                        <div class="fw-bold" style="font-size: 1.5rem; color: grey;">HR.32</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- </div> --}}

        <div class="section">
            <h4>1. Terms of the NHIF</h4>
                <ul class="custom-bullets">
                    <li><strong>1</strong>:All CCBRT Team members (employees/consultants) are required to identify themselves while on duty and for security reasons are required to wear their ID card (version 2018) visible at all times at CCBRT premises. <strong>Volunteers/Interns are required to wear name tags.</strong></li>
                    <li><strong>2</strong>:CCBRT provides a standard CCBRT ID card (version 2018) once to the team members free of charge.</li>
                    <li><strong>3</strong>:For employees, this is issued after completion of the probation period. Upon management approval, a card might be issued during the probation period. Non-staff members will be issued as per contract.</li>
                    <li><strong>4</strong>:CCBRT management has the right to add/withdraw features assigned to the respective card (access rights, credits, etc.) at any time.</li>
                    <li><strong>5</strong>:Old CCBRT ID cards are no longer valid and should be returned to the CCBRT HR office; these cards are no longer authorized by CCBRT as a valid identification method.</li>
                    <li><strong>6</strong>:Staff will be required to wear their name tag (if provided) visible as well.</li>
                    <li><strong>7</strong>:Loss or theft of the ID card needs to be reported within 12 hours to the CCBRT security department. Theft also needs to be reported to the police and a copy of the report to be provided to the CCBRT Security office.</li>
                    <li><strong>8</strong>:Replacement costs need to be paid by the team member.</li>
                    <li><strong>9</strong>:Any wear & tear of the card or malfunctioning of its features should be reported to security immediately. Replacement costs due to wear & tear are not for the cost of the team member. Replacement costs of the card due to negligence are for the team member.</li>
                    <li><strong>10</strong>:Negligence might also result in disciplinary action.</li>
                    <li><strong>11</strong>:The ID card remains the property of CCBRT and should be returned to CCBRT at the end of the employment/contract.</li>
                </ul>
        </div>

        <div class="section">
            <h4>2. CCBRT ID Card Request Form</h4>
            <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">
                <tbody>
                    <tr>
                        <td><label for="category"><strong>Full Employee Name:</strong></label>
                            <span>{{ Auth::user()->fname }} {{ Auth::user()->mname }} {{ Auth::user()->lname }}</span>
                        </td>

                        <td>
                            <!-- Department of Employee -->
                                <label for="category"><strong> Employee Department:</strong></label>
                                <span>{{ Auth::user()->department->dept_name }}</span>
                            </div>
                        </td>

                        <td>
                            <strong>Employee Department:</strong>
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

   <div class="mt-3 d-flex gap-2">
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
                        console.log('Error confirming:', error);
                    }
                });
            } else {
                console.log('Confirmation cancelled');
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
                return comment; // return the comment to proceed
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var comment = result.value;

                $.ajax({
                    url: '/nhif_form_reject',
                    method: 'POST',
                    data: {
                        access_id: access_id, // Send the access_id
                        status: 'rejected', // Indicate the status as rejected
                        comment: comment, // Send the rejection comment
                        _token: '{{ csrf_token() }}' // CSRF token for security
                    },
                    success: function(response) {
                        Swal.fire('Rejected!', response.message, 'success')
                            .then(() => {
                                window.location.href =
                                    '/requestapprove'; // Optionally redirect after success
                            });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire('Error!', 'There was an error rejecting the submission.',
                            'error');
                    }
                });
            } else {
                console.log('Rejection cancelled');
            }
        });
    }
</script>
