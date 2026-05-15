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

            @media print {
                .header, .footer, .fixed-bottom,
                .page-header, .page-sub-header,
                .btn, button, .action-buttons,
                #loader, .loader, .swal2-container,
                nav, .navbar, .no-print { display: none !important; }

                body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
                .content-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
                * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                @page { size: A4 portrait; margin: 10mm; }
                table { page-break-inside: auto; }
                tr { page-break-inside: avoid; }
                img { max-width: 100% !important; }
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
            <h5>HR Confirmation
                @if ($isApproved)
                    <span class="badge bg-success text-white ms-2" style="font-size: 0.8rem;">Approved</span>
                @else
                    <span class="badge bg-warning text-dark ms-2" style="font-size: 0.8rem;">Pending</span>
                @endif
            </h5>
            <table class="form-table">
                <tbody>
                    <tr>
                        <td>HR Officer Name:</td>
                        <td>{{ $HrToApprove ? trim(($HrToApprove->fname ?? '') . ' ' . ($HrToApprove->lname ?? '')) : '' }}</td>
                    </tr>
                    <tr>
                        <td>HR Signature:</td>
                        <td>
                            @if ($HrToApprove && $HrToApprove->signature)
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
                            @if ($HrToApprove && $HrToApprove->updated_at)
                                {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
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
            @if ($isApproved)
                <button type="button" class="btn btn-primary action-buttons me-2" onclick="generatePDF()">Download</button>
            @endif
            @if (!$isApproved && Auth::user()->hasRole('hr'))
                <button type="button" class="btn btn-success action-buttons me-2"
                    onclick="approveIdForm('{{ $idForm->access_id }}')">Confirm</button>
                <button type="button" class="btn btn-danger action-buttons"
                    onclick="rejectIdForm('{{ $idForm->access_id }}')">Reject</button>
            @endif
        </div>
        <br>
    </div>
    <br>

    <!-- Load html2pdf library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function generatePDF() {
            const element = document.querySelector('.content.container-fluid');
            const buttons = document.querySelector('.mt-3.d-flex');

            // Hide buttons during PDF generation
            if (buttons) buttons.style.display = 'none';

            window.scrollTo(0, 0);

            const options = {
                margin: [2, 1, 1, 2],
                filename: 'ID_Card_Form.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    scrollX: 0,
                    scrollY: 0
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait',
                    putOnlyUsedFonts: true,
                    compress: true
                }
            };

            html2pdf().from(element).set(options).save().then(() => {
                // Show buttons again after PDF generation
                if (buttons) buttons.style.display = 'flex';
            });
        }
    </script>
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
