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

    <style>
        /* ── Global form improvements ── */
        .ecf-page { background: #f4f6f8; min-height: 100vh; }
        .ecf-page .content { padding-top: 0 !important; }

        /* Overview hero card */
        .ecf-hero { background: #fff; border-radius: 10px; border: 1px solid #e8eaed; color: #1a1a2e; padding: 20px 24px 0; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
        .ecf-avatar { width: 56px; height: 56px; border-radius: 50%; background: #e6f4ec; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 700; color: #007A33; flex-shrink: 0; border: 2px solid #c3ddc9; }
        .ecf-hero-name { font-size: 1.15rem; font-weight: 700; margin-bottom: 2px; color: #1a1a2e; }
        .ecf-hero-sub { font-size: 0.83rem; color: #6c757d; }
        .ecf-hero-meta { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 10px; padding-bottom: 14px; border-top: 1px solid #f0f2f4; padding-top: 12px; }
        .ecf-hero-meta-item { display: flex; align-items: center; gap: 5px; font-size: 0.81rem; color: #6c757d; }
        .ecf-hero-meta-item i { color: #007A33; font-size: 0.76rem; }
        .ecf-status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 0.76rem; font-weight: 600; }
        .ecf-status-pill.completed { background: #e6f4ec; color: #007A33; border: 1px solid #c3ddc9; }
        .ecf-status-pill.rejected  { background: #fde8e8; color: #dc3545; border: 1px solid #f5c6cb; }
        .ecf-status-pill { background: #fff8e1; color: #856404; border: 1px solid #ffd97a; }

        /* Workflow stepper */
        .ecf-stepper { display: flex; align-items: center; margin: 0 -24px; padding: 12px 24px; border-top: 1px solid #eef0f3; background: #f8f9fa; overflow-x: auto; border-radius: 0 0 10px 10px; }
        .ecf-step { display: flex; flex-direction: column; align-items: center; min-width: 90px; position: relative; flex: 1; }
        .ecf-step + .ecf-step::before { content: ''; position: absolute; top: 14px; left: calc(-50% + 14px); right: calc(50% + 14px); height: 2px; background: #dee2e6; z-index: 0; }
        .ecf-step.done + .ecf-step::before { background: #007A33; }
        .ecf-step-dot { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #ced4da; background: #fff; color: #adb5bd; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; z-index: 1; position: relative; }
        .ecf-step.done .ecf-step-dot  { background: #007A33; border-color: #007A33; color: #fff; }
        .ecf-step.active .ecf-step-dot { background: #e6f4ec; border-color: #007A33; color: #007A33; box-shadow: 0 0 0 3px rgba(0,122,51,0.15); }
        .ecf-step-label { font-size: 0.67rem; color: #adb5bd; margin-top: 5px; text-align: center; line-height: 1.2; }
        .ecf-step.done .ecf-step-label { color: #007A33; font-weight: 600; }
        .ecf-step.active .ecf-step-label { color: #007A33; font-weight: 700; }

        /* Section cards */
        .ecf-section { border: none; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); margin-bottom: 20px; overflow: hidden; }
        .ecf-section .card-header { padding: 14px 20px; border-bottom: 1px solid #eef0f3; display: flex; align-items: center; gap: 10px; }
        .ecf-section-num { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 700; flex-shrink: 0; }
        .ecf-section-title { font-size: 0.95rem; font-weight: 700; color: #1a1a2e; margin: 0; flex: 1; }
        .ecf-section-subtitle { font-size: 0.78rem; color: #6c757d; margin: 0; }
        .ecf-section .card-body { padding: 18px 20px; }

        /* Unified theme colour for all sections */
        .ecf-lm .card-header, .ecf-fin .card-header,
        .ecf-it .card-header, .ecf-hr .card-header,
        .ecf-info .card-header { border-left: 3px solid #007A33; }
        .ecf-lm .ecf-section-num, .ecf-fin .ecf-section-num,
        .ecf-it .ecf-section-num, .ecf-hr .ecf-section-num,
        .ecf-info .ecf-section-num { background: #e6f4ec; color: #007A33; }

        /* Info field */
        .ecf-field { margin-bottom: 14px; }
        .ecf-field-label { font-size: 0.72rem; font-weight: 600; color: #9098a9; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px; }
        .ecf-field-value { font-size: 0.9rem; font-weight: 500; color: #1a1a2e; }

        /* Approval stamp */
        .ecf-stamp { display: flex; align-items: flex-start; gap: 12px; background: #f8fff9; border: 1px solid #c3e6cb; border-radius: 8px; padding: 12px 16px; margin-top: 16px; }
        .ecf-stamp-icon { width: 36px; height: 36px; border-radius: 50%; background: #28a745; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.9rem; flex-shrink: 0; }
        .ecf-stamp-label { font-size: 0.72rem; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.4px; }
        .ecf-stamp-value { font-size: 0.88rem; font-weight: 600; color: #155724; }

        /* Pending indicator */
        .ecf-pending { display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; color: #6c757d; background: #f8f9fa; border: 1px dashed #ced4da; border-radius: 6px; padding: 6px 12px; margin-top: 10px; }

        /* Completion banner */
        .ecf-complete-banner { background: linear-gradient(135deg, #d4edda, #c3e6cb); border: 1px solid #b8dfc4; border-radius: 10px; padding: 18px 22px; margin-bottom: 20px; display: flex; align-items: center; gap: 14px; }
        .ecf-complete-icon { width: 44px; height: 44px; border-radius: 50%; background: #28a745; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; flex-shrink: 0; }

        /* Improve existing label/value patterns inside section cards */
        .ecf-section .card-body label.text-muted.small,
        .ecf-section .card-body .ecf-field-label { font-size: 0.72rem !important; font-weight: 600; color: #9098a9 !important; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 3px; }
        .ecf-section .card-body .mb-3 > p.mb-0 > strong,
        .ecf-section .card-body .mb-3 > p.mb-0 { font-size: 0.9rem; font-weight: 500; color: #1a1a2e; }
        .ecf-section .card-body .mb-3 { margin-bottom: 16px !important; }

        /* Selects inside editable sections */
        .ecf-section .form-control { border-radius: 6px; border-color: #dde1e7; font-size: 0.875rem; }
        .ecf-section .form-control:focus { border-color: #007A33; box-shadow: 0 0 0 3px rgba(0,122,51,0.1); }

        /* Alert info box */
        .ecf-page .alert-info { border-radius: 8px; border-left: 4px solid #0dcaf0; background: #e8f7fc; border-color: #b6e4f0; }

        /* Read-only finance groups */
        .fin-ro-group { border: 1px solid #e8eaed; border-radius: 8px; overflow: hidden; margin-bottom: 12px; }
        .fin-ro-header { display: flex; align-items: center; background: #f8f9fa; padding: 9px 16px; font-weight: 700; font-size: 0.82rem; color: #007A33; border-bottom: 1px solid #e8eaed; text-transform: uppercase; letter-spacing: 0.5px; }
        .fin-ro-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; border-bottom: 1px solid #f3f4f6; gap: 12px; }
        .fin-ro-row:last-child { border-bottom: none; }
        .fin-ro-row:hover { background: #fafffe; }
        .fin-ro-label { font-size: 0.875rem; color: #374151; flex: 1; min-width: 0; }
        .fin-ro-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap; justify-content: flex-end; }
        .fin-ro-extras { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
        .fin-ro-badge { font-size: 0.75rem; padding: 4px 12px; border-radius: 20px; min-width: 48px; text-align: center; font-weight: 600; }
        .fin-ro-chip { display: inline-flex; align-items: center; gap: 4px; background: #f0f2f5; border: 1px solid #e0e2e6; border-radius: 6px; padding: 3px 8px; font-size: 0.76rem; white-space: nowrap; }
        .fin-ro-chip-lbl { color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 0.68rem; letter-spacing: 0.3px; }
        .fin-ro-chip-val { color: #1a1a2e; font-weight: 700; }
        .fin-ro-ref { display: inline-flex; align-items: center; font-size: 0.76rem; color: #6c757d; background: #f8f9fa; border: 1px dashed #ced4da; border-radius: 6px; padding: 3px 8px; white-space: nowrap; }
    </style>

    <div class="page-wrapper ecf-page">
        <div class="content container-fluid" style="padding-top: 20px;">
            @php
                $empName = trim(($clearance->fname ?? '') . ' ' . ($clearance->mname ?? '') . ' ' . ($clearance->lname ?? ''));
                $initials = strtoupper(substr($clearance->fname ?? 'E', 0, 1) . substr($clearance->lname ?? 'C', 0, 1));
                $steps = [
                    ['label' => 'Line Manager',    'key' => 'Line Manager'],
                    ['label' => 'Finance Officer',  'key' => 'Finance Officer'],
                    ['label' => 'IT Officer',       'key' => 'IT Officer'],
                    ['label' => 'HR Officer',       'key' => 'HR Officer'],
                ];
                $stepOrder = ['Line Manager' => 1, 'Finance Officer' => 2, 'IT Officer' => 3, 'HR Officer' => 4];
                $currentOrder = $stepOrder[$currentApprovalStep ?? ''] ?? 0;
                if ($workflowCompleted ?? false) $currentOrder = 99;
            @endphp

            <!-- Employee Overview Hero -->
            <div class="ecf-hero">
                <div class="d-flex align-items-start gap-3 mb-2">
                    <div class="ecf-avatar">{{ $initials }}</div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="ecf-hero-name">{{ $empName }}</span>
                            @if ($statusClass === 'success')
                                <span class="ecf-status-pill completed"><i class="fas fa-check-circle"></i> {{ $statusText }}</span>
                            @elseif ($statusClass === 'danger')
                                <span class="ecf-status-pill rejected"><i class="fas fa-times-circle"></i> {{ $statusText }}</span>
                            @else
                                <span class="ecf-status-pill"><i class="fas fa-clock"></i> {{ $statusText }}</span>
                            @endif
                        </div>
                        <div class="ecf-hero-sub">{{ $clearance->job_title ?? 'N/A' }} &nbsp;·&nbsp; {{ $clearance->dept_name ?? 'N/A' }}</div>
                    </div>
                    @if ((Auth::user()->hasRole('hr') || Auth::user()->hasRole('super-admin') || Auth::user()->can('edit users')) && !empty($clearance->userId))
                    <a href="{{ route('user.edit', $clearance->userId) }}" class="btn btn-sm btn-outline-success" style="font-size:0.8rem;" target="_blank">
                        <i class="fas fa-user-edit me-1"></i>Edit Staff Details
                    </a>
                    @endif
                    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary" style="font-size:0.8rem;">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
                <div class="ecf-hero-meta">
                    <div class="ecf-hero-meta-item"><i class="fas fa-id-badge"></i> {{ $clearance->ccbrt_code ?? 'N/A' }}</div>
                    <div class="ecf-hero-meta-item"><i class="fas fa-envelope"></i> {{ $clearance->email ?? 'N/A' }}</div>
                    <div class="ecf-hero-meta-item"><i class="fas fa-building"></i> {{ $clearance->entity_name ?? 'N/A' }}</div>
                    <div class="ecf-hero-meta-item"><i class="fas fa-calendar-check"></i> Hired {{ $dateOfHire }}</div>
                    <div class="ecf-hero-meta-item"><i class="fas fa-calendar-times"></i> Last Day {{ $lastWorkingDay }}</div>
                    <div class="ecf-hero-meta-item"><i class="fas fa-paper-plane"></i> Submitted {{ $submittedDate }}</div>
                </div>
                <!-- Workflow Stepper -->
                <div class="ecf-stepper">
                    @foreach($steps as $step)
                        @php
                            $sOrder = $stepOrder[$step['key']];
                            $isDone   = ($currentOrder > $sOrder) || ($workflowCompleted ?? false);
                            $isActive = ($currentOrder === $sOrder);
                            $cls = $isDone ? 'done' : ($isActive ? 'active' : '');
                        @endphp
                        <div class="ecf-step {{ $cls }}">
                            <div class="ecf-step-dot">
                                @if($isDone) <i class="fas fa-check"></i>
                                @elseif($isActive) <i class="fas fa-pen" style="font-size:0.65rem;"></i>
                                @else {{ $sOrder }}
                                @endif
                            </div>
                            <div class="ecf-step-label">{{ $step['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">

                    @php
                        $workflowCompleted = isset($workflow) && $workflow->work_flow_completed == 1;
                        // HR should see all sections after full approval
                        $isHRViewingCompleted = $workflowCompleted && Auth::user()->hasRole('hr');

                        // Check if user has "view clearance forms" permission (can see all sections)
                        $hasViewPermission = Auth::user()->hasPermissionTo('view clearance forms');

                        // If previous approver is reviewing (either while in progress or after full completion)
                        // Only restrict to their section when workflow is FULLY completed
                        // While in-progress, show all sections up to theirs (read-only progress view)
                        $isReviewingPreviousApproval = false;
                        if (
                            isset($canReviewPreviousApproval) &&
                            isset($workflowCompleted) &&
                            isset($userApprovalStep)
                        ) {
                            // Only "restrict to my section only" mode when workflow is fully complete
                            $isReviewingPreviousApproval =
                                $canReviewPreviousApproval && $workflowCompleted && !$hasViewPermission;
                        }
                        // Finance Officer / Line Manager / IT tracking progress (approved but workflow not done)
                        $isTrackingProgress = isset($canReviewPreviousApproval) && $canReviewPreviousApproval && !$workflowCompleted && !isset($currentApprovalStep);
                    @endphp

                    @if ($isReviewingPreviousApproval)
                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Review Mode</h6>
                            <p class="mb-0">You are reviewing your previous approval for the
                                <strong>{{ $userApprovalStep }}</strong> section. This form has been fully approved
                                by HR. You can only view the section you previously approved.
                            </p>
                        </div>
                    @elseif (isset($isTrackingProgress) && $isTrackingProgress)
                        <div class="alert mb-4" style="background:#f0faf4;border:1px solid #b7dfc5;border-left:4px solid #007A33;">
                            <h6 class="alert-heading mb-1" style="color:#007A33;"><i class="fas fa-check-circle me-2"></i>You have approved this form</h6>
                            <p class="mb-0 text-muted" style="font-size:0.88rem;">Your <strong>{{ $userApprovalStep }}</strong> approval is complete. The form is now awaiting approval from the next step. You can track progress below.</p>
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
                    Auth::user()->hasAnyRole(['line-manager', 'coo', 'cfo', 'cms', 'hr', 'it', 'finance officer']) ||
                    $hasViewPermission);
            $showFinanceSection = true;
            $showITSection = true;
            $showHRSection = true;
            $showExitInterviewSection = true;
        }
    } elseif ($isTrackingProgress && isset($userApprovalStep)) {
        // Approved approver tracking progress: show all sections up to and including their step (read-only)
        $stepOrder = ['Line Manager' => 1, 'Finance Officer' => 2, 'IT Officer' => 3, 'HR Officer' => 4];
        $myOrder = $stepOrder[$userApprovalStep] ?? 0;
        $showLineManagerSection = $myOrder >= 1;
        $showFinanceSection     = $myOrder >= 2;
        $showITSection          = $myOrder >= 3;
        $showHRSection          = false; // HR section only shown to HR approver
        $showExitInterviewSection = false;
    } else {
        // Normal logic for other users (including those with "view clearance forms" permission)
        $showLineManagerSection =
            (!$workflowCompleted || $isHRViewingCompleted || $hasViewPermission) &&
            ($isFinalApproval ||
                Auth::user()->hasAnyRole(['line-manager', 'coo', 'cfo', 'cms', 'hr', 'it', 'finance officer']) ||
                $hasViewPermission || Auth::user()->hasFinanceOfficerRole());
        $showFinanceSection = true;
        $showITSection = true;
        $showHRSection = true;
        $showExitInterviewSection = true;
    }
}
                    @endphp

                    @if ($showLineManagerSection)
                        <!-- Line Manager Section Card -->
                        <div class="card ecf-section ecf-lm">
                            <div class="card-header bg-white">
                                <div class="ecf-section-num">1</div>
                                <div>
                                    <div class="ecf-section-title"><i class="fas fa-clipboard-list me-2" style="color:#007A33;"></i>Line Manager — Items Collected by Last Day of Work</div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Changing Room Keys</label>
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
                                            <label class="text-muted small ecf-field-label">Office Keys</label>
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
                                            <label class="text-muted small ecf-field-label">Mobile Phone</label>
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
                                            <label class="text-muted small ecf-field-label">Camera</label>
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
                                            <label class="text-muted small ecf-field-label">CCBRT Uniforms</label>
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
                                            <label class="text-muted small ecf-field-label">Office Car Keys</label>
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
                                            <label class="text-muted small ecf-field-label">Any other CCBRT items given (Please
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
                        <div class="card ecf-section ecf-lm">
                            <div class="card-header bg-white">
                                <div class="ecf-section-num">1</div>
                                <div>
                                    <div class="ecf-section-title"><i class="fas fa-check-circle me-2" style="color:#007A33;"></i>Line Manager — Review &amp; Handover</div>
                                </div>
                            </div>
                            </div>
                            <div class="card-body">
                                @if ($currentApprovalStep === 'Line Manager')
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="text-muted small ecf-field-label">Handover Report Received</label>
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
                                                <label class="text-muted small ecf-field-label">Work Responsibilities Transferred</label>
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
                                                <label class="text-muted small ecf-field-label">Projects/Tasks Closed</label>
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
                                                <label class="text-muted small ecf-field-label">Comments/Notes</label>
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
                                                <label class="text-muted small ecf-field-label">Handover Report Received</label>
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
                                                <label class="text-muted small ecf-field-label">Work Responsibilities Transferred</label>
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
                                                <label class="text-muted small ecf-field-label">Projects/Tasks Closed</label>
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
                                                <label class="text-muted small ecf-field-label">Comments/Notes</label>
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
                        <!-- Finance Officer Combined Section Card -->
                        <div class="card ecf-section ecf-fin">
                            <div class="card-header bg-white">
                                <div class="ecf-section-num">2</div>
                                <div>
                                    <div class="ecf-section-title"><i class="fas fa-money-check-alt me-2" style="color:#007A33;"></i>Finance Officer — Confirmation &amp; Review</div>
                                </div>
                            </div>
                            <div class="card-body">
                                @php
                                    $fmtStatus = function($val) {
                                        if ($val === 'Yes' || $val === 1 || $val === true) return ['label' => 'Yes', 'cls' => 'bg-success'];
                                        if ($val === 'No'  || $val === 0 || $val === false) return ['label' => 'No',  'cls' => 'bg-danger'];
                                        if (in_array($val, ['N/A','Not Applicable']))        return ['label' => 'N/A','cls' => 'bg-secondary'];
                                        return ['label' => '—', 'cls' => 'bg-light text-dark border'];
                                    };
                                    $fmtAmt = function($v) { return ($v !== null && $v !== '') ? number_format((float)$v, 2) : '—'; };
                                @endphp
                                @if ($canEdit && $currentApprovalStep === 'Finance Officer')
                                    @php
                                        /* Returns '' (show) or 'display:none;' (hide) based on saved value */
                                        $fcShow = function($field, $onVals) use ($clearance) {
                                            $v = $clearance->$field ?? null;
                                            $norm = match(true) {
                                                $v === 1 || $v === 'Yes'               => 'Yes',
                                                $v === 0 || $v === 'No'                => 'No',
                                                in_array($v, ['N/A','Not Applicable']) => 'N/A',
                                                default                                 => ''
                                            };
                                            return in_array($norm, (array)$onVals) ? '' : 'display:none;';
                                        };
                                    @endphp

                                    @php
                                        /* Helper: render Yes/No/N/A radio group inline */
                                        $radioGroup = function(string $name, $savedVal) {
                                            $out = '';
                                            foreach (['Yes','No','N/A'] as $opt) {
                                                $id    = $name . '_' . strtolower(str_replace('/', '_', $opt));
                                                $chk   = ((string)($savedVal ?? '') === $opt) ? 'checked' : '';
                                                $out  .= "<div class=\"form-check form-check-inline mb-0\">
                                                    <input class=\"form-check-input\" type=\"radio\" name=\"{$name}\" id=\"{$id}\" value=\"{$opt}\" {$chk}>
                                                    <label class=\"form-check-label fc-radio-lbl\" for=\"{$id}\">{$opt}</label>
                                                </div>";
                                            }
                                            return $out;
                                        };
                                        /* Helper: render Yes/No only (no N/A) */
                                        $radioGroupYN = function(string $name, $savedVal) {
                                            $out = '';
                                            foreach (['Yes','No'] as $opt) {
                                                $id  = $name . '_' . strtolower($opt);
                                                $chk = ((string)($savedVal ?? '') === $opt) ? 'checked' : '';
                                                $out .= "<div class=\"form-check form-check-inline mb-0\">
                                                    <input class=\"form-check-input\" type=\"radio\" name=\"{$name}\" id=\"{$id}\" value=\"{$opt}\" {$chk}>
                                                    <label class=\"form-check-label fc-radio-lbl\" for=\"{$id}\">{$opt}</label>
                                                </div>";
                                            }
                                            return $out;
                                        };
                                    @endphp

                                    {{-- ── CATEGORY 1: Advances & Loans ── --}}
                                    <div class="finance-group mb-4">
                                        <div class="finance-group-header">
                                            <i class="fas fa-hand-holding-usd me-2"></i>Advances &amp; Loans
                                        </div>

                                        {{-- Repaid salary advance --}}
                                        <div class="fc-row">
                                            <div class="row align-items-start g-2">
                                                <div class="col-md-5 fc-label">Repaid advance on Salary?</div>
                                                <div class="col-md-3 fc-radios">{!! $radioGroup('repaid_salary_advance', $clearance->repaid_salary_advance ?? null) !!}</div>
                                                <div class="col-md-4 fc-fields">
                                                    <div data-fc-radio="repaid_salary_advance" data-fc-show="Yes" style="{{ $fcShow('repaid_salary_advance','Yes') }}">
                                                        <div class="fc-field-label">Initial Advance Amount (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="repaid_salary_advance_initial_amount"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->repaid_salary_advance_initial_amount ?? '' }}">
                                                        <div class="fc-field-label">Amount Paid / Recovered (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="repaid_salary_advance_amount" id="repaid_salary_advance_amount"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->repaid_salary_advance_amount ?? '' }}">
                                                        <div class="fc-field-label">Balance Remaining (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="repaid_salary_advance_balance"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->repaid_salary_advance_balance ?? '' }}">
                                                        <div class="fc-field-label">Reference / Receipt No.</div>
                                                        <input type="text" name="repaid_salary_advance_details" class="form-control form-control-sm"
                                                            placeholder="Payroll report / receipt no." value="{{ $clearance->repaid_salary_advance_details ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Staff has bonding agreement --}}
                                        <div class="fc-row">
                                            <div class="row align-items-start g-2">
                                                <div class="col-md-5 fc-label">Staff has bonding agreement?</div>
                                                <div class="col-md-3 fc-radios">{!! $radioGroup('has_bonding_agreement', $clearance->has_bonding_agreement ?? null) !!}</div>
                                                <div class="col-md-4 fc-fields">
                                                    <div data-fc-radio="has_bonding_agreement" data-fc-show="Yes" style="{{ $fcShow('has_bonding_agreement','Yes') }}">
                                                        <div class="fc-field-label">Initial Bond Amount (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="bonding_agreement_initial_amount"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->bonding_agreement_initial_amount ?? '' }}">
                                                        <div class="fc-field-label">Amount Paid (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="bonding_agreement_amount" id="bonding_agreement_amount"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->bonding_agreement_amount ?? '' }}">
                                                        <div class="fc-field-label">Balance Remaining (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="bonding_agreement_balance"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->bonding_agreement_balance ?? '' }}">
                                                        <div class="fc-field-label">Agreement Reference</div>
                                                        <input type="text" name="has_bonding_agreement_details" class="form-control form-control-sm"
                                                            placeholder="Bond agreement reference" value="{{ $clearance->has_bonding_agreement_details ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Loan balances informed --}}
                                        <div class="fc-row">
                                            <div class="row align-items-start g-2">
                                                <div class="col-md-5 fc-label">Staff informed Finance of outstanding loan balances?</div>
                                                <div class="col-md-3 fc-radios">{!! $radioGroupYN('loan_balances_informed', $clearance->loan_balances_informed ?? null) !!}</div>
                                                <div class="col-md-4 fc-fields">
                                                    <div data-fc-radio="loan_balances_informed" data-fc-show="Yes" style="{{ $fcShow('loan_balances_informed','Yes') }}">
                                                        <div class="fc-field-label">Amount Loaned (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="loan_amount_loaned"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->loan_amount_loaned ?? '' }}">
                                                        <div class="fc-field-label">Amount Paid (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="loan_amount_paid"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->loan_amount_paid ?? '' }}">
                                                        <div class="fc-field-label">Balance Remaining (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="loan_balance_remaining"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->loan_balance_remaining ?? '' }}">
                                                        <div class="fc-field-label">Reference</div>
                                                        <input type="text" name="loan_balances_informed_details" class="form-control form-control-sm"
                                                            placeholder="Loan statement / reference" value="{{ $clearance->loan_balances_informed_details ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Outstanding loans recovered --}}
                                        <div class="fc-row">
                                            <div class="row align-items-start g-2">
                                                <div class="col-md-5 fc-label">Outstanding Loans Recovered</div>
                                                <div class="col-md-3 fc-radios">{!! $radioGroupYN('outstanding_loans_recovered', $clearance->outstanding_loans_recovered ?? null) !!}</div>
                                                <div class="col-md-4 fc-fields">
                                                    {{-- Shown for both Yes and No --}}
                                                    <div data-fc-radio="outstanding_loans_recovered" data-fc-show="Yes,No" style="{{ $fcShow('outstanding_loans_recovered',['Yes','No']) }}">
                                                        <div class="fc-field-label">Balance Before Clearance (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="loan_balance_before_clearance"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->loan_balance_before_clearance ?? '' }}">
                                                    </div>
                                                    {{-- Shown for Yes only --}}
                                                    <div data-fc-radio="outstanding_loans_recovered" data-fc-show="Yes" style="{{ $fcShow('outstanding_loans_recovered','Yes') }}">
                                                        <div class="fc-field-label">Amount Recovered (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="loan_amount_recovered"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->loan_amount_recovered ?? '' }}">
                                                        <div class="fc-field-label">Balance Remaining (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="loan_balance_remaining"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->loan_balance_remaining ?? '' }}">
                                                        <div class="fc-field-label">Reference / Receipt No.</div>
                                                        <input type="text" name="outstanding_loans_recovered_details" class="form-control form-control-sm"
                                                            placeholder="Receipt / recovery letter no." value="{{ $clearance->outstanding_loans_recovered_details ?? '' }}">
                                                    </div>
                                                    {{-- Shown for No only --}}
                                                    <div data-fc-radio="outstanding_loans_recovered" data-fc-show="No" style="{{ $fcShow('outstanding_loans_recovered','No') }}">
                                                        <div class="fc-field-label">Outstanding Balance (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="loan_balance_remaining"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->loan_balance_remaining ?? '' }}">
                                                        <div class="fc-field-label">Remarks</div>
                                                        <input type="text" name="outstanding_loans_recovered_details" class="form-control form-control-sm"
                                                            placeholder="Reason not yet recovered" value="{{ $clearance->outstanding_loans_recovered_details ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    {{-- ── CATEGORY 2: Operational Funds ── --}}
                                    <div class="finance-group mb-4">
                                        <div class="finance-group-header">
                                            <i class="fas fa-briefcase me-2"></i>Operational Funds
                                        </div>

                                        {{-- Repaid outstanding imprest --}}
                                        <div class="fc-row">
                                            <div class="row align-items-start g-2">
                                                <div class="col-md-5 fc-label">Repaid any outstanding imprest?</div>
                                                <div class="col-md-3 fc-radios">{!! $radioGroup('repaid_outstanding_imprest', $clearance->repaid_outstanding_imprest ?? null) !!}</div>
                                                <div class="col-md-4 fc-fields">
                                                    <div data-fc-radio="repaid_outstanding_imprest" data-fc-show="Yes,No" style="{{ $fcShow('repaid_outstanding_imprest',['Yes','No']) }}">
                                                        <div class="fc-field-label">Amount Issued (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="imprest_amount_issued"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->imprest_amount_issued ?? '' }}">
                                                    </div>
                                                    <div data-fc-radio="repaid_outstanding_imprest" data-fc-show="Yes" style="{{ $fcShow('repaid_outstanding_imprest','Yes') }}">
                                                        <div class="fc-field-label">Amount Cleared (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="imprest_amount_cleared"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->imprest_amount_cleared ?? '' }}">
                                                        <div class="fc-field-label">Voucher / Receipt No.</div>
                                                        <input type="text" name="repaid_outstanding_imprest_details" class="form-control form-control-sm"
                                                            placeholder="Imprest voucher / receipt no." value="{{ $clearance->repaid_outstanding_imprest_details ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Cleared imprest or business advance --}}
                                        <div class="fc-row" style="border-bottom:none;">
                                            <div class="row align-items-start g-2">
                                                <div class="col-md-5 fc-label">Cleared imprest or advance for business activity?</div>
                                                <div class="col-md-3 fc-radios">{!! $radioGroup('cleared_imprest_or_business_advance', $clearance->cleared_imprest_or_business_advance ?? null) !!}</div>
                                                <div class="col-md-4 fc-fields">
                                                    <div data-fc-radio="cleared_imprest_or_business_advance" data-fc-show="Yes" style="{{ $fcShow('cleared_imprest_or_business_advance','Yes') }}">
                                                        <div class="fc-field-label">Supporting Document Ref.</div>
                                                        <input type="text" name="cleared_imprest_or_business_advance_details" class="form-control form-control-sm"
                                                            placeholder="Supporting document ref." value="{{ $clearance->cleared_imprest_or_business_advance_details ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ── CATEGORY 3: Claims & Allowances ── --}}
                                    <div class="finance-group mb-4">
                                        <div class="finance-group-header">
                                            <i class="fas fa-file-invoice-dollar me-2"></i>Claims &amp; Allowances
                                        </div>

                                        {{-- Allowances reconciled --}}
                                        <div class="fc-row">
                                            <div class="row align-items-start g-2">
                                                <div class="col-md-5 fc-label">Allowances Reconciled</div>
                                                <div class="col-md-3 fc-radios">{!! $radioGroup('allowances_reconciled', $clearance->allowances_reconciled ?? null) !!}</div>
                                                <div class="col-md-4 fc-fields">
                                                    <div data-fc-radio="allowances_reconciled" data-fc-show="Yes" style="{{ $fcShow('allowances_reconciled','Yes') }}">
                                                        <div class="fc-field-label">Initial Amount (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="allowances_initial_amount"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->allowances_initial_amount ?? '' }}">
                                                        <div class="fc-field-label">Amount Paid (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="allowances_amount"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->allowances_amount ?? '' }}">
                                                        <div class="fc-field-label">Balance Remaining (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="allowances_balance"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->allowances_balance ?? '' }}">
                                                        <div class="fc-field-label">Reference</div>
                                                        <input type="text" name="allowances_reconciled_details" class="form-control form-control-sm"
                                                            placeholder="Payslip / reconciliation ref." value="{{ $clearance->allowances_reconciled_details ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Pending claims settled --}}
                                        <div class="fc-row">
                                            <div class="row align-items-start g-2">
                                                <div class="col-md-5 fc-label">Pending Claims Settled</div>
                                                <div class="col-md-3 fc-radios">{!! $radioGroupYN('pending_claims_settled', $clearance->pending_claims_settled ?? null) !!}</div>
                                                <div class="col-md-4 fc-fields">
                                                    <div data-fc-radio="pending_claims_settled" data-fc-show="Yes" style="{{ $fcShow('pending_claims_settled','Yes') }}">
                                                        <div class="fc-field-label">Amount (TZS)</div>
                                                        <input type="number" step="0.01" min="0" name="pending_claims_amount"
                                                            class="form-control form-control-sm mb-1" placeholder="0.00" value="{{ $clearance->pending_claims_amount ?? '' }}">
                                                        <div class="fc-field-label">Reference</div>
                                                        <input type="text" name="pending_claims_settled_details" class="form-control form-control-sm"
                                                            placeholder="Claims settlement reference" value="{{ $clearance->pending_claims_settled_details ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Employee eligible for final payment --}}
                                        <div class="fc-row" style="border-bottom:none;">
                                            <div class="row align-items-start g-2">
                                                <div class="col-md-5 fc-label">Employee Eligible for Final Payment</div>
                                                <div class="col-md-3 fc-radios">{!! $radioGroupYN('employee_eligible_for_final_payment', $clearance->employee_eligible_for_final_payment ?? null) !!}</div>
                                                <div class="col-md-4 fc-fields">
                                                    <div data-fc-radio="employee_eligible_for_final_payment" data-fc-show="Yes" style="{{ $fcShow('employee_eligible_for_final_payment','Yes') }}">
                                                        <div class="fc-field-label">Reference / Computation Ref.</div>
                                                        <input type="text" name="employee_eligible_for_final_payment_details" class="form-control form-control-sm"
                                                            placeholder="Final payment computation ref." value="{{ $clearance->employee_eligible_for_final_payment_details ?? '' }}">
                                                    </div>
                                                    <div data-fc-radio="employee_eligible_for_final_payment" data-fc-show="No" style="{{ $fcShow('employee_eligible_for_final_payment','No') }}">
                                                        <div class="alert alert-warning p-2 mb-0" style="font-size:0.78rem;">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            <strong>Not Eligible:</strong> HR will be alerted and must provide a supporting document to override and proceed with approval.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ── CATEGORY 4: Finance Remarks & Outstanding Issues ── --}}
                                    <div class="finance-group mb-4">
                                        <div class="finance-group-header">
                                            <i class="fas fa-comment-alt me-2"></i>Finance Remarks &amp; Outstanding Issues
                                        </div>
                                        <div class="row g-3 pt-2 px-2 pb-2">
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium" style="font-size:0.875rem;">Finance Remarks</label>
                                                <textarea name="finance_comments" id="finance_comments" class="form-control" rows="3"
                                                    placeholder="General remarks about this clearance…">{{ $clearance->finance_comments ?? '' }}</textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium" style="font-size:0.875rem;">Any Outstanding Issues</label>
                                                <textarea name="finance_outstanding_issues" id="finance_outstanding_issues" class="form-control" rows="3"
                                                    placeholder="List any unresolved items or exceptions…">{{ $clearance->finance_outstanding_issues ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ── CATEGORY 5: Supporting Attachment ── --}}
                                    <div class="finance-group mb-4">
                                        <div class="finance-group-header">
                                            <i class="fas fa-paperclip me-2"></i>Supporting Document Attachment
                                        </div>
                                        <div class="px-3 pt-2 pb-1">
                                            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:8px 12px;font-size:0.78rem;color:#166534;">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <strong>What to attach:</strong> Attach any relevant financial documents supporting your entries above — e.g. payroll reconciliation reports, loan statements, bond agreement copies, advance recovery receipts, or any document justifying the figures entered.
                                                <span class="d-block mt-1 text-warning-emphasis"><i class="fas fa-exclamation-triangle me-1"></i><strong>Required if "Employee Eligible for Final Payment" is No:</strong> attach a document explaining why the employee is not eligible and any supporting authorisation for HR to override.</span>
                                            </div>
                                        </div>
                                        <div class="row g-3 pt-2 px-2 pb-2">
                                            <div class="col-md-8">
                                                <label class="form-label fw-medium" style="font-size:0.875rem;">Upload Supporting Document <span class="text-muted fw-normal">(PDF, Excel, Word — max 5MB)</span></label>
                                                <input type="file" name="finance_attachment" id="finance_attachment" class="form-control"
                                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                                @if (!empty($clearance->finance_attachment_path))
                                                    <div class="mt-1 small text-success">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Previously uploaded:
                                                        <a href="{{ asset('storage/' . $clearance->finance_attachment_path) }}" target="_blank" class="text-success fw-medium">
                                                            View Document
                                                        </a>
                                                        <span class="text-muted ms-2">(uploading a new file will replace this)</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ── CATEGORY 6: Checklist Completion ── --}}
                                    <div class="finance-group mb-2">
                                        <div class="finance-group-header">
                                            <i class="fas fa-tasks me-2"></i>Checklist Completion
                                        </div>
                                        <div class="pt-2 pb-2 px-3 d-flex flex-wrap gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="finance_checklist_reviewed" id="finance_checklist_reviewed" value="1"
                                                    {{ $clearance->finance_checklist_reviewed ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="finance_checklist_reviewed">
                                                    All sections reviewed and verified
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="finance_documents_attached" id="finance_documents_attached" value="1"
                                                    {{ $clearance->finance_documents_attached ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="finance_documents_attached">
                                                    Supporting documents attached
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <style>
                                        /* Editable finance form rows */
                                        .finance-group { border: 1px solid #e3e6ea; border-radius: 8px; overflow: hidden; }
                                        .finance-group-header { background: linear-gradient(135deg, #f0fdf4, #f8f9fa); padding: 10px 16px; font-weight: 700; font-size: 0.85rem; color: #155724; border-bottom: 1px solid #e3e6ea; letter-spacing: 0.3px; }
                                        .fc-row { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; }
                                        .fc-row:hover { background-color: #fafffe; }
                                        .fc-label { font-size: 0.875rem; font-weight: 500; color: #333; padding-top: 3px; }
                                        .fc-radios .form-check-inline { margin-right: 8px; }
                                        .fc-radios .form-check-input { width: 0.85rem; height: 0.85rem; cursor: pointer; }
                                        .fc-radio-lbl { font-size: 0.82rem; cursor: pointer; }
                                        .fc-fields .form-control-sm { font-size: 0.82rem; }
                                        .fc-field-label { font-size: 0.72rem; font-weight: 600; color: #6c757d; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.3px; }
                                        [data-fc-radio] { animation: fcFadeIn 0.18s ease; }
                                        @keyframes fcFadeIn { from { opacity:0; transform:translateY(-3px); } to { opacity:1; transform:translateY(0); } }

                                    </style>
                                    <script>
                                    // Vanilla JS — runs before jQuery loads (jQuery is deferred to bottom of layout)
                                    (function() {
                                        function fcToggle(radioName) {
                                            var checked = document.querySelector('input[name="' + radioName + '"]:checked');
                                            var val = checked ? checked.value : '';
                                            document.querySelectorAll('[data-fc-radio="' + radioName + '"]').forEach(function(el) {
                                                var showOn = String(el.getAttribute('data-fc-show')).split(',');
                                                if (showOn.indexOf(val) !== -1) {
                                                    el.style.display = '';
                                                } else {
                                                    el.style.display = 'none';
                                                    el.querySelectorAll('input, textarea').forEach(function(inp) { inp.value = ''; });
                                                }
                                            });
                                        }

                                        var fcRadios = [
                                            'repaid_salary_advance','has_bonding_agreement','loan_balances_informed',
                                            'outstanding_loans_recovered',
                                            'repaid_outstanding_imprest','cleared_imprest_or_business_advance',
                                            'allowances_reconciled','pending_claims_settled','employee_eligible_for_final_payment'
                                        ];

                                        function initFcToggles() {
                                            fcRadios.forEach(function(name) {
                                                fcToggle(name);
                                            });
                                            document.addEventListener('change', function(e) {
                                                if (e.target && e.target.type === 'radio' && fcRadios.indexOf(e.target.name) !== -1) {
                                                    fcToggle(e.target.name);
                                                }
                                            });
                                        }

                                        if (document.readyState === 'loading') {
                                            document.addEventListener('DOMContentLoaded', initFcToggles);
                                        } else {
                                            initFcToggles();
                                        }
                                    })();
                                    </script>
                                @else
                                    {{-- Read-only grouped table view --}}

                                    {{-- CATEGORY 1: Advances & Loans --}}
                                    @php
                                        $roRow = function($label, $val, $details = null, $amountFields = []) use ($fmtStatus, $fmtAmt) {
                                            $s = $fmtStatus($val);
                                            $badgeHtml = "<span class=\"badge {$s['cls']}\">{$s['label']}</span>";
                                            $amtHtml = '';
                                            foreach ($amountFields as $lbl => $v) {
                                                if ($v !== null && $v !== '') {
                                                    $amtHtml .= "<span class=\"text-muted\" style=\"font-size:0.72rem;font-weight:600;text-transform:uppercase;\">{$lbl}:</span> <span class=\"fw-medium\">" . number_format((float)$v, 2) . "</span><br>";
                                                }
                                            }
                                            return ['badge' => $badgeHtml, 'amount' => $amtHtml, 'details' => $details];
                                        };
                                    @endphp

                                    @php
                                        $renderFinanceGroup = function($icon, $title, $items) use ($fmtStatus) {
                                            $html = "<div class=\"fin-ro-group mb-3\">";
                                            $html .= "<div class=\"fin-ro-header\"><i class=\"fas fa-{$icon} me-2\"></i>{$title}</div>";
                                            foreach ($items as $item) {
                                                $s = $fmtStatus($item['val'] ?? null);
                                                $hasExtra = false;
                                                $extraHtml = '';
                                                foreach (($item['amounts'] ?? []) as $albl => $av) {
                                                    if ($av !== null && $av !== '') {
                                                        $hasExtra = true;
                                                        $formatted = number_format((float)$av, 2);
                                                        $extraHtml .= "<span class=\"fin-ro-chip\"><span class=\"fin-ro-chip-lbl\">{$albl}</span><span class=\"fin-ro-chip-val\">TZS {$formatted}</span></span>";
                                                    }
                                                }
                                                if (!empty($item['details'])) {
                                                    $hasExtra = true;
                                                    $esc = htmlspecialchars($item['details']);
                                                    $extraHtml .= "<span class=\"fin-ro-ref\"><i class=\"fas fa-tag me-1\"></i>{$esc}</span>";
                                                }
                                                $html .= "<div class=\"fin-ro-row\">";
                                                $html .= "<span class=\"fin-ro-label\">" . htmlspecialchars($item['label']) . "</span>";
                                                $html .= "<span class=\"fin-ro-right\">";
                                                if ($hasExtra) $html .= "<span class=\"fin-ro-extras\">{$extraHtml}</span>";
                                                $html .= "<span class=\"badge {$s['cls']} fin-ro-badge\">{$s['label']}</span>";
                                                $html .= "</span></div>";
                                            }
                                            $html .= "</div>";
                                            return $html;
                                        };
                                    @endphp

                                    {!! $renderFinanceGroup('hand-holding-usd', 'Advances &amp; Loans', [
                                        ['label' => 'Repaid advance on Salary?',      'val' => $clearance->repaid_salary_advance,       'details' => $clearance->repaid_salary_advance_details,       'amounts' => ['Amount (TZS)' => $clearance->repaid_salary_advance_amount]],
                                        ['label' => 'Staff has bonding agreement?',    'val' => $clearance->has_bonding_agreement,        'details' => $clearance->has_bonding_agreement_details,        'amounts' => ['Bond Amount (TZS)' => $clearance->bonding_agreement_amount]],
                                        ['label' => 'Staff informed Finance of outstanding loan balances?', 'val' => $clearance->loan_balances_informed, 'details' => $clearance->loan_balances_informed_details, 'amounts' => []],
                                        ['label' => 'Outstanding Loans Recovered',    'val' => $clearance->outstanding_loans_recovered,  'details' => $clearance->outstanding_loans_recovered_details,  'amounts' => ['Balance (TZS)' => $clearance->loan_balance_before_clearance, 'Recovered (TZS)' => $clearance->loan_amount_recovered]],
                                        ['label' => 'All Salary Advances Cleared',    'val' => $clearance->all_salary_advances_cleared,  'details' => $clearance->all_salary_advances_cleared_details,  'amounts' => []],
                                    ]) !!}

                                    {!! $renderFinanceGroup('briefcase', 'Operational Funds', [
                                        ['label' => 'Repaid any outstanding imprest?',               'val' => $clearance->repaid_outstanding_imprest,        'details' => $clearance->repaid_outstanding_imprest_details,        'amounts' => ['Issued (TZS)' => $clearance->imprest_amount_issued, 'Cleared (TZS)' => $clearance->imprest_amount_cleared]],
                                        ['label' => 'Cleared imprest or advance for business activity?', 'val' => $clearance->cleared_imprest_or_business_advance, 'details' => $clearance->cleared_imprest_or_business_advance_details, 'amounts' => []],
                                    ]) !!}

                                    {!! $renderFinanceGroup('file-invoice-dollar', 'Claims &amp; Allowances', [
                                        ['label' => 'Allowances Reconciled',               'val' => $clearance->allowances_reconciled,              'details' => $clearance->allowances_reconciled_details,              'amounts' => ['Amount (TZS)' => $clearance->allowances_amount]],
                                        ['label' => 'Pending Claims Settled',              'val' => $clearance->pending_claims_settled,             'details' => $clearance->pending_claims_settled_details,             'amounts' => ['Amount (TZS)' => $clearance->pending_claims_amount]],
                                        ['label' => 'Employee Eligible for Final Payment', 'val' => $clearance->employee_eligible_for_final_payment,'details' => $clearance->employee_eligible_for_final_payment_details,'amounts' => []],
                                    ]) !!}

                                    {{-- CATEGORY 4: Finance Remarks --}}
                                    @if (!empty($clearance->finance_comments) || !empty($clearance->finance_outstanding_issues))
                                    <div class="finance-group mb-4">
                                        <div class="finance-group-header"><i class="fas fa-comment-alt me-2"></i>Finance Remarks &amp; Outstanding Issues</div>
                                        <div class="row g-3 p-3">
                                            @if (!empty($clearance->finance_comments))
                                            <div class="col-md-6">
                                                <label class="text-muted small fw-medium">Finance Remarks</label>
                                                <p class="mb-0"><strong>{{ $clearance->finance_comments }}</strong></p>
                                            </div>
                                            @endif
                                            @if (!empty($clearance->finance_outstanding_issues))
                                            <div class="col-md-6">
                                                <label class="text-muted small fw-medium">Any Outstanding Issues</label>
                                                <p class="mb-0"><strong>{{ $clearance->finance_outstanding_issues }}</strong></p>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endif

                                    {{-- CATEGORY 5: Attachment --}}
                                    @if (!empty($clearance->finance_attachment_path))
                                    <div class="finance-group mb-4">
                                        <div class="finance-group-header"><i class="fas fa-paperclip me-2"></i>Supporting Document</div>
                                        <div class="p-3">
                                            <a href="{{ asset('storage/' . $clearance->finance_attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-download me-1"></i>View / Download Attachment
                                            </a>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- CATEGORY 6: Checklist --}}
                                    <div class="d-flex flex-wrap gap-4 mb-3 px-1">
                                        <span class="{{ $clearance->finance_checklist_reviewed ? 'text-success' : 'text-muted' }}">
                                            <i class="fas fa-{{ $clearance->finance_checklist_reviewed ? 'check-circle' : 'circle' }} me-1"></i>
                                            All sections reviewed and verified
                                        </span>
                                        <span class="{{ $clearance->finance_documents_attached ? 'text-success' : 'text-muted' }}">
                                            <i class="fas fa-{{ $clearance->finance_documents_attached ? 'check-circle' : 'circle' }} me-1"></i>
                                            Supporting documents attached
                                        </span>
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
                        <div class="card ecf-section ecf-it">
                            <div class="card-header bg-white">
                                <div class="ecf-section-num">3</div>
                                <div>
                                    <div class="ecf-section-title"><i class="fas fa-laptop me-2" style="color:#007A33;"></i>IT Officer — ICT Checklist</div>
                                </div>
                            </div>
                            <div class="card-body">
                                @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                    {{-- Editable form --}}
                                    <div class="row">
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Keyboard &amp; Mouse Returned?</label>
                                            <select name="keyboard_mouse_returned" class="form-control" id="keyboard_mouse_returned">
                                                <option value="Yes" {{ ($clearance->keyboard_mouse_returned ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->keyboard_mouse_returned ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->keyboard_mouse_returned ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Phone Returned?</label>
                                            <select name="phone_returned" class="form-control" id="phone_returned">
                                                <option value="Yes" {{ ($clearance->phone_returned ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->phone_returned ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->phone_returned ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">ID Card Returned?</label>
                                            <select name="id_card_returned" class="form-control" id="id_card_returned">
                                                <option value="Yes" {{ ($clearance->id_card_returned ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->id_card_returned ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->id_card_returned ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Laptop/iPad &amp; Accessories &amp; Gate Pass Returned?</label>
                                            <select name="laptop_returned" class="form-control" id="laptop_returned">
                                                <option value="Yes" {{ ($clearance->laptop_returned ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->laptop_returned ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->laptop_returned ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Access Card Returned?</label>
                                            <select name="access_card_returned" class="form-control" id="access_card_returned">
                                                <option value="Yes" {{ ($clearance->access_card_returned ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->access_card_returned ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->access_card_returned ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-12"><h6 style="color:#007A33;font-size:0.85rem;font-weight:700;"><i class="fas fa-server me-2"></i>ICT Systems</h6></div>
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Domain Account Disabled?</label>
                                            <select name="domain_account_disabled" class="form-control" id="domain_account_disabled">
                                                <option value="Yes" {{ ($clearance->domain_account_disabled ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->domain_account_disabled ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->domain_account_disabled ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Email Account Disabled?</label>
                                            <select name="email_account_disabled" class="form-control" id="email_account_disabled">
                                                <option value="Yes" {{ ($clearance->email_account_disabled ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->email_account_disabled ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->email_account_disabled ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Telephone Pin Code Disabled?</label>
                                            <select name="telephone_pin_disabled" class="form-control" id="telephone_pin_disabled">
                                                <option value="Yes" {{ ($clearance->telephone_pin_disabled ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->telephone_pin_disabled ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->telephone_pin_disabled ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Health AI Disabled?</label>
                                            <select name="health_ai_disabled" class="form-control" id="health_ai_disabled">
                                                <option value="Yes" {{ ($clearance->health_ai_disabled ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->health_ai_disabled ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->health_ai_disabled ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">SAP Account Disabled?</label>
                                            <select name="sap_account_disabled" class="form-control" id="sap_account_disabled">
                                                <option value="Yes" {{ ($clearance->sap_account_disabled ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->sap_account_disabled ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->sap_account_disabled ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Aruti Account Disabled?</label>
                                            <select name="aruti_account_disabled" class="form-control" id="aruti_account_disabled">
                                                <option value="Yes" {{ ($clearance->aruti_account_disabled ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->aruti_account_disabled ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->aruti_account_disabled ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div></div>
                                    </div>
                                @else
                                    {{-- Read-only grouped view --}}
                                    {!! $renderFinanceGroup('desktop', 'Hardware &amp; Physical Items', [
                                        ['label' => 'Keyboard &amp; Mouse Returned',                 'val' => $clearance->keyboard_mouse_returned ?? null, 'amounts' => [], 'details' => null],
                                        ['label' => 'Phone Returned',                                'val' => $clearance->phone_returned ?? null,          'amounts' => [], 'details' => null],
                                        ['label' => 'ID Card Returned',                              'val' => $clearance->id_card_returned ?? null,        'amounts' => [], 'details' => null],
                                        ['label' => 'Laptop / iPad &amp; Accessories &amp; Gate Pass Returned', 'val' => $clearance->laptop_returned ?? null, 'amounts' => [], 'details' => null],
                                        ['label' => 'Access Card Returned',                          'val' => $clearance->access_card_returned ?? null,    'amounts' => [], 'details' => null],
                                    ]) !!}
                                    {!! $renderFinanceGroup('server', 'ICT Systems', [
                                        ['label' => 'Domain Account Disabled',    'val' => $clearance->domain_account_disabled ?? null,   'amounts' => [], 'details' => null],
                                        ['label' => 'Email Account Disabled',     'val' => $clearance->email_account_disabled ?? null,    'amounts' => [], 'details' => null],
                                        ['label' => 'Telephone Pin Code Disabled','val' => $clearance->telephone_pin_disabled ?? null,    'amounts' => [], 'details' => null],
                                        ['label' => 'Health AI Disabled',         'val' => $clearance->health_ai_disabled ?? null,        'amounts' => [], 'details' => null],
                                        ['label' => 'SAP Account Disabled',       'val' => $clearance->sap_account_disabled ?? null,      'amounts' => [], 'details' => null],
                                        ['label' => 'Aruti Account Disabled',     'val' => $clearance->aruti_account_disabled ?? null,    'amounts' => [], 'details' => null],
                                    ]) !!}
                                @endif
                            </div>
                        </div>

                        <!-- IT Officer Review & Ticket Card -->
                        <div class="card ecf-section ecf-it">
                            <div class="card-header bg-white">
                                <div class="ecf-section-num">3</div>
                                <div>
                                    <div class="ecf-section-title"><i class="fas fa-check-circle me-2" style="color:#007A33;"></i>IT Officer — Review &amp; Ticket</div>
                                </div>
                            </div>
                            <div class="card-body">
                                @if ($canEdit && $currentApprovalStep === 'IT Officer')
                                    <div class="row">
                                        <div class="col-md-6"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">IT Ticket No</label>
                                            <input type="text" name="it_ticket_no" class="form-control" id="it_ticket_no"
                                                value="{{ $clearance->it_ticket_no ?? '' }}" placeholder="Enter IT ticket number">
                                        </div></div>
                                        <div class="col-md-12"><div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Comments / Notes</label>
                                            <textarea name="it_comments" class="form-control" id="it_comments" rows="3"
                                                placeholder="Add any comments or notes">{{ $clearance->it_comments ?? '' }}</textarea>
                                        </div></div>
                                    </div>
                                @else
                                    @php
                                        $itHistory = isset($allApproversWithDetails) ? $allApproversWithDetails->firstWhere('step_name','IT Officer') : null;
                                        $showITSignature = $itHistory && isset($itHistory['approver_name']) && $itHistory['approver_name'] !== 'N/A';
                                    @endphp
                                    @if (!empty($clearance->it_ticket_no) || !empty($clearance->it_comments))
                                        <div class="fin-ro-group mb-3">
                                            <div class="fin-ro-header"><i class="fas fa-ticket-alt me-2"></i>Ticket &amp; Notes</div>
                                            @if (!empty($clearance->it_ticket_no))
                                                <div class="fin-ro-row">
                                                    <span class="fin-ro-label">IT Ticket No</span>
                                                    <span class="fin-ro-right"><span class="fin-ro-chip"><span class="fin-ro-chip-lbl">Ticket</span><span class="fin-ro-chip-val">{{ $clearance->it_ticket_no }}</span></span></span>
                                                </div>
                                            @endif
                                            @if (!empty($clearance->it_comments))
                                                <div class="fin-ro-row" style="flex-direction:column;align-items:flex-start;">
                                                    <span style="font-size:0.7rem;font-weight:700;color:#9098a9;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:4px;">Comments</span>
                                                    <span style="font-size:0.875rem;color:#374151;">{{ $clearance->it_comments }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($showITSignature)
                                        <div class="ecf-stamp">
                                            <div class="ecf-stamp-icon"><i class="fas fa-user-check"></i></div>
                                            <div class="flex-grow-1">
                                                <div class="ecf-stamp-label">Approved by IT Officer</div>
                                                <div class="ecf-stamp-value">{{ $itHistory['approver_name'] }}</div>
                                                <div class="text-muted" style="font-size:0.8rem;margin-top:2px;">
                                                    {{ isset($itHistory['approval_date']) ? \Carbon\Carbon::parse($itHistory['approval_date'])->format('d F Y') : '' }}
                                                </div>
                                            </div>
                                            @if (!empty($itHistory['signature']))
                                                <div class="text-center" style="flex-shrink:0;">
                                                    <div class="ecf-stamp-label mb-1">Signature</div>
                                                    <img src="data:image/png;base64,{{ $itHistory['signature'] }}" alt="Signature" style="max-width:120px;height:auto;border:1px solid #dee2e6;border-radius:4px;padding:4px;background:#fff;">
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="ecf-pending"><i class="fas fa-hourglass-half"></i> Awaiting IT approval</div>
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
                        <div class="card ecf-section ecf-hr">
                            <div class="card-header bg-white">
                                <div class="ecf-section-num">4</div>
                                <div>
                                    <div class="ecf-section-title"><i class="fas fa-clipboard-check me-2" style="color:#007A33;"></i>HR Officer — Confirmation</div>
                                    @if ($isFinalApproval)
                                        <div class="ecf-section-subtitle">All prior approvals complete — please review and finalise.</div>
                                    @endif
                                    @php
                                        $financeEligible = $clearance->employee_eligible_for_final_payment ?? null;
                                        $notEligible = ($financeEligible === 'No' || $financeEligible === 0 || $financeEligible === false);
                                    @endphp
                                    @if ($notEligible)
                                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-0 mt-2 py-2 px-3" style="font-size:0.82rem;border-radius:7px;">
                                            <i class="fas fa-ban mt-1 flex-shrink-0"></i>
                                            <div>
                                                <strong>Finance Officer marked this employee as NOT eligible for final payment.</strong>
                                                You may not approve this form unless you provide a supporting document and justification to override this status.
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body">
                                @php
                                    $canEditHRItems =
                                        ($canEdit && $currentApprovalStep === 'HR Officer') ||
                                        ($isFinalApproval && Auth::user()->hasRole('hr'));
                                    $hrFmtVal = function($v) {
                                        if ($v === 1 || $v === 'Yes') return 'Yes';
                                        if ($v === 0 || $v === 'No') return 'No';
                                        if (in_array($v, ['N/A','Not Applicable'])) return 'N/A';
                                        return $v ?? '—';
                                    };
                                @endphp
                                @if (!$canEditHRItems)
                                    {{-- Read-only grouped view --}}
                                    {!! $renderFinanceGroup('file-alt', 'Documentation &amp; Records', [
                                        ['label' => 'Resignation Letter Received',  'val' => $hrFmtVal($clearance->resignation_letter_received),  'amounts' => [], 'details' => null],
                                        ['label' => 'Exit Interview Completed',     'val' => $hrFmtVal($clearance->exit_interview_completed),      'amounts' => [], 'details' => null],
                                        ['label' => 'Leave Balance Confirmed',      'val' => $hrFmtVal($clearance->leave_balance_confirmed),       'amounts' => [], 'details' => null],
                                        ['label' => 'Contract File Reviewed',       'val' => $hrFmtVal($clearance->contract_file_reviewed),        'amounts' => [], 'details' => null],
                                    ]) !!}
                                    {!! $renderFinanceGroup('id-card', 'ID &amp; Permits', [
                                        ['label' => 'CCBRT Identification Card',                   'val' => $hrFmtVal($clearance->ccbrt_id_card),             'amounts' => [], 'details' => null],
                                        ['label' => 'CCBRT Name Tag',                              'val' => $hrFmtVal($clearance->ccbrt_name_tag),            'amounts' => [], 'details' => null],
                                        ['label' => 'NHIF Cards (including dependents\')',         'val' => $hrFmtVal($clearance->nhif_cards),                'amounts' => [], 'details' => null],
                                        ['label' => 'Work Permit Cancelled (non-Tanzanians)',      'val' => $hrFmtVal($clearance->work_permit_cancelled),      'amounts' => [], 'details' => null],
                                        ['label' => 'Residence Permit Cancelled (non-Tanzanians)', 'val' => $hrFmtVal($clearance->residence_permit_cancelled), 'amounts' => [], 'details' => null],
                                    ]) !!}
                                @else
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Resignation Letter Received</label>
                                            <select name="resignation_letter_received" class="form-control" id="resignation_letter_received">
                                                <option value="Yes" {{ $clearance->resignation_letter_received == 1 || $clearance->resignation_letter_received === 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ $clearance->resignation_letter_received == 0 || $clearance->resignation_letter_received === 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ $clearance->resignation_letter_received === 'N/A' || $clearance->resignation_letter_received === 'Not Applicable' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Exit Interview Completed</label>
                                            <select name="exit_interview_completed" class="form-control" id="exit_interview_completed">
                                                <option value="Yes" {{ $clearance->exit_interview_completed == 1 || $clearance->exit_interview_completed === 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ $clearance->exit_interview_completed == 0 || $clearance->exit_interview_completed === 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ $clearance->exit_interview_completed === 'N/A' || $clearance->exit_interview_completed === 'Not Applicable' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Leave Balance Confirmed</label>
                                            <select name="leave_balance_confirmed" class="form-control" id="leave_balance_confirmed">
                                                <option value="Yes" {{ $clearance->leave_balance_confirmed == 1 || $clearance->leave_balance_confirmed === 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ $clearance->leave_balance_confirmed == 0 || $clearance->leave_balance_confirmed === 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ $clearance->leave_balance_confirmed === 'N/A' || $clearance->leave_balance_confirmed === 'Not Applicable' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Contract File Reviewed</label>
                                            <select name="contract_file_reviewed" class="form-control" id="contract_file_reviewed">
                                                <option value="Yes" {{ $clearance->contract_file_reviewed == 1 || $clearance->contract_file_reviewed === 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ $clearance->contract_file_reviewed == 0 || $clearance->contract_file_reviewed === 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ $clearance->contract_file_reviewed === 'N/A' || $clearance->contract_file_reviewed === 'Not Applicable' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">CCBRT Identification Card</label>
                                            <select name="ccbrt_id_card" class="form-control" id="ccbrt_id_card">
                                                <option value="Yes" {{ ($clearance->ccbrt_id_card ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->ccbrt_id_card ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->ccbrt_id_card ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">CCBRT Name Tag</label>
                                            <select name="ccbrt_name_tag" class="form-control" id="ccbrt_name_tag">
                                                <option value="Yes" {{ ($clearance->ccbrt_name_tag ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->ccbrt_name_tag ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->ccbrt_name_tag ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">NHIF Cards (Including dependents' cards)</label>
                                            <select name="nhif_cards" class="form-control" id="nhif_cards">
                                                <option value="Yes" {{ ($clearance->nhif_cards ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->nhif_cards ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->nhif_cards ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Work Permit Cancelled (for non-Tanzanians)</label>
                                            <select name="work_permit_cancelled" class="form-control" id="work_permit_cancelled">
                                                <option value="Yes" {{ ($clearance->work_permit_cancelled ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->work_permit_cancelled ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->work_permit_cancelled ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Residence Permit Cancelled (for non-Tanzanians)</label>
                                            <select name="residence_permit_cancelled" class="form-control" id="residence_permit_cancelled">
                                                <option value="Yes" {{ ($clearance->residence_permit_cancelled ?? '') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ ($clearance->residence_permit_cancelled ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ ($clearance->residence_permit_cancelled ?? '') == 'N/A' ? 'selected' : '' }}>N/A</option>
                                            </select>
                                        </div>
                                    </div>
                                    @if ($isFinalApproval)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Office Keys Returned</label>

                                            <select name="office_keys_returned" class="form-control" id="office_keys_returned">
                                                <option value="Yes" {{ $clearance->office_keys_returned == 1 || $clearance->office_keys_returned === 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ $clearance->office_keys_returned == 0 || $clearance->office_keys_returned === 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ $clearance->office_keys_returned === 'N/A' ? 'selected' : '' }}>Not Applicable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Uniform/PPE Returned</label>
                                            <select name="uniform_ppe_returned" class="form-control" id="uniform_ppe_returned">
                                                <option value="Yes" {{ $clearance->uniform_ppe_returned == 1 || $clearance->uniform_ppe_returned === 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ $clearance->uniform_ppe_returned == 0 || $clearance->uniform_ppe_returned === 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ $clearance->uniform_ppe_returned === 'N/A' ? 'selected' : '' }}>Not Applicable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Tools &amp; Equipment Returned</label>
                                            <select name="tools_equipment_returned" class="form-control" id="tools_equipment_returned">
                                                <option value="Yes" {{ $clearance->tools_equipment_returned == 1 || $clearance->tools_equipment_returned === 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ $clearance->tools_equipment_returned == 0 || $clearance->tools_equipment_returned === 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ $clearance->tools_equipment_returned === 'N/A' ? 'selected' : '' }}>Not Applicable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Vehicle Clearance</label>
                                            <select name="vehicle_clearance" class="form-control" id="vehicle_clearance">
                                                <option value="Yes" {{ $clearance->vehicle_clearance == 1 || $clearance->vehicle_clearance === 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ $clearance->vehicle_clearance == 0 || $clearance->vehicle_clearance === 'No' ? 'selected' : '' }}>No</option>
                                                <option value="N/A" {{ $clearance->vehicle_clearance === 'N/A' ? 'selected' : '' }}>Not Applicable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Reason for Leaving <span class="text-danger">*</span></label>
                                            <select name="reason_for_leaving" class="form-control" id="reason_for_leaving" required>
                                                <option value="" disabled {{ !$clearance->reason_for_leaving ? 'selected' : '' }}>--- Select Reason ---</option>
                                                <option value="Resignation" {{ $clearance->reason_for_leaving == 'Resignation' ? 'selected' : '' }}>Resignation</option>
                                                <option value="Termination" {{ $clearance->reason_for_leaving == 'Termination' ? 'selected' : '' }}>Termination</option>
                                                <option value="Contract End" {{ $clearance->reason_for_leaving == 'Contract End' ? 'selected' : '' }}>Contract End</option>
                                                <option value="Retirement" {{ $clearance->reason_for_leaving == 'Retirement' ? 'selected' : '' }}>Retirement</option>
                                                <option value="Other" {{ $clearance->reason_for_leaving == 'Other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                            <div id="reason_other_div" style="display: {{ $clearance->reason_for_leaving == 'Other' ? 'block' : 'none' }}; margin-top: 10px;">
                                                <input type="text" name="reason_for_leaving_other" id="reason_for_leaving_other" class="form-control"
                                                    value="{{ $clearance->reason_for_leaving_other ?? '' }}" placeholder="Please specify other reason">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="text-muted small ecf-field-label">Comments/Notes</label>
                                            <textarea name="hr_comments" class="form-control" id="hr_comments" rows="3"
                                                placeholder="Add any comments or notes">{{ $clearance->hr_comments ?? '' }}</textarea>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif {{-- end canEditHRItems --}}
                            </div>
                        </div>

                        <!-- HR Review Section Card -->
                        <div class="card ecf-section ecf-hr">
                            <div class="card-header bg-white">
                                <div class="ecf-section-num">4</div>
                                <div>
                                    <div class="ecf-section-title"><i class="fas fa-check-circle me-2" style="color:#007A33;"></i>HR Officer — Final Review &amp; Comments</div>
                                </div>
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
                                                <label class="text-muted small ecf-field-label">Comments/Notes</label>
                                                <textarea name="hr_manager_comments" class="form-control" id="hr_manager_comments" rows="3"
                                                    placeholder="Add any comments or notes">{{ $clearance->hr_manager_comments ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @php
                                        $hrHistory = null;
                                        if (isset($allApproversWithDetails)) {
                                            $hrHistory = $allApproversWithDetails->firstWhere('step_name', 'HR Officer');
                                        }
                                        $showHRSignature = $hrHistory && isset($hrHistory['approver_name']) && $hrHistory['approver_name'] !== 'N/A';
                                    @endphp
                                    @if ($showHRSignature)
                                        <div class="ecf-stamp">
                                            <div class="ecf-stamp-icon"><i class="fas fa-user-check"></i></div>
                                            <div class="flex-grow-1">
                                                <div class="ecf-stamp-label">Approved by HR Officer</div>
                                                <div class="ecf-stamp-value">{{ $hrHistory['approver_name'] }}</div>
                                                <div class="text-muted" style="font-size:0.8rem;margin-top:2px;">
                                                    {{ isset($hrHistory['approval_date']) ? \Carbon\Carbon::parse($hrHistory['approval_date'])->format('d F Y') : '' }}
                                                </div>
                                                @if (!empty($clearance->hr_manager_comments))
                                                    <div class="mt-2 pt-2" style="border-top:1px solid #c3e6cb;">
                                                        <span class="ecf-stamp-label">Comments</span>
                                                        <p class="mb-0" style="font-size:0.88rem;color:#374151;">{{ $clearance->hr_manager_comments }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                            @if (!empty($hrHistory['signature']))
                                                <div class="text-center" style="flex-shrink:0;">
                                                    <div class="ecf-stamp-label mb-1">Signature</div>
                                                    <img src="data:image/png;base64,{{ $hrHistory['signature'] }}" alt="Signature" style="max-width:120px;height:auto;border:1px solid #dee2e6;border-radius:4px;padding:4px;background:#fff;">
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="ecf-pending"><i class="fas fa-hourglass-half"></i> Awaiting HR approval</div>
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
                        <div class="card ecf-section ecf-info">
                            <div class="card-header bg-white">
                                <div class="ecf-section-num"><i class="fas fa-comments" style="font-size:0.75rem;"></i></div>
                                <div>
                                    <div class="ecf-section-title">Exit Interview — Employee Responses</div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                {{-- Primary reason chips --}}
                                <div class="fin-ro-group mb-0" style="border:none;border-radius:0;border-bottom:1px solid #e8eaed;">
                                    <div class="fin-ro-header"><i class="fas fa-door-open me-2"></i>Primary Reason for Exit</div>
                                    <div style="padding:12px 16px;">
                                        @if (!empty($exitReasonArray) && is_array($exitReasonArray))
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach ($exitReasonArray as $reason)
                                                    <span style="background:#e6f4ec;color:#007A33;border:1px solid #c3ddc9;border-radius:20px;padding:4px 12px;font-size:0.82rem;font-weight:600;">{{ $reason }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted" style="font-size:0.875rem;">Not provided</span>
                                        @endif
                                    </div>
                                </div>
                                {{-- Text responses --}}
                                @php
                                    $exitTextItems = [
                                        ['icon' => 'comment-dots', 'label' => 'Additional Details',           'val' => $clearance->exit_explanation ?? null],
                                        ['icon' => 'lightbulb',    'label' => 'Suggestions for Improvement',  'val' => $clearance->suggestions ?? null],
                                        ['icon' => 'thumbs-up',    'label' => 'Would Recommend CCBRT',        'val' => $clearance->would_recommend ?? null],
                                    ];
                                @endphp
                                @foreach ($exitTextItems as $eti)
                                    <div style="display:flex;align-items:flex-start;gap:12px;padding:13px 16px;border-bottom:1px solid #f3f4f6;">
                                        <div style="width:32px;height:32px;border-radius:50%;background:#e6f4ec;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fas fa-{{ $eti['icon'] }}" style="color:#007A33;font-size:0.75rem;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size:0.7rem;font-weight:700;color:#9098a9;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:3px;">{{ $eti['label'] }}</div>
                                            @if (!empty($eti['val']))
                                                <div style="font-size:0.9rem;color:#1a1a2e;font-weight:500;">{{ $eti['val'] }}</div>
                                            @else
                                                <div style="font-size:0.875rem;color:#adb5bd;font-style:italic;">Not provided</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($workflowCompleted)
                        <div class="ecf-complete-banner mb-4">
                            <div class="ecf-complete-icon"><i class="fas fa-check"></i></div>
                            <div class="flex-grow-1">
                                <div style="font-size:1rem;font-weight:700;color:#155724;">Clearance Form Fully Approved</div>
                                <div style="font-size:0.84rem;color:#1a5c2a;margin-top:2px;">
                                    @if (Auth::user()->hasRole('hr'))
                                        All sections are displayed above for your review.
                                    @else
                                        Approved by all departments. Contact HR for a copy.
                                    @endif
                                </div>
                            </div>
                            @if (Auth::user()->hasRole('hr'))
                                <a href="{{ route('clearance.download', $clearance->access_id) }}"
                                    class="btn btn-sm"
                                    style="background:#007A33;color:#fff;font-weight:600;border:none;white-space:nowrap;">
                                    <i class="fas fa-download me-1"></i>Download PDF
                                </a>
                            @endif
                        </div>

                        {{-- Certificate of Service action — HR only --}}
                        @if (Auth::user()->hasRole('hr'))
                        @php
                            $cosStaffUser   = $clearance->user ?? null;
                            $cosAlreadyDone = $cosStaffUser && \App\Models\CertificateOfService::where('user_id', $cosStaffUser->id)->exists();
                        @endphp
                        <div class="mb-4 p-3 rounded-3" style="background:#f0fdf4;border:1px solid #b7dfc5;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <div style="font-size:.88rem;font-weight:700;color:#155724;">
                                        <i class="fas fa-certificate me-2"></i>Certificate of Service
                                    </div>
                                    @if ($cosAlreadyDone)
                                        <div style="font-size:.8rem;color:#1a5c2a;margin-top:3px;">
                                            Certificate of Service has already been created for this staff member.
                                        </div>
                                    @elseif ($clearance->cos_notified_at)
                                        <div style="font-size:.8rem;color:#1a5c2a;margin-top:3px;">
                                            COO notified on {{ \Carbon\Carbon::parse($clearance->cos_notified_at)->format('d M Y, H:i') }}.
                                        </div>
                                    @else
                                        <div style="font-size:.8rem;color:#1a5c2a;margin-top:3px;">
                                            Notify the COO to create a Certificate of Service for this staff member.
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    @if ($cosAlreadyDone)
                                        <span class="badge bg-success" style="font-size:.8rem;padding:.45rem .8rem;">
                                            <i class="fas fa-check-circle me-1"></i>Certificate Created
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('clearance.notify-cos', $clearance->access_id) }}"
                                              onsubmit="return confirm('{{ $clearance->cos_notified_at ? 'Resend COO notification for this staff member?' : 'Notify COO to create Certificate of Service for this staff member?\n\nAn email will be sent to the COO.' }}')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm"
                                                style="background:#007A33;color:#fff;font-weight:600;border:none;white-space:nowrap;">
                                                <i class="fas fa-paper-plane me-1"></i>
                                                {{ $clearance->cos_notified_at ? 'Resend Notification' : 'Initialize Certificate of Service' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    @endif

                    <!-- Action Buttons -->
                    @if (($canEdit || $isFinalApproval) && !$isReviewingPreviousApproval && !($workflowCompleted ?? false))
                        <div class="mt-4">
                            @if ($isFinalApproval)
                                <div class="d-flex align-items-center gap-2 mb-3 px-3 py-2 rounded-3"
                                    style="background: linear-gradient(135deg, #f0fdf4, #e7f3ff); border: 1px solid #c3e6cb;">
                                    <i class="fas fa-shield-alt" style="color: #007A33; font-size: 1.1rem;"></i>
                                    <div>
                                        <strong style="font-size: 0.9rem; color: #155724;">Final Approval Review</strong>
                                        <p class="mb-0" style="font-size: 0.8rem; color: #6c757d;">
                                            All previous approvers have signed off. Your decision is final.
                                        </p>
                                    </div>
                                </div>
                            @endif
                            <div class="d-flex justify-content-end gap-3">
                                @php $notEligibleBtn = ($clearance->employee_eligible_for_final_payment === 'No' || $clearance->employee_eligible_for_final_payment === 0); @endphp
                                @if ($notEligibleBtn && ($currentApprovalStep === 'HR Officer' || $isFinalApproval) && Auth::user()->hasRole('hr'))
                                    <button type="button" class="btn-action-approve"
                                        data-bs-toggle="modal" data-bs-target="#hrOverrideModal">
                                        <span class="btn-action-icon"><i class="fas fa-exclamation-circle"></i></span>
                                        <span class="btn-action-label">Override &amp; Approve</span>
                                    </button>
                                @else
                                    <button type="button"
                                        onclick="approveClearanceForm('{{ $clearance->access_id }}')"
                                        class="btn-action-approve">
                                        <span class="btn-action-icon"><i class="fas fa-check-circle"></i></span>
                                        <span class="btn-action-label">Approve</span>
                                    </button>
                                @endif
                                <button type="button"
                                    onclick="rejectForm('{{ $clearance->access_id }}')"
                                    class="btn-action-reject">
                                    <span class="btn-action-icon"><i class="fas fa-times-circle"></i></span>
                                    <span class="btn-action-label">Reject</span>
                                </button>
                            </div>

                            {{-- HR Override Modal (only shown when eligible = No) --}}
                            @if ($notEligibleBtn && ($currentApprovalStep === 'HR Officer' || $isFinalApproval) && Auth::user()->hasRole('hr'))
                            <div class="modal fade" id="hrOverrideModal" tabindex="-1" aria-labelledby="hrOverrideModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold" id="hrOverrideModalLabel">
                                                Override Eligibility &amp; Approve
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p style="font-size:0.83rem;color:#495057;">Finance Officer indicated this employee is <strong>not eligible for final payment</strong>. Please explain why you are approving despite this.</p>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Reason / Justification <span class="text-danger">*</span></label>
                                                <textarea id="hrForceReason" class="form-control" rows="3" placeholder="Explain why you are overriding the eligibility status..."></textarea>
                                                <div id="hrForceReasonError" class="text-danger small mt-1 d-none">Please provide a justification before proceeding.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Supporting Document <span class="text-muted fw-normal">(optional but recommended)</span></label>
                                                <input type="file" id="hrForceDocument" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                                <div class="form-text">Attach any authorisation or document supporting this override decision.</div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="button" class="btn btn-success fw-semibold" onclick="submitHrOverrideApproval('{{ $clearance->access_id }}')">
                                                <i class="fas fa-check-circle me-1"></i> Confirm Override &amp; Approve
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                            function submitHrOverrideApproval(accessId) {
                                var reason = document.getElementById('hrForceReason').value.trim();
                                if (!reason) {
                                    document.getElementById('hrForceReasonError').classList.remove('d-none');
                                    return;
                                }
                                document.getElementById('hrForceReasonError').classList.add('d-none');
                                // Inject force reason so approveClearanceForm picks it up
                                var hidden = document.getElementById('_hrForceReason');
                                if (!hidden) {
                                    hidden = document.createElement('input');
                                    hidden.type = 'hidden';
                                    hidden.id = '_hrForceReason';
                                    hidden.name = 'hr_force_approve_reason';
                                    document.body.appendChild(hidden);
                                }
                                hidden.value = reason;
                                // Close modal then submit
                                bootstrap.Modal.getInstance(document.getElementById('hrOverrideModal')).hide();
                                setTimeout(function() { approveClearanceForm(accessId); }, 300);
                            }
                            </script>
                            @endif
                        </div>
                        <style>
                            .btn-action-approve, .btn-action-reject {
                                display: inline-flex;
                                align-items: center;
                                gap: 10px;
                                padding: 10px 28px;
                                font-size: 0.95rem;
                                font-weight: 600;
                                border: none;
                                border-radius: 8px;
                                cursor: pointer;
                                transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
                                letter-spacing: 0.3px;
                            }
                            .btn-action-approve {
                                background: linear-gradient(135deg, #28a745, #20c843);
                                color: #fff;
                                box-shadow: 0 3px 10px rgba(40, 167, 69, 0.35);
                            }
                            .btn-action-reject {
                                background: linear-gradient(135deg, #dc3545, #e85565);
                                color: #fff;
                                box-shadow: 0 3px 10px rgba(220, 53, 69, 0.35);
                            }
                            .btn-action-approve:hover { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4); }
                            .btn-action-reject:hover  { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4); }
                            .btn-action-approve:active, .btn-action-reject:active { transform: translateY(0); filter: brightness(0.95); }
                            .btn-action-icon { font-size: 1.1rem; }
                        </style>
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
            const getRadioValue = (name) => $(`input[name="${name}"]:checked`).val() || '';

            if ($('input[name="all_salary_advances_cleared"]').length) {
                formData.append('all_salary_advances_cleared', getRadioValue('all_salary_advances_cleared'));
                formData.append('allowances_reconciled', getRadioValue('allowances_reconciled'));
                formData.append('pending_claims_settled', getRadioValue('pending_claims_settled'));
                formData.append('outstanding_loans_recovered', getRadioValue('outstanding_loans_recovered'));
                formData.append('employee_eligible_for_final_payment', getRadioValue('employee_eligible_for_final_payment'));
                formData.append('finance_comments', $('#finance_comments').val());
            }
            // Collect Finance Confirmation fields
            if ($('input[name="repaid_salary_advance"]').length) {
                // Advances & Loans
                formData.append('repaid_salary_advance', getRadioValue('repaid_salary_advance'));
                formData.append('repaid_salary_advance_amount', $('#repaid_salary_advance_amount').val());
                formData.append('repaid_salary_advance_details', $('input[name="repaid_salary_advance_details"]').val());
                formData.append('has_bonding_agreement', getRadioValue('has_bonding_agreement'));
                formData.append('bonding_agreement_amount', $('#bonding_agreement_amount').val());
                formData.append('has_bonding_agreement_details', $('input[name="has_bonding_agreement_details"]').val());
                formData.append('loan_balances_informed', getRadioValue('loan_balances_informed'));
                formData.append('loan_balances_informed_details', $('input[name="loan_balances_informed_details"]').val());
                formData.append('outstanding_loans_recovered', getRadioValue('outstanding_loans_recovered'));
                formData.append('loan_balance_before_clearance', $('input[name="loan_balance_before_clearance"]').val());
                formData.append('loan_amount_recovered', $('input[name="loan_amount_recovered"]').val());
                formData.append('outstanding_loans_recovered_details', $('input[name="outstanding_loans_recovered_details"]').val());
                formData.append('all_salary_advances_cleared', getRadioValue('all_salary_advances_cleared'));
                formData.append('all_salary_advances_cleared_details', $('input[name="all_salary_advances_cleared_details"]').val());
                // Operational Funds
                formData.append('repaid_outstanding_imprest', getRadioValue('repaid_outstanding_imprest'));
                formData.append('imprest_amount_issued', $('input[name="imprest_amount_issued"]').val());
                formData.append('imprest_amount_cleared', $('input[name="imprest_amount_cleared"]').val());
                formData.append('repaid_outstanding_imprest_details', $('input[name="repaid_outstanding_imprest_details"]').val());
                formData.append('cleared_imprest_or_business_advance', getRadioValue('cleared_imprest_or_business_advance'));
                formData.append('cleared_imprest_or_business_advance_details', $('input[name="cleared_imprest_or_business_advance_details"]').val());
                // Claims & Allowances
                formData.append('allowances_reconciled', getRadioValue('allowances_reconciled'));
                formData.append('allowances_amount', $('input[name="allowances_amount"]').val());
                formData.append('allowances_reconciled_details', $('input[name="allowances_reconciled_details"]').val());
                formData.append('pending_claims_settled', getRadioValue('pending_claims_settled'));
                formData.append('pending_claims_amount', $('input[name="pending_claims_amount"]').val());
                formData.append('pending_claims_settled_details', $('input[name="pending_claims_settled_details"]').val());
                formData.append('employee_eligible_for_final_payment', getRadioValue('employee_eligible_for_final_payment'));
                formData.append('employee_eligible_for_final_payment_details', $('input[name="employee_eligible_for_final_payment_details"]').val());
                // Remarks & Checklist
                formData.append('finance_comments', $('#finance_comments').val());
                formData.append('finance_outstanding_issues', $('#finance_outstanding_issues').val());
                formData.append('finance_checklist_reviewed', $('#finance_checklist_reviewed').is(':checked') ? 1 : 0);
                formData.append('finance_documents_attached', $('#finance_documents_attached').is(':checked') ? 1 : 0);
                // Attach finance document if selected
                var financeFile = $('#finance_attachment')[0];
                if (financeFile && financeFile.files.length > 0) {
                    formData.append('finance_attachment', financeFile.files[0]);
                }
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

                // HR force-approve override reason (set by override modal)
                var hrForceEl = document.getElementById('_hrForceReason');
                if (hrForceEl && hrForceEl.value) {
                    formData.append('hr_force_approve_reason', hrForceEl.value);
                }

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

        .finance-radio-group {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 0.9rem;
            background-color: #fbfcfd;
        }

        .finance-options .form-check {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin: 0;
        }

        .finance-options .form-check-label {
            margin-bottom: 0;
            font-weight: 500;
            color: #495057;
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
