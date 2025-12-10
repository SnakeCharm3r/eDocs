@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">My Requests</h3>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-success">
                                        <tr>
                                            <th>#</th>
                                            <th>Request Type</th>
                                            <th>Approval Level</th>
                                            <th>Status</th>
                                            <th>Remark</th>
                                            <th>Submitted</th>
                                            <th>Approved Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $counter = 1;

                                            $roleDisplayNames = [
                                                'line-manager' => 'Line Manager',
                                                'it' => 'IT Officer',
                                                'hr' => 'HR BP',
                                                'finance officer' => 'Finance Officer',
                                                'price_committee' => 'Price Committee',
                                                'payroll_accountant' => 'Payroll Accountant',
                                                'coo' => 'COO',
                                                'cfo' => 'CFO',
                                                'cms' => 'CMS',
                                                'chief_accountant' => 'Chief Accountant',
                                                'ceo' => 'CEO',
                                                'hr' => 'HR',
                                            ];

                                            // Default status mapping
                                            $defaultStatusMap = [
                                                0 => ['class' => 'warning', 'text' => 'Pending'],
                                                1 => ['class' => 'success', 'text' => 'Approved'],
                                                2 => ['class' => 'danger', 'text' => 'Rejected'],
                                                -1 => ['class' => 'danger', 'text' => 'Rejected'],
                                            ];
                                        @endphp

                                        {{-- Display Regular Forms (ICT, HR, etc.) --}}
                                        @foreach ($form as $aform)
                                            @php
                                                // Check if workflow is fully completed and approved
                                                $isWorkflowCompleted =
                                                    $aform->work_flow_completed == 1 &&
                                                    (strtolower($aform->work_flow_status ?? '') == 'approved' ||
                                                        strtolower($aform->work_flow_status ?? '') == 'approve');

                                                $approvalDetails = [];
                                                $uniqueRoles = [];
                                                $submittedDate = \Carbon\Carbon::parse($aform->created_at)->format(
                                                    'd F Y',
                                                );
                                                $lastApprovedDate = null;
                                            @endphp

                                            @foreach ($histories[$aform->id] as $ahistory)
                                                @php
                                                    $user = \App\Models\User::find($ahistory->attended_by);
                                                    $roles = $user ? $user->roles : collect();
                                                    $date = \Carbon\Carbon::parse($ahistory->created_at)->format(
                                                        'd F Y',
                                                    );
                                                @endphp
                                                @foreach ($roles as $role)
                                                    @if ($role->name != 'requester' && !in_array($role->name, $uniqueRoles))
                                                        @php
                                                            $uniqueRoles[] = $role->name;

                                                            $statusInfo = $defaultStatusMap[$ahistory->status] ?? [
                                                                'class' => 'secondary',
                                                                'text' => 'Unknown',
                                                            ];
                                                            $approvedDate =
                                                                $ahistory->status == 1 ||
                                                                $ahistory->status == 2 ||
                                                                $ahistory->status == -1
                                                                    ? \Carbon\Carbon::parse(
                                                                        $ahistory->updated_at ??
                                                                            ($ahistory->attend_date ??
                                                                                $ahistory->created_at),
                                                                    )->format('d F Y')
                                                                    : null;

                                                            $rejectionReason =
                                                                $ahistory->status == 2 || $ahistory->status == -1
                                                                    ? $ahistory->rejection_reason ?? ''
                                                                    : '';

                                                            if ($lastApprovedDate) {
                                                                $submittedDate = $lastApprovedDate;
                                                            }
                                                            if ($approvedDate) {
                                                                $lastApprovedDate = $approvedDate;
                                                            }

                                                            // For change requests, get comments/remark
                                                            $remark = '';
                                                            $hasRemark = false;
                                                            if ($aform->change_request_id) {
                                                                if ($ahistory->status == 1) {
                                                                    // Approved - prioritize comments
                                                                    $remark = !empty($ahistory->comments)
                                                                        ? $ahistory->comments
                                                                        : $ahistory->remark ?? '';
                                                                } elseif (
                                                                    $ahistory->status == 2 ||
                                                                    $ahistory->status == -1
                                                                ) {
                                                                    // Rejected - show rejection reason
                                                                    $remark = $ahistory->rejection_reason ?? '';
                                                                } else {
                                                                    // Pending - show remark
                                                                    $remark = $ahistory->remark ?? '';
                                                                }
                                                                // If remark is empty or contains default "Awaiting" messages, show "No"
                                                                $defaultMessages = [
                                                                    'Awaiting Line Manager approval',
                                                                    'Awaiting approval from Price Committee',
                                                                    'Awaiting approval from HEC Member',
                                                                    'Awaiting approval from IT',
                                                                    'Awaiting Price Committee approval',
                                                                    'Awaiting HEC Member approval',
                                                                    'Awaiting HEC Member approval (Line Manager request)',
                                                                    'Awaiting Price Committee approval (Line Manager request)',
                                                                ];
                                                                $isDefaultMessage = false;
                                                                foreach ($defaultMessages as $defaultMsg) {
                                                                    if (str_contains($remark, $defaultMsg)) {
                                                                        $isDefaultMessage = true;
                                                                        break;
                                                                    }
                                                                }
                                                                if (empty(trim($remark)) || $isDefaultMessage) {
                                                                    $remark = 'No';
                                                                    $hasRemark = false;
                                                                } else {
                                                                    $hasRemark = true;
                                                                }
                                                            } else {
                                                                // For other forms (ICT Access, HR, etc.)
                                                                if ($ahistory->status == -1) {
                                                                    // Rejected - show rejection reason if available
                                                                    $remark =
                                                                        $ahistory->rejection_reason ??
                                                                        ($ahistory->comments ?? 'Rejected');
                                                                    $hasRemark = !empty(trim($remark));
                                                                } elseif ($ahistory->status == 1) {
                                                                    // Approved status
                                                                    $remark =
                                                                        $ahistory->comments ??
                                                                        ($ahistory->remark ?? '');

                                                                    // If workflow is fully completed, always show "Approved" instead of "Forwarded for approval"
                                                                    if ($isWorkflowCompleted) {
                                                                        // Workflow is fully completed - replace "Forwarded for approval" with "Approved"
                                                                        if (
                                                                            empty(trim($remark)) ||
                                                                            str_contains(
                                                                                strtolower($remark),
                                                                                'forwarded for approval',
                                                                            )
                                                                        ) {
                                                                            $remark = 'Approved';
                                                                        }
                                                                    }

                                                                    // If still empty after processing, set to "Approved" for approved status
                                                                    if (empty(trim($remark))) {
                                                                        $remark = 'Approved';
                                                                    }

                                                                    $hasRemark = true;
                                                                } else {
                                                                    // Pending (status == 0) - show remark
                                                                    $remark = $ahistory->remark ?? '';
                                                                    if (!empty(trim($remark))) {
                                                                        $hasRemark = true;
                                                                    }
                                                                }
                                                            }

                                                            $approvalDetails[] = [
                                                                'role' => $role->name,
                                                                'status' => "<span class='badge bg-{$statusInfo['class']}'>{$statusInfo['text']}</span>",
                                                                'rejectionReason' => $rejectionReason,
                                                                'remark' => $remark,
                                                                'hasRemark' => $hasRemark,
                                                                'approvedDate' => $approvedDate,
                                                                'tosubmitdata' => $date,
                                                            ];
                                                        @endphp
                                                    @endif
                                                @endforeach
                                            @endforeach

                                            @foreach ($approvalDetails as $detail)
                                                <tr>
                                                    @if ($loop->first)
                                                        <td rowspan="{{ count($approvalDetails) }}">{{ $counter++ }}</td>
                                                        <td rowspan="{{ count($approvalDetails) }}">
                                                            @if ($aform->ict_request_resource_id)
                                                                ICT Access Form
                                                            @elseif ($aform->hr_form)
                                                                HR Form
                                                            @elseif ($aform->bank_form)
                                                                Bank Details Form
                                                            @elseif ($aform->heslb_form)
                                                                Loan Board Form
                                                            @elseif ($aform->nhif_form)
                                                                NHIF Form
                                                            @elseif ($aform->id_form)
                                                                ID Card Form
                                                            @elseif ($aform->change_request_id)
                                                                Change Request Form
                                                            @else
                                                                No Form
                                                            @endif
                                                        </td>
                                                    @endif
                                                    <td>{{ $roleDisplayNames[$detail['role']] ?? $detail['role'] }}</td>
                                                    <td>{!! $detail['status'] !!}</td>
                                                    <td>
                                                        @if (str_contains($detail['status'], 'Rejected'))
                                                            @if (!empty($detail['rejectionReason']))
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#rejectionReasonModal{{ $aform->id }}{{ $loop->index }}"
                                                                    title="View rejection reason">
                                                                    <i class="fas fa-eye"></i> View Reason
                                                                </button>
                                                                {{-- Rejection Reason Modal --}}
                                                                <div class="modal fade"
                                                                    id="rejectionReasonModal{{ $aform->id }}{{ $loop->index }}"
                                                                    tabindex="-1"
                                                                    aria-labelledby="rejectionReasonModalLabel{{ $aform->id }}{{ $loop->index }}"
                                                                    aria-hidden="true">
                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                        <div class="modal-content">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title"
                                                                                    id="rejectionReasonModalLabel{{ $aform->id }}{{ $loop->index }}">
                                                                                    <i class="fas fa-info-circle"></i>
                                                                                    Rejection Reason
                                                                                </h5>
                                                                                <button type="button" class="btn-close"
                                                                                    data-bs-dismiss="modal"
                                                                                    aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <p><strong>Request:</strong> Change Request
                                                                                    Form
                                                                                    #{{ $aform->change_request_id ?? 'N/A' }}
                                                                                </p>
                                                                                <p><strong>Rejected by:</strong>
                                                                                    {{ $roleDisplayNames[$detail['role']] ?? $detail['role'] }}
                                                                                </p>
                                                                                <hr>
                                                                                <p><strong>Rejection Reason:</strong></p>
                                                                                <div class="alert alert-warning">
                                                                                    {{ $detail['rejectionReason'] }}
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button"
                                                                                    class="btn btn-secondary"
                                                                                    data-bs-dismiss="modal">Close</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                No reason provided
                                                            @endif
                                                        @else
                                                            {{-- Don't show remark for approved requests --}}
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $detail['tosubmitdata'] }}</td>
                                                    <td>{{ $detail['approvedDate'] ?? 'Not Approved' }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach

                                        {{-- Display Clearance Forms --}}
                                        @foreach ($clearForm as $exit)
                                            @php
                                                $clearApprovalDetails = [];
                                                $uniqueRoles = [];
                                                $submittedDate = \Carbon\Carbon::parse($exit->created_at)->format(
                                                    'd F Y',
                                                );
                                            @endphp

                                            @foreach ($clearHistories[$exit->id] as $clearHistory)
                                                @php
                                                    $user = \App\Models\User::find($clearHistory->attended_by);
                                                    $roles = $user ? $user->roles : collect();
                                                    $date = \Carbon\Carbon::parse($clearHistory->created_at)->format(
                                                        'd F Y',
                                                    );
                                                @endphp
                                                @foreach ($roles as $role)
                                                    @if ($role->name != 'requester' && !in_array($role->name, $uniqueRoles))
                                                        @php
                                                            $uniqueRoles[] = $role->name;
                                                            $statusInfo = $defaultStatusMap[$clearHistory->status] ?? [
                                                                'class' => 'secondary',
                                                                'text' => 'Unknown',
                                                            ];
                                                            $approvedDate =
                                                                $clearHistory->status == 1
                                                                    ? \Carbon\Carbon::parse(
                                                                        $clearHistory->updated_at,
                                                                    )->format('d F Y')
                                                                    : null;
                                                            $rejectionReason =
                                                                $clearHistory->status == 2
                                                                    ? $clearHistory->rejection_reason
                                                                    : '';
                                                            $remark = $clearHistory->remark ?? '';
                                                            $hasRemark = !empty(trim($remark));

                                                            $clearApprovalDetails[] = [
                                                                'role' => $role->name,
                                                                'status' => "<span class='badge bg-{$statusInfo['class']}'>{$statusInfo['text']}</span>",
                                                                'rejectionReason' => $rejectionReason,
                                                                'remark' => $remark,
                                                                'hasRemark' => $hasRemark,
                                                                'approvedDate' => $approvedDate,
                                                                'tosubmitdata' => $date,
                                                            ];
                                                        @endphp
                                                    @endif
                                                @endforeach
                                            @endforeach

                                            @foreach ($clearApprovalDetails as $detail)
                                                <tr>
                                                    @if ($loop->first)
                                                        <td rowspan="{{ count($clearApprovalDetails) }}">
                                                            {{ $counter++ }}
                                                        </td>
                                                        <td rowspan="{{ count($clearApprovalDetails) }}">Clearance Form
                                                        </td>
                                                    @endif
                                                    <td>{{ $roleDisplayNames[$detail['role']] ?? $detail['role'] }}</td>
                                                    <td>{!! $detail['status'] !!}</td>
                                                    <td>
                                                        @if (str_contains($detail['status'], 'Rejected'))
                                                            @if (!empty($detail['rejectionReason']))
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#clearanceRejectionModal{{ $exit->id }}{{ $loop->index }}"
                                                                    title="View rejection reason">
                                                                    <i class="fas fa-eye"></i> View Reason
                                                                </button>
                                                                {{-- Clearance Rejection Reason Modal --}}
                                                                <div class="modal fade"
                                                                    id="clearanceRejectionModal{{ $exit->id }}{{ $loop->index }}"
                                                                    tabindex="-1"
                                                                    aria-labelledby="clearanceRejectionModalLabel{{ $exit->id }}{{ $loop->index }}"
                                                                    aria-hidden="true">
                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                        <div class="modal-content">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title"
                                                                                    id="clearanceRejectionModalLabel{{ $exit->id }}{{ $loop->index }}">
                                                                                    <i class="fas fa-info-circle"></i>
                                                                                    Rejection Reason
                                                                                </h5>
                                                                                <button type="button" class="btn-close"
                                                                                    data-bs-dismiss="modal"
                                                                                    aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <p><strong>Request:</strong> Clearance Form
                                                                                </p>
                                                                                <p><strong>Rejected by:</strong>
                                                                                    {{ $roleDisplayNames[$detail['role']] ?? $detail['role'] }}
                                                                                </p>
                                                                                <hr>
                                                                                <p><strong>Rejection Reason:</strong></p>
                                                                                <div class="alert alert-warning">
                                                                                    {{ $detail['rejectionReason'] }}
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button"
                                                                                    class="btn btn-secondary"
                                                                                    data-bs-dismiss="modal">Close</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                No reason provided
                                                            @endif
                                                        @else
                                                            {{-- Don't show remark for approved requests --}}
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $detail['tosubmitdata'] }}</td>
                                                    <td>{{ $detail['approvedDate'] ?? 'Not Approved Yet' }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            font-size: 0.875rem;
            padding: 0.5em 0.75em;
            border-radius: 0.2rem;
        }

        .request-row {
            border-top: 2px solid #333;
        }
    </style>
@endpush
