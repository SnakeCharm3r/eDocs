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

        <div id="pdf-content">
            <!-- Header Section -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="text-center d-flex justify-content-between align-items-center border p-2">
                            <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Stamp" class="img-fluid"
                                style="max-width: 80px;">
                            <div>
                                <h3 class="fw-bold" style="color: #459c51;">HIGHER EDUCATION STUDENTS LOANS BOARD</h3>
                                <p class="mb-0 text-muted" style="font-style: italic;">Employee declaration</p>
                            </div>
                            <div class="fw-bold" style="font-size: 1.5rem; color: grey;">HR.32</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="section">
                <h4>1. HESLB Information</h4>
                <div class="mb-4">
                    <ul class="custom-bullets">
                        <li>CCBRT as an employer is legally required to ensure the recovery of outstanding HESLB loans from its
                            employees.</li>
                        <li>Employees of CCBRT must declare whether they have an outstanding HESLB loan.</li>
                        <li>CCBRT will confirm outstanding obligations with HESLB for its employees.</li>
                        <li>In accordance with the law, monthly deductions are made as per the percentage specified in the HESLB
                            Act through payroll, with CCBRT submitting the deductions to HESLB on behalf of the employee.</li>
                        <li>HESLB deductions take precedence over all other loan deductions applicable to an employee.</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h4>2. Declaration</h4>
                <p>I, <strong> {{ $heslbForm->fname }} {{ $heslbForm->mname }}
                        {{ $heslbForm->lname }}</strong>, hereby have read and understood the above and I hereby confirm the
                    following:</p>
                <p>I have obtained a loan from the HESLB loans board under Form IV Index No.
                    <strong> {{ $heslbForm->form_iv_index }}</strong><br>
                    which is still to be paid back and I
                    hereby instruct CCBRT to deduct from my monthly salary as per the guidelines from the HESLB. Deductions will
                    start in the next upcoming payroll.
                </p>
                <p>I have no outstanding a loan from the HESLB loans board.</p>
                <p>Name staff: <strong> {{ $heslbForm->fname }} {{ $heslbForm->mname }}
                        {{ $heslbForm->lname }}</strong>.</p>
                <p>Staff Signature: @if ($heslbForm->signature)
                        <img src="data:image/png;base64,{{ $heslbForm->signature }}" alt="User Signature"
                            style="max-width: 100px; height: auto; vertical-align: middle;">
                    @else
                        <span>No Signature</span>
                    @endif
                </p>
                <p>
                    Date: <span><strong>{{ \Carbon\Carbon::now()->format('d, F Y') }}</strong></span>
                </p>
            </div>

            <div class="section">
                <h5 class="mb-3">3. HR Confirmation of Receipt</h5>
                <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">
                    <tbody>
                        <tr>
                            <td class="w-33">
                                HR Officer Name:
                                {{ trim(($HrToApprove->fname ?? '') . ' ' . ($HrToApprove->lname ?? '')) }}
                            </td>
                            <td class="w-33">
                                HR Signature:
                                @if ($HrToApprove)
                                    <img src="data:image/png;base64,{{ $HrToApprove->signature }}" alt="HR Officer Signature"
                                        style="max-width: 10%; height: 5%;">
                                @endif
                            </td>
                            <td class="w-33">
                                Date:
                                @if ($heslbForm->status == 0 && $HrToApprove && $HrToApprove->updated_at)
                                    {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
                                @elseif ($heslbForm->status == 1 && $HrToApprove && $HrToApprove->updated_at)
                                    {{ \Carbon\Carbon::parse($HrToApprove->updated_at)->format('d F Y') }}
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <div class="d-flex justify-content-between mt-3">
                <button type="button" class="btn btn-primary" onclick="generatePDF()">Download</button>
            </div>
        </div>
        <br>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script>
            function generatePDF() {
                const element = document.getElementById('pdf-content');

                window.scrollTo(0, 0);

                const options = {
                    margin: [2, 1, 1, 2],
                    filename: 'HESLB_Declaration_Form.pdf',
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
