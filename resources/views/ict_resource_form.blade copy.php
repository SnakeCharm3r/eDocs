@extends('layouts.template')
<br>
<br>
<br>
@section('breadcrumb')
@include('sweetalert::alert')
<div class="page-wrapper py-6 bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto">
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="p-4">
                    <!-- Page Header -->
                    {{-- <div class="border-b border-gray-200 pb-3 mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 text-center">ICT Access Form</h2>
                            <p class="text-xs text-gray-600 mt-1">
                                <strong>Previous Approval:</strong>
                                @php
                                    $forwardedBy = \App\Models\User::find($ictForm->forwarded_by);
                                @endphp
                                {{ $forwardedBy ? $forwardedBy->fname . ' ' . $forwardedBy->lname : 'N/A' }}
                    </p>
                </div> --}}

                <!-- ICT Access Form Details Table -->
                <div class="form-container bg-white border border-gray-300 rounded-sm p-3">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr>
                                <th colspan="2"
                                    class="bg-gray-200 text-left p-2 font-semibold text-gray-700 border border-gray-300 text-base">
                                    <!-- Changed to text-base --> ICT Access Form
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-300 p-2 w-1/2">
                                    <img src="{{ asset('assets/img/logo-small.png') }}" alt="Company Logo"
                                        class="max-w-[60px] mx-auto">
                                </td>
                                <td class="border border-gray-300 p-2 w-1/2">
                                    <strong>Document No:</strong> 01<br>
                                    <strong>Version:</strong> 6.1<br>
                                    <strong>Relevant Area:</strong> IT & All Other Departments
                                </td>
                            </tr>

                            <!-- Personal Details Section -->
                            <tr>
                                <td colspan="2"
                                    class="bg-gray-100 text-left p-2 font-semibold text-gray-700 border border-gray-300">
                                    Personal Details
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>First Name:</strong> {{ $ictForm->fname }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>Middle Name:</strong> {{ $ictForm->mname ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>Last Name:</strong> {{ $ictForm->lname }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>Email:</strong> {{ $ictForm->email }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>Department:</strong> {{ $ictForm->dept_name }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>Mobile Number:</strong> {{ $ictForm->mobile }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>Starting Date:</strong> {{ $ictForm->start_date }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>End Date:</strong> {{ $ictForm->end_date ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>MCT Reg. No:</strong> {{ $ictForm->professional_reg_number ?? 'N/A' }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>Employment Type:</strong> {{ $ictForm->employment_type }}
                                </td>
                            </tr>

                            <!-- IT Assets and Access Section -->
                            <tr>
                                <td colspan="2"
                                    class="bg-gray-100 text-left p-2 font-semibold text-gray-700 border border-gray-300">
                                    IT Assets Requested & Software/Logical Access
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>User Category:</strong> {{ $ictForm->privi->prv_name ?? 'N/A' }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>Hardware:</strong> {{ $ictForm->hardware_request ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>Active Directory:</strong> {{ $ictForm->ad->prv_name ?? 'N/A' }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>CCBRT Email:</strong> {{ $ictForm->privi->prv_name ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>HealthAI HMIS:</strong> {{ $ictForm->names ?? 'N/A' }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>Aruti HR MIS:</strong>
                                    {{ $ictForm->arutiPrivilege->prv_name ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>SAP ERP:</strong> {{ $ictForm->ASPLevel->access_name ?? 'N/A' }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>Network Access (VPN):</strong>
                                    {{ $ictForm->vpnPrivilege->prv_name ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>Call Manager - PABX:</strong>
                                    {{ $ictForm->pbaxPrivilege->prv_name ?? 'N/A' }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>NHIF Qualifications:</strong> {{ $ictForm->name ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">
                                    <strong>Network Directory Name:</strong>
                                    {{ $ictForm->network_folder ?? 'N/A' }}
                                </td>
                                <td class="border border-gray-300 p-2">
                                    <strong>Network Directory Access:</strong>
                                    {{ $ictForm->folderAccess->prv_name ?? 'N/A' }}
                                    {{-- {{ $ictForm->folder_privilege->privilege_levels->prv_name ?? 'N/A' }} --}}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Declaration and Signature Section -->
                    <div class="mt-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Declaration and Signatures</h3>
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr>
                                    <th
                                        class="p-2 text-left font-semibold text-gray-700 border border-gray-300 bg-gray-200">
                                        Details</th>
                                    <th
                                        class="p-2 text-left font-semibold text-gray-700 border border-gray-300 bg-gray-200">
                                        Signature</th>
                                    <th
                                        class="p-2 text-left font-semibold text-gray-700 border border-gray-300 bg-gray-200">
                                        Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="p-2 border border-gray-300 align-middle">
                                        <strong>Requester:</strong> {{ $ictForm->fname }} {{ $ictForm->lname }}
                                    </td>
                                    <td class="p-2 border border-gray-300 align-middle bg-gray-50">
                                        <div class="flex justify-center items-center h-12">
                                            @if ($ictForm->signature)
                                            <img src="data:image/png;base64,{{ $ictForm->signature }}"
                                                alt="Requester Signature"
                                                class="max-w-[100px] max-h-10 object-contain">
                                            @else
                                            <span
                                                class="border-b border-dashed border-gray-500 w-full text-center">&nbsp;</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-2 border border-gray-300 align-middle">
                                        {{ $ictForm->created_at ? \Carbon\Carbon::parse($ictForm->created_at)->format('d F Y') : 'N/A' }}
                                    </td>
                                </tr>

                                @if ($lineManager)
                                <tr>
                                    <td class="p-2 border border-gray-300 align-middle">
                                        <strong>Line Manager:</strong>
                                        {{ trim(($lineManager->fname ?? '') . ' ' . ($lineManager->lname ?? '')) }}
                                    </td>
                                    <td class="p-2 border border-gray-300 align-middle bg-gray-50">
                                        <div class="flex justify-center items-center h-12">
                                            @if ($lineManager->signature)
                                            <img src="data:image/png;base64,{{ $lineManager->signature }}"
                                                alt="Line Manager Signature"
                                                class="max-w-[100px] max-h-10 object-contain">
                                            @else
                                            <span
                                                class="border-b border-dashed border-gray-500 w-full text-center">&nbsp;</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-2 border border-gray-300 align-middle">
                                        {{ ($ictForm->status == 0 || $ictForm->status == 1) && $lineManager->updated_at ? \Carbon\Carbon::parse($lineManager->updated_at)->format('d F Y') : 'N/A' }}
                                    </td>
                                </tr>
                                @endif

                                @if ($approver)
                                <tr>
                                    <td class="p-2 border border-gray-300 align-middle">
                                        <strong>Approver:</strong>
                                        {{ trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) }}
                                    </td>
                                    <td class="p-2 border border-gray-300 align-middle bg-gray-50">
                                        <div class="flex justify-center items-center h-12">
                                            @if ($approver->signature)
                                            <img src="data:image/png;base64,{{ $approver->signature }}"
                                                alt="Approver Signature"
                                                class="max-w-[100px] max-h-10 object-contain">
                                            @else
                                            <span
                                                class="border-b border-dashed border-gray-500 w-full text-center">&nbsp;</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-2 border border-gray-300 align-middle">
                                        {{ ($ictForm->status == 0 || $ictForm->status == 1) && $approver->updated_at ? \Carbon\Carbon::parse($approver->updated_at)->format('d F Y') : 'N/A' }}
                                    </td>
                                </tr>
                                @endif

                                @if ($hrOfficer)
                                <tr>
                                    <td class="p-2 border border-gray-300 align-middle">
                                        <strong>HR Officer:</strong>
                                        {{ trim(($hrOfficer->fname ?? '') . ' ' . ($hrOfficer->lname ?? '')) }}
                                    </td>
                                    <td class="p-2 border border-gray-300 align-middle bg-gray-50">
                                        <div class="flex justify-center items-center h-12">
                                            @if ($hrOfficer->signature)
                                            <img src="data:image/png;base64,{{ $hrOfficer->signature }}"
                                                alt="HR Officer Signature"
                                                class="max-w-[100px] max-h-10 object-contain">
                                            @else
                                            <span
                                                class="border-b border-dashed border-gray-500 w-full text-center">&nbsp;</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-2 border border-gray-300 align-middle">
                                        {{ ($ictForm->status == 0 || $ictForm->status == 1) && $hrOfficer->updated_at ? \Carbon\Carbon::parse($hrOfficer->updated_at)->format('d F Y') : 'N/A' }}
                                    </td>
                                </tr>
                                @endif

                                @if ($itOfficer)
                                <tr>
                                    <td class="p-2 border border-gray-300 align-middle">
                                        <strong>IT Officer:</strong>
                                        {{ trim(($itOfficer->fname ?? '') . ' ' . ($itOfficer->lname ?? '')) }}
                                    </td>
                                    <td class="p-2 border border-gray-300 align-middle bg-gray-50">
                                        <div class="flex justify-center items-center h-12">
                                            @if ($itOfficer->signature)
                                            <img src="data:image/png;base64,{{ $itOfficer->signature }}"
                                                alt="IT Officer Signature"
                                                class="max-w-[100px] max-h-10 object-contain">
                                            @else
                                            <span
                                                class="border-b border-dashed border-gray-500 w-full text-center">&nbsp;</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-2 border border-gray-300 align-middle">
                                        {{ ($ictForm->status == 0 || $ictForm->status == 1) && $itOfficer->updated_at ? \Carbon\Carbon::parse($itOfficer->updated_at)->format('d F Y') : 'N/A' }}
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify gap-2 mt-4">
                    <button type="button"
                        class="btn bg-gray-500 text-white px-3 py-1.5 rounded hover:bg-gray-600 transition text-xs"
                        onclick="window.history.back()" aria-label="Go Back">
                        Back
                    </button>

                    @if ($ictForm->status == 0)
                    <button type="button"
                        class="btn bg-green-500 text-white px-3 py-1.5 rounded hover:bg-green-600 transition text-xs"
                        onclick="approveForm('{{ $ictForm->access_id }}')" aria-label="Approve Form">
                        Approve
                    </button>

                    <button type="button"
                        class="btn bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700 transition text-xs"
                        onclick="rejectForm('{{ $ictForm->access_id }}')" aria-label="Reject Form">
                        Reject
                    </button>
                    @endif

                    @if ($ictForm->status == 1)
                    <button type="button"
                        class="btn bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 transition text-xs"
                        onclick="generatePDF()" aria-label="Download PDF">
                        Download PDF
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Include Tailwind CSS via CDN -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<!-- Include jsPDF and html2pdf -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    // function generatePDF() {
    //     const element = document.querySelector('.form-container');
    //     window.scrollTo(0, 0);

    //     const options = {
    //         margin: [3, 3, 3, 3],
    //         filename: 'ICT_Access_Form.pdf',
    //         image: {
    //             type: 'jpeg',
    //             quality: 0.98
    //         },
    //         html2canvas: {
    //             scale: 2,
    //             useCORS: true,
    //             logging: false
    //         },
    //         jsPDF: {
    //             unit: 'mm',
    //             format: 'a4',
    //             orientation: 'portrait',
    //             compress: true
    //         }
    //     };

    //     html2pdf().from(element).set(options).save().catch(err => {
    //         console.error('PDF generation failed:', err);
    //         Swal.fire('Error', 'Failed to generate PDF.', 'error');
    //     });
    // }
    function generatePDF() {
        $.ajax({
            method: 'POST',
            url: '{{ route("generate_pdf") }}',
            data: {
                id: '{{ $ictForm->access_id }}'
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(response) {
                const url = window.URL.createObjectURL(new Blob([response], {
                    type: 'application/pdf'
                }));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'ICT_Access_Form.pdf');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                Swal.fire('Success', 'PDF generated successfully.', 'success');
            },
            error: function(xhr, status, error) {
                console.error('PDF generation failed:', error);
                Swal.fire('Error', 'Failed to generate PDF: ' + (xhr.responseJSON?.error || 'Unknown error'), 'error');
            }
        });
    }

    function approveForm(accessId) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        Swal.fire({
            title: 'Confirm Approval',
            text: 'Are you sure you want to approve this form?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    method: 'POST',
                    url: '/approve_form',
                    data: {
                        access_id: accessId
                    },
                    success: response => {
                        Swal.fire({
                            title: 'Success',
                            text: 'Form approved successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '/requestapprove';
                        });
                    },
                    error: (xhr, status, error) => {
                        console.error('Approval failed:', error);
                        Swal.fire('Error', 'Failed to approve form.', 'error');
                    }
                });
            }
        });
    }

    function rejectForm(accessId) {
        Swal.fire({
            title: 'Reason for Rejection',
            text: 'Please provide a reason for rejecting this form:',
            input: 'textarea',
            inputPlaceholder: 'Enter reason here...',
            showCancelButton: true,
            confirmButtonText: 'Reject',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            inputValidator: value => !value && 'You must provide a reason!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    method: 'POST',
                    url: '/reject_form',
                    data: {
                        access_id: accessId,
                        reason: result.value
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: response => {
                        Swal.fire({
                            title: 'Rejected',
                            text: 'Form rejected successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '/requestapprove';
                        });
                    },
                    error: (xhr, status, error) => {
                        console.error('Rejection failed:', error);
                        Swal.fire('Error', 'Failed to reject form.', 'error');
                    }
                });
            }
        });
    }
</script>
@endsection