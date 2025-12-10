@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    @php
        // Format dates
        $submittedDate = $clearance->created_at
            ? \Carbon\Carbon::parse($clearance->created_at)->format('d F Y, H:i')
            : 'N/A';
        $dateOfHire = $clearance->date_of_hire
            ? \Carbon\Carbon::parse($clearance->date_of_hire)->format('d F Y')
            : 'N/A';
        $lastWorkingDay = $clearance->last_working_day
            ? \Carbon\Carbon::parse($clearance->last_working_day)->format('d F Y')
            : 'N/A';
        $endOfContract = $clearance->end_of_contract
            ? \Carbon\Carbon::parse($clearance->end_of_contract)->format('d F Y')
            : 'N/A';

        // Get status badge
        $statusClass = 'warning';
        $statusText = 'Pending';
        if (isset($workflow) && $workflow->work_flow_completed == 1) {
            $statusClass = 'success';
            $statusText = 'Completed';
        } elseif ($clearance->status === 'rejected') {
            $statusClass = 'danger';
            $statusText = 'Rejected';
        } elseif (isset($workflow)) {
            $statusText = $workflow->work_flow_status ?? 'Pending';
        }

        // Define isReviewingPreviousApproval
        $isReviewingPreviousApproval = false;
        if (isset($canReviewPreviousApproval) && isset($workflowCompleted) && isset($userApprovalStep)) {
            $hasViewPermission = Auth::user()->hasPermissionTo('view clearance forms');
            $isReviewingPreviousApproval = $canReviewPreviousApproval && $workflowCompleted && !$hasViewPermission;
        }
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">
            <br>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Employee Clearance Form Review</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <!-- Request Summary Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-info-circle me-2" style="color: #007A33;"></i>Request Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-2"><strong>Submitted Date:</strong> {{ $submittedDate }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-2"><strong>Status:</strong>
                                        <span class="badge badge-{{ $statusClass }}"
                                            style="font-size: 0.9rem; padding: 0.5em 0.75em; font-weight: 600;">{{ $statusText }}</span>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-2"><strong>Form Type:</strong>
                                        <span class="badge badge-info">Exit Clearance</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Staff Details Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-user-circle me-2" style="color: #007A33;"></i>Staff Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Full Name</label>
                                        <p class="mb-0"><strong>{{ $clearance->fname }} {{ $clearance->mname ?? '' }}
                                                {{ $clearance->lname }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Email</label>
                                        <p class="mb-0"><strong>{{ $clearance->email ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Mobile Number</label>
                                        <p class="mb-0"><strong>{{ $clearance->mobile ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Department</label>
                                        <p class="mb-0"><strong>{{ $clearance->dept_name ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Job Title</label>
                                        <p class="mb-0"><strong>{{ $clearance->job_title ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">CCBRT Code</label>
                                        <p class="mb-0"><strong>{{ $clearance->ccbrt_code ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employment Dates Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-calendar-alt me-2" style="color: #007A33;"></i>Employment Dates
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Date of Hire</label>
                                        <p class="mb-0"><strong>{{ $dateOfHire }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Last Working Day</label>
                                        <p class="mb-0"><strong>{{ $lastWorkingDay }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">End of Contract</label>
                                        <p class="mb-0"><strong>{{ $endOfContract }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $workflowCompleted = isset($workflow) && $workflow->work_flow_completed == 1;
                        // HR should see all sections after full approval
                        $isHRViewingCompleted = $workflowCompleted && Auth::user()->hasRole('hr');

                        // Check if user has "view clearance forms" permission (can see all sections)
                        $hasViewPermission = Auth::user()->hasPermissionTo('view clearance forms');

                        // If previous approver is reviewing after HR approval, show only their section
                        // BUT if they have "view clearance forms" permission, show all sections
                        $isReviewingPreviousApproval = false;
                        if (
                            isset($canReviewPreviousApproval) &&
                            isset($workflowCompleted) &&
                            isset($userApprovalStep)
                        ) {
                            $isReviewingPreviousApproval =
                                $canReviewPreviousApproval && $workflowCompleted && !$hasViewPermission;
                        }
                    @endphp

                    @if ($isReviewingPreviousApproval)
                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Review Mode</h6>
                            <p class="mb-0">You are reviewing your previous approval for the
                                <strong>{{ $userApprovalStep }}</strong> section. This form has been fully approved
                                by HR. You can only view the section you previously approved.
                            </p>
                        </div>
                    @endif

                    @php
                        $showOnlyMySection = false;
                        $mySection = null;
                        if ($isReviewingPreviousApproval && isset($userApprovalStep)) {
                            $showOnlyMySection = true;
                            $mySection = $userApprovalStep;
                        }

                        // Progressive disclosure based on current approval step
                        // Line Manager sees only their section
                        // Finance Officer sees Line Manager (read-only) + Finance Officer (editable)
                        // IT Officer sees Line Manager + Finance Officer (read-only) + IT Officer (editable)
                        // HR Officer sees all previous sections (read-only) + HR Officer (editable)

                        if ($showOnlyMySection) {
                            // Show only the section that matches the user's approval step (for reviewing previous approvals)
    $showLineManagerSection = $mySection === 'Line Manager';
    $showFinanceSection = $mySection === 'Finance Officer';
    $showITSection = $mySection === 'IT Officer';
    $showHRSection = false;
    $showExitInterviewSection = false;
} else {
    // Progressive disclosure based on current approval step
    if (isset($currentApprovalStep)) {
        // Line Manager: Show only Line Manager section
        if ($currentApprovalStep === 'Line Manager') {
            $showLineManagerSection = true;
            $showFinanceSection = false;
            $showITSection = false;
            $showHRSection = false;
            $showExitInterviewSection = false;
        }
        // Finance Officer: Show Line Manager (read-only) + Finance Officer (editable)
        elseif ($currentApprovalStep === 'Finance Officer') {
            $showLineManagerSection = true; // Show previous approver
            $showFinanceSection = true;
            $showITSection = false;
            $showHRSection = false;
            $showExitInterviewSection = false;
        }
        // IT Officer: Show Line Manager + Finance Officer (read-only) + IT Officer (editable)
        elseif ($currentApprovalStep === 'IT Officer') {
            $showLineManagerSection = true; // Show previous approvers
            $showFinanceSection = true; // Show previous approvers
            $showITSection = true;
            $showHRSection = false;
            $showExitInterviewSection = false;
        }
        // HR Officer: Show all previous sections (read-only) + HR Officer (editable)
        elseif ($currentApprovalStep === 'HR Officer' || $isFinalApproval) {
            $showLineManagerSection = true; // Show all previous approvers
            $showFinanceSection = true; // Show all previous approvers
            $showITSection = true; // Show all previous approvers
            $showHRSection = true;
            $showExitInterviewSection = true;
        }
        // Default: Show all sections (for users with view permission or HR viewing completed)
        else {
            $showLineManagerSection =
                (!$workflowCompleted || $isHRViewingCompleted || $hasViewPermission) &&
                ($isFinalApproval ||
                    Auth::user()->hasAnyRole(['line-manager', 'coo', 'cfo', 'cms', 'hr']) ||
                    $hasViewPermission);
            $showFinanceSection = true;
            $showITSection = true;
            $showHRSection = true;
            $showExitInterviewSection = true;
        }
    } else {
        // Normal logic for other users (including those with "view clearance forms" permission)
        $showLineManagerSection =
            (!$workflowCompleted || $isHRViewingCompleted || $hasViewPermission) &&
            ($isFinalApproval ||
                Auth::user()->hasAnyRole(['line-manager', 'coo', 'cfo', 'cms', 'hr']) ||
                                        $hasViewPermission);
                                $showFinanceSection = true;
                                $showITSection = true;
                                $showHRSection = true;
                                $showExitInterviewSection = true;
                            }
                        }
                    @endphp

                    @if ($showLineManagerSection)
                        <!-- Line Manager Section Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-clipboard-list me-2" style="color: #007A33;"></i>Line Manager Section -
                                    Items Collected by Last Day of Work
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Changing Room Keys</label>
                                            @if ($canEdit && $currentApprovalStep === 'Line Manager')
                                                <select name="changing_room_keys" class="form-control"
                                                    id="changing_room_keys">
                                                    <option value="Yes"
                                                        {{ ($clearance->changing_room_keys ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->changing_room_keys ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->changing_room_keys ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->changing_room_keys ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Office Keys</label>
                                            @if ($canEdit && $currentApprovalStep === 'Line Manager')
                                                <select name="office_keys" class="form-control" id="office_keys">
                                                    <option value="Yes"
                                                        {{ ($clearance->office_keys ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->office_keys ?? '') == 'No' ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="N/A"
                                                        {{ ($clearance->office_keys ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0"><strong>{{ $clearance->office_keys ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Mobile Phone</label>
                                            @if ($canEdit && $currentApprovalStep === 'Line Manager')
                                                <select name="mobile_phone" class="form-control" id="mobile_phone">
                                                    <option value="Yes"
                                                        {{ ($clearance->mobile_phone ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->mobile_phone ?? '') == 'No' ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="N/A"
                                                        {{ ($clearance->mobile_phone ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0"><strong>{{ $clearance->mobile_phone ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Camera</label>
                                            @if ($canEdit && $currentApprovalStep === 'Line Manager')
                                                <select name="camera" class="form-control" id="camera">
                                                    <option value="Yes"
                                                        {{ ($clearance->camera ?? '') == 'Yes' ? 'selected' : '' }}>Yes
                                                    </option>
                                                    <option value="No"
                                                        {{ ($clearance->camera ?? '') == 'No' ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="N/A"
                                                        {{ ($clearance->camera ?? '') == 'N/A' ? 'selected' : '' }}>N/A
                                                    </option>
                                                </select>
                                            @else
                                                <p class="mb-0"><strong>{{ $clearance->camera ?? 'N/A' }}</strong></p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">CCBRT Uniforms</label>
                                            @if ($canEdit && $currentApprovalStep === 'Line Manager')
                                                <select name="ccbrt_uniforms" class="form-control" id="ccbrt_uniforms">
                                                    <option value="Yes"
                                                        {{ ($clearance->ccbrt_uniforms ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->ccbrt_uniforms ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->ccbrt_uniforms ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->ccbrt_uniforms ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Office Car Keys</label>
                                            @if ($canEdit && $currentApprovalStep === 'Line Manager')
                                                <select name="office_car_keys" class="form-control" id="office_car_keys">
                                                    <option value="Yes"
                                                        {{ ($clearance->office_car_keys ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->office_car_keys ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->office_car_keys ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->office_car_keys ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="text-muted small">Any other CCBRT items given (Please
                                                specify)</label>
                                            @if ($canEdit && $currentApprovalStep === 'Line Manager')
                                                <input type="text" name="other_items" class="form-control"
                                                    id="other_items" value="{{ $clearance->other_items ?? '' }}"
                                                    placeholder="Specify other items">
                                            @else
                                                <p class="mb-0"><strong>{{ $clearance->other_items ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Line Manager Review Section Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-check-circle me-2" style="color: #007A33;"></i>Line Manager Review
                                    Section
                                </h5>
                            </div>
                            <div class="card-body">
                                @if ($currentApprovalStep === 'Line Manager')
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Handover Report Received</label>
                                                <select name="handover_report_received" class="form-control"
                                                    id="handover_report_received">
                                                    <option value="Yes"
                                                        {{ $clearance->handover_report_received == 1 || $clearance->handover_report_received === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->handover_report_received == 0 || $clearance->handover_report_received === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->handover_report_received === 'N/A' || $clearance->handover_report_received === 'Not Applicable' ? 'selected' : '' }}>
                                                        Not Applicable</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Work Responsibilities Transferred</label>
                                                <select name="work_responsibilities_transferred" class="form-control"
                                                    id="work_responsibilities_transferred">
                                                    <option value="Yes"
                                                        {{ $clearance->work_responsibilities_transferred == 1 || $clearance->work_responsibilities_transferred === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->work_responsibilities_transferred == 0 || $clearance->work_responsibilities_transferred === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->work_responsibilities_transferred === 'N/A' ? 'selected' : '' }}>
                                                        Not Applicable</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Projects/Tasks Closed</label>
                                                <select name="projects_tasks_closed" class="form-control"
                                                    id="projects_tasks_closed">
                                                    <option value="Yes"
                                                        {{ $clearance->projects_tasks_closed == 1 || $clearance->projects_tasks_closed === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->projects_tasks_closed == 0 || $clearance->projects_tasks_closed === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->projects_tasks_closed === 'N/A' ? 'selected' : '' }}>
                                                        Not Applicable</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="text-muted small">Comments/Notes</label>
                                                <textarea name="line_manager_comments" class="form-control" id="line_manager_comments" rows="3"
                                                    placeholder="Add any comments or notes (e.g., user did not return office key)">{{ $clearance->line_manager_comments ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    {{-- Show read-only fields for subsequent approvers --}}
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Handover Report Received</label>
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->handover_report_received == 1 || $clearance->handover_report_received === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->handover_report_received == 0 || $clearance->handover_report_received === 'No')
                                                            No
                                                        @elseif ($clearance->handover_report_received === 'N/A' || $clearance->handover_report_received === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->handover_report_received ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Work Responsibilities Transferred</label>
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->work_responsibilities_transferred == 1 || $clearance->work_responsibilities_transferred === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->work_responsibilities_transferred == 0 || $clearance->work_responsibilities_transferred === 'No')
                                                            No
                                                        @elseif (
                                                            $clearance->work_responsibilities_transferred === 'N/A' ||
                                                                $clearance->work_responsibilities_transferred === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->work_responsibilities_transferred ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Projects/Tasks Closed</label>
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->projects_tasks_closed == 1 || $clearance->projects_tasks_closed === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->projects_tasks_closed == 0 || $clearance->projects_tasks_closed === 'No')
                                                            No
                                                        @elseif ($clearance->projects_tasks_closed === 'N/A' || $clearance->projects_tasks_closed === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->projects_tasks_closed ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="text-muted small">Comments/Notes</label>
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->line_manager_comments ?? 'N/A' }}</strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @php
                                        $lmHistory = null;
                                        if (isset($allApproversWithDetails)) {
                                            $lmHistory = $allApproversWithDetails->firstWhere(
                                                'step_name',
                                                'Line Manager',
                                            );
                                        }
                                        $hasLMFields =
                                            !empty($clearance->line_manager_comments) ||
                                            isset($clearance->handover_report_received) ||
                                            isset($clearance->work_responsibilities_transferred) ||
                                            isset($clearance->projects_tasks_closed);
                                        $showLMSignature =
                                            ($lmHistory &&
                                                isset($lmHistory['approver_name']) &&
                                                $lmHistory['approver_name'] !== 'N/A') ||
                                            $hasLMFields;
                                        $lmName = 'N/A';
                                        $lmDate = null;
                                        if (
                                            $lmHistory &&
                                            isset($lmHistory['approver_name']) &&
                                            $lmHistory['approver_name'] !== 'N/A'
                                        ) {
                                            $lmName = $lmHistory['approver_name'];
                                            $lmDate = $lmHistory['approval_date'] ?? null;
                                        } elseif ($hasLMFields && isset($clearance->user)) {
                                            $lmName = trim(
                                                ($clearance->user->fname ?? '') . ' ' . ($clearance->user->lname ?? ''),
                                            );
                                            $lmDate = $clearance->created_at ?? null;
                                        }
                                        // Show approval details if approved, or show pending status if section is shown but not yet approved
                                        $showLMApprovalDetails =
                                            $showLMSignature ||
                                            ($showLineManagerSection && $currentApprovalStep !== 'Line Manager');
                                    @endphp
                                    @if ($showLMApprovalDetails)
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="mb-3 p-3"
                                                    style="background-color: #f8f9fa; border-left: 4px solid #007A33; border-radius: 4px;">
                                                    @if ($showLMSignature)
                                                        <p class="mb-2"><strong>I confirm receiving the above
                                                                items.</strong></p>
                                                        <p class="mb-1"><strong>Full Name:</strong> {{ $lmName }}
                                                        </p>
                                                        @if ($lmHistory && isset($lmHistory['signature']) && $lmHistory['signature'])
                                                            <p class="mb-1"><strong>Signature:</strong></p>
                                                            <img src="data:image/png;base64,{{ $lmHistory['signature'] }}"
                                                                alt="Line Manager Signature"
                                                                style="max-width: 200px; height: auto;">
                                                        @endif
                                                        <p class="mb-0"><strong>Date:</strong>
                                                            {{ $lmDate ? \Carbon\Carbon::parse($lmDate)->format('d F Y') : 'N/A' }}
                                                        </p>
                                                    @else
                                                        <p class="mb-0 text-muted"><i
                                                                class="fas fa-clock me-2"></i>Pending Line Manager approval
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    @php
                        $isHRViewingCompleted = $workflowCompleted && Auth::user()->hasRole('hr');
                        if (!isset($showFinanceSection)) {
                            $hasViewPermission = Auth::user()->hasPermissionTo('view clearance forms');
                            $showFinanceSection =
                                (!$workflowCompleted || $isHRViewingCompleted || $hasViewPermission) &&
                                ($isFinalApproval ||
                                    Auth::user()->hasAnyRole(['finance officer', 'hr']) ||
                                    $hasViewPermission);
                        }
                    @endphp
                    @if ($showFinanceSection)
                        <!-- Finance Officer Section Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-money-check-alt me-2" style="color: #007A33;"></i>Finance Officer
                                    Section - Finance Confirmation
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Repaid advance on Salary?</label>
                                            @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                                <select name="repaid_salary_advance" class="form-control"
                                                    id="repaid_salary_advance">
                                                    <option value="Yes"
                                                        {{ ($clearance->repaid_salary_advance ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->repaid_salary_advance ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->repaid_salary_advance ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->repaid_salary_advance ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Staff informed Finance of outstanding loan
                                                balances?</label>
                                            @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                                <select name="loan_balances_informed" class="form-control"
                                                    id="loan_balances_informed">
                                                    <option value="Yes"
                                                        {{ ($clearance->loan_balances_informed ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->loan_balances_informed ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->loan_balances_informed ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->loan_balances_informed ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Repaid any outstanding imprest?</label>
                                            @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                                <select name="repaid_outstanding_imprest" class="form-control"
                                                    id="repaid_outstanding_imprest">
                                                    <option value="Yes"
                                                        {{ ($clearance->repaid_outstanding_imprest ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->repaid_outstanding_imprest ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->repaid_outstanding_imprest ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->repaid_outstanding_imprest ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Finance Officer Review Section Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-check-circle me-2" style="color: #007A33;"></i>Finance Officer Review
                                    Section
                                </h5>
                            </div>
                            <div class="card-body">
                                @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">All Salary Advances Cleared</label>
                                                <select name="all_salary_advances_cleared" class="form-control"
                                                    id="all_salary_advances_cleared">
                                                    <option value="Yes"
                                                        {{ $clearance->all_salary_advances_cleared == 1 || $clearance->all_salary_advances_cleared === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->all_salary_advances_cleared == 0 || $clearance->all_salary_advances_cleared === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->all_salary_advances_cleared === 'N/A' ? 'selected' : '' }}>
                                                        Not Applicable</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Allowances Reconciled</label>
                                                <select name="allowances_reconciled" class="form-control"
                                                    id="allowances_reconciled">
                                                    <option value="Yes"
                                                        {{ $clearance->allowances_reconciled == 1 || $clearance->allowances_reconciled === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->allowances_reconciled == 0 || $clearance->allowances_reconciled === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->allowances_reconciled === 'N/A' ? 'selected' : '' }}>
                                                        Not Applicable</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Pending Claims Settled</label>
                                                <select name="pending_claims_settled" class="form-control"
                                                    id="pending_claims_settled">
                                                    <option value="Yes"
                                                        {{ $clearance->pending_claims_settled == 1 || $clearance->pending_claims_settled === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->pending_claims_settled == 0 || $clearance->pending_claims_settled === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->pending_claims_settled === 'N/A' ? 'selected' : '' }}>
                                                        Not Applicable</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Outstanding Loans Recovered</label>
                                                <select name="outstanding_loans_recovered" class="form-control"
                                                    id="outstanding_loans_recovered">
                                                    <option value="Yes"
                                                        {{ $clearance->outstanding_loans_recovered == 1 || $clearance->outstanding_loans_recovered === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->outstanding_loans_recovered == 0 || $clearance->outstanding_loans_recovered === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->outstanding_loans_recovered === 'N/A' ? 'selected' : '' }}>
                                                        Not Applicable</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Employee Eligible for Final Payment</label>
                                                <select name="employee_eligible_for_final_payment" class="form-control"
                                                    id="employee_eligible_for_final_payment">
                                                    <option value="Yes"
                                                        {{ $clearance->employee_eligible_for_final_payment == 1 || $clearance->employee_eligible_for_final_payment === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->employee_eligible_for_final_payment == 0 || $clearance->employee_eligible_for_final_payment === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->employee_eligible_for_final_payment === 'N/A' ? 'selected' : '' }}>
                                                        Not Applicable</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="text-muted small">Comments/Notes</label>
                                                <textarea name="finance_comments" class="form-control" id="finance_comments" rows="3"
                                                    placeholder="Add any comments or notes">{{ $clearance->finance_comments ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    {{-- Show read-only fields for subsequent approvers --}}
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">All Salary Advances Cleared</label>
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->all_salary_advances_cleared == 1 || $clearance->all_salary_advances_cleared === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->all_salary_advances_cleared == 0 || $clearance->all_salary_advances_cleared === 'No')
                                                            No
                                                        @elseif ($clearance->all_salary_advances_cleared === 'N/A' || $clearance->all_salary_advances_cleared === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->all_salary_advances_cleared ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Allowances Reconciled</label>
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->allowances_reconciled == 1 || $clearance->allowances_reconciled === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->allowances_reconciled == 0 || $clearance->allowances_reconciled === 'No')
                                                            No
                                                        @elseif ($clearance->allowances_reconciled === 'N/A' || $clearance->allowances_reconciled === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->allowances_reconciled ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Pending Claims Settled</label>
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->pending_claims_settled == 1 || $clearance->pending_claims_settled === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->pending_claims_settled == 0 || $clearance->pending_claims_settled === 'No')
                                                            No
                                                        @elseif ($clearance->pending_claims_settled === 'N/A' || $clearance->pending_claims_settled === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->pending_claims_settled ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Outstanding Loans Recovered</label>
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->outstanding_loans_recovered == 1 || $clearance->outstanding_loans_recovered === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->outstanding_loans_recovered == 0 || $clearance->outstanding_loans_recovered === 'No')
                                                            No
                                                        @elseif ($clearance->outstanding_loans_recovered === 'N/A' || $clearance->outstanding_loans_recovered === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->outstanding_loans_recovered ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">Employee Eligible for Final Payment</label>
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->employee_eligible_for_final_payment == 1 || $clearance->employee_eligible_for_final_payment === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->employee_eligible_for_final_payment == 0 || $clearance->employee_eligible_for_final_payment === 'No')
                                                            No
                                                        @elseif (
                                                            $clearance->employee_eligible_for_final_payment === 'N/A' ||
                                                                $clearance->employee_eligible_for_final_payment === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->employee_eligible_for_final_payment ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="text-muted small">Comments/Notes</label>
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->finance_comments ?? 'N/A' }}</strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @php
                                        $foHistory = null;
                                        if (isset($allApproversWithDetails)) {
                                            $foHistory = $allApproversWithDetails->firstWhere(
                                                'step_name',
                                                'Finance Officer',
                                            );
                                        }
                                        $showFOSignature =
                                            $foHistory &&
                                            isset($foHistory['approver_name']) &&
                                            $foHistory['approver_name'] !== 'N/A';
                                    @endphp
                                    @if ($showFOSignature)
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="mb-3 p-3"
                                                    style="background-color: #f8f9fa; border-left: 4px solid #007A33; border-radius: 4px;">
                                                    <p class="mb-2"><strong>I confirm the above finance details.</strong>
                                                    </p>
                                                    <p class="mb-1"><strong>Full Name:</strong>
                                                        {{ $foHistory['approver_name'] ?? 'N/A' }}</p>
                                                    @if ($foHistory && isset($foHistory['signature']) && $foHistory['signature'])
                                                        <p class="mb-1"><strong>Signature:</strong></p>
                                                        <img src="data:image/png;base64,{{ $foHistory['signature'] }}"
                                                            alt="Finance Officer Signature"
                                                            style="max-width: 200px; height: auto;">
                                                    @endif
                                                    <p class="mb-0"><strong>Date:</strong>
                                                        {{ isset($foHistory['approval_date']) ? \Carbon\Carbon::parse($foHistory['approval_date'])->format('d F Y') : 'N/A' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    @php
                        $isHRViewingCompleted = $workflowCompleted && Auth::user()->hasRole('hr');
                        if (!isset($showITSection)) {
                            $hasViewPermission = Auth::user()->hasPermissionTo('view clearance forms');
                            $showITSection =
                                (!$workflowCompleted || $isHRViewingCompleted || $hasViewPermission) &&
                                ($isFinalApproval || Auth::user()->hasAnyRole(['it', 'hr']) || $hasViewPermission);
                        }
                    @endphp
                    @if ($showITSection)
                        <!-- IT Officer Section Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-laptop me-2" style="color: #007A33;"></i>IT Officer Section - ICT
                                    Checklist
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Keyboard & Mouse Returned?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="keyboard_mouse_returned" class="form-control"
                                                    id="keyboard_mouse_returned">
                                                    <option value="Yes"
                                                        {{ ($clearance->keyboard_mouse_returned ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->keyboard_mouse_returned ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->keyboard_mouse_returned ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->keyboard_mouse_returned ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Phone Returned?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="phone_returned" class="form-control" id="phone_returned">
                                                    <option value="Yes"
                                                        {{ ($clearance->phone_returned ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->phone_returned ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->phone_returned ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->phone_returned ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">ID Card Returned?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="id_card_returned" class="form-control"
                                                    id="id_card_returned">
                                                    <option value="Yes"
                                                        {{ ($clearance->id_card_returned ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->id_card_returned ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->id_card_returned ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->id_card_returned ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Laptop/iPad & Accessories & Gate Pass
                                                Returned?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="laptop_returned" class="form-control" id="laptop_returned">
                                                    <option value="Yes"
                                                        {{ ($clearance->laptop_returned ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->laptop_returned ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->laptop_returned ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->laptop_returned ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Access Card Returned?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="access_card_returned" class="form-control"
                                                    id="access_card_returned">
                                                    <option value="Yes"
                                                        {{ ($clearance->access_card_returned ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->access_card_returned ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->access_card_returned ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->access_card_returned ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <h6 class="text-muted mb-3" style="color: #007A33;"><i
                                                class="fas fa-server me-2"></i>ICT Systems</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Domain Account Disabled?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="domain_account_disabled" class="form-control"
                                                    id="domain_account_disabled">
                                                    <option value="Yes"
                                                        {{ ($clearance->domain_account_disabled ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->domain_account_disabled ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->domain_account_disabled ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->domain_account_disabled ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Email Account Disabled?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="email_account_disabled" class="form-control"
                                                    id="email_account_disabled">
                                                    <option value="Yes"
                                                        {{ ($clearance->email_account_disabled ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->email_account_disabled ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->email_account_disabled ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->email_account_disabled ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Telephone Pin Code Disabled?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="telephone_pin_disabled" class="form-control"
                                                    id="telephone_pin_disabled">
                                                    <option value="Yes"
                                                        {{ ($clearance->telephone_pin_disabled ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->telephone_pin_disabled ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->telephone_pin_disabled ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->telephone_pin_disabled ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Health AI Disabled?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="health_ai_disabled" class="form-control"
                                                    id="health_ai_disabled">
                                                    <option value="Yes"
                                                        {{ ($clearance->health_ai_disabled ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->health_ai_disabled ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->health_ai_disabled ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->health_ai_disabled ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">SAP Account Disabled?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="sap_account_disabled" class="form-control"
                                                    id="sap_account_disabled">
                                                    <option value="Yes"
                                                        {{ ($clearance->sap_account_disabled ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->sap_account_disabled ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->sap_account_disabled ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->sap_account_disabled ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Aruti Account Disabled?</label>
                                            @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                                <select name="aruti_account_disabled" class="form-control"
                                                    id="aruti_account_disabled">
                                                    <option value="Yes"
                                                        {{ ($clearance->aruti_account_disabled ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->aruti_account_disabled ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->aruti_account_disabled ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->aruti_account_disabled ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IT Officer Review Section Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-check-circle me-2" style="color: #007A33;"></i>IT Officer Review
                                    Section
                                </h5>
                            </div>
                            <div class="card-body">
                                @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">IT Ticket No</label>
                                                <input type="text" name="it_ticket_no" class="form-control"
                                                    id="it_ticket_no" value="{{ $clearance->it_ticket_no ?? '' }}"
                                                    placeholder="Enter IT ticket number">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="text-muted small">Comments/Notes</label>
                                                <textarea name="it_comments" class="form-control" id="it_comments" rows="3"
                                                    placeholder="Add any comments or notes">{{ $clearance->it_comments ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    {{-- Show read-only fields for subsequent approvers --}}
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small">IT Ticket No</label>
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->it_ticket_no ?? 'N/A' }}</strong>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="text-muted small">Comments/Notes</label>
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->it_comments ?? 'N/A' }}</strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @php
                                        $itHistory = null;
                                        if (isset($allApproversWithDetails)) {
                                            $itHistory = $allApproversWithDetails->firstWhere(
                                                'step_name',
                                                'IT Officer',
                                            );
                                        }
                                        $showITSignature =
                                            $itHistory &&
                                            isset($itHistory['approver_name']) &&
                                            $itHistory['approver_name'] !== 'N/A';
                                    @endphp
                                    @if ($showITSignature)
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="mb-3 p-3"
                                                    style="background-color: #f8f9fa; border-left: 4px solid #007A33; border-radius: 4px;">
                                                    <p class="mb-2"><strong>I confirm the above ICT items have been
                                                            processed.</strong></p>
                                                    <p class="mb-1"><strong>Full Name:</strong>
                                                        {{ $itHistory['approver_name'] ?? 'N/A' }}</p>
                                                    @if ($itHistory && isset($itHistory['signature']) && $itHistory['signature'])
                                                        <p class="mb-1"><strong>Signature:</strong></p>
                                                        <img src="data:image/png;base64,{{ $itHistory['signature'] }}"
                                                            alt="IT Officer Signature"
                                                            style="max-width: 200px; height: auto;">
                                                    @endif
                                                    <p class="mb-0"><strong>Date:</strong>
                                                        {{ isset($itHistory['approval_date']) ? \Carbon\Carbon::parse($itHistory['approval_date'])->format('d F Y') : 'N/A' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    @php
                        $isHRViewingCompleted = $workflowCompleted && Auth::user()->hasRole('hr');
                        if (!isset($showHRSection)) {
                            $hasViewPermission = Auth::user()->hasPermissionTo('view clearance forms');
                            $showHRSection =
                                (!$workflowCompleted || $isHRViewingCompleted || $hasViewPermission) &&
                                ($isFinalApproval || Auth::user()->hasAnyRole(['hr']) || $hasViewPermission);
                        }
                    @endphp
                    @if ($showHRSection)
                        <!-- HR Section Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-user-tie me-2" style="color: #007A33;"></i>HR Section - HR
                                    Confirmation
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Resignation Letter Received</label>
                                            @php
                                                $canEditHRItems =
                                                    ($canEdit && $currentApprovalStep === 'HR Officer') ||
                                                    ($isFinalApproval && Auth::user()->hasRole('hr'));
                                            @endphp
                                            @if ($canEditHRItems)
                                                <select name="resignation_letter_received" class="form-control"
                                                    id="resignation_letter_received">
                                                    <option value="Yes"
                                                        {{ $clearance->resignation_letter_received == 1 || $clearance->resignation_letter_received === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->resignation_letter_received == 0 || $clearance->resignation_letter_received === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->resignation_letter_received === 'N/A' || $clearance->resignation_letter_received === 'Not Applicable' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->resignation_letter_received == 1 || $clearance->resignation_letter_received === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->resignation_letter_received == 0 || $clearance->resignation_letter_received === 'No')
                                                            No
                                                        @elseif ($clearance->resignation_letter_received === 'N/A' || $clearance->resignation_letter_received === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->resignation_letter_received ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Exit Interview Completed</label>
                                            @if ($canEditHRItems)
                                                <select name="exit_interview_completed" class="form-control"
                                                    id="exit_interview_completed">
                                                    <option value="Yes"
                                                        {{ $clearance->exit_interview_completed == 1 || $clearance->exit_interview_completed === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->exit_interview_completed == 0 || $clearance->exit_interview_completed === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->exit_interview_completed === 'N/A' || $clearance->exit_interview_completed === 'Not Applicable' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->exit_interview_completed == 1 || $clearance->exit_interview_completed === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->exit_interview_completed == 0 || $clearance->exit_interview_completed === 'No')
                                                            No
                                                        @elseif ($clearance->exit_interview_completed === 'N/A' || $clearance->exit_interview_completed === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->exit_interview_completed ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Leave Balance Confirmed</label>
                                            @if ($canEditHRItems)
                                                <select name="leave_balance_confirmed" class="form-control"
                                                    id="leave_balance_confirmed">
                                                    <option value="Yes"
                                                        {{ $clearance->leave_balance_confirmed == 1 || $clearance->leave_balance_confirmed === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->leave_balance_confirmed == 0 || $clearance->leave_balance_confirmed === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->leave_balance_confirmed === 'N/A' || $clearance->leave_balance_confirmed === 'Not Applicable' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->leave_balance_confirmed == 1 || $clearance->leave_balance_confirmed === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->leave_balance_confirmed == 0 || $clearance->leave_balance_confirmed === 'No')
                                                            No
                                                        @elseif ($clearance->leave_balance_confirmed === 'N/A' || $clearance->leave_balance_confirmed === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->leave_balance_confirmed ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Contract File Reviewed</label>
                                            @if ($canEditHRItems)
                                                <select name="contract_file_reviewed" class="form-control"
                                                    id="contract_file_reviewed">
                                                    <option value="Yes"
                                                        {{ $clearance->contract_file_reviewed == 1 || $clearance->contract_file_reviewed === 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ $clearance->contract_file_reviewed == 0 || $clearance->contract_file_reviewed === 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ $clearance->contract_file_reviewed === 'N/A' || $clearance->contract_file_reviewed === 'Not Applicable' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>
                                                        @if ($clearance->contract_file_reviewed == 1 || $clearance->contract_file_reviewed === 'Yes')
                                                            Yes
                                                        @elseif ($clearance->contract_file_reviewed == 0 || $clearance->contract_file_reviewed === 'No')
                                                            No
                                                        @elseif ($clearance->contract_file_reviewed === 'N/A' || $clearance->contract_file_reviewed === 'Not Applicable')
                                                            N/A
                                                        @else
                                                            {{ $clearance->contract_file_reviewed ?? 'N/A' }}
                                                        @endif
                                                    </strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">CCBRT Identification Card</label>
                                            @if ($canEditHRItems)
                                                <select name="ccbrt_id_card" class="form-control" id="ccbrt_id_card">
                                                    <option value="Yes"
                                                        {{ ($clearance->ccbrt_id_card ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->ccbrt_id_card ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->ccbrt_id_card ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->ccbrt_id_card ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">CCBRT Name Tag</label>
                                            @if ($canEditHRItems)
                                                <select name="ccbrt_name_tag" class="form-control" id="ccbrt_name_tag">
                                                    <option value="Yes"
                                                        {{ ($clearance->ccbrt_name_tag ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->ccbrt_name_tag ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->ccbrt_name_tag ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->ccbrt_name_tag ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">NHIF Cards (Including dependents'
                                                cards)</label>
                                            @if ($canEditHRItems)
                                                <select name="nhif_cards" class="form-control" id="nhif_cards">
                                                    <option value="Yes"
                                                        {{ ($clearance->nhif_cards ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->nhif_cards ?? '') == 'No' ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="N/A"
                                                        {{ ($clearance->nhif_cards ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0"><strong>{{ $clearance->nhif_cards ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Work Permit Cancelled (for
                                                non-Tanzanians)</label>
                                            @if ($canEditHRItems)
                                                <select name="work_permit_cancelled" class="form-control"
                                                    id="work_permit_cancelled">
                                                    <option value="Yes"
                                                        {{ ($clearance->work_permit_cancelled ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->work_permit_cancelled ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->work_permit_cancelled ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->work_permit_cancelled ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Residence Permit Cancelled (for
                                                non-Tanzanians)</label>
                                            @if ($canEditHRItems)
                                                <select name="residence_permit_cancelled" class="form-control"
                                                    id="residence_permit_cancelled">
                                                    <option value="Yes"
                                                        {{ ($clearance->residence_permit_cancelled ?? '') == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ ($clearance->residence_permit_cancelled ?? '') == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                    <option value="N/A"
                                                        {{ ($clearance->residence_permit_cancelled ?? '') == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                </select>
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $clearance->residence_permit_cancelled ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- HR Review Section Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-check-circle me-2" style="color: #007A33;"></i>HR Review Section
                                </h5>
                            </div>
                            <div class="card-body">
                                @php
                                    $canEditHRItems =
                                        ($canEdit && $currentApprovalStep === 'HR Officer') ||
                                        ($isFinalApproval && Auth::user()->hasRole('hr'));
                                @endphp
                                @if ($canEditHRItems)
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="text-muted small">Comments/Notes</label>
                                                <textarea name="hr_manager_comments" class="form-control" id="hr_manager_comments" rows="3"
                                                    placeholder="Add any comments or notes">{{ $clearance->hr_manager_comments ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @php
                                        $hrHistory = null;
                                        if (isset($allApproversWithDetails)) {
                                            $hrHistory = $allApproversWithDetails->firstWhere(
                                                'step_name',
                                                'HR Officer',
                                            );
                                        }
                                        $showHRSignature =
                                            $hrHistory &&
                                            isset($hrHistory['approver_name']) &&
                                            $hrHistory['approver_name'] !== 'N/A';
                                    @endphp
                                    @if ($showHRSignature)
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3 p-3"
                                                    style="background-color: #f8f9fa; border-left: 4px solid #007A33; border-radius: 4px;">
                                                    <p class="mb-2"><strong>I confirm the above HR items have been
                                                            processed.</strong></p>
                                                    <p class="mb-1"><strong>Full Name:</strong>
                                                        {{ $hrHistory['approver_name'] ?? 'N/A' }}</p>
                                                    @if ($hrHistory && isset($hrHistory['signature']) && $hrHistory['signature'])
                                                        <p class="mb-1"><strong>Signature:</strong></p>
                                                        <img src="data:image/png;base64,{{ $hrHistory['signature'] }}"
                                                            alt="HR Officer Signature"
                                                            style="max-width: 200px; height: auto;">
                                                    @endif
                                                    <p class="mb-0"><strong>Date:</strong>
                                                        {{ isset($hrHistory['approval_date']) ? \Carbon\Carbon::parse($hrHistory['approval_date'])->format('d F Y') : 'N/A' }}
                                                    </p>
                                                    @if (!empty($clearance->hr_manager_comments))
                                                        <div class="mt-3">
                                                            <strong>Comments:</strong>
                                                            <p class="mb-0">{{ $clearance->hr_manager_comments }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Exit Interview Questions Section (Visible to HR and during approval, but not if LM created for staff) -->
                    @php
                        // If reviewing previous approval, use the variable set earlier, otherwise use normal logic
                        if (!isset($showExitInterviewSection)) {
                            $hasViewPermission = Auth::user()->hasPermissionTo('view clearance forms');
                            // Check if form was created by line manager for staff
                            $wasCreatedByLM = false;
                            if (isset($workflow) && $workflow) {
                                // Check for Line Manager Submission step (when LM creates form)
                                $lmSubmissionHistory = \App\Models\Clearance_work_flow_history::where(
                                    'work_flow_id',
                                    $workflow->id,
                                )
                                    ->where('step_name', 'Line Manager Submission')
                                    ->where('status', 1)
                                    ->first();

                                // Also check if LM created it (forwarded_by is LM, not the employee)
                                if ($lmSubmissionHistory) {
                                    $wasCreatedByLM = $lmSubmissionHistory->forwarded_by != $clearance->userId;
                                } else {
                                    // Fallback: check if first history entry was created by LM
                                    $firstHistory = \App\Models\Clearance_work_flow_history::where(
                                        'work_flow_id',
                                        $workflow->id,
                                    )
                                        ->where('step_name', 'Line Manager')
                                        ->where('status', 1)
                                        ->where('forwarded_by', '!=', $clearance->userId)
                                        ->first();
                                    $wasCreatedByLM =
                                        $firstHistory && $firstHistory->forwarded_by != $clearance->userId;
                                }
                            }

                            $showExitInterviewSection =
                                !$wasCreatedByLM &&
                                ($isFinalApproval ||
                                    Auth::user()->hasRole('hr') ||
                                    $workflowCompleted ||
                                    $currentApprovalStep === 'HR Officer' ||
                                    $hasViewPermission);
                        }
                        $exitReasonArray = [];
                        if (!empty($clearance->exit_reason)) {
                            if (is_string($clearance->exit_reason)) {
                                $exitReasonArray = json_decode($clearance->exit_reason, true) ?? [];
                            } else {
                                $exitReasonArray = is_array($clearance->exit_reason) ? $clearance->exit_reason : [];
                            }
                        }
                    @endphp
                    @if ($showExitInterviewSection)
                        <!-- Exit Interview Questions Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-comments me-2" style="color: #007A33;"></i>Exit Interview Questions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="text-muted small">Primary Reason for Exit</label>
                                            @if (!empty($exitReasonArray) && is_array($exitReasonArray))
                                                <ul class="mb-0">
                                                    @foreach ($exitReasonArray as $reason)
                                                        <li><strong>{{ $reason }}</strong></li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="mb-0"><strong class="text-muted">Not provided</strong></p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="text-muted small">Additional Details</label>
                                            <p class="mb-0">
                                                <strong>{{ !empty($clearance->exit_explanation) ? $clearance->exit_explanation : 'Not provided' }}</strong>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="text-muted small">Suggestions for Improvement</label>
                                            <p class="mb-0">
                                                <strong>{{ !empty($clearance->suggestions) ? $clearance->suggestions : 'Not provided' }}</strong>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="text-muted small">Would Recommend CCBRT</label>
                                            <p class="mb-0">
                                                <strong>{{ !empty($clearance->would_recommend) ? $clearance->would_recommend : 'Not provided' }}</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($isFinalApproval)
                        <!-- Final Approval - HR Review Details Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-clipboard-check me-2" style="color: #007A33;"></i>HR Department -
                                    Review Details
                                </h5>
                                <small class="text-muted">All approvals have been completed. Please review all details
                                    below and approve or reject.</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Office Keys Returned</label>
                                            <select name="office_keys_returned" class="form-control"
                                                id="office_keys_returned">
                                                <option value="Yes"
                                                    {{ $clearance->office_keys_returned == 1 || $clearance->office_keys_returned === 'Yes' ? 'selected' : '' }}>
                                                    Yes</option>
                                                <option value="No"
                                                    {{ $clearance->office_keys_returned == 0 || $clearance->office_keys_returned === 'No' ? 'selected' : '' }}>
                                                    No</option>
                                                <option value="N/A"
                                                    {{ $clearance->office_keys_returned === 'N/A' ? 'selected' : '' }}>Not
                                                    Applicable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Uniform/PPE Returned</label>
                                            <select name="uniform_ppe_returned" class="form-control"
                                                id="uniform_ppe_returned">
                                                <option value="Yes"
                                                    {{ $clearance->uniform_ppe_returned == 1 || $clearance->uniform_ppe_returned === 'Yes' ? 'selected' : '' }}>
                                                    Yes</option>
                                                <option value="No"
                                                    {{ $clearance->uniform_ppe_returned == 0 || $clearance->uniform_ppe_returned === 'No' ? 'selected' : '' }}>
                                                    No</option>
                                                <option value="N/A"
                                                    {{ $clearance->uniform_ppe_returned === 'N/A' ? 'selected' : '' }}>Not
                                                    Applicable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Tools & Equipment Returned</label>
                                            <select name="tools_equipment_returned" class="form-control"
                                                id="tools_equipment_returned">
                                                <option value="Yes"
                                                    {{ $clearance->tools_equipment_returned == 1 || $clearance->tools_equipment_returned === 'Yes' ? 'selected' : '' }}>
                                                    Yes</option>
                                                <option value="No"
                                                    {{ $clearance->tools_equipment_returned == 0 || $clearance->tools_equipment_returned === 'No' ? 'selected' : '' }}>
                                                    No</option>
                                                <option value="N/A"
                                                    {{ $clearance->tools_equipment_returned === 'N/A' ? 'selected' : '' }}>
                                                    Not Applicable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Vehicle Clearance</label>
                                            <select name="vehicle_clearance" class="form-control" id="vehicle_clearance">
                                                <option value="Yes"
                                                    {{ $clearance->vehicle_clearance == 1 || $clearance->vehicle_clearance === 'Yes' ? 'selected' : '' }}>
                                                    Yes</option>
                                                <option value="No"
                                                    {{ $clearance->vehicle_clearance == 0 || $clearance->vehicle_clearance === 'No' ? 'selected' : '' }}>
                                                    No</option>
                                                <option value="N/A"
                                                    {{ $clearance->vehicle_clearance === 'N/A' ? 'selected' : '' }}>Not
                                                    Applicable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Reason for Leaving <span
                                                    class="text-danger">*</span></label>
                                            <select name="reason_for_leaving" class="form-control"
                                                id="reason_for_leaving" required>
                                                <option value="" disabled
                                                    {{ !$clearance->reason_for_leaving ? 'selected' : '' }}>--- Select
                                                    Reason ---</option>
                                                <option value="Resignation"
                                                    {{ $clearance->reason_for_leaving == 'Resignation' ? 'selected' : '' }}>
                                                    Resignation</option>
                                                <option value="Termination"
                                                    {{ $clearance->reason_for_leaving == 'Termination' ? 'selected' : '' }}>
                                                    Termination</option>
                                                <option value="Contract End"
                                                    {{ $clearance->reason_for_leaving == 'Contract End' ? 'selected' : '' }}>
                                                    Contract End</option>
                                                <option value="Retirement"
                                                    {{ $clearance->reason_for_leaving == 'Retirement' ? 'selected' : '' }}>
                                                    Retirement</option>
                                                <option value="Other"
                                                    {{ $clearance->reason_for_leaving == 'Other' ? 'selected' : '' }}>
                                                    Other</option>
                                            </select>
                                            <div id="reason_other_div"
                                                style="display: {{ $clearance->reason_for_leaving == 'Other' ? 'block' : 'none' }}; margin-top: 10px;">
                                                <input type="text" name="reason_for_leaving_other"
                                                    id="reason_for_leaving_other" class="form-control"
                                                    value="{{ $clearance->reason_for_leaving_other ?? '' }}"
                                                    placeholder="Please specify other reason">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="text-muted small">Comments/Notes</label>
                                            <textarea name="hr_comments" class="form-control" id="hr_comments" rows="3"
                                                placeholder="Add any comments or notes">{{ $clearance->hr_comments ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($workflowCompleted)
                        <div
                            style="width: 100%; margin: 20px 0; padding: 15px; background-color: #d4edda; border-left: 4px solid #28a745; border-radius: 4px;">
                            <strong style="color: #155724;">✓ Form Completed and Approved</strong>
                            <p class="mb-0" style="font-size: 0.9rem; color: #155724; margin-top: 5px;">
                                This clearance form has been fully approved. @if (Auth::user()->hasRole('hr'))
                                    All sections are displayed above for your review.
                                @else
                                    Only HR sections are displayed above for reference purposes.
                                @endif
                            </p>
                            @if (Auth::user()->hasRole('hr'))
                                <div style="margin-top: 15px;">
                                    <a href="{{ route('clearance.download', $clearance->access_id) }}"
                                        class="btn btn-primary"
                                        style="background-color: #007A33; border: none; color: white; padding: 10px 20px; font-size: 14px; font-weight: 600; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-download"></i>
                                        <span>Download PDF</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    @if (($canEdit || $isFinalApproval) && !$isReviewingPreviousApproval)
                        <div class="buttons-container"
                            style="margin-top: 15px; display: flex; flex-direction: column; align-items: flex-end; gap: 15px; padding-right: 3%;">
                            @if ($isFinalApproval)
                                <div
                                    style="width: 100%; margin-bottom: 10px; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #007A33; border-radius: 4px;">
                                    <strong>Final Approval Review</strong>
                                    <p class="mb-0" style="font-size: 0.9rem; color: #666;">
                                        This form has been reviewed and approved by all previous approvers. Please
                                        review all details above and make your final decision.
                                    </p>
                                </div>
                            @endif
                            <div style="display: flex; gap: 15px;">
                                <button type="button" class="btn btn-approve-final"
                                    onclick="approveClearanceForm('{{ $clearance->access_id }}')"
                                    style="background-color: #32cd32; border: none; color: white; padding: 12px 30px; font-size: 16px; font-weight: 600; border-radius: 6px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 120px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: all 0.3s;">
                                    <i class="fas fa-check" style="font-size: 24px; margin-bottom: 5px;"></i>
                                    <span>Approve</span>
                                </button>
                                <button type="button" class="btn btn-reject-final"
                                    onclick="rejectForm('{{ $clearance->access_id }}')"
                                    style="background-color: #dc3545; border: none; color: white; padding: 12px 30px; font-size: 16px; font-weight: 600; border-radius: 6px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 120px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: all 0.3s;">
                                    <i class="fas fa-times" style="font-size: 24px; margin-bottom: 5px;"></i>
                                    <span>Reject</span>
                                </button>
                            </div>
                            {{-- <button type="button" class="btn btn-primary" onclick="generatePDF()">Download</button> --}}
                        </div>
                    @endif

                </div>

            </div>
        </div>
    </div>
    </div>
    </div>

    <script>
        // Handle reason for leaving dropdown change
        $(document).ready(function() {
            $('#reason_for_leaving').on('change', function() {
                if ($(this).val() === 'Other') {
                    $('#reason_other_div').show();
                } else {
                    $('#reason_other_div').hide();
                    $('#reason_for_leaving_other').val('');
                }
            });
        });

        function approveClearanceForm(access_id) {
            // Use FormData to handle file uploads
            var formData = new FormData();
            formData.append('access_id', access_id);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            // Collect Line Manager fields - collect all fields if they exist
            if ($('#handover_report_received').length) {
                formData.append('handover_report_received', $('#handover_report_received').val() || 'No');
            }
            if ($('#work_responsibilities_transferred').length) {
                formData.append('work_responsibilities_transferred', $('#work_responsibilities_transferred').val() || 'No');
            }
            if ($('#projects_tasks_closed').length) {
                formData.append('projects_tasks_closed', $('#projects_tasks_closed').val() || 'No');
            }
            if ($('#line_manager_comments').length) {
                formData.append('line_manager_comments', $('#line_manager_comments').val() || '');
            }
            // Collect items collected by last day of work
            if ($('#changing_room_keys').length) {
                formData.append('changing_room_keys', $('#changing_room_keys').val() || 'N/A');
            }
            if ($('#office_keys').length) {
                formData.append('office_keys', $('#office_keys').val() || 'N/A');
            }
            if ($('#mobile_phone').length) {
                formData.append('mobile_phone', $('#mobile_phone').val() || 'N/A');
            }
            if ($('#camera').length) {
                formData.append('camera', $('#camera').val() || 'N/A');
            }
            if ($('#ccbrt_uniforms').length) {
                formData.append('ccbrt_uniforms', $('#ccbrt_uniforms').val() || 'N/A');
            }
            if ($('#office_car_keys').length) {
                formData.append('office_car_keys', $('#office_car_keys').val() || 'N/A');
            }
            if ($('#other_items').length) {
                formData.append('other_items', $('#other_items').val() || '');
            }

            // Collect Finance Officer fields
            if ($('#all_salary_advances_cleared').length) {
                formData.append('all_salary_advances_cleared', $('#all_salary_advances_cleared').val());
                formData.append('allowances_reconciled', $('#allowances_reconciled').val());
                formData.append('pending_claims_settled', $('#pending_claims_settled').val());
                formData.append('outstanding_loans_recovered', $('#outstanding_loans_recovered').val());
                formData.append('employee_eligible_for_final_payment', $('#employee_eligible_for_final_payment').val());
                formData.append('finance_comments', $('#finance_comments').val());
            }
            // Collect Finance Confirmation fields
            if ($('#repaid_salary_advance').length) {
                formData.append('repaid_salary_advance', $('#repaid_salary_advance').val());
                formData.append('loan_balances_informed', $('#loan_balances_informed').val());
                formData.append('repaid_outstanding_imprest', $('#repaid_outstanding_imprest').val());
            }

            // Collect IT Officer fields
            if ($('#keyboard_mouse_returned').length) {
                formData.append('keyboard_mouse_returned', $('#keyboard_mouse_returned').val());
                formData.append('phone_returned', $('#phone_returned').val());
                formData.append('id_card_returned', $('#id_card_returned').val());
                formData.append('laptop_returned', $('#laptop_returned').val());
                formData.append('access_card_returned', $('#access_card_returned').val());
                formData.append('domain_account_disabled', $('#domain_account_disabled').val());
                formData.append('email_account_disabled', $('#email_account_disabled').val());
                formData.append('telephone_pin_disabled', $('#telephone_pin_disabled').val());
                formData.append('health_ai_disabled', $('#health_ai_disabled').val());
                formData.append('sap_account_disabled', $('#sap_account_disabled').val());
                formData.append('aruti_account_disabled', $('#aruti_account_disabled').val());
                formData.append('it_ticket_no', $('#it_ticket_no').val());
                formData.append('it_comments', $('#it_comments').val());
            }

            // Collect HR Officer fields
            if ($('#resignation_letter_received').length) {
                formData.append('resignation_letter_received', $('#resignation_letter_received').val());
                formData.append('exit_interview_completed', $('#exit_interview_completed').val());
                formData.append('leave_balance_confirmed', $('#leave_balance_confirmed').val());
                formData.append('contract_file_reviewed', $('#contract_file_reviewed').val());
                formData.append('office_keys_returned', $('#office_keys_returned').val());
                formData.append('uniform_ppe_returned', $('#uniform_ppe_returned').val());
                formData.append('tools_equipment_returned', $('#tools_equipment_returned').val());
                formData.append('vehicle_clearance', $('#vehicle_clearance').val());
                formData.append('hr_comments', $('#hr_comments').val());

                // Reason for leaving (for final approval)
                if ($('#reason_for_leaving').length) {
                    formData.append('reason_for_leaving', $('#reason_for_leaving').val());
                    if ($('#reason_for_leaving_other').length) {
                        formData.append('reason_for_leaving_other', $('#reason_for_leaving_other').val());
                    }
                }
            }
            // Collect HR items collected fields
            if ($('#ccbrt_id_card').length) {
                formData.append('ccbrt_id_card', $('#ccbrt_id_card').val());
                formData.append('ccbrt_name_tag', $('#ccbrt_name_tag').val());
                formData.append('nhif_cards', $('#nhif_cards').val());
                formData.append('work_permit_cancelled', $('#work_permit_cancelled').val());
                formData.append('residence_permit_cancelled', $('#residence_permit_cancelled').val());
            }

            Swal.fire({
                title: 'Confirm Approval',
                text: 'Are you sure you want to approve this clearance form?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Approve',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                confirmButtonColor: '#007A33'
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while the form is being approved.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        method: 'POST',
                        url: '/approve_clearform',
                        data: formData,
                        processData: false, // Don't process the data
                        contentType: false, // Don't set content type (let browser set it for FormData)
                        dataType: 'json',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: response => {
                            if (response.success) {
                                let message = response.message ||
                                    'Clearance form approved successfully!';
                                let details = '';

                                if (response.nextStep) {
                                    details =
                                        `<br><br><strong>Next Step:</strong> ${response.nextStep}`;
                                    if (response.nextApprover) {
                                        details +=
                                            `<br><strong>Next Approver:</strong> ${response.nextApprover}`;
                                    }
                                }

                                Swal.fire({
                                    title: 'Success',
                                    html: message + details,
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#007A33'
                                }).then(() => {
                                    if (response.redirect) {
                                        window.location.href = response.redirect;
                                    } else {
                                        window.location.href = '/clearance';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message || 'Failed to approve form.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: (xhr, status, error) => {
                            let errorMessage = 'Failed to approve form. Please try again.';

                            if (xhr.responseJSON) {
                                if (xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.responseJSON.error) {
                                    errorMessage = xhr.responseJSON.error;
                                }

                                // Handle validation errors
                                if (xhr.responseJSON.errors) {
                                    const errors = Object.values(xhr.responseJSON.errors).flat();
                                    errorMessage = errors.join('\n');
                                }
                            } else if (xhr.status === 0) {
                                errorMessage = 'Network error. Please check your connection.';
                            } else if (xhr.status === 500) {
                                errorMessage = 'Server error. Please try again later.';
                            }

                            Swal.fire({
                                title: 'Error',
                                text: errorMessage,
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    });
                }
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
                            window.location.href = '/clearance';
                        }
                    });
                }
            }).catch((error) => {
                // Error is already handled in preConfirm
                console.error('Rejection error:', error);
            });
        }
    </script>
@endsection

@section('styles')
    <style>
        .page-wrapper {
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 8px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #007A33;
            margin-bottom: 0;
            border-bottom: 2px solid #007A33;
            padding-bottom: 0.5rem;
        }

        .section-title i,
        .card-body h6 i {
            color: #007A33;
        }

        .card-header.bg-white {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e9ecef;
        }

        .text-muted.small {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            margin: 0;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-approve-final:hover {
            background-color: #28a745 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3) !important;
        }

        .btn-reject-final:hover {
            background-color: #c82333 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3) !important;
        }

        .btn-approve-final:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
        }

        .btn-reject-final:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
        }
    </style>
@endsection
