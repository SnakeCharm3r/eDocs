@extends('layouts.template')

@php
    use Illuminate\Support\Str;

    // Get status information (matching index page logic)
    $latestHistory = null;
    $statusInfo = null;
    $showStatus = false;
    $isHRProcessing = false;
    $isCompleted = false;

    if ($requisition->workflow) {
        // Check if workflow is completed
        $isCompleted = $requisition->workflow->work_flow_completed == 1;

        if ($requisition->workflow->histories->count()) {
            $latestHistory = $requisition->workflow->histories->sortByDesc('created_at')->first();

            // If completed, show "Completed" status
            if ($isCompleted) {
                $statusInfo = [
                    'label' => 'Completed',
                    'class' => 'bg-success text-white',
                ];
                $showStatus = true;
            } else {
                // Check if HR is currently assigned (pending) on this workflow
                $hrAssigned = $requisition->workflow
                    ->histories()
                    ->whereHas('attendedBy.roles', function ($q) {
                        $q->where('name', 'hr');
                    })
                    ->whereIn('requisition_status', [1, 3])
                    ->whereNull('decision_date')
                    ->exists();

                if ($hrAssigned && !$isCompleted) {
                    // Override label when HR is the current pending step
                    $isHRProcessing = true;
                    $statusInfo = [
                        'label' => 'Pending HR Review',
                        'class' => 'bg-info text-white',
                    ];
                    $showStatus = true;
                } else {
                    $statusInfo = getRequisitionStatusLabel($latestHistory->requisition_status);

                    // Only show status if it's Pending or Approved/Completed
                $statusLabel = strtolower($statusInfo['label']);
                if (
                    strpos($statusLabel, 'pending') !== false ||
                    strpos($statusLabel, 'approved') !== false ||
                    strpos($statusLabel, 'completed') !== false
                ) {
                    $showStatus = true;
                }
            }
        }
    }
}

// Determine initiator and line manager for summary
$summaryInitiator = null;
$summaryLineManager = null;
$initiatedByHECInSummary = false;

// Use stored initiator fields when available
$initiatorNameDisplay = $requisition->initiator_name ?? null;
$initiatorRoleKey = strtolower($requisition->initiator_role ?? '');
$hecRoleKeysForSummary = ['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant'];

if (in_array($initiatorRoleKey, $hecRoleKeysForSummary)) {
    $initiatedByHECInSummary = true;
}

// Fallback to workflow history to determine initiator/line manager if needed
if ($requisition->workflow && $requisition->workflow->histories && $requisition->workflow->histories->count() > 0) {
    $firstHistoryForSummary = $requisition->workflow->histories->sortBy('created_at')->first();
    if ($firstHistoryForSummary && $firstHistoryForSummary->forwarded_by) {
        $summaryInitiator = \App\Models\User::with('roles')->find($firstHistoryForSummary->forwarded_by);

        // If no stored initiator name, build it from user
        if (!$initiatorNameDisplay && $summaryInitiator) {
            $initiatorNameDisplay = trim(($summaryInitiator->fname ?? '') . ' ' . ($summaryInitiator->lname ?? ''));
        }

        if (
            $summaryInitiator &&
            $summaryInitiator->hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant'])
        ) {
            // Initiated by HEC member
            $initiatedByHECInSummary = true;
            $summaryLineManager = $requisition->user; // selected line manager
        } else {
            // Initiated by line manager (or other)
            $summaryLineManager = $summaryInitiator ?: $requisition->user;
        }
    }
}

// If still no line manager found, fall back to requisition owner
if (!$summaryLineManager) {
    $summaryLineManager = $requisition->user;
}

// Human-readable initiator role label for display
$initiatorRoleLabel = null;
if (in_array($initiatorRoleKey, $hecRoleKeysForSummary)) {
    $initiatorRoleLabel = strtoupper($initiatorRoleKey); // e.g. COO, CFO
} elseif ($initiatorRoleKey === 'line-manager') {
    $initiatorRoleLabel = 'Line Manager';
} elseif ($initiatorRoleKey === 'regular' || $initiatorRoleKey === '') {
    $initiatorRoleLabel = 'Regular User';
} else {
    $initiatorRoleLabel = strtoupper($initiatorRoleKey);
}

// Determine current workflow status
$currentStatus = null;
$workflowCompleted = false;
if ($requisition->workflow) {
    $currentStatus = $requisition->workflow->work_flow_status;
    $workflowCompleted = $requisition->workflow->work_flow_completed == 1;
}

// Check if sections have been approved/completed
$payrollApproved = false;
$hecApproved = false;
$cfoApproved = false;
$ceoApproved = false;
$hrApproved = false;

// Check workflow histories if workflow exists
if ($requisition->workflow && $requisition->workflow->id) {
    $workflowId = $requisition->workflow->id;
    
    // Check if payroll accountant has actually approved (must have decision_date in workflow history)
    // This ensures we only show "Approved" when Payroll Accountant has actually reviewed and approved
    $payrollHistoryCheck = \App\Models\WorkFlowHistory::where('work_flow_id', $workflowId)
        ->whereHas('attendedBy.roles', function ($q) {
            $q->where('name', 'payroll_accountant');
        })
        ->whereIn('requisition_status', [0, -1, 1])
        ->whereNotNull('decision_date')
        ->exists();
    
    // Also check if budget_approved is explicitly set to 1 (Yes) - meaning Payroll Accountant approved
    $payrollApproved = $payrollHistoryCheck || ($requisition->budget_approved == 1);

    // Check if HEC member has approved (status 1 with decision)
    $hecHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $workflowId)
        ->whereHas('attendedBy.roles', function ($q) {
            $q->whereIn('name', ['coo', 'cms', 'chief_accountant']);
        })
        ->where('requisition_status', 1)
        ->whereNotNull('decision_date')
        ->exists();
    $hecApproved = $hecHistory;

    // Check if CFO has approved (status -1 means approved/processed)
    $cfoHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $workflowId)
        ->whereHas('attendedBy.roles', function ($q) {
            $q->where('name', 'cfo');
        })
        ->where('requisition_status', -1)
        ->whereNotNull('decision_date')
        ->exists();
    $cfoApproved = $cfoHistory;

    // Check if CEO has approved (status -1 means approved/processed)
    $ceoHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $workflowId)
        ->whereHas('attendedBy.roles', function ($q) {
            $q->where('name', 'ceo');
        })
        ->where('requisition_status', -1)
        ->whereNotNull('decision_date')
        ->exists();
    $ceoApproved = $ceoHistory;

    // Check if HR has approved (status 3 with decision OR hr_processed is true)
    $hrHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $workflowId)
        ->whereHas('attendedBy.roles', function ($q) {
            $q->where('name', 'hr');
        })
        ->where('requisition_status', 3)
        ->whereNotNull('decision_date')
        ->exists();
    $hrApproved = $hrHistory || ($requisition->hr_processed ?? false);

    // Check if there is a pending HR step (status 1, no decision yet)
    $hrHasPending = \App\Models\WorkFlowHistory::where('work_flow_id', $workflowId)
        ->whereHas('attendedBy.roles', function ($q) {
            $q->where('name', 'hr');
        })
        ->where('requisition_status', 1)
        ->whereNull('decision_date')
        ->exists();
} else {
    // If workflow doesn't exist, check hr_processed directly
    $hrApproved = $requisition->hr_processed ?? false;
}

// Determine edit permissions based on role and status
$user = auth()->user();
$isCreator = $requisition->user_id == $user->id;

// Can edit position details:
// 1. Creator (line manager) if workflow hasn't started and NOT initiated by HEC
    // 2. HEC member who initiated the requisition (can edit both Section 1 and Section 3)
    $canEditPositionDetails = false;
    $hecInitiatedByCurrentUser = false;

    // Check if this was initiated by HEC member
    $wasInitiatedByHEC = false;
    if ($requisition->workflow && $requisition->workflow->histories && $requisition->workflow->histories->count() > 0) {
        $firstHistory = $requisition->workflow->histories->sortBy('created_at')->first();
        if ($firstHistory && $firstHistory->forwarded_by) {
            $initiator = \App\Models\User::with('roles')->find($firstHistory->forwarded_by);
            if ($initiator && $initiator->hasAnyRole(['coo', 'cms', 'chief_accountant'])) {
                $wasInitiatedByHEC = true;
                // Check if current user is the HEC initiator
                if ($initiator->id == $user->id) {
                    $hecInitiatedByCurrentUser = true;
                }
            }
        }
    }

    // Allow editing if:
    // 1. HEC member initiated the requisition (they can edit Section 1)
    // 2. OR creator (line manager) and workflow hasn't started and NOT initiated by HEC
if ($hecInitiatedByCurrentUser) {
    // HEC member who initiated can edit Section 1
    $canEditPositionDetails = true;
} elseif ($isCreator && (!$requisition->workflow || $currentStatus === null)) {
    // Line manager can edit only if NOT initiated by HEC
    $canEditPositionDetails = !$wasInitiatedByHEC || !$user->hasRole('line-manager');
}

// Can edit budget allocation: only payroll_accountant, and only if status is 0 or -1 and not yet approved
$canEditBudget =
    $user->hasRole('payroll_accountant') && in_array($currentStatus, [0, -1, null]) && !$payrollApproved;

// Can edit HEC review: only HEC member (coo, cms, chief_accountant), and only if status is 1 and not yet approved
// Check if the current user is assigned to review this requisition in workflow history
$isAssignedHEC = false;
if ($requisition->workflow && $requisition->workflow->histories) {
    // Check if user is assigned to review (status 1) and hasn't made a decision yet
        $isAssignedHEC = $requisition->workflow
            ->histories()
            ->where('attended_by', $user->id)
            ->where('requisition_status', 1)
            ->whereNull('decision_date')
            ->exists();
    }

    // Allow editing if: user is HEC member AND not yet approved
    // Also allow if HEC member initiated the requisition (they can fill both Section 1 and Section 3)
    $canEditHEC = false;
    if ($user->hasAnyRole(['coo', 'cms', 'chief_accountant'])) {
        // Check if this requisition was initiated by the current HEC member
        // Use the $hecInitiatedByCurrentUser variable already set above
        if (!isset($hecInitiatedByCurrentUser)) {
            $hecInitiatedByCurrentUser = false;
            if (isset($initiatedByHEC) && $initiatedByHEC && isset($hecInitiator) && $hecInitiator) {
                $hecInitiatedByCurrentUser = $hecInitiator->id == $user->id;
            }
        }

        // Check if already approved by this user specifically
        $userHasApproved = false;
        if ($requisition->workflow && $requisition->workflow->histories) {
            $userHasApproved = $requisition->workflow
                ->histories()
                ->where('attended_by', $user->id)
                ->where('requisition_status', 1)
                ->whereNotNull('decision_date')
                ->exists();
        }

        // Allow editing if:
        // 1. HEC member initiated the requisition (they can fill Section 3), OR
        // 2. Not approved by this user and not approved by any HEC member
        if ($hecInitiatedByCurrentUser || (!$userHasApproved && !$hecApproved)) {
            $canEditHEC = true;
        }
    }

    // Can edit CFO review: only CFO, and only if they haven't approved yet
$canEditCFO = false;
if ($user->hasRole('cfo')) {
    // Simple check: If CFO is viewing and hasn't approved, allow editing
        // Check if there's a pending workflow history for this CFO
    $hasPendingReview = false;
    if ($requisition->workflow) {
        $hasPendingReview = $requisition->workflow
            ->histories()
            ->where('attended_by', $user->id)
            ->where('requisition_status', 1)
            ->whereNull('decision_date')
            ->exists();
    }

    // Allow editing if: CFO hasn't approved AND (has pending review OR viewing the requisition)
        // This is permissive - if CFO can see the section, they can edit it unless already approved
        $canEditCFO = !$cfoApproved;
    }

    // Can edit CEO review: only CEO, and only if they haven't approved yet
$canEditCEO = false;
if ($user->hasRole('ceo')) {
    // Simple check: If CEO is viewing and hasn't approved, allow editing
        // Check if there's a pending workflow history for this CEO
    $hasPendingReview = false;
    if ($requisition->workflow) {
        $hasPendingReview = $requisition->workflow
            ->histories()
            ->where('attended_by', $user->id)
            ->where('requisition_status', 1)
            ->whereNull('decision_date')
            ->exists();
    }

    // Allow editing if: CEO hasn't approved AND (has pending review OR viewing the requisition)
        // This is permissive - if CEO can see the section, they can edit it unless already approved
        $canEditCEO = !$ceoApproved;
    }

    // Can edit HR finalization: only HR, and only if workflow is not completed and HR hasn't approved yet
$canEditHR = $user->hasRole('hr') && !$workflowCompleted && !$hrApproved;

// Map conditions to readable labels
$conditionLabels = [
    'medical_operational' => 'Medical/Operational necessity',
    'safety_reputational' => 'Safety/Reputational risk',
    'legal' => 'Legal requirements',
    'financial_loss' => 'Financial loss prevention',
    'increase_income' => 'Increase income/revenue',
];

$backgrounds = [
    'new_position' => 'New position',
    'replacement' => 'Replacement',
    'contract_renewal' => 'Contract renewal/Extension',
];

$contractTypes = [
    'minimal_1_year' => 'Minimal 1 year (employment)',
    'termed_less_1_year' => 'Termed < 1 year: consultant / specific task',
    'health_volunteer' => 'Health Volunteer (50% basic, minimal 1 year)',
    'work_exposure' => 'Work Exposure placement (no pay, max 2x3 months)',
    ];
@endphp

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <br>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Recruitment Requisition Form Review</h3>
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
                                    <p class="mb-2"><strong>Submitted Date:</strong>
                                        {{ $requisition->created_at ? \Carbon\Carbon::parse($requisition->created_at)->format('d F Y, H:i') : 'N/A' }}
                                    </p>
                                </div>
                                @if ($showStatus && $statusInfo)
                                    <div class="col-md-4">
                                        <p class="mb-2"><strong>Status:</strong>
                                            <span
                                                class="badge {{ $isCompleted ? 'bg-success text-white' : ($isHRProcessing ? 'bg-info text-white' : (strpos(strtolower($statusInfo['label']), 'pending') !== false ? 'bg-warning text-dark' : 'bg-success text-white')) }}"
                                                style="font-size: 0.9rem; padding: 0.5em 0.75em; font-weight: 600;">
                                                {{ $statusInfo['label'] }}
                                            </span>
                                        </p>
                                    </div>
                                @endif
                            </div>
                            <div class="row mt-2">
                                @if ($initiatorNameDisplay)
                                    <div class="col-md-4">
                                        <p class="mb-1">
                                            <strong>Initiated By:</strong>
                                            <span class="text-dark">
                                                {{ $initiatorNameDisplay }}
                                                @if ($initiatorRoleLabel)
                                                    <span
                                                        class="badge {{ $initiatedByHECInSummary ? 'bg-success' : 'bg-secondary' }} text-white ms-1"
                                                        style="font-size: 0.7rem;">
                                                        {{ $initiatorRoleLabel }}
                                                    </span>
                                                @endif
                                            </span>
                                        </p>
                                    </div>
                                @endif
                                @if ($summaryLineManager)
                                    <div class="col-md-4">
                                        <p class="mb-1">
                                            <strong>Line Manager:</strong>
                                            <span class="text-dark">
                                                {{ $summaryLineManager->fname ?? '' }}
                                                {{ $summaryLineManager->lname ?? '' }}
                                            </span>
                                        </p>
                                        @if ($summaryLineManager->department ?? null)
                                            <p class="mb-0">
                                                <small class="text-muted">
                                                    <i class="fas fa-building me-1"></i>
                                                    {{ $summaryLineManager->department->dept_name }}
                                                </small>
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('requisitions.update', $requisition->access_id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- ================= SECTION 1: POSITION DETAILS ================= --}}
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-briefcase me-2" style="color: #007A33;"></i>1. Position Details
                                </h5>
                                <small class="text-muted d-block mt-1" style="font-size: 0.9rem;">
                                    @if ($initiatedByHECInSummary ?? false)
                                        To be filled by HEC Member
                                    @else
                                        To be filled by respective Head of Department
                                    @endif
                                </small>
                            </div>
                            <div class="card-body">

                                {{-- Staff Personal Details (when initiated by HEC) --}}
                                @php
                                    $initiatedByHEC = false;
                                    $hecInitiator = null;
                                    $lineManagerUser = null;

                                    if ($requisition->workflow && $requisition->workflow->histories->count() > 0) {
                                        $firstHistory = $requisition->workflow->histories
                                            ->sortBy('created_at')
                                            ->first();
                                        if ($firstHistory) {
                                            $initiator = $firstHistory->forwardedBy;
                                            if (!$initiator && $firstHistory->forwarded_by) {
                                                $initiator = \App\Models\User::with('roles')->find(
                                                    $firstHistory->forwarded_by,
                                                );
                                            }
                                            if (
                                                $initiator &&
                                                $initiator->hasAnyRole(['coo', 'cms', 'chief_accountant'])
                                            ) {
                                                $initiatedByHEC = true;
                                                $hecInitiator = $initiator;
                                                $lineManagerUser = $requisition->user; // The selected line manager
                                            }
                                        }
                                    }
                                @endphp

                                @if ($initiatedByHEC && $lineManagerUser)
                                    <div class="mb-4 p-3 bg-light rounded border">
                                        <h5 class="mb-3" style="color: #6c757d; font-size: 1.1rem; font-weight: 600;">
                                            <i class="fas fa-user-tie me-2 text-success"></i>Selected Staff Personal Details
                                        </h5>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold mb-2">Full Name</label>
                                                <div class="p-2 bg-white rounded">
                                                    <span class="text-dark">{{ $lineManagerUser->fname ?? '' }}
                                                        {{ $lineManagerUser->mname ?? '' }}
                                                        {{ $lineManagerUser->lname ?? '' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold mb-2">Staff Code (CCBRT Code)</label>
                                                <div class="p-2 bg-white rounded">
                                                    <span
                                                        class="text-dark">{{ $lineManagerUser->employee_id ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold mb-2">Email</label>
                                                <div class="p-2 bg-white rounded">
                                                    <span class="text-dark">{{ $lineManagerUser->email ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold mb-2">Mobile</label>
                                                <div class="p-2 bg-white rounded">
                                                    <span class="text-dark">{{ $lineManagerUser->mobile ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold mb-2">Job Title</label>
                                                <div class="p-2 bg-white rounded">
                                                    <span
                                                        class="text-dark">{{ $lineManagerUser->jobTitle->job_title ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold mb-2">Department</label>
                                                <div class="p-2 bg-white rounded">
                                                    <span
                                                        class="text-dark">{{ $lineManagerUser->department->dept_name ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Job Title & Background --}}
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Job Title</label>
                                            @if ($canEditPositionDetails)
                                                <input type="text" name="job_title" class="form-control"
                                                    value="{{ $requisition->jobTitle->job_title ?? ($requisition->new_job_title ?? '') }}"
                                                    placeholder="Enter job title">
                                            @else
                                                <p class="mb-0">
                                                    <strong>{{ $requisition->jobTitle->job_title ?? ($requisition->new_job_title ?? 'N/A') }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Background</label>
                                            @if ($canEditPositionDetails)
                                                <div class="d-flex flex-wrap gap-3">
                                                    <div class="form-check">
                                                        <input type="radio" name="background" value="new_position"
                                                            class="form-check-input"
                                                            {{ $requisition->background == 'new_position' ? 'checked' : '' }}>
                                                        <label class="form-check-label">New position</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input type="radio" name="background" value="replacement"
                                                            class="form-check-input"
                                                            {{ $requisition->background == 'replacement' ? 'checked' : '' }}>
                                                        <label class="form-check-label">Replacement</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input type="radio" name="background" value="contract_renewal"
                                                            class="form-check-input"
                                                            {{ $requisition->background == 'contract_renewal' ? 'checked' : '' }}>
                                                        <label class="form-check-label">Contract renewal/Extension</label>
                                                    </div>
                                                </div>
                                            @else
                                                @php
                                                    $backgroundLabels = [
                                                        'new_position' => 'New position',
                                                        'replacement' => 'Replacement',
                                                        'contract_renewal' => 'Contract renewal/Extension',
                                                    ];
                                                @endphp
                                                <p class="mb-0">
                                                    <strong>{{ $backgroundLabels[$requisition->background] ?? 'N/A' }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>


                                {{-- Department & Organization --}}
                                <div class="mb-4">
                                    <h5 class="mb-3" style="color: #6c757d; font-size: 1.1rem; font-weight: 600;">
                                        <i class="fas fa-building me-2 text-success"></i>Department & Organization
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold mb-2">CCBRT Hiring Department</label>
                                            <div class="p-2 bg-light rounded">
                                                <span
                                                    class="text-dark">{{ $requisition->department->dept_name ?? ($requisition->user->department->dept_name ?? 'N/A') }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold mb-2">CCBRT Responsibility Centre</label>
                                            @if ($canEditPositionDetails)
                                                <input type="text" name="responsibility_centre" class="form-control"
                                                    value="{{ $requisition->responsibility_centre ?? '' }}">
                                            @else
                                                <div class="p-2 bg-light rounded">
                                                    <span
                                                        class="text-dark">{{ $requisition->responsibility_centre ?? 'N/A' }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label fw-semibold mb-2">Reporting Line (Reports to
                                                Position)</label>
                                            @if ($canEditPositionDetails)
                                                <input type="text" name="reporting_line" class="form-control"
                                                    value="{{ $requisition->reporting_line ?? '' }}">
                                            @else
                                                <div class="p-2 bg-light rounded">
                                                    <span
                                                        class="text-dark">{{ $requisition->reporting_line ?? 'N/A' }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Contract Type --}}
                                <div class="mb-4">
                                    <h5 class="mb-3" style="color: #6c757d; font-size: 1.1rem; font-weight: 600;">
                                        <i class="fas fa-file-contract me-2 text-success"></i>Contract Type
                                    </h5>
                                    @if ($canEditPositionDetails)
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="form-check p-2 border rounded">
                                                    <input type="radio" name="contract_type" value="minimal_1_year"
                                                        class="form-check-input"
                                                        {{ $requisition->contract_type == 'minimal_1_year' ? 'checked' : '' }}>
                                                    <label class="form-check-label">Minimal 1 year (employment)</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check p-2 border rounded">
                                                    <input type="radio" name="contract_type" value="termed_less_1_year"
                                                        class="form-check-input"
                                                        {{ $requisition->contract_type == 'termed_less_1_year' ? 'checked' : '' }}>
                                                    <label class="form-check-label">Termed < 1 year: consultant / specific
                                                            task</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check p-2 border rounded">
                                                    <input type="radio" name="contract_type" value="health_volunteer"
                                                        class="form-check-input"
                                                        {{ $requisition->contract_type == 'health_volunteer' ? 'checked' : '' }}>
                                                    <label class="form-check-label">Health Volunteer (50% basic, minimal 1
                                                        year)</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check p-2 border rounded">
                                                    <input type="radio" name="contract_type" value="work_exposure"
                                                        class="form-check-input"
                                                        {{ $requisition->contract_type == 'work_exposure' ? 'checked' : '' }}>
                                                    <label class="form-check-label">Work Exposure placement (no pay, max
                                                        2×3
                                                        months)</label>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        @php
                                            $contractTypeLabels = [
                                                'minimal_1_year' => 'Minimal 1 year (employment)',
                                                'termed_less_1_year' => 'Termed < 1 year: consultant / specific task',
                                                'health_volunteer' => 'Health Volunteer (50% basic, minimal 1 year)',
                                                'work_exposure' => 'Work Exposure placement (no pay, max 2×3 months)',
                                            ];
                                        @endphp
                                        <div class="p-2 bg-light rounded">
                                            <span
                                                class="text-dark">{{ $contractTypeLabels[$requisition->contract_type] ?? 'N/A' }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Conditions & Justification --}}
                                <div class="mb-4 pt-4 border-top">
                                    <h5 class="mb-3" style="color: #6c757d; font-size: 1.1rem; font-weight: 600;">
                                        <i class="fas fa-check-square me-2 text-success"></i>Conditions & Justification
                                    </h5>
                                    <p class="text-muted small mb-3">
                                        <i class="fas fa-info-circle me-1"></i>Tick at least one of the below conditions
                                    </p>

                                    <div class="mb-4">
                                        <label class="text-muted small mb-2 d-block">Selected Conditions</label>
                                        @if ($canEditPositionDetails)
                                            <div class="row g-2">
                                                @php
                                                    $conditions = [
                                                        'medical_operational' =>
                                                            '1. There are overwhelming medical or operational imperatives to fill the post',
                                                        'safety_reputational' =>
                                                            '2. There are safety or reputational risks to the organization if the post is not filled',
                                                        'legal' => '3. There are legal requirements to fill the post',
                                                        'financial_loss' =>
                                                            '4. There is evidence that not filling the post will result in demonstrable financial loss to the organisation',
                                                        'increase_income' =>
                                                            '5. The post is necessary to increase income significantly',
                                                    ];
                                                @endphp
                                                @foreach ($conditions as $key => $label)
                                                    <div class="col-md-6">
                                                        <div class="form-check p-2 border rounded">
                                                            <input type="checkbox" name="conditions[]"
                                                                value="{{ $key }}" class="form-check-input"
                                                                id="cond{{ $loop->iteration }}"
                                                                {{ is_array($requisition->conditions) && in_array($key, $requisition->conditions) ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="cond{{ $loop->iteration }}">{{ $label }}</label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            @php
                                                $conditionLabels = [
                                                    'medical_operational' =>
                                                        '1. There are overwhelming medical or operational imperatives to fill the post',
                                                    'safety_reputational' =>
                                                        '2. There are safety or reputational risks to the organization if the post is not filled',
                                                    'legal' => '3. There are legal requirements to fill the post',
                                                    'financial_loss' =>
                                                        '4. There is evidence that not filling the post will result in demonstrable financial loss to the organisation',
                                                    'increase_income' =>
                                                        '5. The post is necessary to increase income significantly',
                                                ];
                                                $selectedConditions = is_array($requisition->conditions)
                                                    ? $requisition->conditions
                                                    : [];
                                            @endphp
                                            @if (count($selectedConditions) > 0)
                                                <div class="p-3 bg-light rounded">
                                                    <ul class="mb-0" style="padding-left: 20px;">
                                                        @foreach ($selectedConditions as $condition)
                                                            <li class="text-dark mb-2">
                                                                {{ $conditionLabels[$condition] ?? $condition }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @else
                                                <div class="p-3 bg-light rounded">
                                                    <span class="text-muted">No conditions selected</span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="pt-3 border-top mt-4">
                                        <label class="text-muted small mb-2 d-block">Reasoning & Business Impact</label>
                                        <p class="text-muted small mb-2">
                                            Elaborate brief reasoning, business impact and why actions cannot be absorbed by
                                            existing
                                            staff or through work exposure placement or health volunteer
                                        </p>
                                        @if ($canEditPositionDetails)
                                            <textarea name="reasoning" class="form-control" rows="5">{{ $requisition->reasoning ?? '' }}</textarea>
                                        @else
                                            <div class="p-3 bg-light rounded" style="min-height: 100px;">
                                                <span class="text-dark">{{ $requisition->reasoning ?? 'N/A' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </div>
                </div>
            </div>

            {{-- ================= HEAD OF DEPARTMENT APPROVAL ================= --}}
            @if (!($initiatedByHECInSummary ?? false))
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0 section-title">
                            <i class="fas fa-signature me-2" style="color: #007A33;"></i>Head of Department Approval
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="text-muted small">HOD Name</label>
                                    <p class="mb-0">
                                        <strong>
                                            @if ($requisition->user && ($requisition->user->fname || $requisition->user->lname))
                                                {{ $requisition->user->fname ?? '' }}
                                                {{ $requisition->user->lname ?? '' }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </strong>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="text-muted small">Signature</label>
                                    <p class="mb-0">
                                        @if ($requisition->signature)
                                            <img src="data:image/png;base64,{{ $requisition->signature }}"
                                                alt="HOD Signature"
                                                style="max-width: 150px; max-height: 60px; object-fit: contain;">
                                        @else
                                            <span class="text-muted"><strong>N/A</strong></span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="text-muted small">Date</label>
                                    @if ($canEditPositionDetails)
                                        <input type="date" name="hod_signature_date" class="form-control"
                                            value="{{ $requisition->created_at ? \Carbon\Carbon::parse($requisition->created_at)->format('Y-m-d') : '' }}"
                                            required>
                                    @else
                                        <p class="mb-0">
                                            <strong>
                                                {{ $requisition->created_at ? \Carbon\Carbon::parse($requisition->created_at)->format('d M Y') : 'N/A' }}
                                            </strong>
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="text-muted small">Attached Documents</label>
                                    <p class="mb-0">
                                        @if ($requisition->job_description_file)
                                            <a href="{{ asset('storage/' . $requisition->job_description_file) }}"
                                                target="_blank" class="text-decoration-none" style="color: #007A33;">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>
                                                <strong>Updated Job/Task Description</strong>
                                                <i class="fas fa-external-link-alt ms-1" style="font-size: 0.85rem;"></i>
                                            </a>
                                        @else
                                            <span class="text-muted"><strong>No document attached</strong></span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 p-2 bg-light rounded border">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>After step 2, this document needs to be brought
                        to respective HEC member for review.
                    </small>
                </div>
            @endif

            {{-- ================= SECTION 2: BUDGET ALLOCATION ================= --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0 section-title">
                        <i class="fas fa-money-bill-wave me-2" style="color: #007A33;"></i>Budget Allocation
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted small">Position Approved in Budget</label>
                                @if ($canEditBudget)
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input type="radio" name="budget_approved" value="1"
                                                id="budget_approved_yes" class="form-check-input"
                                                {{ $requisition->budget_approved == 1 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="budget_approved_yes">Yes</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="radio" name="budget_approved" value="0"
                                                id="budget_approved_no" class="form-check-input"
                                                {{ $requisition->budget_approved != 1 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="budget_approved_no">No</label>
                                        </div>
                                    </div>
                                @else
                                    <p class="mb-0">
                                        <strong>{{ $requisition->budget_approved == 1 ? 'Yes' : 'No' }}</strong>
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4" id="budgetFields"
                            style="display: {{ $requisition->budget_approved == 1 ? 'block' : 'none' }};">
                            <div class="mb-3">
                                <label class="text-muted small">Max Monthly Budget</label>
                                @if ($canEditBudget)
                                    <input type="number" name="max_monthly_budget" class="form-control" step="0.01"
                                        max="9999999999" value="{{ $requisition->max_monthly_budget ?? '' }}">
                                @else
                                    <p class="mb-0">
                                        <strong>{{ $requisition->max_monthly_budget ? number_format($requisition->max_monthly_budget, 2) . ' TZS' : 'N/A' }}</strong>
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4" id="fundingFields"
                            style="display: {{ $requisition->budget_approved == 1 ? 'block' : 'none' }};">
                            <div class="mb-3">
                                <label class="text-muted small">Funding Available</label>
                                @if ($canEditBudget)
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input type="radio" name="funding_available" value="1"
                                                class="form-check-input"
                                                {{ $requisition->funding_available == 1 ? 'checked' : '' }}>
                                            <label class="form-check-label">Yes</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="radio" name="funding_available" value="0"
                                                class="form-check-input"
                                                {{ $requisition->funding_available != 1 ? 'checked' : '' }}>
                                            <label class="form-check-label">No</label>
                                        </div>
                                    </div>
                                @else
                                    <p class="mb-0">
                                        <strong>{{ $requisition->funding_available == 1 ? 'Yes' : 'No' }}</strong>
                                    </p>
                                @endif
                            </div>
                        </div>
                        @if ($requisition->donor_code)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-2">Donor Code</label>
                                @if ($canEditBudget)
                                    <input type="text" name="donor_code" class="form-control"
                                        value="{{ $requisition->donor_code ?? '' }}">
                                @else
                                    <div class="p-2 bg-white rounded">
                                        <span class="text-dark">{{ $requisition->donor_code }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                        @if ($requisition->activity_code)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-2">Activity Code</label>
                                @if ($canEditBudget)
                                    <input type="text" name="activity_code" class="form-control"
                                        value="{{ $requisition->activity_code ?? '' }}">
                                @else
                                    <div class="p-2 bg-white rounded">
                                        <span class="text-dark">{{ $requisition->activity_code }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted small">Payroll Accountant Name</label>
                                <p class="mb-0">
                                    <strong>
                                        @php
                                            $payrollAccountantUser = null;
                                            $payrollHasApproved = $payrollApproved || $requisition->budget_approved !== null;

                                            if ($payrollHasApproved) {
                                                // Try to get from payrollHistory first
                                                if (isset($payrollHistory) && $payrollHistory && $payrollHistory->attendedBy) {
                                                    $payrollAccountantUser = $payrollHistory->attendedBy;
                                                }
                                                // Try from payroll_accountant variable
                                                if (!$payrollAccountantUser && isset($payroll_accountant) && $payroll_accountant) {
                                                    $payrollAccountantUser = $payroll_accountant;
                                                }
                                                // Fallback: Query directly from workflow history (more permissive - don't require decision_date)
                                                if (!$payrollAccountantUser && $requisition->workflow && $requisition->workflow->id) {
                                                    $payrollHistoryRecord = \App\Models\WorkFlowHistory::where(
                                                        'work_flow_id',
                                                        $requisition->workflow->id,
                                                    )
                                                        ->whereHas('attendedBy.roles', function ($q) {
                                                            $q->where('name', 'payroll_accountant');
                                                        })
                                                        ->whereIn('requisition_status', [0, -1, 1, 2])
                                                        ->with('attendedBy')
                                                        ->orderBy('updated_at', 'desc')
                                                        ->orderBy('id', 'desc')
                                                        ->first();

                                                    if ($payrollHistoryRecord && $payrollHistoryRecord->attendedBy) {
                                                        $payrollAccountantUser = $payrollHistoryRecord->attendedBy;
                                                    }
                                                }
                                                // Last resort: Get any payroll accountant who has interacted with this workflow
                                                if (!$payrollAccountantUser && $requisition->workflow && $requisition->workflow->id) {
                                                    $payrollAccountantIds = \App\Models\User::whereHas('roles', function ($q) {
                                                        $q->where('name', 'payroll_accountant');
                                                    })->pluck('id')->toArray();
                                                    
                                                    if (!empty($payrollAccountantIds)) {
                                                        $anyPayrollHistory = \App\Models\WorkFlowHistory::where(
                                                            'work_flow_id',
                                                            $requisition->workflow->id,
                                                        )
                                                            ->whereIn('attended_by', $payrollAccountantIds)
                                                            ->with('attendedBy')
                                                            ->orderBy('updated_at', 'desc')
                                                            ->orderBy('id', 'desc')
                                                            ->first();

                                                        if ($anyPayrollHistory && $anyPayrollHistory->attendedBy) {
                                                            $payrollAccountantUser = $anyPayrollHistory->attendedBy;
                                                        }
                                                    }
                                                }
                                                // Final fallback: If budget_approved is set but no history found, get first payroll accountant
                                                if (!$payrollAccountantUser && $requisition->budget_approved !== null) {
                                                    $payrollAccountantUser = \App\Models\User::whereHas('roles', function ($q) {
                                                        $q->where('name', 'payroll_accountant');
                                                    })
                                                    ->select('id', 'fname', 'lname', 'mname', 'signature', 'email', 'updated_at')
                                                    ->first();
                                                }
                                            }
                                        @endphp
                                        @if ($payrollAccountantUser)
                                            {{ $payrollAccountantUser->fname ?? '' }}
                                            {{ $payrollAccountantUser->lname ?? '' }}
                                        @else
                                            <span class="text-muted">Pending</span>
                                        @endif
                                    </strong>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted small">Payroll Accountant Signature</label>
                                <p class="mb-0">
                                    @php
                                        $payrollAccountantUser = null;
                                        $payrollHasApproved = $payrollApproved || $requisition->budget_approved !== null;

                                        if ($payrollHasApproved) {
                                            // Try to get from payrollHistory first
                                            if (isset($payrollHistory) && $payrollHistory && $payrollHistory->attendedBy) {
                                                $payrollAccountantUser = $payrollHistory->attendedBy;
                                            }
                                            // Try from payroll_accountant variable
                                            if (!$payrollAccountantUser && isset($payroll_accountant) && $payroll_accountant) {
                                                $payrollAccountantUser = $payroll_accountant;
                                            }
                                            // Fallback: Query directly from workflow history (more permissive - don't require decision_date)
                                            if (!$payrollAccountantUser && $requisition->workflow && $requisition->workflow->id) {
                                                $payrollHistoryRecord = \App\Models\WorkFlowHistory::where(
                                                    'work_flow_id',
                                                    $requisition->workflow->id,
                                                )
                                                    ->whereHas('attendedBy.roles', function ($q) {
                                                        $q->where('name', 'payroll_accountant');
                                                    })
                                                    ->whereIn('requisition_status', [0, -1, 1, 2])
                                                    ->with('attendedBy')
                                                    ->orderBy('updated_at', 'desc')
                                                    ->orderBy('id', 'desc')
                                                    ->first();

                                                if ($payrollHistoryRecord && $payrollHistoryRecord->attendedBy) {
                                                    $payrollAccountantUser = $payrollHistoryRecord->attendedBy;
                                                }
                                            }
                                            // Last resort: Get any payroll accountant who has interacted with this workflow
                                            if (!$payrollAccountantUser && $requisition->workflow && $requisition->workflow->id) {
                                                $payrollAccountantIds = \App\Models\User::whereHas('roles', function ($q) {
                                                    $q->where('name', 'payroll_accountant');
                                                })->pluck('id')->toArray();
                                                
                                                if (!empty($payrollAccountantIds)) {
                                                    $anyPayrollHistory = \App\Models\WorkFlowHistory::where(
                                                        'work_flow_id',
                                                        $requisition->workflow->id,
                                                    )
                                                        ->whereIn('attended_by', $payrollAccountantIds)
                                                        ->with('attendedBy')
                                                        ->orderBy('updated_at', 'desc')
                                                        ->orderBy('id', 'desc')
                                                        ->first();

                                                    if ($anyPayrollHistory && $anyPayrollHistory->attendedBy) {
                                                        $payrollAccountantUser = $anyPayrollHistory->attendedBy;
                                                    }
                                                }
                                            }
                                            // Final fallback: If budget_approved is set but no history found, get first payroll accountant
                                            if (!$payrollAccountantUser && $requisition->budget_approved !== null) {
                                                $payrollAccountantUser = \App\Models\User::whereHas('roles', function ($q) {
                                                    $q->where('name', 'payroll_accountant');
                                                })
                                                ->select('id', 'fname', 'lname', 'mname', 'signature', 'email', 'updated_at')
                                                ->first();
                                            }
                                        }
                                    @endphp
                                    @if ($payrollAccountantUser && $payrollAccountantUser->signature)
                                        <img src="data:image/png;base64,{{ $payrollAccountantUser->signature }}"
                                            alt="Payroll Accountant Signature"
                                            style="max-width: 150px; max-height: 60px; object-fit: contain;">
                                    @else
                                        <span class="text-muted"><strong>Pending</strong></span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted small">Date &amp; Time</label>
                                <p class="mb-0">
                                    <strong>
                                        @php
                                            $payrollDate = null;
                                            $payrollHasApproved = $payrollApproved || $requisition->budget_approved !== null;

                                            if ($payrollHasApproved && $requisition->workflow && $requisition->workflow->id) {
                                                $workflowId = $requisition->workflow->id;
                                                
                                                // Priority 1: Get the MOST RECENT workflow history with decision_date (when approval happened)
                                                // This ensures we get the latest approval action, not an old one
                                                $payrollAccountantIds = \App\Models\User::whereHas('roles', function ($q) {
                                                    $q->where('name', 'payroll_accountant');
                                                })->pluck('id')->toArray();
                                                
                                                if (!empty($payrollAccountantIds)) {
                                                    // First, try to get the most recent record with decision_date (actual approval)
                                                    $mostRecentApproval = \App\Models\WorkFlowHistory::where(
                                                        'work_flow_id',
                                                        $workflowId,
                                                    )
                                                        ->whereIn('attended_by', $payrollAccountantIds)
                                                        ->whereIn('requisition_status', [0, -1, 1, 2])
                                                        ->whereNotNull('decision_date')
                                                        ->orderBy('decision_date', 'desc')
                                                        ->orderBy('updated_at', 'desc')
                                                        ->orderBy('id', 'desc')
                                                        ->first();

                                                    if ($mostRecentApproval) {
                                                        $payrollDate = $mostRecentApproval->decision_date;
                                                    } else {
                                                        // If no decision_date, get most recent by updated_at
                                                        $mostRecentHistory = \App\Models\WorkFlowHistory::where(
                                                            'work_flow_id',
                                                            $workflowId,
                                                        )
                                                            ->whereIn('attended_by', $payrollAccountantIds)
                                                            ->whereIn('requisition_status', [0, -1, 1, 2])
                                                            ->orderBy('updated_at', 'desc')
                                                            ->orderBy('id', 'desc')
                                                            ->first();

                                                        if ($mostRecentHistory) {
                                                            $payrollDate = $mostRecentHistory->updated_at ?? $mostRecentHistory->created_at;
                                                        }
                                                    }
                                                }
                                                
                                                // Fallback: Try from payrollHistory variable if still no date
                                                // But only if it's more recent than what we already have
                                                if (!$payrollDate && isset($payrollHistory) && $payrollHistory) {
                                                    $historyDate = null;
                                                    if ($payrollHistory->decision_date) {
                                                        $historyDate = $payrollHistory->decision_date;
                                                    } elseif ($payrollHistory->updated_at) {
                                                        $historyDate = $payrollHistory->updated_at;
                                                    } elseif ($payrollHistory->created_at) {
                                                        $historyDate = $payrollHistory->created_at;
                                                    }
                                                    
                                                    // Only use if we don't have a date yet, or if this one is more recent
                                                    if ($historyDate && (!$payrollDate || \Carbon\Carbon::parse($historyDate)->gt(\Carbon\Carbon::parse($payrollDate)))) {
                                                        $payrollDate = $historyDate;
                                                    }
                                                }
                                                
                                                // Fallback: Try from payroll_accountant variable
                                                if (!$payrollDate && isset($payroll_accountant) && $payroll_accountant) {
                                                    if (isset($payroll_accountant->decision_date) && $payroll_accountant->decision_date) {
                                                        $payrollDate = $payroll_accountant->decision_date;
                                                    } elseif (isset($payroll_accountant->history_updated_at) && $payroll_accountant->history_updated_at) {
                                                        $payrollDate = $payroll_accountant->history_updated_at;
                                                    }
                                                }
                                                
                                                // Final fallback: Use requisition updated_at when budget_approved was set
                                                if (!$payrollDate && $requisition->budget_approved !== null && $requisition->updated_at) {
                                                    $payrollDate = $requisition->updated_at;
                                                }
                                            }
                                        @endphp
                                        @if ($payrollDate)
                                            {{ \Carbon\Carbon::parse($payrollDate)->format('d M Y, H:i') }}
                                        @else
                                            <span class="text-muted">Pending</span>
                                        @endif
                                    </strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Approval Flow Status --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="border-top pt-3">
                                <h6 class="mb-3 fw-semibold">
                                    <i class="fas fa-tasks me-2 text-primary"></i>Approval Flow Status
                                </h6>
                                @php
                                    // Determine workflow routing logic
                                    // Check both initiatedByHEC and initiatedByHECInSummary for compatibility
                                    $isHecInitiated =
                                        (isset($initiatedByHEC) && $initiatedByHEC) ||
                                        (isset($initiatedByHECInSummary) && $initiatedByHECInSummary);
                                    $budgetApproved = $requisition->budget_approved == 1;
                                    $isNewPosition =
                                        isset($requisition->background) && $requisition->background === 'new_position';
                                    $isRenewalOrReplacement = in_array($requisition->background ?? '', [
                                        'contract_renewal',
                                        'replacement',
                                    ]);

                                    // Determine if steps are skipped or required
                                    // If HEC initiated + budget approved + renewal/replacement → Skip HEC, CFO, CEO, go to HR
                                    // If HEC initiated + budget approved + new position → Skip HEC, but go through CFO → CEO → HR (mandatory)
                                    // If budget not approved → Go through HEC → (then CFO/CEO if needed) → HR

                                    $skipHEC = $isHecInitiated && $budgetApproved;
                                    $skipCFO = $isHecInitiated && $budgetApproved && $isRenewalOrReplacement;
                                    $skipCEO = $isHecInitiated && $budgetApproved && $isRenewalOrReplacement;
                                    $cfoRequired = $isHecInitiated && $budgetApproved && $isNewPosition;
                                    $ceoRequired = $isHecInitiated && $budgetApproved && $isNewPosition;
                                @endphp
                                <div class="d-flex flex-wrap gap-3">
                                    {{-- Payroll Accountant Status --}}
                                    <div class="approval-status-item">
                                        <div class="d-flex align-items-center">
                                            <div
                                                class="status-indicator me-2 {{ $payrollApproved ? 'status-approved' : 'status-pending' }}">
                                                <i
                                                    class="fas {{ $payrollApproved ? 'fa-check-circle' : 'fa-clock' }}"></i>
                                            </div>
                                            <div>
                                                <small class="d-block fw-semibold">Payroll Accountant</small>
                                                <small class="text-muted">
                                                    @if ($payrollApproved)
                                                        <span class="text-success">Approved</span>
                                                    @else
                                                        <span class="text-warning">Pending</span>
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- HEC Member Status --}}
                                    <div class="approval-status-item">
                                        <div class="d-flex align-items-center">
                                            @if ($skipHEC)
                                                <div class="status-indicator me-2 status-skipped">
                                                    <i class="fas fa-forward"></i>
                                                </div>
                                                <div>
                                                    <small class="d-block fw-semibold">HEC Member</small>
                                                    <small class="text-muted">
                                                        <span class="text-info">Skipped</span>
                                                    </small>
                                                </div>
                                            @else
                                                <div
                                                    class="status-indicator me-2 {{ $hecApproved ? 'status-approved' : ($payrollApproved ? 'status-pending' : 'status-not-started') }}">
                                                    <i
                                                        class="fas {{ $hecApproved ? 'fa-check-circle' : ($payrollApproved ? 'fa-clock' : 'fa-minus-circle') }}"></i>
                                                </div>
                                                <div>
                                                    <small class="d-block fw-semibold">HEC Member</small>
                                                    <small class="text-muted">
                                                        @if ($hecApproved)
                                                            <span class="text-success">Approved</span>
                                                        @elseif ($payrollApproved)
                                                            <span class="text-warning">Pending</span>
                                                        @else
                                                            <span class="text-muted">Not Started</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- CFO Status --}}
                                    <div class="approval-status-item">
                                        <div class="d-flex align-items-center">
                                            @if ($skipCFO)
                                                <div class="status-indicator me-2 status-skipped">
                                                    <i class="fas fa-forward"></i>
                                                </div>
                                                <div>
                                                    <small class="d-block fw-semibold">CFO</small>
                                                    <small class="text-muted">
                                                        <span class="text-info">Skipped</span>
                                                    </small>
                                                </div>
                                            @else
                                                <div
                                                    class="status-indicator me-2 {{ $cfoApproved ? 'status-approved' : ($hecApproved || ($skipHEC && $cfoRequired) ? 'status-pending' : 'status-not-started') }}">
                                                    <i
                                                        class="fas {{ $cfoApproved ? 'fa-check-circle' : ($hecApproved || ($skipHEC && $cfoRequired) ? 'fa-clock' : 'fa-minus-circle') }}"></i>
                                                </div>
                                                <div>
                                                    <small class="d-block fw-semibold">CFO</small>
                                                    <small class="text-muted">
                                                        @if ($cfoApproved)
                                                            <span class="text-success">Approved</span>
                                                        @elseif ($hecApproved || ($skipHEC && $cfoRequired))
                                                            <span class="text-warning">Pending</span>
                                                        @else
                                                            <span class="text-muted">Not Started</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- CEO Status --}}
                                    <div class="approval-status-item">
                                        <div class="d-flex align-items-center">
                                            @if ($skipCEO)
                                                <div class="status-indicator me-2 status-skipped">
                                                    <i class="fas fa-forward"></i>
                                                </div>
                                                <div>
                                                    <small class="d-block fw-semibold">CEO</small>
                                                    <small class="text-muted">
                                                        <span class="text-info">Skipped</span>
                                                    </small>
                                                </div>
                                            @else
                                                <div
                                                    class="status-indicator me-2 {{ $ceoApproved ? 'status-approved' : ($cfoApproved ? 'status-pending' : 'status-not-started') }}">
                                                    <i
                                                        class="fas {{ $ceoApproved ? 'fa-check-circle' : ($cfoApproved ? 'fa-clock' : 'fa-minus-circle') }}"></i>
                                                </div>
                                                <div>
                                                    <small class="d-block fw-semibold">CEO</small>
                                                    <small class="text-muted">
                                                        @if ($ceoApproved)
                                                            <span class="text-success">Approved</span>
                                                        @elseif ($cfoApproved)
                                                            <span class="text-warning">Pending</span>
                                                        @else
                                                            <span class="text-muted">Not Started</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- HR Status --}}
                                    <div class="approval-status-item">
                                        <div class="d-flex align-items-center">
                                            @php
                                                $hrCanStart = false;
                                                if ($skipHEC && $skipCFO && $skipCEO) {
                                                    // Direct to HR (renewal/replacement with budget)
                                                    $hrCanStart = $payrollApproved;
                                                } elseif ($skipHEC && $cfoRequired && $ceoRequired) {
                                                    // New position: need CFO and CEO
                                                    $hrCanStart = $ceoApproved;
                                                } elseif ($ceoApproved) {
                                                    // Normal flow: CEO approved
                                                    $hrCanStart = true;
                                                } elseif ($hecApproved && !$budgetApproved) {
                                                    // HEC approved, no budget
                                                    $hrCanStart = true;
                                                }
                                            @endphp
                                            <div
                                                class="status-indicator me-2 {{ $hrApproved ? 'status-approved' : ($hrCanStart ? 'status-pending' : 'status-not-started') }}">
                                                <i
                                                    class="fas {{ $hrApproved ? 'fa-check-circle' : ($hrCanStart ? 'fa-clock' : 'fa-minus-circle') }}"></i>
                                            </div>
                                            <div>
                                                <small class="d-block fw-semibold">HR</small>
                                                <small class="text-muted">
                                                    @if ($hrApproved)
                                                        <span class="text-success">Approved</span>
                                                    @elseif ($hrCanStart)
                                                        <span class="text-warning">Pending</span>
                                                    @else
                                                        <span class="text-muted">Not Started</span>
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                // Determine the initiator of the form
                $formInitiator = null;
                $initiatorRole = null;

                if (isset($initiatedByHEC) && $initiatedByHEC && $hecInitiator) {
                    $formInitiator = $hecInitiator;
                    $initiatorRole = 'HEC Member';
                } elseif (isset($isLineManagerRequester) && $isLineManagerRequester && $requisition->user) {
                    $formInitiator = $requisition->user;
                    $initiatorRole = 'Line Manager';
                }
            @endphp

            {{-- ================= SECTION 3: HEC REVIEW (Show after Section 2 if HEC initiated) ================= --}}
            @if ($payrollApproved && isset($hecInitiatedByCurrentUser) && $hecInitiatedByCurrentUser)
                @php
                    // Always show section, but use blank data if not approved
                    $showHECDataEarly = $hecApproved;
                @endphp
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0 section-title">
                            <i class="fas fa-users me-2" style="color: #007A33;"></i>Review by Committee Member
                        </h5>
                        <small class="text-muted d-block mt-1" style="font-size: 0.9rem;">
                            <i class="fas fa-info-circle me-1"></i>To be filled by HEC Member (who initiated this
                            requisition)
                        </small>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Funding Status</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input type="radio" name="funding_status" id="funding_with_funds_early"
                                        value="with_funds" class="form-check-input"
                                        {{ $requisition->budget_approved == 1 ? 'checked' : '' }}
                                        {{ !$canEditHEC ? 'disabled' : '' }} onchange="toggleFundingSectionsEarly()">
                                    <label class="form-check-label" for="funding_with_funds_early">With
                                        Funds</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="funding_status" id="funding_no_funds_early"
                                        value="no_funds" class="form-check-input"
                                        {{ $requisition->budget_approved != 1 ? 'checked' : '' }}
                                        {{ !$canEditHEC ? 'disabled' : '' }} onchange="toggleFundingSectionsEarly()">
                                    <label class="form-check-label" for="funding_no_funds_early">No Funds</label>
                                </div>
                            </div>
                        </div>

                        {{-- Section 3a: With Funds --}}
                        <div id="section_3a_early" class="border rounded p-3 mb-3"
                            style="display: {{ $requisition->budget_approved == 1 ? 'block' : 'none' }};">
                            <h6 class="mb-3"><i class="fas fa-check-circle me-2 text-success"></i>Position
                                is in budget and funds are confirmed</h6>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Decision</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input type="radio" name="hec_objection" value="in_budget_no_objection"
                                            id="no_objection_early" class="form-check-input"
                                            {{ (isset($initiatedByHEC) && $initiatedByHEC && $requisition->budget_approved == 1 && !$requisition->hec_objection) || $requisition->hec_objection == 'in_budget_no_objection' ? 'checked' : '' }}
                                            {{ !$canEditHEC ? 'disabled' : '' }}>
                                        <label class="form-check-label" for="no_objection_early">No objection to
                                            start recruitment/renewal</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="hec_objection" value="in_budget_with_objection"
                                            id="with_objection_early" class="form-check-input"
                                            {{ $requisition->hec_objection == 'in_budget_with_objection' ? 'checked' : '' }}
                                            {{ !$canEditHEC ? 'disabled' : '' }}>
                                        <label class="form-check-label" for="with_objection_early">Objection to
                                            start recruitment/renewal</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Comments</label>
                                @if (!$canEditHEC && empty($requisition->hec_member_comment))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="hec_member_comment" id="hec_member_comment_3a_early" class="form-control" rows="4"
                                        placeholder="Enter your comments here..." {{ !$canEditHEC ? 'readonly disabled' : '' }}
                                        style="min-height: 100px; resize: vertical;">{{ isset($initiatedByHEC) && $initiatedByHEC && $requisition->budget_approved == 1 && !$requisition->hec_objection ? '' : $requisition->hec_member_comment ?? '' }}</textarea>
                                @endif
                                @error('hec_member_comment')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>After this step is
                                signed, document to be returned to HOD and forwarded to HR; if no objection HR will
                                start process.</small>
                        </div>

                        {{-- Section 3b: No Funds - Objection --}}
                        <div id="section_3b_early" class="border rounded p-3 mb-3"
                            style="display: {{ $requisition->budget_approved != 1 && $requisition->hec_objection == 'Objection to start' ? 'block' : 'none' }};">
                            <h6 class="mb-3"><i class="fas fa-times-circle me-2 text-danger"></i>Position is
                                not in budget and/or no funds are confirmed</h6>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="radio" name="hec_objection" value="Objection to start"
                                        id="objection_start_early" class="form-check-input"
                                        {{ $requisition->hec_objection == 'Objection to start' ? 'checked' : '' }}
                                        {{ !$canEditHEC ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="objection_start_early">Objection to start
                                        recruitment/renewal</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Comments</label>
                                @if (!$canEditHEC && empty($requisition->hec_member_comment))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="hec_member_comment" class="form-control" rows="4" placeholder="Enter your comments here..."
                                        {{ !$canEditHEC ? 'readonly disabled' : '' }} style="min-height: 100px; resize: vertical;">{{ $requisition->hec_member_comment ?? '' }}</textarea>
                                @endif
                                @error('hec_member_comment')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>After this step is
                                signed, document to be brought to HR for filing.</small>
                        </div>

                        {{-- Section 3c: No Funds - No Objection --}}
                        <div id="section_3c_early" class="border rounded p-3 mb-3"
                            style="display: {{ $requisition->budget_approved != 1 && $requisition->hec_objection == 'No objection to start' ? 'block' : 'none' }};">
                            <h6 class="mb-3"><i class="fas fa-exclamation-circle me-2 text-warning"></i>Position is not in budget and/or no funds are confirmed</h6>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="radio" name="hec_objection" value="No objection to start"
                                        id="no_objection_start_early" class="form-check-input"
                                        {{ $requisition->hec_objection == 'No objection to start' ? 'checked' : '' }}
                                        {{ !$canEditHEC ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="no_objection_start_early">No objection to
                                        start recruitment/renewal</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Justification</label>
                                @if (!$canEditHEC && empty($requisition->hec_justification))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="hec_justification" class="form-control" rows="3"
                                        {{ !$canEditHEC ? 'readonly disabled' : '' }}>{{ $requisition->hec_justification ?? '' }}</textarea>
                                @endif
                                @error('hec_justification')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Proposed Funding</label>
                                @if (!$canEditHEC && empty($requisition->hec_proposed_funding))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="hec_proposed_funding" class="form-control" rows="3"
                                        {{ !$canEditHEC ? 'readonly disabled' : '' }}>{{ $requisition->hec_proposed_funding ?? '' }}</textarea>
                                @endif
                                @error('hec_proposed_funding')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>After this step is
                                signed, document to be brought to CFO for financial review, thereafter to
                                CEO.</small>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3"><i class="fas fa-user-tie me-2 text-success"></i>HEC Member Signature
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small">Name</label>
                                <div class="p-2 bg-light rounded">
                                    @if ($showHECDataEarly && isset($approver) && $approver)
                                        {{ $approver->fname }} {{ $approver->lname }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small">Signature</label>
                                <div class="p-2 bg-light rounded">
                                    @if ($showHECDataEarly && isset($approver) && $approver && $approver->signature)
                                        <img src="data:image/png;base64,{{ $approver->signature }}" alt="Signature"
                                            style="max-width: 150px; max-height: 60px; object-fit: contain;">
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small">Date</label>
                                @if ($canEditHEC)
                                    <input type="date" name="hec_signature_date" class="form-control"
                                        value="{{ $showHECDataEarly && isset($approver) && $approver && $approver->updated_at ? \Carbon\Carbon::parse($approver->updated_at)->format('Y-m-d') : '' }}">
                                    @error('hec_signature_date')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                @else
                                    <div class="p-2 bg-light rounded">
                                        <span
                                            class="text-muted">{{ $showHECDataEarly && isset($approver) && $approver && $approver->updated_at ? \Carbon\Carbon::parse($approver->updated_at)->format('Y-m-d') : 'N/A' }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ================= SECTION 4: HEC REVIEW (Original position - hide if HEC initiated) ================= --}}
            @if ($payrollApproved && (!isset($hecInitiatedByCurrentUser) || !$hecInitiatedByCurrentUser))
                @php
                    // Always show section, but use blank data if not approved
                    $showHECData = $hecApproved;
                @endphp
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0 section-title">
                            <i class="fas fa-users me-2" style="color: #007A33;"></i>4. Review by
                            {{ isset($approver) && $approver->hasRole('chief_accountant') ? 'Chief Accountant' : 'Committee Member' }}
                        </h5>
                        <small class="text-muted d-block mt-1" style="font-size: 0.9rem;">
                            <i class="fas fa-info-circle me-1"></i>To be filled by HEC Member
                        </small>
                    </div>
                    <div class="card-body">

                        {{-- Funding Status Section --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">Funding Status</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input type="radio" name="funding_status" id="funding_with_funds"
                                        value="with_funds" class="form-check-input"
                                        {{ $requisition->budget_approved == 1 ? 'checked' : '' }}
                                        {{ !$canEditHEC ? 'disabled' : '' }} onchange="toggleFundingSections()">
                                    <label class="form-check-label" for="funding_with_funds">With Funds</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="funding_status" id="funding_no_funds" value="no_funds"
                                        class="form-check-input"
                                        {{ $requisition->budget_approved != 1 ? 'checked' : '' }}
                                        {{ !$canEditHEC ? 'disabled' : '' }} onchange="toggleFundingSections()">
                                    <label class="form-check-label" for="funding_no_funds">No Funds</label>
                                </div>
                            </div>
                        </div>

                        {{-- Section 3a: With Funds --}}
                        <div id="section_3a" class="border rounded p-4 mb-4"
                            style="display: {{ $requisition->budget_approved == 1 ? 'block' : 'none' }}; background-color: #f8f9fa;">
                            <h6 class="mb-3 fw-semibold">
                                <i class="fas fa-check-circle me-2 text-success"></i>Position is in budget and funds
                                are confirmed
                            </h6>
                            <div class="mb-3">
                                <label class="form-label fw-semibold mb-2">Decision</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input type="radio" name="hec_objection" value="in_budget_no_objection"
                                            id="no_objection" class="form-check-input"
                                            {{ (isset($initiatedByHEC) && $initiatedByHEC && $requisition->budget_approved == 1 && !$requisition->hec_objection) || $requisition->hec_objection == 'in_budget_no_objection' ? 'checked' : '' }}
                                            {{ !$canEditHEC ? 'disabled' : '' }}>
                                        <label class="form-check-label" for="no_objection">No objection to start
                                            recruitment/renewal</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="hec_objection" value="in_budget_with_objection"
                                            id="with_objection" class="form-check-input"
                                            {{ $requisition->hec_objection == 'in_budget_with_objection' ? 'checked' : '' }}
                                            {{ !$canEditHEC ? 'disabled' : '' }}>
                                        <label class="form-check-label" for="with_objection">Objection to start
                                            recruitment/renewal</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold mb-2">Comments</label>
                                @if (!$canEditHEC && empty($requisition->hec_member_comment))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="hec_member_comment" id="hec_member_comment_3a" class="form-control" rows="4"
                                        placeholder="Enter your comments here..." {{ !$canEditHEC ? 'readonly disabled' : '' }}
                                        style="min-height: 100px; resize: vertical;">{{ isset($initiatedByHEC) && $initiatedByHEC && $requisition->budget_approved == 1 && !$requisition->hec_objection ? '' : $requisition->hec_member_comment ?? '' }}</textarea>
                                @endif
                                @error('hec_member_comment')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        {{-- Section 3b: No Funds - Objection --}}
                        <div id="section_3b" class="border rounded p-3 mb-3"
                            style="display: {{ $requisition->budget_approved != 1 && $requisition->hec_objection == 'Objection to start' ? 'block' : 'none' }};">
                            <h6 class="mb-3"><i class="fas fa-times-circle me-2 text-danger"></i>Position is
                                not
                                in
                                budget and/or no funds are confirmed</h6>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="radio" name="hec_objection" value="Objection to start"
                                        id="objection_start" class="form-check-input"
                                        {{ $requisition->hec_objection == 'Objection to start' ? 'checked' : '' }}
                                        {{ !$canEditHEC ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="objection_start">Objection to start
                                        recruitment/renewal</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Comments</label>
                                @if (!$canEditHEC && empty($requisition->hec_member_comment))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="hec_member_comment" class="form-control" rows="4" placeholder="Enter your comments here..."
                                        {{ !$canEditHEC ? 'readonly disabled' : '' }} style="min-height: 100px; resize: vertical;">{{ $requisition->hec_member_comment ?? '' }}</textarea>
                                @endif
                                @error('hec_member_comment')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>After this step is
                                signed,
                                document to be brought to HR for filing.</small>
                        </div>

                        {{-- Section 3c: No Funds - No Objection --}}
                        <div id="section_3c" class="border rounded p-3 mb-3"
                            style="display: {{ $requisition->budget_approved != 1 && $requisition->hec_objection == 'No objection to start' ? 'block' : 'none' }};">
                            <h6 class="mb-3"><i class="fas fa-exclamation-circle me-2 text-warning"></i>Position
                                is
                                not in budget and/or no funds are confirmed</h6>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="radio" name="hec_objection" value="No objection to start"
                                        id="no_objection_start" class="form-check-input"
                                        {{ $requisition->hec_objection == 'No objection to start' ? 'checked' : '' }}
                                        {{ !$canEditHEC ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="no_objection_start">No objection to
                                        start
                                        recruitment/renewal</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Justification</label>
                                @if (!$canEditHEC && empty($requisition->hec_justification))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="hec_justification" class="form-control" rows="3"
                                        {{ !$canEditHEC ? 'readonly disabled' : '' }}>{{ $requisition->hec_justification ?? '' }}</textarea>
                                @endif
                                @error('hec_justification')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Proposed Funding</label>
                                @if (!$canEditHEC && empty($requisition->hec_proposed_funding))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="hec_proposed_funding" class="form-control" rows="3"
                                        {{ !$canEditHEC ? 'readonly disabled' : '' }}>{{ $requisition->hec_proposed_funding ?? '' }}</textarea>
                                @endif
                                @error('hec_proposed_funding')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>After this step is
                                signed,
                                document to be brought to CFO for financial review, thereafter to CEO.</small>
                        </div>

                        {{-- HEC Member Signature Section --}}
                        <hr class="my-4">
                        <div class="border-top pt-4">
                            <h6 class="mb-3 fw-semibold">
                                <i
                                    class="fas fa-user-tie me-2 text-success"></i>{{ isset($approver) && $approver && $approver->hasRole('chief_accountant') ? 'Chief Accountant' : 'HEC Member' }}
                                Signature
                            </h6>
                            @if (isset($approver) && $approver)
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-muted small">Name</label>
                                        <div class="p-2 bg-light rounded">
                                            {{ $approver->fname }} {{ $approver->lname }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-muted small">Signature</label>
                                        <div class="p-2 bg-light rounded">
                                            @if ($approver->signature)
                                                <img src="data:image/png;base64,{{ $approver->signature }}"
                                                    alt="Signature"
                                                    style="max-width: 150px; max-height: 60px; object-fit: contain;">
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-muted small">Date</label>
                                        @if ($canEditHEC)
                                            <input type="date" name="hec_signature_date" class="form-control"
                                                value="{{ $approver->updated_at ? \Carbon\Carbon::parse($approver->updated_at)->format('Y-m-d') : '' }}">
                                            @error('hec_signature_date')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        @else
                                            <div class="p-2 bg-light rounded">
                                                <span
                                                    class="text-muted">{{ $approver->updated_at ? \Carbon\Carbon::parse($approver->updated_at)->format('Y-m-d') : 'N/A' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                {{-- Only show error if HEC section is actually active and approver is required --}}
                                @if ($canEditHEC)
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        HEC approver will be assigned automatically when the requisition reaches HEC review
                                        stage.
                                    </div>
                                @else
                                    {{-- For viewers (Line Managers, etc.), show informational message --}}
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        HEC approver information will be displayed once the requisition is reviewed by HEC.
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
        </div>
        @endif

        {{-- Section 5: CFO & CEO Review --}}
        {{-- Show ONLY after HEC member action (no objection) for requests that are NOT in budget --}}
        @php
            // Default: hide CFO & CEO section
            $showCfoCeoSection = false;

            // Show when:
            // - Position is NOT in budget (budget_approved != 1)
            // - HEC has already approved (hecApproved == true)
            // - HEC decision is "No objection to start" (path 3c)
            if (
                !$requisition->budget_approved &&
                $hecApproved &&
                $requisition->hec_objection === 'No objection to start'
            ) {
                $showCfoCeoSection = true;
            }
        @endphp
        @php
            // Always show section, but use blank data if not approved
            $showCFOData = $cfoApproved;
            $showCEOData = $ceoApproved;
        @endphp
        @if ($showCfoCeoSection)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0 section-title">
                        <i class="fas fa-user-tie me-2" style="color: #007A33;"></i>4. Review by CFO & CEO
                    </h5>
                </div>
                <div class="card-body">
                    {{-- CFO Section --}}
                    <div class="border rounded p-3 mb-4">
                        <h6 class="mb-3"><i class="fas fa-user-tie me-2 text-success"></i>CFO Review
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Financing Confirmation</label>
                                @if (!$canEditCFO && empty($requisition->cfo_financing_confirmation))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="cfo_financing_confirmation" class="form-control" rows="3"
                                        {{ !$canEditCFO ? 'readonly disabled' : '' }}>{{ $requisition->cfo_financing_confirmation ?? '' }}</textarea>
                                @endif
                                @error('cfo_financing_confirmation')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Financing Code</label>
                                @if (!$canEditCFO && empty($requisition->cfo_financing_code))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <input type="text" name="cfo_financing_code" class="form-control"
                                        {{ !$canEditCFO ? 'readonly disabled' : '' }}
                                        value="{{ $requisition->cfo_financing_code ?? '' }}">
                                @endif
                                @error('cfo_financing_code')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Comment</label>
                                @if (!$canEditCFO && empty($requisition->cfo_comment))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="cfo_comment" class="form-control" rows="3" {{ !$canEditCFO ? 'readonly disabled' : '' }}>{{ $requisition->cfo_comment ?? '' }}</textarea>
                                @endif
                                @error('cfo_comment')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        @if (isset($cfoToApprove))
                            <hr class="my-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-muted small">CFO
                                        Name</label>
                                    <div class="p-2 bg-light rounded">
                                        {{ $cfoToApprove->fname }} {{ $cfoToApprove->lname }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-muted small">CFO
                                        Signature</label>
                                    <div class="p-2 bg-light rounded">
                                        @if (isset($cfoToApprove->signature))
                                            <img src="data:image/png;base64,{{ $cfoToApprove->signature }}"
                                                alt="CFO Signature"
                                                style="max-width: 150px; max-height: 60px; object-fit: contain;">
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-muted small">Date</label>
                                    <input type="date" name="cfo_signature_date" class="form-control"
                                        {{ !$canEditCFO ? 'readonly disabled' : '' }}
                                        value="{{ $cfoToApprove->updated_at ? \Carbon\Carbon::parse($cfoToApprove->updated_at)->format('Y-m-d') : '' }}">
                                    @error('cfo_signature_date')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- CEO Section --}}
                    @role(['hr', 'ceo'])
                        <div class="border rounded p-3 bg-light">
                            <h6 class="mb-3"><i class="fas fa-user-tie me-2 text-success"></i>CEO Review
                            </h6>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">CEO Comment</label>
                                @if (!$canEditCEO && empty($requisition->ceo_comment))
                                    <div class="p-2 bg-light rounded text-muted">N/A</div>
                                @else
                                    <textarea name="ceo_comment" class="form-control" rows="4"
                                        placeholder="Enter your comments or reasons for decision..." {{ !$canEditCEO ? 'readonly disabled' : '' }}>{{ old('ceo_comment', $requisition->ceo_comment ?? '') }}</textarea>
                                @endif
                                @error('ceo_comment')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            @if (isset($ceoToApprove))
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-muted small">CEO
                                            Name</label>
                                        <div class="p-2 bg-white rounded">
                                            {{ $ceoToApprove->fname ?? 'N/A' }}
                                            {{ $ceoToApprove->lname ?? '' }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-muted small">CEO
                                            Signature</label>
                                        <div class="p-2 bg-white rounded">
                                            @if (isset($ceoToApprove->signature) && !empty($ceoToApprove->signature))
                                                <img src="data:image/png;base64,{{ $ceoToApprove->signature }}"
                                                    alt="CEO Signature"
                                                    style="max-width: 150px; max-height: 60px; object-fit: contain;">
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-muted small">Date</label>
                                        <input type="date" name="ceo_signature_date" class="form-control"
                                            {{ !$canEditCEO ? 'readonly disabled' : '' }}
                                            value="{{ isset($ceoToApprove->updated_at) && $ceoToApprove->updated_at ? \Carbon\Carbon::parse($ceoToApprove->updated_at)->format('Y-m-d') : '' }}">
                                        @error('ceo_signature_date')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endrole
                </div>
            </div>
        @endif

        {{-- Section 6: HR Finalization --}}
        @role('hr')
            @php
                // Check if requisition is ready for HR finalization
                // Ready if: HEC approved with budget (no objection) OR workflow status is 3 (sent to HR)
                $isReadyForHR = false;
                if ($requisition->workflow) {
                    $isReadyForHR =
                        $requisition->workflow->work_flow_status == 3 ||
                        ($requisition->budget_approved &&
                            $requisition->funding_available &&
                            $requisition->hec_objection == 'in_budget_no_objection');
                }
            @endphp
            {{-- Always show HR section to HR users --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0 section-title">
                        <i class="fas fa-user-check me-2" style="color: #007A33;"></i>HR Finalization & Processing
                    </h5>
                    <small class="text-muted d-block mt-1" style="font-size: 0.9rem;">
                        <i class="fas fa-info-circle me-1"></i>To be completed by the HR Officer after all previous approvals
                        are done. Once this section is completed the recruitment requisition is fully processed and can be
                        filed.
                    </small>
                </div>
                <div class="card-body">
                    @php
                        $isHRCompletedGlobal = $workflowCompleted;
                        // Get HR user for display
                        $hrUser = isset($hrToApprove) ? $hrToApprove : null;
                        if (!$hrUser && $requisition->workflow) {
                            $hrHistoryRecord = $requisition->workflow
                                ->histories()
                                ->whereHas('attendedBy.roles', function ($q) {
                                    $q->where('name', 'hr');
                                })
                                ->latest()
                                ->first();
                            if ($hrHistoryRecord && $hrHistoryRecord->attendedBy) {
                                $hrUser = $hrHistoryRecord->attendedBy;
                            }
                        }
                    @endphp

                    {{-- HR Comments/Notes Section --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-2">HR Comments/Notes</label>
                        @if (!empty($requisition->hr_comments))
                            {{-- Show comments if they exist --}}
                            <div class="p-3 bg-light rounded">
                                {{ $requisition->hr_comments }}
                            </div>
                        @elseif ($canEditHR && !$isHRCompletedGlobal)
                            {{-- Show textarea only if HR can edit and form is not completed --}}
                            <textarea name="hr_comments" class="form-control" rows="5"
                                placeholder="Enter any comments or notes regarding the requisition processing..."
                                style="min-height: 120px; resize: vertical;">{{ old('hr_comments', '') }}</textarea>
                            @error('hr_comments')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        @else
                            {{-- Show N/A if no comments and cannot edit --}}
                            <div class="p-3 bg-light rounded text-muted">N/A</div>
                        @endif
                    </div>

                    {{-- HR Officer Signature Section --}}
                    <hr class="my-4">
                    <div class="border-top pt-4">
                        <h6 class="mb-3 fw-semibold">
                            <i class="fas fa-user-tie me-2 text-success"></i>HR Officer Signature
                        </h6>
                        @if ($hrUser || $requisition->hr_processed)
                            <div class="row g-3">
                                @if ($hrUser)
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-muted small">Name</label>
                                        <div class="p-2 bg-light rounded">
                                            {{ $hrUser->fname ?? 'N/A' }} {{ $hrUser->lname ?? '' }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-muted small">Signature</label>
                                        <div class="p-2 bg-light rounded">
                                            @if (isset($hrUser->signature) && !empty($hrUser->signature))
                                                <img src="data:image/png;base64,{{ $hrUser->signature }}" alt="HR Signature"
                                                    style="max-width: 150px; max-height: 60px; object-fit: contain;">
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-muted small">Date</label>
                                    @if ($canEditHR && !$isHRCompletedGlobal)
                                        <input type="date" name="hr_signature_date" class="form-control"
                                            value="{{ $requisition->hr_signature_date ? \Carbon\Carbon::parse($requisition->hr_signature_date)->format('Y-m-d') : (isset($hrUser->updated_at) && $hrUser->updated_at ? \Carbon\Carbon::parse($hrUser->updated_at)->format('Y-m-d') : '') }}">
                                        @error('hr_signature_date')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    @else
                                        <div class="p-2 bg-light rounded">
                                            <span class="text-dark">
                                                @if ($requisition->hr_signature_date)
                                                    {{ \Carbon\Carbon::parse($requisition->hr_signature_date)->format('Y-m-d') }}
                                                @elseif (isset($hrUser->updated_at) && $hrUser->updated_at)
                                                    {{ \Carbon\Carbon::parse($hrUser->updated_at)->format('Y-m-d') }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- HR Processing Details --}}
                            @if ($requisition->hr_processed)
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-muted small">Processed Status</label>
                                        <div class="p-2 bg-light rounded">
                                            <span class="badge bg-success">Processed</span>
                                        </div>
                                    </div>
                                    @if ($requisition->hr_processed_at)
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold text-muted small">Processed At</label>
                                            <div class="p-2 bg-light rounded">
                                                <span
                                                    class="text-dark">{{ \Carbon\Carbon::parse($requisition->hr_processed_at)->format('F d, Y H:i') }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                HR Officer information will be displayed once the requisition is processed by HR.
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        @endrole

        {{-- HR Status Section (visible to all users when HR has made a decision) --}}
        {{-- For HEC members: only show if CEO has approved (HR comes after CEO) --}}
        @php
            $user = auth()->user();
            $isHECMember = $user->hasAnyRole(['coo', 'cms', 'chief_accountant']);
            $canViewHRStatus = !$user->hasRole('hr') && $requisition->workflow;

            // For HEC members: only show HR status if CEO has approved
            if ($isHECMember && !$ceoApproved) {
                $canViewHRStatus = false;
            }
        @endphp
        @if ($canViewHRStatus)
            @php
                $hrStatus = null;
                $hrHistory = $requisition->workflow
                    ->histories()
                    ->whereHas('attendedBy.roles', function ($q) {
                        $q->where('name', 'hr');
                    })
                    ->whereIn('requisition_status', [3, 2]) // Completed or Rejected
                    ->latest()
                    ->first();

                if ($hrHistory) {
                    if ($hrHistory->requisition_status == 3) {
                        $hrStatus = 'approved';
                    } elseif ($hrHistory->requisition_status == 2) {
                        $hrStatus = 'rejected';
                    }
                } elseif (
                    $requisition->workflow->work_flow_completed == 1 &&
                    $requisition->workflow->work_flow_status == 3
                ) {
                    $hrStatus = 'approved';
                } elseif (
                    $requisition->workflow->work_flow_completed == 1 &&
                    $requisition->workflow->work_flow_status == 2
                ) {
                    $hrStatus = 'rejected';
                }
            @endphp

            @if ($hrStatus)
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0 text-dark">
                            <i class="fas fa-user-check me-2 text-success"></i>HR Finalization &
                            Processing
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ($hrStatus === 'approved')
                            <div class="alert alert-success mb-3">
                                <h6 class="fw-semibold mb-2">
                                    <i class="fas fa-check-circle me-2"></i>HR Review Status: Approved
                                </h6>
                                <p class="mb-1">
                                    <span class="text-muted">Status:</span>
                                    <span class="badge bg-success">Approved & Completed</span>
                                </p>
                                @if ($hrHistory && $hrHistory->decision_date)
                                    <p class="mb-1">
                                        <span class="text-muted">Decision Date &amp; Time:</span>
                                        <span
                                            class="fw-semibold">{{ \Carbon\Carbon::parse($hrHistory->decision_date)->format('F d, Y H:i') }}</span>
                                    </p>
                                @endif
                                @if ($hrHistory && $hrHistory->attendedBy)
                                    <p class="mb-1">
                                        <span class="text-muted">Processed by:</span>
                                        <span class="fw-semibold">{{ $hrHistory->attendedBy->fname }}
                                            {{ $hrHistory->attendedBy->lname }}</span>
                                    </p>
                                    @if (!empty($hrHistory->attendedBy->signature))
                                        <p class="mb-1">
                                            <span class="text-muted">HR Officer Signature:</span><br>
                                            <img src="data:image/png;base64,{{ $hrHistory->attendedBy->signature }}"
                                                alt="HR Officer Signature"
                                                style="max-width: 180px; max-height: 70px; object-fit: contain;">
                                        </p>
                                    @endif
                                @endif
                            </div>
                        @elseif ($hrStatus === 'rejected')
                            <div class="alert alert-danger mb-3">
                                <h6 class="fw-semibold mb-2">
                                    <i class="fas fa-times-circle me-2"></i>HR Review Status: Rejected
                                </h6>
                                <p class="mb-1">
                                    <span class="text-muted">Status:</span>
                                    <span class="badge bg-danger">Rejected</span>
                                </p>
                                @if ($hrHistory && $hrHistory->decision_date)
                                    <p class="mb-1">
                                        <span class="text-muted">Decision Date &amp; Time:</span>
                                        <span
                                            class="fw-semibold">{{ \Carbon\Carbon::parse($hrHistory->decision_date)->format('F d, Y H:i') }}</span>
                                    </p>
                                @endif
                                @if ($hrHistory && $hrHistory->rejection_reason)
                                    <p class="mb-1">
                                        <span class="text-muted">Rejection Reason:</span>
                                        <span class="fw-normal">{{ $hrHistory->rejection_reason }}</span>
                                    </p>
                                @endif
                                @if ($hrHistory && $hrHistory->attendedBy)
                                    <p class="mb-1">
                                        <span class="text-muted">Rejected by:</span>
                                        <span class="fw-semibold">{{ $hrHistory->attendedBy->fname }}
                                            {{ $hrHistory->attendedBy->lname }}</span>
                                    </p>
                                    @if (!empty($hrHistory->attendedBy->signature))
                                        <p class="mb-1">
                                            <span class="text-muted">HR Officer Signature:</span><br>
                                            <img src="data:image/png;base64,{{ $hrHistory->attendedBy->signature }}"
                                                alt="HR Officer Signature"
                                                style="max-width: 180px; max-height: 70px; object-fit: contain;">
                                        </p>
                                    @endif
                                @endif
                            </div>
                        @endif

                        {{-- HR Comments/Notes --}}
                        @if (!empty($requisition->hr_comments))
                            <div class="mb-4">
                                <label class="form-label fw-semibold mb-2">HR Comments/Notes</label>
                                <div class="p-3 bg-light rounded">
                                    {{ $requisition->hr_comments }}
                                </div>
                            </div>
                        @endif

                        {{-- HR Officer Signature Section --}}
                        @php
                            $hrProcessor = null;
                            if (isset($hrToApprove) && $hrToApprove) {
                                $hrProcessor = $hrToApprove;
                            } elseif ($hrHistory && $hrHistory->attendedBy) {
                                $hrProcessor = $hrHistory->attendedBy;
                            }
                        @endphp
                        @if ($hrProcessor || $requisition->hr_processed)
                            <hr class="my-4">
                            <div class="border-top pt-4">
                                <h6 class="mb-3 fw-semibold">
                                    <i class="fas fa-user-tie me-2 text-success"></i>HR Officer Signature
                                </h6>
                                <div class="row g-3">
                                    @if ($hrProcessor)
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-muted small">Name</label>
                                            <div class="p-2 bg-light rounded">
                                                {{ $hrProcessor->fname ?? 'N/A' }} {{ $hrProcessor->lname ?? '' }}
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-muted small">Signature</label>
                                            <div class="p-2 bg-light rounded">
                                                @if (isset($hrProcessor->signature) && !empty($hrProcessor->signature))
                                                    <img src="data:image/png;base64,{{ $hrProcessor->signature }}"
                                                        alt="HR Signature"
                                                        style="max-width: 150px; max-height: 60px; object-fit: contain;">
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-muted small">Date</label>
                                        <div class="p-2 bg-light rounded">
                                            @if ($requisition->hr_signature_date)
                                                <span
                                                    class="text-dark">{{ \Carbon\Carbon::parse($requisition->hr_signature_date)->format('Y-m-d') }}</span>
                                            @elseif (isset($hrHistory) && $hrHistory->decision_date)
                                                <span
                                                    class="text-dark">{{ \Carbon\Carbon::parse($hrHistory->decision_date)->format('Y-m-d') }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- HR Processing Details --}}
                                @if ($requisition->hr_processed)
                                    <div class="row g-3 mt-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold text-muted small">Processed Status</label>
                                            <div class="p-2 bg-light rounded">
                                                <span class="badge bg-success">Processed</span>
                                            </div>
                                        </div>
                                        @if ($requisition->hr_processed_at)
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold text-muted small">Processed At</label>
                                                <div class="p-2 bg-light rounded">
                                                    <span
                                                        class="text-dark">{{ \Carbon\Carbon::parse($requisition->hr_processed_at)->format('F d, Y H:i') }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        {{-- Action Buttons --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('requisitions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>

                    @php
                        // Prevent initiator (line manager or HEC member who started the form) from approving/rejecting their own requisition
                        $isInitiatorSelf =
                            ($requisition->initiator_id && $requisition->initiator_id === $user->id) ||
                            (!$requisition->initiator_id && $requisition->user_id === $user->id);
                    @endphp

                    @role(['coo', 'cfo', 'cms', 'ceo', 'chief_accountant', 'payroll_accountant'])
                        @php
                            // Enable button if user can edit any section they have access to
                            $canApprove = false;
                            if ($user->hasRole('payroll_accountant') && $canEditBudget) {
                                $canApprove = true;
                            } elseif ($user->hasAnyRole(['coo', 'cms', 'chief_accountant']) && $canEditHEC) {
                                $canApprove = true;
                            } elseif ($user->hasRole('cfo') && $canEditCFO) {
                                $canApprove = true;
                            } elseif ($user->hasRole('ceo') && $canEditCEO) {
                                $canApprove = true;
                            }
                        @endphp
                        @if (!$isInitiatorSelf)
                            <button type="submit" name="action" value="approve" id="globalApproveButton"
                                class="btn btn-success" {{ !$canApprove ? 'disabled' : '' }}>
                                <i class="fas fa-check me-1"></i> Approve & Save
                            </button>
                        @endif
                    @endrole
                    @role(['coo', 'cfo', 'cms', 'ceo', 'chief_accountant'])
                        @php
                            // Enable reject button if user can edit their section
                            $canReject = false;
                            if ($user->hasAnyRole(['coo', 'cms', 'chief_accountant']) && $canEditHEC) {
                                $canReject = true;
                            } elseif ($user->hasRole('cfo') && $canEditCFO) {
                                $canReject = true;
                            } elseif ($user->hasRole('ceo') && $canEditCEO) {
                                $canReject = true;
                            }
                        @endphp
                        @if (!$isInitiatorSelf)
                            <button type="button" class="btn btn-danger" onclick="rejectRequisition()"
                                {{ !$canReject ? 'disabled' : '' }}>
                                <i class="fas fa-times me-1"></i> Reject & Save
                            </button>
                        @endif
                    @endrole

                    @role('hr')
                        @php
                            // Ready for HR if workflow is not completed yet
                            $isHRCompleted = $requisition->workflow && $requisition->workflow->work_flow_completed == 1;
                            $isReadyForHR = !$isHRCompleted;
                        @endphp
                        @if (!$isHRCompleted && $isReadyForHR)
                            <button type="submit" name="action" value="approve" class="btn btn-success"
                                {{ !$canEditHR ? 'disabled' : '' }}>
                                <i class="fas fa-check me-1"></i> Approve & Save
                            </button>
                            <button type="button" class="btn btn-danger" onclick="rejectRequisition()"
                                {{ !$canEditHR ? 'disabled' : '' }}>
                                <i class="fas fa-times me-1"></i> Reject & Save
                            </button>
                        @elseif($isHRCompleted)
                            <span class="badge bg-success mb-2">
                                <i class="fas fa-check me-1"></i>HR Review Completed
                            </span>
                        @endif
                        @if ($isHRCompleted)
                            <a href="{{ route('requisitions.download', $requisition->access_id) }}" class="btn btn-success">
                                <i class="fas fa-download me-1"></i> Download PDF
                            </a>
                        @endif
                    @endrole

                    {{-- Line Manager PDF download when form is completed --}}
                    @if ($isCreator && $workflowCompleted)
                        <a href="{{ route('requisitions.download', $requisition->access_id) }}" class="btn btn-success">
                            <i class="fas fa-download me-1"></i> Download PDF
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <input type="hidden" name="rejection_reason" id="rejection_reason" value="">
        </form>
    </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="fas fa-times-circle me-2"></i>Reject Requisition
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('requisitions.update', $requisition->access_id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Are you sure you want to reject this requisition? This action cannot be undone.
                        </div>
                        <div class="mb-3">
                            <label for="rejection_reason_modal" class="form-label fw-semibold">Rejection Reason <span
                                    class="text-danger">*</span></label>
                            <textarea name="rejection_reason" id="rejection_reason_modal" class="form-control" rows="4" required
                                placeholder="Please provide a reason for rejection..."></textarea>
                        </div>
                        <input type="hidden" name="action" value="reject">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i> Reject Requisition
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Return to Line Manager Modal --}}
    <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="returnModalLabel">
                        <i class="fas fa-undo me-2"></i>Return to Line Manager
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('requisitions.update', $requisition->access_id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This will return the requisition to the Line Manager for review and corrections.
                        </div>
                        <div class="mb-3">
                            <label for="return_comment" class="form-label fw-semibold">Comments <span
                                    class="text-danger">*</span></label>
                            <textarea name="return_comment" id="return_comment" class="form-control" rows="4" required
                                placeholder="Please provide comments for the Line Manager..."></textarea>
                        </div>
                        <input type="hidden" name="action" value="return">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo me-1"></i> Return to Line Manager
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Ensure footer is at the bottom of the page */
        .page-wrapper {
            min-height: calc(100vh - 200px);
            padding-bottom: 100px;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            padding: 17px 0;
            background: #fff;
            text-align: center;
            border-top: 1px solid #e9ecef;
            z-index: 100;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
        }

        footer span {
            color: #808191;
            font-size: 14px;
            margin: 0 10px;
        }

        .approval-status-item {
            min-width: 150px;
            padding: 0.5rem;
            border-radius: 6px;
            background-color: #f8f9fa;
        }

        .status-indicator {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .status-indicator.status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-indicator.status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-indicator.status-not-started {
            background-color: #e9ecef;
            color: #6c757d;
        }

        .status-indicator.status-skipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .approval-status-item small {
            font-size: 0.75rem;
        }
    </style>
    <style>
        /* Enhanced Information Display Styles */
        .info-display-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid #e9ecef;
            border-left: 4px solid #6c757d;
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .info-display-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            border-left-color: #495057;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            min-width: 200px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
        }

        .info-label i {
            margin-right: 0.5rem;
            width: 20px;
            color: #6c757d;
        }

        .info-value {
            color: #212529;
            font-size: 0.95rem;
            flex: 1;
            font-weight: 500;
        }

        .info-value .badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }

        /* Section Headers */
        .section-header-modern {
            background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
            color: white;
            padding: 1.25rem 1.5rem;
            border-radius: 0.5rem 0.5rem 0 0;
            margin: -1.5rem -1.5rem 1.5rem -1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-header-modern h5 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .section-header-modern .badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.4rem 0.8rem;
        }

        /* Hide disabled form fields and show info display instead */
        .form-control:disabled,
        .form-select:disabled,
        textarea:disabled {
            display: none !important;
        }

        .form-control[readonly],
        .form-select[readonly],
        textarea[readonly] {
            display: none !important;
        }

        /* Edit Mode Toggle */
        .edit-mode-badge {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Progress indicator */
        .progress-indicator {
            min-width: 100%;
        }

        .progress {
            background-color: #e9ecef;
            border-radius: 6px;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        /* Workflow Timeline Styles */
        .workflow-timeline {
            position: relative;
            padding: 20px 0;
        }

        .workflow-step {
            display: flex;
            margin-bottom: 30px;
            position: relative;
        }

        .workflow-step:last-child {
            margin-bottom: 0;
        }

        .step-indicator {
            position: relative;
            margin-right: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 3px solid #dee2e6;
            font-size: 1.2rem;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .workflow-step.active .step-icon {
            background: #fff3cd;
            border-color: #ffc107;
            animation: pulse 2s infinite;
        }

        .workflow-step.completed .step-icon {
            background: #d1e7dd;
            border-color: #198754;
        }

        .workflow-step.rejected .step-icon {
            background: #f8d7da;
            border-color: #dc3545;
        }

        .step-connector {
            width: 3px;
            height: 60px;
            background: #dee2e6;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .step-connector.completed {
            background: #198754;
        }

        .workflow-step.active .step-connector {
            background: linear-gradient(to bottom, #198754 0%, #dee2e6 100%);
        }

        .step-content {
            flex: 1;
            padding: 10px 0;
        }

        .step-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .step-name {
            font-weight: 600;
            color: #212529;
            margin: 0;
        }

        .step-approver {
            font-size: 0.85rem;
        }

        .step-comment {
            margin-top: 5px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #6c757d;
        }

        .action-panel {
            border-left: 4px solid #6c757d;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .workflow-step {
                flex-direction: column;
            }

            .step-indicator {
                margin-right: 0;
                margin-bottom: 15px;
            }

            .step-connector {
                display: none;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function toggleFundingSectionsEarly() {
            const withFunds = document.getElementById('funding_with_funds_early');
            const noFunds = document.getElementById('funding_no_funds_early');
            const section3a = document.getElementById('section_3a_early');
            const section3b = document.getElementById('section_3b_early');
            const section3c = document.getElementById('section_3c_early');

            if (withFunds && withFunds.checked) {
                if (section3a) section3a.style.display = 'block';
                if (section3b) section3b.style.display = 'none';
                if (section3c) section3c.style.display = 'none';
            } else if (noFunds && noFunds.checked) {
                if (section3a) section3a.style.display = 'none';
                // Show 3b or 3c based on objection value
                const objectionValue = document.querySelector('input[name="hec_objection"]:checked')?.value;
                if (objectionValue === 'Objection to start') {
                    if (section3b) section3b.style.display = 'block';
                    if (section3c) section3c.style.display = 'none';
                } else {
                    if (section3b) section3b.style.display = 'none';
                    if (section3c) section3c.style.display = 'block';
                }
            }
        }

        function toggleFundingSections() {
            const withFunds = document.getElementById('funding_with_funds').checked;
            document.getElementById('section_3a').style.display = withFunds ? 'block' : 'none';
            document.getElementById('section_3b').style.display = withFunds ? 'none' : 'block';
            document.getElementById('section_3c').style.display = withFunds ? 'none' : 'block';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Toggle budget fields based on "Position Approved in Budget" selection
            const budgetApprovedRadios = document.querySelectorAll('input[name="budget_approved"]');
            const budgetFields = document.getElementById('budgetFields');
            const fundingFields = document.getElementById('fundingFields');

            function toggleBudgetFields() {
                // If there are no radios on this page (view-only users like HR/HEC),
                // do not override the server-rendered visibility.
                if (!budgetApprovedRadios || budgetApprovedRadios.length === 0) {
                    return;
                }

                const selected = document.querySelector('input[name="budget_approved"]:checked');
                if (selected && selected.value == '1') {
                    if (budgetFields) budgetFields.style.display = 'block';
                    if (fundingFields) fundingFields.style.display = 'block';
                } else {
                    if (budgetFields) budgetFields.style.display = 'none';
                    if (fundingFields) fundingFields.style.display = 'none';
                }
            }

            if (budgetApprovedRadios && budgetApprovedRadios.length > 0) {
                budgetApprovedRadios.forEach(function(radio) {
                    radio.addEventListener('change', toggleBudgetFields);
                });

                // Initial state for users who can change budget radios
                toggleBudgetFields();
            }

            // Initialize regular funding sections
            @if (isset($requisition->hec_objection))
                const hecStatus = '{{ $requisition->hec_objection }}';
                if (hecStatus === 'Objection to start' || hecStatus === 'No objection to start') {
                    document.getElementById('funding_no_funds').checked = true;
                    toggleFundingSections();
                } else {
                    toggleFundingSections();
                }
            @else
                toggleFundingSections();
            @endif

            // Initialize early funding sections (for HEC initiated forms)
            @if (isset($hecInitiatedByCurrentUser) && $hecInitiatedByCurrentUser)
                @if (isset($requisition->hec_objection))
                    const hecStatusEarly = '{{ $requisition->hec_objection }}';
                    if (hecStatusEarly === 'Objection to start' || hecStatusEarly === 'No objection to start') {
                        const noFundsEarly = document.getElementById('funding_no_funds_early');
                        if (noFundsEarly) {
                            noFundsEarly.checked = true;
                            toggleFundingSectionsEarly();
                        }
                    } else {
                        toggleFundingSectionsEarly();
                    }
                @else
                    toggleFundingSectionsEarly();
                @endif
            @endif

            // Auto-set decision and clear comments if form was initiated by HEC and has budget
            @if (isset($initiatedByHEC) && $initiatedByHEC && $requisition->budget_approved == 1 && !$requisition->hec_objection)
                const section3a = document.getElementById('section_3a');
                if (section3a && section3a.style.display !== 'none') {
                    // Auto-select "No objection to start recruitment/renewal"
                    const noObjectionRadio = document.getElementById('no_objection');
                    if (noObjectionRadio && !noObjectionRadio.checked) {
                        noObjectionRadio.checked = true;
                    }

                    // Clear comments field
                    const commentsField = document.getElementById('hec_member_comment_3a');
                    if (commentsField && commentsField.value.trim() === '') {
                        commentsField.value = '';
                    }
                }
            @endif
        });

        function rejectRequisition() {
            Swal.fire({
                title: 'Reason for Rejection',
                text: 'Please provide a reason for rejecting this requisition:',
                input: 'textarea',
                inputPlaceholder: 'Enter reason here...',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                inputValidator: (value) => {
                    if (!value) {
                        return 'You must provide a reason!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('rejection_reason').value = result.value;
                    let actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'reject';
                    let form = document.querySelector('form');
                    form.appendChild(actionInput);
                    form.submit();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const approveButton = document.querySelector('button[name="action"][value="approve"]');
            if (approveButton) {
                approveButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Confirm Approval',
                        text: 'Are you sure you want to approve this requisition?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Approve',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let form = document.querySelector('form');
                            form.submit();
                        }
                    });
                });
            }

            // Auto-hide global approve button when HEC selects an Objection option
            var globalApproveBtn = document.getElementById('globalApproveButton');
            if (globalApproveBtn) {
                function updateApproveVisibilityForHEC() {
                    var selected = document.querySelector('input[name="hec_objection"]:checked');
                    if (!selected) {
                        globalApproveBtn.classList.remove('d-none');
                        return;
                    }
                    var v = selected.value;
                    var isObjection = (v === 'Objection to start' || v === 'in_budget_with_objection');
                    if (isObjection) {
                        globalApproveBtn.classList.add('d-none');
                    } else {
                        globalApproveBtn.classList.remove('d-none');
                    }
                }

                document.querySelectorAll('input[name="hec_objection"]').forEach(function(radio) {
                    radio.addEventListener('change', updateApproveVisibilityForHEC);
                });

                // Initial state on page load
                updateApproveVisibilityForHEC();
            }
        });
    </script>
@endpush
