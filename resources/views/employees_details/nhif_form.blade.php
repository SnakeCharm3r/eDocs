@extends('layouts.template2')
@section('breadcrumb')
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

        <!-- Wrap the entire content in a single container -->
        <div id="pdf-content">
            <!-- Header Section -->
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

            <!-- Form Content -->
            <div class="section">
                <h4>1. Terms of the NHIF</h4>
                <ol>
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
                </ol>
            </div>

            <div class="section">
                <h4>2. Employee voluntary registration for NHIF</h4>
                <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">
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
        </div>

        <!-- Action Buttons -->
        <div class="mt-3 d-flex justify-content-between">
            <button type="button" class="btn btn-primary" onclick="generatePDF()">Download</button>
        </div>
        <br>

        <!-- Load html2pdf library -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script>
            function generatePDF() {
                const element = document.getElementById('pdf-content'); // Target the container with the header

                // Ensure the element is at the top of the page
                window.scrollTo(0, 0);

                // Options for html2pdf
                const options = {
                    margin: [2, 1, 1, 2], // Adjust margins to fit content better
                    filename: 'NHIF_Registration_Form.pdf',
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

                html2pdf().from(element).set(options).save();
            }
        </script>
    </div>
@endsection
