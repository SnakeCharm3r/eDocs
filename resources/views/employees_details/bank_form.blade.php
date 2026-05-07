@extends('layouts.template2')
@section('breadcrumb')
    <br><br><br>
    <div class="content container-fluid" style="background-color: #ffffff; max-width:1300px; margin: auto;">
        <style>
            @media (max-width: 768px) {
                .fw-bold {
                    font-size: 1.5rem !important;
                }
            }
        </style>

        <!-- Wrap the entire content in a single container -->
        <div id="pdf-content">
            <!-- Header Section -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="text-center d-flex justify-content-between align-items-center border p-3">
                            <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Stamp" class="img-fluid"
                                style="max-width: 80px;">
                            <div>
                                <h3 class="fw-bold" style="color: #459c51;">Bank Details Form</h3>
                                <p class="mb-0 text-muted" style="font-style: italic;">REGISTRATION FORM</p>
                            </div>
                            <div class="fw-bold text-secondary" style="font-size: 1.5rem;">HR.32</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="container my-5">
                <!-- Bank Information Section -->
                <div class="border p-4 mb-4">
                    <h4 class="section-title mb-3 text-secondary" style="font-size: 1.5rem;">1. Bank Information</h4>
                    <table class="table table-bordered">
                        <tr>
                            <td class="w-50"><strong>Bank Name:</strong> <span
                                    class="text-muted">{{ $bankForm->bank_name }}</span></td>
                            <td class="w-50"><strong>Branch Name:</strong> <span
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
                                    class="text-muted">{{ $bankForm->mobile_number ?? 'N/A' }}</span></td>
                            <td><strong>Home Address:</strong> <span
                                    class="text-muted">{{ $bankForm->home_address ?? 'N/A' }}</span></td>
                        </tr>
                    </table>
                </div>

                <!-- Employee Confirmation Section -->
                <div class="border p-4 mb-4">
                    <h4 class="section-title mb-3 text-secondary" style="font-size: 1.5rem;">2. Employee Confirmation</h4>
                    <p>
                        I, <span class="fw-bold">{{ $bankForm->fname }} {{ $bankForm->mname }} {{ $bankForm->lname }}</span>,
                        with my own will, do hereby request CCBRT to pay my eligible payments to the bank account details as
                        provided above.
                        I also agree that any delay due to bank processing, costs, or other issues will be my responsibility.
                    </p>
                    <table class="table table-bordered">
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
                <div class="border p-4 mb-4">
                    <h4 class="section-title mb-3 text-secondary" style="font-size: 1.5rem;">3. HR Confirmation Receipt</h4>
                    <table class="table table-bordered">
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

                <!-- Action Buttons -->
                <div class="mt-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-primary" onclick="generatePDF()">Download</button>
                </div>
                <br>
            </div>
        </div>

        <!-- Load html2pdf library -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script>
            function generatePDF() {
                const element = document.getElementById('pdf-content'); // Target the container with the header

                window.scrollTo(0, 0);

                const options = {
                    margin: [2, 1, 1, 2],
                    filename: 'Bank_details_form.pdf',
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
