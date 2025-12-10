@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Page Header -->
                            <div class="page-header"
                                style="padding: 5px; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                                <div class="row">
                                    {{-- {{dd($ictForm)}} --}}
                                    <div class="page-sub-header"
                                        style="display: flex; flex-direction: column; align-items: flex-start;">
                                        <!-- Previous Approval Section -->
                                        <p style="font-size: 14px; color: #6c757d;">
                                            <strong>Previous Approval:</strong>
                                            @php
                                                $forwardedBy = \App\Models\User::find($ictForm->forwarded_by);
                                            @endphp
                                            {{ $forwardedBy ? $forwardedBy->fname . ' ' . $forwardedBy->lname : 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- ICT Access Form Details Table -->
                            <div class="form-container"
                                style="margin: 10px auto; max-width: 95%; border: 0.5px solid #dee2e6; padding: 3px; border-radius: 3px; box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <td colspan="2"
                                                style="border: 1px solid #000; text-align: left; background-color: #e9ecef; padding: 6px; font-weight: bold;">
                                                ICT Access Form
                                            </td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td rowspan="3"
                                                style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                                <img src="{{ asset('assets/img/logo-small.png') }}" alt="Logo"
                                                    style="max-width: 100px;">
                                            </td>
                                            <td style="border: 1px solid #000; text-align: center; padding: 6px;">Document
                                                No: 01</td>

                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: center; padding: 6px;">Version:
                                                6.1</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: center; padding: 6px;">Relevant
                                                area: IT & all other departments</td>
                                        </tr>

                                        <!-- Personal Details Section -->
                                        <tr>
                                            <td colspan="2"
                                                style="border: 1px solid #000; text-align: left; background-color: #d4edda; padding: 6px; font-weight: bold;">
                                                Personal Details
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>First Name:</strong> {{ $ictForm->fname }}
                                            </td>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Middle Name:</strong> {{ $ictForm->mname }}

                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Last Name:</strong> {{ $ictForm->lname }}
                                            </td>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Email:</strong> {{ $ictForm->email }}
                                            </td>

                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Department:</strong> {{ $ictForm->dept_name }}
                                            </td>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Mobile Number:</strong> {{ $ictForm->mobile }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Starting Date:</strong> {{ $ictForm->start_date }}
                                            </td>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>End Date:</strong> {{ $ictForm->end_date }}
                                            </td>

                                        </tr>

                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>MCT Reg. No:</strong> {{ $ictForm->professional_reg_number }}
                                            </td>

                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Employment Type:</strong> {{ $ictForm->employment_type }}
                                            </td>
                                        </tr>
                                        <!-- IT Assets and Access Section -->
                                        <tr>
                                            <td colspan="2"
                                                style="border: 1px solid #000; text-align: left; background-color: #d4edda; padding: 6px; font-weight: bold;">
                                                IT Assets Requested "Hardware" and Software/Logical Access
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>User Category:</strong> {{ $ictForm->privi->prv_name }}
                                            </td>
                                            <td colspan="2"
                                                style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Hardware:</strong> {{ $ictForm->hardware_request }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Active Directory:</strong> {{ $ictForm->ad->prv_name }}
                                            </td>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>CCBRT Email:</strong> {{ $ictForm->ccbrtEmail->email ?? '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>OpenClinic HMS:</strong> {{ $ictForm->names }}
                                            </td>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Aruti HR MIS:</strong> {{ $ictForm->arutiPrivilege->prv_name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>SAP ERP:</strong> {{ $ictForm->sapPrivilege->prv_name }}
                                            </td>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Network Access (VPN):</strong>
                                                {{ $ictForm->vpnPrivilege->prv_name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Call Manager - PABX:</strong>
                                                {{ $ictForm->pbaxPrivilege->prv_name }}
                                            </td>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>NHIF Qualifications:</strong> {{ $ictForm->name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Network Directory Name:</strong> {{ $ictForm->network_folder }}
                                            </td>
                                            <td style="border: 1px solid #000; text-align: left; padding: 6px;">
                                                <strong>Network Directory Access:</strong>
                                                {{ $ictForm->folderAccess->name ?? '' }}
                                            </td>
                                        </tr>

                                    </tbody>
                                </table>

                                <!-- Disclaimer and Declaration Section -->
                                <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">

                                    <tbody>

                                        <tr>
                                            <th style="border: 1px solid #000; text-align: left; padding: 6px;">Details:
                                            </th>
                                            <th style="border: 1px solid #000; text-align: left; padding: 6px;">Signature:
                                            </th>
                                            <th style="border: 1px solid #000; text-align: left; padding: 6px;">Date:</th>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 8px;">
                                                <strong>Requester:</strong> {{ $ictForm->fname }} {{ $ictForm->lname }}
                                            </td>
                                            <td style="border: 1px solid #000; padding: 1px;">
                                                @if ($ictForm->signature)
                                                    <img src="data:image/png;base64,{{ $ictForm->signature }}"
                                                        alt="User Signature" style="max-width: 40%; height: 5%;">
                                                @endif
                                            </td>
                                            <td style="border: 1px solid #000; padding: 8px;">

                                                @if ($ictForm->created_at)
                                                    {{ \Carbon\Carbon::parse($ictForm->created_at)->format('d F Y') }}
                                                @endif

                                            </td>
                                        </tr>
                                        <tr>

                                            @if ($ictForm->status == 1 && isset($lineManager))
                                                <td style="border: 1px solid #000; padding: 6px;">
                                                    <strong>Line Manager Name:</strong>
                                                    {{ trim(($lineManager->fname ?? '') . ' ' . ($lineManager->lname ?? '')) }}
                                                </td>

                                                <td style="border: 1px solid #000; padding: 1px;">
                                                    @if ($lineManager->signature)
                                                        <img src="data:image/png;base64,{{ $lineManager->signature }}"
                                                            alt="Line Manager Signature"
                                                            style="max-width: 40%; height: 5%;">
                                                    @endif
                                                </td>

                                                <td style="border: 1px solid #000; padding: 6px;">
                                                    @if ($lineManager->updated_at)
                                                        {{ \Carbon\Carbon::parse($lineManager->updated_at)->format('d F Y') }}
                                                    @endif
                                                </td>
                                            @elseif ($ictForm->status == 1 && $approver)
                                                <td style="border: 1px solid #000; padding: 6px;">
                                                    <strong>Line Manager Name:</strong>
                                                    {{ trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) }}
                                                </td>

                                                <td style="border: 1px solid #000; padding: 1px;">
                                                    @if ($approver->signature)
                                                        <img src="data:image/png;base64,{{ $approver->signature }}"
                                                            alt="Approver Signature" style="max-width: 40%; height: 5%;">
                                                    @endif
                                                </td>

                                                <td style="border: 1px solid #000; padding: 6px;">
                                                    @if ($approver->updated_at)
                                                        {{ \Carbon\Carbon::parse($approver->updated_at)->format('d F Y') }}
                                                    @endif
                                                </td>
                                            @else
                                                <td colspan="3"
                                                    style="border: 1px solid #000; padding: 6px; text-align: center; color: red;">
                                                    No Approval Found
                                                </td>
                                            @endif
                                        </tr>

                                        <tr>
                                            <td style="border: 1px solid #000; padding: 6px;">
                                                <strong> HR Officer Name:</strong>
                                                {{ trim(($hrOfficer->fname ?? '') . ' ' . ($hrOfficer->lname ?? '')) }}
                                            </td>
                                            <td style="border: 1px solid #000; padding: 1px;">
                                                @if ($hrOfficer)
                                                    <img src="data:image/png;base64,{{ $hrOfficer->signature }}"
                                                        alt="HR Officer Signature" style="max-width: 40%; height: 5%;">
                                                @endif
                                            </td>
                                            <td style="border: 1px solid #000; padding: 6px;">
                                                @if ($ictForm->status == 0 && $hrOfficer && $hrOfficer->updated_at)
                                                    {{ \Carbon\Carbon::parse($hrOfficer->updated_at)->format('d F Y') }}
                                                @elseif ($ictForm->status == 1 && $hrOfficer && $hrOfficer->updated_at)
                                                    {{ \Carbon\Carbon::parse($hrOfficer->updated_at)->format('d F Y') }}
                                                @endif
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="border: 1px solid #000; padding: 6px;">
                                                <strong>IT Officer Name: </strong>
                                                {{ trim(($itOfficer->fname ?? '') . ' ' . ($itOfficer->lname ?? '')) }}
                                            </td>
                                            <td style="border: 1px solid hsl(0, 0%, 0%); padding: 1px;">
                                                @if ($itOfficer)
                                                    <img src="data:image/png;base64,{{ $itOfficer->signature }}"
                                                        alt="IT Officer Signature" style="max-width: 40%; height: 5%;">
                                                @endif
                                            </td>
                                            <td style="border: 1px solid #000; padding: 6px;">
                                                @if ($ictForm->status == 0 && $itOfficer && $itOfficer->updated_at)
                                                    {{ \Carbon\Carbon::parse($itOfficer->updated_at)->format('d F Y') }}
                                                @elseif ($ictForm->status == 1 && $itOfficer && $itOfficer->updated_at)
                                                    {{ \Carbon\Carbon::parse($itOfficer->updated_at)->format('d F Y') }}
                                                @endif
                                            </td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>


                            <!-- Action Buttons -->
                            <div class="buttons-container"
                                style="margin-top: 15px; display: flex; justify-content: flex-end; gap: 10px; padding-right: 3%;">
                                {{-- <button type="button" class="btn btn-success"
                                    onclick="approveForm('{{ $ictForm->access_id }}')">Approve</button>
                                <button type="button" class="btn btn-danger"
                                    onclick="rejectForm('{{ $ictForm->access_id }}')">Reject</button> --}}
                                <button type="button" class="btn btn-primary" onclick="generatePDF()">Download</button>
                            </div>

                        </div>
                    </div>

                    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
                    <script src="https://raw.githack.com/eKoopmans/html2pdf/master/dist/html2pdf.bundle.js"></script>
                    <script>
                        function generatePDF() {
                            const element = document.querySelector('.form-container');

                            // Ensure the element is at the top of the page
                            window.scrollTo(0, 0);

                            // Options for html2pdf
                            const options = {
                                margin: [2, 1, 1, 2], // Adjust margins to fit content better
                                filename: 'ICT_Access_Form.pdf',
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
            </div>
        </div>
    </div>
@endsection
