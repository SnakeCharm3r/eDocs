@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        {{-- <div class="page-header"
                            style="padding: 5px; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                            <div class="row">
                                <div class="page-sub-header"
                                    style="display: flex; flex-direction: column; align-items: flex-start;">
                                    <!-- Previous Approval Section -->
                                    <p style="font-size: 14px; color: #6c757d;">
                                        <strong>Previous Approval:</strong>
                                        @php
                                            $forwardedBy = \App\Models\User::find($clearance->forwarded_by);
                                        @endphp
                                        {{ $forwardedBy ? $forwardedBy->fname . ' ' . $forwardedBy->lname : 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div> --}}

                        <div class="form-container p-4 border rounded shadow-sm">
                            <!-- Section I: Staff Details -->

                            <table
                                style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; border: 1px solid black;">
                                <thead>
                                    <!-- Title Row -->
                                    <tr>
                                        <td colspan="4"
                                            style="text-align: center; font-weight: bold; font-size: 16px; padding: 10px; border: 1px solid black;">
                                            User Clearance Form
                                        </td>
                                    </tr>
                                    <!-- HR Exit Clearance Form Title and Logo -->
                                    <tr>
                                        <td colspan="3"
                                            style="text-align: center; font-weight: bold; font-size: 14px; padding: 10px; border: 1px solid black;">
                                            HR Exit Clearance Form
                                        </td>
                                        <td style="text-align: center; padding: 10px; border: 1px solid black;">
                                            <img src="{{ asset('assets/img/logo-small.png') }}" alt="CCBRT Logo"
                                                style="max-width: 100px;">
                                        </td>
                                    </tr>
                                    <!-- Document Information Row -->
                                    <tr>
                                        <td
                                            style="border: 1px solid black; text-align: center; padding: 8px; font-weight: bold;">
                                            Document No: HR.16</td>
                                        <td
                                            style="border: 1px solid black; text-align: center; padding: 8px; font-weight: bold;">
                                            Version: 1.1</td>
                                        <td
                                            style="border: 1px solid black; text-align: center; padding: 8px; font-weight: bold;">
                                            Effective</td>
                                        <td
                                            style="border: 1px solid black; text-align: center; padding: 8px; font-weight: bold;">
                                            Relevant area: HR, IT & all other departments</td>
                                    </tr>
                                </thead>
                            </table>
                            <br>
                            <table
                                style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; border: 1px solid black;">
                                <thead>
                                    <tr>
                                        <th colspan="2"
                                            style="background-color: hsl(86, 43%, 84%); font-weight: bold; padding: 10px; border: 1px solid black;">
                                            Staff Details
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td
                                            style="width: 30%; background-color: hsl(86, 43%, 84%); font-weight: bold; padding: 8px; border: 1px solid black;">
                                            Employee Name:
                                        </td>
                                        <td style="width: 70%; padding: 8px; border: 1px solid black;">
                                            {{ $clearance->fname }} {{ $clearance->lname }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="background-color: hsl(86, 43%, 84%); font-weight: bold; padding: 8px; border: 1px solid black;">
                                            Position / Job Title:
                                        </td>
                                        <td style="padding: 8px; border: 1px solid black;">
                                            {{ $clearance->job_title ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="background-color: hsl(86, 43%, 84%); font-weight: bold; padding: 8px; border: 1px solid black;">
                                            Department:
                                        </td>
                                        <td style="padding: 8px; border: 1px solid black;">
                                            {{ $clearance->dept_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="background-color: hsl(86, 43%, 84%); font-weight: bold; padding: 8px; border: 1px solid black;">
                                            Employee ID:
                                        </td>
                                        <td style="padding: 8px; border: 1px solid black;">
                                            {{ $clearance->ccbrt_code ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="background-color: hsl(86, 43%, 84%); font-weight: bold; padding: 8px; border: 1px solid black;">
                                            Date of Hire:
                                        </td>
                                        <td style="padding: 8px; border: 1px solid black;">
                                            @if (isset($clearance->date_of_hire) && $clearance->date_of_hire)
                                                {{ \Carbon\Carbon::parse($clearance->date_of_hire)->format('d F Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="background-color: hsl(86, 43%, 84%); font-weight: bold; padding: 8px; border: 1px solid black;">
                                            Last Working Day:
                                        </td>
                                        <td style="padding: 8px; border: 1px solid black;">
                                            @if (isset($clearance->last_working_day) && $clearance->last_working_day)
                                                {{ \Carbon\Carbon::parse($clearance->last_working_day)->format('d F Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="background-color: hsl(86, 43%, 84%); font-weight: bold; padding: 8px; border: 1px solid black;">
                                            Reason for Leaving:
                                        </td>
                                        <td style="padding: 8px; border: 1px solid black;">
                                            @if (isset($clearance->reason_for_leaving))
                                                {{ $clearance->reason_for_leaving }}
                                                @if ($clearance->reason_for_leaving === 'Other' && $clearance->reason_for_leaving_other)
                                                    - {{ $clearance->reason_for_leaving_other }}
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="background-color: hsl(86, 43%, 84%); font-weight: bold; padding: 8px; border: 1px solid black;">
                                            Form Submission Date:
                                        </td>
                                        <td style="padding: 8px; border: 1px solid black;">
                                            {{ \Carbon\Carbon::parse($clearance->created_at)->format('d F Y') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <br>
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif;">
                                <thead>
                                    <tr style="background-color: hsl(86, 43%, 84%);">
                                        <th colspan="2"
                                            style="padding: 10px; text-align: left; font-weight: bold; border: 1px solid #000000;">
                                            Items Collected by Last Day of Work
                                        </th>
                                    </tr>
                                    <tr style="background-color: #f2f2f2;">
                                        <th style="width: 60%; padding: 10px; border: 1px solid #000000;">Item</th>
                                        <th style="width: 40%; padding: 10px; border: 1px solid #000000;">Status/Details
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Changing Room
                                                Keys</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <select name="changing_room_keys" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->changing_room_keys ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->changing_room_keys ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->changing_room_keys ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                {{ $clearance->changing_room_keys ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Office Keys</strong>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <select name="office_keys" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->office_keys ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->office_keys ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->office_keys ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                {{ $clearance->office_keys ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Mobile Phone</strong>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <select name="mobile_phone" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->mobile_phone ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->mobile_phone ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->mobile_phone ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                {{ $clearance->mobile_phone ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Camera</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <select name="camera" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->camera ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->camera ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->camera ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                {{ $clearance->camera ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>CCBRT
                                                Uniforms</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <select name="ccbrt_uniforms" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->ccbrt_uniforms ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->ccbrt_uniforms ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->ccbrt_uniforms ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                {{ $clearance->ccbrt_uniforms ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Office Car
                                                Keys</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <select name="office_car_keys" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->office_car_keys ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->office_car_keys ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->office_car_keys ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                {{ $clearance->office_car_keys ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Any other CCBRT items
                                                given (Please specify)</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <textarea name="other_items" class="form-control form-control-sm" rows="2" placeholder="Specify other items">{{ $clearance->other_items ?? '' }}</textarea>
                                            @else
                                                {{ $clearance->other_items ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>


                            <br>
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000;">
                                <tbody>
                                    <tr>
                                        <td rowspan="4"
                                            style="width: 25%; background-color: #d9e8c5; padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000;">
                                            Line Manager (LM)
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            I confirm receiving the above items.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Full Name:</strong>
                                            @if ($clearance->status == 1 && isset($lineManager))
                                                {{ trim(($lineManager->fname ?? '') . ' ' . ($lineManager->lname ?? '')) }}
                                            @elseif ($clearance->status == 1 && $approver)
                                                {{ trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) }}
                                            @else
                                                <span style="color: red;">No Approval Found</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Signature:</strong>
                                            @if ($clearance->status == 1 && isset($lineManager) && $lineManager->signature)
                                                <img src="data:image/png;base64,{{ $lineManager->signature }}"
                                                    alt="Line Manager Signature" style="max-width: 20%; height: 5%;">
                                            @elseif ($clearance->status == 1 && $approver && $approver->signature)
                                                <img src="data:image/png;base64,{{ $approver->signature }}"
                                                    alt="Approver Signature" style="max-width: 20%; height: 5%;">
                                            @else
                                                <span style="color: red;">No Signature Found</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Date:</strong>
                                            @if ($clearance->status == 1 && isset($lineManager) && $lineManager->updated_at)
                                                {{ \Carbon\Carbon::parse($lineManager->updated_at)->format('d F Y') }}
                                            @elseif ($clearance->status == 1 && $approver && $approver->updated_at)
                                                {{ \Carbon\Carbon::parse($approver->updated_at)->format('d F Y') }}
                                            @else
                                                <span style="color: red;">No Date Found</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <br>
                            <!-- HR Department Section -->
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif;">
                                <thead>
                                    <tr style="background-color: hsl(86, 43%, 84%);">
                                        <th colspan="2"
                                            style="padding: 10px; text-align: left; font-weight: bold; border: 1px solid #000000;">
                                            A. Human Resources Department
                                        </th>
                                    </tr>
                                    <tr style="background-color: #f2f2f2;">
                                        <th style="width: 60%; padding: 10px; border: 1px solid #000000;">Item</th>
                                        <th style="width: 40%; padding: 10px; border: 1px solid #000000;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Resignation letter
                                                received</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <select name="resignation_letter_received"
                                                    class="form-control form-control-sm" style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->resignation_letter_received ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="1"
                                                        {{ $clearance->resignation_letter_received ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            @else
                                                @if (isset($clearance->resignation_letter_received) && $clearance->resignation_letter_received)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Exit interview
                                                completed</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <select name="exit_interview_completed"
                                                    class="form-control form-control-sm" style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->exit_interview_completed ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="1"
                                                        {{ $clearance->exit_interview_completed ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            @else
                                                @if (isset($clearance->exit_interview_completed) && $clearance->exit_interview_completed)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Leave balance
                                                confirmed</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <select name="leave_balance_confirmed"
                                                    class="form-control form-control-sm" style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->leave_balance_confirmed ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="1"
                                                        {{ $clearance->leave_balance_confirmed ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            @else
                                                @if (isset($clearance->leave_balance_confirmed) && $clearance->leave_balance_confirmed)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Contract file
                                                reviewed</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'HR Officer')
                                                <select name="contract_file_reviewed" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->contract_file_reviewed ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="1"
                                                        {{ $clearance->contract_file_reviewed ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            @else
                                                @if (isset($clearance->contract_file_reviewed) && $clearance->contract_file_reviewed)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <br>
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000;">
                                <tbody>
                                    <tr>
                                        <td rowspan="4"
                                            style="width: 25%; background-color: #d9e8c5; padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000;">
                                            HR Officer
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            I confirm the above items have been completed.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Full Name:</strong>
                                            @if (isset($hrOfficer) && $hrOfficer)
                                                {{ trim(($hrOfficer->fname ?? '') . ' ' . ($hrOfficer->lname ?? '')) }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Signature:</strong>
                                            @if (isset($hrOfficer) && $hrOfficer && $hrOfficer->signature)
                                                <img src="data:image/png;base64,{{ $hrOfficer->signature }}"
                                                    alt="HR Officer Signature" style="max-width: 20%; height: 5%;">
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Date:</strong>
                                            @if (isset($hrOfficer) && $hrOfficer && $hrOfficer->updated_at)
                                                {{ \Carbon\Carbon::parse($hrOfficer->updated_at)->format('d F Y') }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <br>

                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif;">
                                <thead>
                                    <tr style="background-color: hsl(86, 43%, 84%);">
                                        <th colspan="2"
                                            style="padding: 10px; text-align: left; font-weight: bold; border: 1px solid #000000;">
                                            B. Finance Department
                                        </th>
                                    </tr>
                                    <tr style="background-color: #f2f2f2;">
                                        <th style="width: 60%; padding: 10px; border: 1px solid #000000;">Finance
                                            Confirmation
                                            Details</th>
                                        <th style="width: 40%; padding: 10px; border: 1px solid #000000;">Status/Details
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Repaid advance on
                                                Salary?</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                                <select name="repaid_salary_advance" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="">Select...</option>
                                                    <option value="Yes"
                                                        {{ ($clearance->repaid_salary_advance ?? '') === 'Yes' ? 'selected' : '' }}>
                                                        Yes
                                                    </option>
                                                    <option value="No"
                                                        {{ ($clearance->repaid_salary_advance ?? '') === 'No' ? 'selected' : '' }}>
                                                        No
                                                    </option>
                                                    <option value="N/A"
                                                        {{ ($clearance->repaid_salary_advance ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A
                                                    </option>
                                                </select>
                                            @else
                                                {{ $clearance->repaid_salary_advance ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Staff informed
                                                Finance of outstanding loan balances?</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                                <select name="loan_balances_informed" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="">Select...</option>
                                                    <option value="Yes"
                                                        {{ ($clearance->loan_balances_informed ?? '') === 'Yes' ? 'selected' : '' }}>
                                                        Yes
                                                    </option>
                                                    <option value="No"
                                                        {{ ($clearance->loan_balances_informed ?? '') === 'No' ? 'selected' : '' }}>
                                                        No
                                                    </option>
                                                    <option value="N/A"
                                                        {{ ($clearance->loan_balances_informed ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A
                                                    </option>
                                                </select>
                                            @else
                                                {{ $clearance->loan_balances_informed ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>All salary advances
                                                cleared</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                                <select name="all_salary_advances_cleared"
                                                    class="form-control form-control-sm" style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->all_salary_advances_cleared ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="1"
                                                        {{ $clearance->all_salary_advances_cleared ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            @else
                                                @if (isset($clearance->all_salary_advances_cleared) && $clearance->all_salary_advances_cleared)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Allowances
                                                reconciled</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                                <select name="allowances_reconciled" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->allowances_reconciled ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="1"
                                                        {{ $clearance->allowances_reconciled ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            @else
                                                @if (isset($clearance->allowances_reconciled) && $clearance->allowances_reconciled)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Pending claims
                                                settled</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                                <select name="pending_claims_settled" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->pending_claims_settled ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="1"
                                                        {{ $clearance->pending_claims_settled ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            @else
                                                @if (isset($clearance->pending_claims_settled) && $clearance->pending_claims_settled)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Outstanding loans
                                                recovered</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                                <select name="outstanding_loans_recovered"
                                                    class="form-control form-control-sm" style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->outstanding_loans_recovered ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="1"
                                                        {{ $clearance->outstanding_loans_recovered ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            @else
                                                @if (isset($clearance->outstanding_loans_recovered) && $clearance->outstanding_loans_recovered)
                                                    ✓ Yes
                                                @elseif(isset($clearance->loan_balances_informed))
                                                    {{ $clearance->loan_balances_informed }}
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Repaid any
                                                outstanding imprest?</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                                <input type="text" name="repaid_outstanding_imprest"
                                                    class="form-control form-control-sm"
                                                    value="{{ $clearance->repaid_outstanding_imprest ?? '' }}"
                                                    placeholder="Enter status">
                                            @else
                                                @if (isset($clearance->repaid_outstanding_imprest))
                                                    {{ $clearance->repaid_outstanding_imprest }}
                                                @else
                                                    N/A
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                        <tr>
                                            <td style="padding: 10px; border: 1px solid #000000;"><strong>Employee eligible
                                                    for final payment</strong></td>
                                            <td style="padding: 10px; border: 1px solid #000000;">
                                                <select name="employee_eligible_for_final_payment"
                                                    class="form-control form-control-sm" style="width: 100%;">
                                                    <option value="">Select...</option>
                                                    <option value="1"
                                                        {{ $clearance->employee_eligible_for_final_payment == 1 ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="0"
                                                        {{ $clearance->employee_eligible_for_final_payment === 0 ? 'selected' : '' }}>
                                                        No</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; border: 1px solid #000000;"><strong>Finance
                                                    Comments</strong></td>
                                            <td style="padding: 10px; border: 1px solid #000000;">
                                                <textarea name="finance_comments" class="form-control form-control-sm" rows="3" placeholder="Enter comments">{{ $clearance->finance_comments ?? '' }}</textarea>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <br>
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000;">
                                <tbody>
                                    <tr>
                                        <td rowspan="5"
                                            style="width: 25%; background-color: #d9e8c5; padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000;">
                                            Finance Officer (FO)
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            I confirm all financial matters have been cleared.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Full Name:</strong>
                                            @if (isset($financeOfficer) && $financeOfficer)
                                                {{ trim(($financeOfficer->fname ?? '') . ' ' . ($financeOfficer->lname ?? '')) }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Signature:</strong>
                                            @if (isset($financeOfficer) && $financeOfficer && $financeOfficer->signature)
                                                <img src="data:image/png;base64,{{ $financeOfficer->signature }}"
                                                    alt="Finance Officer Signature" style="max-width: 20%; height: 5%;">
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Date:</strong>
                                            @if (isset($financeOfficer) && $financeOfficer && $financeOfficer->updated_at)
                                                {{ \Carbon\Carbon::parse($financeOfficer->updated_at)->format('d F Y') }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Employee eligible for final payment:</strong>
                                            @if (isset($clearance->employee_eligible_for_final_payment))
                                                @if ($clearance->employee_eligible_for_final_payment)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if (isset($clearance->finance_comments) && $clearance->finance_comments)
                                        <tr>
                                            <td colspan="2" style="padding: 10px; border: 1px solid #000;">
                                                <strong>Comments:</strong> {{ $clearance->finance_comments }}
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            <br>


                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif;">
                                <thead>
                                    <tr style="background-color: hsl(86, 43%, 84%);">
                                        <th colspan="2"
                                            style="padding: 10px; text-align: left; font-weight: bold; border: 1px solid #000000;">
                                            C. IT Department
                                        </th>
                                    </tr>
                                    <tr style="background-color: #f2f2f2;">
                                        <th style="width: 60%; padding: 10px; border: 1px solid #000000;">ICT Checklist
                                            Items
                                        </th>
                                        <th style="width: 40%; padding: 10px; border: 1px solid #000000;">Status/Details
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Laptop
                                                returned</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="laptop_returned" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->laptop_returned ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->laptop_returned ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->laptop_returned ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                                @if (($clearance->laptop_returned ?? '') === 'Other')
                                                    <input type="text" name="laptop_returned_other"
                                                        class="form-control form-control-sm mt-1"
                                                        value="{{ $clearance->laptop_returned_other ?? '' }}"
                                                        placeholder="Specify">
                                                @endif
                                            @else
                                                {{ $clearance->laptop_returned ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Keyboard/Mouse
                                                returned</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="keyboard_mouse_returned"
                                                    class="form-control form-control-sm" style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->keyboard_mouse_returned ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->keyboard_mouse_returned ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->keyboard_mouse_returned ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                {{ $clearance->keyboard_mouse_returned ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Phone
                                                returned</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="phone_returned" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->phone_returned ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->phone_returned ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->phone_returned ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                {{ $clearance->phone_returned ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>ID Card
                                                returned</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="id_card_returned" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->id_card_returned ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->id_card_returned ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->id_card_returned ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                {{ $clearance->id_card_returned ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                        <tr>
                                            <td style="padding: 10px; border: 1px solid #000000;"><strong>IT Ticket
                                                    No</strong></td>
                                            <td style="padding: 10px; border: 1px solid #000000;">
                                                <input type="text" name="it_ticket_no"
                                                    class="form-control form-control-sm"
                                                    value="{{ $clearance->it_ticket_no ?? '' }}"
                                                    placeholder="Enter IT ticket number">
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Domain Account
                                                Disabled?</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->domain_account_disabled }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Email Account
                                                Disabled?</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->email_account_disabled }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Telephone Pin Code
                                                Disabled?</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->telephone_pin_disabled }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>OpenClinic Account
                                                Disabled?</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->openclinic_account_disabled }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>SAP Account
                                                Disabled?</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->sap_account_disabled }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Aruti Account
                                                Disabled?</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->aruti_account_disabled }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <br>
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000;">
                                <tbody>
                                    <tr>
                                        <td rowspan="5"
                                            style="width: 25%; background-color: #d9e8c5; padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000;">
                                            IT Officer
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            I confirm the above items have been completed.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Full Name:</strong>
                                            @if (isset($itOfficer) && $itOfficer)
                                                {{ trim(($itOfficer->fname ?? '') . ' ' . ($itOfficer->lname ?? '')) }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Ticket No:</strong>
                                            @if (isset($clearance->it_ticket_no) && $clearance->it_ticket_no)
                                                {{ $clearance->it_ticket_no }}
                                            @else
                                                <span style="color: #999;">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Signature:</strong>
                                            @if (isset($itOfficer) && $itOfficer && $itOfficer->signature)
                                                <img src="data:image/png;base64,{{ $itOfficer->signature }}"
                                                    alt="IT Officer Signature" style="max-width: 20%; height: 5%;">
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Date:</strong>
                                            @if (isset($itOfficer) && $itOfficer && $itOfficer->updated_at)
                                                {{ \Carbon\Carbon::parse($itOfficer->updated_at)->format('d F Y') }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>


                            <br>
                            <!-- Administration/Assets Section -->
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif;">
                                <thead>
                                    <tr style="background-color: hsl(86, 43%, 84%);">
                                        <th colspan="2"
                                            style="padding: 10px; text-align: left; font-weight: bold; border: 1px solid #000000;">
                                            D. Administration / Assets
                                        </th>
                                    </tr>
                                    <tr style="background-color: #f2f2f2;">
                                        <th style="width: 60%; padding: 10px; border: 1px solid #000000;">Item</th>
                                        <th style="width: 40%; padding: 10px; border: 1px solid #000000;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Office keys
                                                returned</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && ($currentApprovalStep === 'HR Officer' || $currentApprovalStep === 'Administration'))
                                                <select name="office_keys_returned" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->office_keys_returned ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->office_keys_returned ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->office_keys_returned ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                @if (isset($clearance->office_keys_returned) && $clearance->office_keys_returned)
                                                    {{ $clearance->office_keys_returned === 'Returned' ? '✓ Returned' : $clearance->office_keys_returned }}
                                                @elseif(isset($clearance->office_keys))
                                                    {{ $clearance->office_keys }}
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Uniform/PPE
                                                returned</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && ($currentApprovalStep === 'HR Officer' || $currentApprovalStep === 'Administration'))
                                                <select name="uniform_ppe_returned" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->uniform_ppe_returned ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->uniform_ppe_returned ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->uniform_ppe_returned ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                @if (isset($clearance->uniform_ppe_returned) && $clearance->uniform_ppe_returned)
                                                    {{ $clearance->uniform_ppe_returned === 'Returned' ? '✓ Returned' : $clearance->uniform_ppe_returned }}
                                                @elseif(isset($clearance->ccbrt_uniforms))
                                                    {{ $clearance->ccbrt_uniforms }}
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Tools & equipment
                                                returned</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && ($currentApprovalStep === 'HR Officer' || $currentApprovalStep === 'Administration'))
                                                <select name="tools_equipment_returned"
                                                    class="form-control form-control-sm" style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->tools_equipment_returned ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->tools_equipment_returned ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->tools_equipment_returned ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                @if (isset($clearance->tools_equipment_returned) && $clearance->tools_equipment_returned)
                                                    {{ $clearance->tools_equipment_returned === 'Returned' ? '✓ Returned' : $clearance->tools_equipment_returned }}
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Vehicle clearance (if
                                                applicable)</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && ($currentApprovalStep === 'HR Officer' || $currentApprovalStep === 'Administration'))
                                                <select name="vehicle_clearance" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="N/A"
                                                        {{ ($clearance->vehicle_clearance ?? '') === 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Returned"
                                                        {{ ($clearance->vehicle_clearance ?? '') === 'Returned' ? 'selected' : '' }}>
                                                        Returned</option>
                                                    <option value="Other"
                                                        {{ ($clearance->vehicle_clearance ?? '') === 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            @else
                                                @if (isset($clearance->vehicle_clearance) && $clearance->vehicle_clearance)
                                                    {{ $clearance->vehicle_clearance === 'Returned' ? '✓ Returned' : $clearance->vehicle_clearance }}
                                                @elseif(isset($clearance->office_car_keys))
                                                    {{ $clearance->office_car_keys }}
                                                @else
                                                    N/A
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <br>
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000;">
                                <tbody>
                                    <tr>
                                        <td rowspan="4"
                                            style="width: 25%; background-color: #d9e8c5; padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000;">
                                            Admin Officer
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            I confirm the above items have been returned.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Full Name:</strong>
                                            <span style="color: #999;">Pending</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Signature:</strong>
                                            <span style="color: #999;">Pending</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Date:</strong>
                                            <span style="color: #999;">Pending</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <br>
                            <!-- Line Manager Section -->
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif;">
                                <thead>
                                    <tr style="background-color: hsl(86, 43%, 84%);">
                                        <th colspan="2"
                                            style="padding: 10px; text-align: left; font-weight: bold; border: 1px solid #000000;">
                                            E. Line Manager / Supervisor
                                        </th>
                                    </tr>
                                    <tr style="background-color: #f2f2f2;">
                                        <th style="width: 60%; padding: 10px; border: 1px solid #000000;">Item</th>
                                        <th style="width: 40%; padding: 10px; border: 1px solid #000000;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Handover report
                                                received</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'Line Manager')
                                                <select name="handover_report_received"
                                                    class="form-control form-control-sm" style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->handover_report_received ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="1"
                                                        {{ $clearance->handover_report_received ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            @else
                                                @if (isset($clearance->handover_report_received) && $clearance->handover_report_received)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Work responsibilities
                                                transferred</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'Line Manager')
                                                <select name="work_responsibilities_transferred"
                                                    class="form-control form-control-sm" style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->work_responsibilities_transferred ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="1"
                                                        {{ $clearance->work_responsibilities_transferred ? 'selected' : '' }}>
                                                        Yes</option>
                                                </select>
                                            @else
                                                @if (isset($clearance->work_responsibilities_transferred) && $clearance->work_responsibilities_transferred)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Projects/tasks
                                                closed</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            @if ($canEdit && $currentApprovalStep === 'Line Manager')
                                                <select name="projects_tasks_closed" class="form-control form-control-sm"
                                                    style="width: 100%;">
                                                    <option value="0"
                                                        {{ !$clearance->projects_tasks_closed ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="1"
                                                        {{ $clearance->projects_tasks_closed ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            @else
                                                @if (isset($clearance->projects_tasks_closed) && $clearance->projects_tasks_closed)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <br>
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000;">
                                <tbody>
                                    <tr>
                                        <td rowspan="4"
                                            style="width: 25%; background-color: #d9e8c5; padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000;">
                                            Line Manager (LM)
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            I confirm the above items have been completed.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Full Name:</strong>
                                            @if (isset($lineManager) && $lineManager)
                                                {{ trim(($lineManager->fname ?? '') . ' ' . ($lineManager->lname ?? '')) }}
                                            @elseif (isset($approver) && $approver)
                                                {{ trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Signature:</strong>
                                            @if (isset($lineManager) && $lineManager && $lineManager->signature)
                                                <img src="data:image/png;base64,{{ $lineManager->signature }}"
                                                    alt="Line Manager Signature" style="max-width: 20%; height: 5%;">
                                            @elseif (isset($approver) && $approver && $approver->signature)
                                                <img src="data:image/png;base64,{{ $approver->signature }}"
                                                    alt="Approver Signature" style="max-width: 20%; height: 5%;">
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Date:</strong>
                                            @if (isset($lineManager) && $lineManager && $lineManager->updated_at)
                                                {{ \Carbon\Carbon::parse($lineManager->updated_at)->format('d F Y') }}
                                            @elseif (isset($approver) && $approver && $approver->updated_at)
                                                {{ \Carbon\Carbon::parse($approver->updated_at)->format('d F Y') }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <br>
                            <!-- Previous Approvers Summary Section - Enhanced for Final HR Review -->
                            @if (isset($allApproversWithDetails) && $allApproversWithDetails->count() > 0)
                                @php
                                    $isFinalHRReview =
                                        $currentApprovalStep === 'HR Officer' ||
                                        (isset($workflow) &&
                                            ($workflow->work_flow_status === 'Pending Approval - HR Officer'));
                                @endphp
                                <div
                                    style="margin-bottom: 30px; padding: 15px; background-color: {{ $isFinalHRReview ? '#e8f5e9' : '#f8f9fa' }}; border-left: 5px solid #007A33; border-radius: 5px;">
                                    <h4 style="color: #007A33; margin-bottom: 20px;">
                                        <i class="fas fa-users me-2"></i>All Previous Approvers and Their Details
                                        @if ($isFinalHRReview)
                                            <span class="badge bg-success ms-2">Final HR Review</span>
                                        @endif
                                    </h4>
                                    @foreach ($allApproversWithDetails as $approverHistory)
                                        <table class="table table-bordered"
                                            style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000; margin-bottom: 20px;">
                                            <thead>
                                                <tr style="background-color: hsl(86, 43%, 84%);">
                                                    <th colspan="2"
                                                        style="padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000000;">
                                                        {{ $approverHistory['step_name'] }} - Approval Details
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td
                                                        style="width: 30%; background-color: #f2f2f2; padding: 10px; border: 1px solid #000000; font-weight: bold;">
                                                        Approver Name:
                                                    </td>
                                                    <td style="padding: 10px; border: 1px solid #000000;">
                                                        {{ $approverHistory['approver_name'] }}
                                                        @if ($approverHistory['approver_email'] && $approverHistory['approver_email'] !== 'N/A')
                                                            <br><small
                                                                style="color: #666;">{{ $approverHistory['approver_email'] }}</small>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        style="background-color: #f2f2f2; padding: 10px; border: 1px solid #000000; font-weight: bold;">
                                                        Approval Date:
                                                    </td>
                                                    <td style="padding: 10px; border: 1px solid #000000;">
                                                        @if ($approverHistory['approval_date'])
                                                            {{ \Carbon\Carbon::parse($approverHistory['approval_date'])->format('d F Y, h:i A') }}
                                                        @else
                                                            <span style="color: #999;">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        style="background-color: #f2f2f2; padding: 10px; border: 1px solid #000000; font-weight: bold;">
                                                        Signature:
                                                    </td>
                                                    <td style="padding: 10px; border: 1px solid #000000;">
                                                        @if ($approverHistory['signature'])
                                                            <img src="data:image/png;base64,{{ $approverHistory['signature'] }}"
                                                                alt="Signature"
                                                                style="max-width: 150px; max-height: 60px; object-fit: contain; border: 1px solid #ddd;">
                                                        @else
                                                            <span style="color: #999;">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @if ($approverHistory['remark'])
                                                    <tr>
                                                        <td
                                                            style="background-color: #f2f2f2; padding: 10px; border: 1px solid #000000; font-weight: bold;">
                                                            Remarks:
                                                        </td>
                                                        <td style="padding: 10px; border: 1px solid #000000;">
                                                            {{ $approverHistory['remark'] }}
                                                        </td>
                                                    </tr>
                                                @endif
                                                @if (isset($approverHistory['section_details']) && count($approverHistory['section_details']) > 0)
                                                    <tr>
                                                        <td colspan="2"
                                                            style="padding: 10px; border: 1px solid #000000; background-color: #e9ecef; font-weight: bold;">
                                                            Section Details Filled by {{ $approverHistory['step_name'] }}:
                                                        </td>
                                                    </tr>
                                                    @foreach ($approverHistory['section_details'] as $detailKey => $detailValue)
                                                        <tr>
                                                            <td
                                                                style="background-color: #f8f9fa; padding: 8px; border: 1px solid #000000;">
                                                                <strong>{{ $detailKey }}:</strong>
                                                            </td>
                                                            <td style="padding: 8px; border: 1px solid #000000;">
                                                                {{ $detailValue }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    @endforeach
                                </div>
                            @endif

                            <br>
                            <!-- Final HR Approval Section -->
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000;">
                                <tbody>
                                    <tr>
                                        <td rowspan="5"
                                            style="width: 25%; background-color: #d9e8c5; padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000;">
                                            3. Final HR Approval
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            I confirm that the employee has completed all required clearances and all
                                            previous approvals have been verified.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>HR Manager Name:</strong>
                                            @if (isset($hrOfficer) &&
                                                    $hrOfficer &&
                                                    isset($clearance->work_flow_status) &&
                                                    $clearance->work_flow_status === 'Approved - Completed')
                                                {{ trim(($hrOfficer->fname ?? '') . ' ' . ($hrOfficer->lname ?? '')) }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Signature:</strong>
                                            @if (isset($hrOfficer) &&
                                                    $hrOfficer &&
                                                    $hrOfficer->signature &&
                                                    isset($clearance->work_flow_status) &&
                                                    $clearance->work_flow_status === 'Approved - Completed')
                                                <img src="data:image/png;base64,{{ $hrOfficer->signature }}"
                                                    alt="HR Manager Signature" style="max-width: 20%; height: 5%;">
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Date:</strong>
                                            @if (isset($hrOfficer) &&
                                                    $hrOfficer &&
                                                    $hrOfficer->updated_at &&
                                                    isset($clearance->work_flow_status) &&
                                                    $clearance->work_flow_status === 'Approved - Completed')
                                                {{ \Carbon\Carbon::parse($hrOfficer->updated_at)->format('d F Y') }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if (isset($clearance->hr_manager_comments) && $clearance->hr_manager_comments)
                                        <tr>
                                            <td style="padding: 10px; border: 1px solid #000;">
                                                <strong>Comments:</strong> {{ $clearance->hr_manager_comments }}
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            <br>
                            <!-- Finance Release Section -->
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000;">
                                <tbody>
                                    <tr>
                                        <td rowspan="4"
                                            style="width: 25%; background-color: #d9e8c5; padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000;">
                                            4. Finance Release
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Employee eligible for final payment:</strong>
                                            @if (isset($clearance->employee_eligible_for_final_payment))
                                                @if ($clearance->employee_eligible_for_final_payment)
                                                    ✓ Yes
                                                @else
                                                    ☐ No
                                                @endif
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if (isset($clearance->finance_comments) && $clearance->finance_comments)
                                        <tr>
                                            <td style="padding: 10px; border: 1px solid #000;">
                                                <strong>Comments:</strong> {{ $clearance->finance_comments }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Finance Manager Signature:</strong>
                                            @if (isset($financeOfficer) && $financeOfficer && $financeOfficer->signature)
                                                <img src="data:image/png;base64,{{ $financeOfficer->signature }}"
                                                    alt="Finance Manager Signature" style="max-width: 20%; height: 5%;">
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Date:</strong>
                                            @if (isset($financeOfficer) && $financeOfficer && $financeOfficer->updated_at)
                                                {{ \Carbon\Carbon::parse($financeOfficer->updated_at)->format('d F Y') }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <br>
                            <!-- Employee Confirmation Section -->
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000;">
                                <tbody>
                                    <tr>
                                        <td rowspan="3"
                                            style="width: 25%; background-color: #d9e8c5; padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000;">
                                            5. Employee Confirmation
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            I confirm that I have returned all organization property and completed the
                                            clearance process.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Employee Signature:</strong>
                                            @if (isset($clearance->employee_confirmed) && $clearance->employee_confirmed)
                                                <span style="color: #28a745;">✓ Confirmed</span>
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Date:</strong>
                                            @if (isset($clearance->employee_confirmed_at) && $clearance->employee_confirmed_at)
                                                {{ \Carbon\Carbon::parse($clearance->employee_confirmed_at)->format('d F Y') }}
                                            @else
                                                <span style="color: #999;">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <br>
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif;">
                                <thead>
                                    <tr style="background-color: hsl(86, 43%, 84%);">
                                        <th colspan="2"
                                            style="padding: 10px; text-align: left; font-weight: bold; border: 1px solid #ddd;">
                                            Collect the following items by Last day of work
                                        </th>
                                    </tr>
                                    <tr style="background-color: #f2f2f2;">
                                        <th style="width: 60%; padding: 10px; border: 1px solid #000000;">Items</th>
                                        <th style="width: 40%; padding: 10px; border: 1px solid #000000;">Status/Details
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>CCBRT
                                                Identification
                                                Card</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->ccbrt_id_card }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>CCBRT Name
                                                Tag</strong>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->ccbrt_name_tag }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>NHIF Cards
                                                (Including
                                                dependents’ cards)</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->nhif_cards }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Work Permit
                                                Cancelled
                                                (for non-Tanzanians)</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->work_permit_cancelled }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000000;"><strong>Residence Permit
                                                Cancelled (for non-Tanzanians)</strong></td>
                                        <td style="padding: 10px; border: 1px solid #000000;">
                                            {{ $clearance->residence_permit_cancelled }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <br>
                            <table class="table table-bordered"
                                style="width: 100%; border-collapse: collapse; text-align: left; font-family: Arial, sans-serif; border: 1px solid #000;">
                                <tbody>
                                    <tr>
                                        <td rowspan="4"
                                            style="width: 25%; background-color: #d9e8c5; padding: 10px; text-align: center; font-weight: bold; border: 1px solid #000;">
                                            HR Officer
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            I confirm receiving the above items.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Full Name:</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Signature:</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #000;">
                                            <strong>Date:</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>




                            <!-- Action Buttons -->
                            <div class="buttons-container"
                                style="margin-top: 15px; display: flex; justify-content: flex-end; gap: 10px; padding-right: 3%;">
                                @php
                                    $workflow = \App\Models\Clearance_work_flow::where(
                                        'requested_resource_id',
                                        $clearance->access_id,
                                    )->first();
                                    $pendingHistory = $workflow
                                        ? \App\Models\Clearance_work_flow_history::where('work_flow_id', $workflow->id)
                                            ->where('status', 0)
                                            ->where('attended_by', Auth::id())
                                            ->first()
                                        : null;
                                @endphp
                                @if ($pendingHistory)
                                    <button type="button" class="btn btn-success"
                                        onclick="approveClearanceForm('{{ $clearance->access_id }}')">
                                        <i class="fas fa-check me-2"></i>Approve
                                    </button>
                                    <button type="button" class="btn btn-danger"
                                        onclick="rejectForm('{{ $clearance->access_id }}')">
                                        <i class="fas fa-times me-2"></i>Reject
                                    </button>
                                @endif
                                <button type="button" class="btn btn-primary" onclick="generatePDF()">
                                    <i class="fas fa-download me-2"></i>Download PDF
                                </button>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://raw.githack.com/eKoopmans/html2pdf/master/dist/html2pdf.bundle.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function generatePDF() {
            const element = document.querySelector('.form-container');

            // Ensure the element is at the top of the page
            window.scrollTo(0, 0);

            // Options for html2pdf
            const options = {
                margin: [2, 1, 1, 2], // Adjust margins to fit content better
                filename: 'Exit Form.pdf',
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

        function approveClearanceForm(access_id) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Collect all form data from editable fields
            var formData = {
                access_id: access_id
            };

            // Collect all select and input fields within the form
            $('select[name], input[name], textarea[name]').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                if (name && value !== undefined && value !== null && value !== '') {
                    formData[name] = value;
                }
            });

            Swal.fire({
                title: 'Approve Clearance Form?',
                text: "Are you sure you want to approve this clearance form?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007A33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, approve it!',
                cancelButtonText: 'No, cancel!',
                reverseButtons: true,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return $.ajax({
                        method: 'POST',
                        url: '/approve_clearform',
                        data: formData,
                        dataType: 'json'
                    }).then(function(response) {
                        if (response.success) {
                            return response;
                        } else {
                            throw new Error(response.message || 'Failed to approve form');
                        }
                    }).catch(function(xhr) {
                        let errorMessage = 'An error occurred while approving the form.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        } else if (xhr.status === 0) {
                            errorMessage = 'Network error. Please check your connection and try again.';
                        } else if (xhr.status === 404) {
                            errorMessage = 'Clearance workflow not found.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Server error. Please contact the administrator.';
                        }
                        Swal.showValidationMessage(errorMessage);
                        throw new Error(errorMessage);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const response = result.value;
                    let message = response.message || 'Clearance form approved successfully!';
                    let details = '';

                    if (response.nextStep) {
                        details = `<br><br><strong>Next Step:</strong> ${response.nextStep}`;
                        if (response.nextApprover) {
                            details += `<br><strong>Next Approver:</strong> ${response.nextApprover}`;
                        }
                    }

                    Swal.fire({
                        title: 'Success!',
                        html: message + details,
                        icon: 'success',
                        confirmButtonColor: '#007A33',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                window.location.href = '/requestapprove';
                            }
                        }
                    });
                }
            }).catch((error) => {
                console.error('Approval error:', error);
            });
        }

        function rejectForm(access_id) {
            Swal.fire({
                title: 'Reject Clearance Form',
                text: "Please provide a reason for rejection:",
                input: 'textarea',
                inputPlaceholder: 'Enter your reason here...',
                inputAttributes: {
                    'aria-label': 'Type your rejection reason here'
                },
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Reject',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                inputValidator: (value) => {
                    if (!value || value.trim().length === 0) {
                        return 'You need to provide a reason for rejection!'
                    }
                    if (value.trim().length < 10) {
                        return 'Please provide a more detailed reason (at least 10 characters)'
                    }
                },
                showLoaderOnConfirm: true,
                preConfirm: (reason) => {
                    return $.ajax({
                        method: 'POST',
                        url: '/exit_forms/' + access_id + '/reject',
                        data: {
                            reason: reason,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        dataType: 'json'
                    }).then(function(response) {
                        if (response.success !== false) {
                            return response;
                        } else {
                            throw new Error(response.message || 'Failed to reject form');
                        }
                    }).catch(function(xhr) {
                        let errorMessage = 'An error occurred while rejecting the form.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        } else if (xhr.status === 0) {
                            errorMessage = 'Network error. Please check your connection and try again.';
                        } else if (xhr.status === 404) {
                            errorMessage = 'Clearance form not found.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Server error. Please contact the administrator.';
                        }
                        Swal.showValidationMessage(errorMessage);
                        throw new Error(errorMessage);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const response = result.value;
                    Swal.fire({
                        title: 'Rejected!',
                        text: response.message || 'Clearance form has been rejected successfully.',
                        icon: 'success',
                        confirmButtonColor: '#007A33',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                window.location.href = '/requestapprove';
                            }
                        }
                    });
                }
            }).catch((error) => {
                console.error('Rejection error:', error);
            });
        }
    </script>
@endsection

@section('styles')
    <style>
        .form-check-input {
            width: 20px;
            height: 20px;
            margin: 0;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: bold;
        }

        .table td {
            vertical-align: middle;
        }
    </style>
@endsection
