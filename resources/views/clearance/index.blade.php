@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
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
                            <h3 class="page-title"><i class="fas fa-clipboard-check me-2"></i>Employee Clearance Form</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    @if ($clearance && $clearance->userId === Auth::id())
                        <!-- Status Card -->
                        <div class="card shadow-sm mb-4" style="border-left: 4px solid #007A33;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-12">
                                        <h5 class="card-title mb-3">
                                            <i class="fas fa-info-circle me-2" style="color: #007A33;"></i>Status
                                        </h5>
                                        <div class="mb-2">
                                            <strong>Current Status:</strong>
                                            @if ($clearance->status === 'rejected')
                                                <span class="badge bg-danger ms-2"
                                                    style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                                    <i class="fas fa-times-circle me-1"></i>Rejected
                                                </span>
                                            @elseif($workflow && $workflow->work_flow_completed)
                                                <span class="badge bg-success ms-2"
                                                    style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                                    <i class="fas fa-check-circle me-1"></i>Completed
                                                </span>
                                            @elseif($workflow)
                                                <span class="badge bg-warning text-dark ms-2"
                                                    style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                                    <i
                                                        class="fas fa-clock me-1"></i>{{ $workflow->work_flow_status ?? 'Pending' }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary ms-2"
                                                    style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                                    <i class="fas fa-hourglass-half me-1"></i>Submitted
                                                </span>
                                            @endif
                                        </div>
                                        @if ($currentStep && $currentApprover && $clearance->status !== 'rejected')
                                            <div class="mb-2">
                                                <strong>Pending with:</strong>
                                                <span class="text-primary ms-2">
                                                    {{ $currentStep }} -
                                                    {{ trim(($currentApprover->fname ?? '') . ' ' . ($currentApprover->lname ?? '')) }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="mb-2">
                                            <strong>Submitted:</strong>
                                            <span class="text-muted ms-2">
                                                {{ \Carbon\Carbon::parse($clearance->created_at)->format('d F Y, h:i A') }}
                                            </span>
                                        </div>
                                        @if ($workflow && $workflow->work_flow_completed)
                                            <div class="mb-2">
                                                <strong>Completed:</strong>
                                                <span class="text-success ms-2">
                                                    {{ \Carbon\Carbon::parse($workflow->updated_at)->format('d F Y, h:i A') }}
                                                </span>
                                            </div>
                                        @endif

                                        {{-- Show rejection reason if rejected --}}
                                        @if ($clearance->status === 'rejected' && $clearance->rejection_reason)
                                            <div class="alert alert-danger mt-3 mb-0">
                                                <h6 class="alert-heading mb-2">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>Rejection Reason:
                                                </h6>
                                                <p class="mb-0">{{ $clearance->rejection_reason }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Important Details Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-user-circle me-2 text-primary"></i>Employee Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt text-muted me-2"></i>
                                            <strong>Date of Hire:</strong>
                                            <div class="mt-1">
                                                {{ $clearance->date_of_hire ? \Carbon\Carbon::parse($clearance->date_of_hire)->format('d F Y') : 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="info-item">
                                            <i class="fas fa-calendar-times text-muted me-2"></i>
                                            <strong>Last Working Day:</strong>
                                            <div class="mt-1">
                                                {{ $clearance->last_working_day ? \Carbon\Carbon::parse($clearance->last_working_day)->format('d F Y') : 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="info-item">
                                            <i class="fas fa-sign-out-alt text-muted me-2"></i>
                                            <strong>Reason for Leaving:</strong>
                                            <div class="mt-1">
                                                {{ $clearance->reason_for_leaving ?? 'N/A' }}
                                                @if ($clearance->reason_for_leaving === 'Other' && $clearance->reason_for_leaving_other)
                                                    <br><small class="text-muted">-
                                                        {{ $clearance->reason_for_leaving_other }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Show button to request new form if rejected --}}
                        @if ($clearance->status === 'rejected' && Auth::user()->hasRole('requester'))
                            <div class="card shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <p class="mb-2">Your clearance form has been rejected. You can submit a new
                                            clearance form.</p>
                                        <a href="{{ route('clearance.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Request New Clearance Form
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        {{-- Only show "No Clearance Form Found" if user is not an approver (HR, line-manager, finance officer, IT officer, etc.) --}}
                        @php
                            $isApprover = Auth::user()->hasAnyRole([
                                'hr',
                                'line-manager',
                                'finance officer',
                                'it',
                                'coo',
                                'cfo',
                                'cms',
                            ]);
                        @endphp
                        @if (!$isApprover)
                            <div class="card shadow-sm">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                                    <h4>No Clearance Form Found</h4>
                                    <p class="text-muted">You haven't submitted a clearance form yet.</p>
                                    @if (Auth::user()->hasRole('requester'))
                                        <a href="{{ route('clearance.create') }}" class="btn btn-primary mt-3">
                                            <i class="fas fa-plus me-2"></i>Create Clearance Form
                                        </a>
                                    @elseif (Auth::user()->hasRole('line-manager'))
                                        <a href="{{ route('clearance.create') }}" class="btn btn-primary mt-3">
                                            <i class="fas fa-user-plus me-2"></i>Request Clearance Form for Staff
                                        </a>
                                    @else
                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Only users with the "requester" role or line managers can create clearance
                                            forms.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Show Staff Clearances if Line Manager --}}
                    @if (isset($isLineManager) && $isLineManager)
                        <div class="card shadow-sm mt-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Clearance Forms for Line
                                    Manager Approval</h5>
                                <a href="{{ route('clearance.create') }}" class="btn btn-light btn-sm">
                                    <i class="fas fa-user-plus me-2"></i>Request New Clearance Form
                                </a>
                            </div>
                            @if (isset($staffClearances) && $staffClearances->count() > 0)
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Employee Name</th>
                                                    <th>Date of Hire</th>
                                                    <th>Last Working Day</th>
                                                    <th>Status</th>
                                                    <th>Current Step</th>
                                                    <th>Submitted</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($staffClearances as $staffClearance)
                                                    @php
                                                        $staffWorkflow = $staffClearance->workflow;
                                                        $currentHistory = null;
                                                        $currentStep = 'N/A';
                                                        $currentApprover = null;
                                                        $staffStatus = 'pending';

                                                        if ($staffWorkflow) {
                                                            // Get the latest history for this Line Manager
                                                            $currentHistory = \App\Models\Clearance_work_flow_history::where(
                                                                'work_flow_id',
                                                                $staffWorkflow->id,
                                                            )
                                                                ->where('attended_by', Auth::id())
                                                                ->where('step_name', 'Line Manager')
                                                                ->orderBy('created_at', 'desc')
                                                                ->first();

                                                            if ($currentHistory) {
                                                                $currentApprover = \App\Models\User::find(
                                                                    $currentHistory->attended_by,
                                                                );
                                                                $currentStep = $currentHistory->step_name;
                                                                $staffStatus =
                                                                    $currentHistory->status == 1
                                                                        ? 'approved'
                                                                        : ($currentHistory->status == 2
                                                                            ? 'rejected'
                                                                            : 'pending');
                                                            } else {
                                                                // Get current pending step
                                                                $currentHistory = \App\Models\Clearance_work_flow_history::where(
                                                                    'work_flow_id',
                                                                    $staffWorkflow->id,
                                                                )
                                                                    ->where('status', 0)
                                                                    ->orderBy('created_at', 'desc')
                                                                    ->first();
                                                                if ($currentHistory) {
                                                                    $currentApprover = \App\Models\User::find(
                                                                        $currentHistory->attended_by,
                                                                    );
                                                                    $currentStep = $currentHistory->step_name;
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $staffClearance->user->fname ?? '' }}
                                                                {{ $staffClearance->user->lname ?? '' }}</strong>
                                                            <br><small
                                                                class="text-muted">{{ $staffClearance->user->email ?? 'N/A' }}</small>
                                                        </td>
                                                        <td>{{ $staffClearance->date_of_hire ? \Carbon\Carbon::parse($staffClearance->date_of_hire)->format('d M Y') : 'N/A' }}
                                                        </td>
                                                        <td>{{ $staffClearance->last_working_day ? \Carbon\Carbon::parse($staffClearance->last_working_day)->format('d M Y') : 'N/A' }}
                                                        </td>
                                                        <td>
                                                            @if ($staffClearance->status === 'rejected')
                                                                <span class="badge bg-danger">Rejected</span>
                                                            @elseif($staffWorkflow && $staffWorkflow->work_flow_completed)
                                                                <span class="badge bg-success">Completed</span>
                                                            @elseif($staffStatus === 'approved')
                                                                <span class="badge bg-success">Approved</span>
                                                            @elseif($staffWorkflow)
                                                                <span
                                                                    class="badge bg-warning text-dark">{{ $staffWorkflow->work_flow_status ?? 'Pending' }}</span>
                                                            @else
                                                                <span class="badge bg-secondary">Submitted</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($currentStep !== 'N/A' && $currentApprover)
                                                                <small>{{ $currentStep }}<br><span
                                                                        class="text-muted">{{ trim(($currentApprover->fname ?? '') . ' ' . ($currentApprover->lname ?? '')) }}</span></small>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td><small>{{ \Carbon\Carbon::parse($staffClearance->created_at)->format('d M Y, h:i A') }}</small>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('exit_forms.show', $staffClearance->id) }}"
                                                                class="btn btn-sm btn-primary" title="View Details">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="card-body text-center py-4">
                                    <p class="text-muted mb-3">No clearance forms pending your approval.</p>
                                    <a href="{{ route('clearance.create') }}" class="btn btn-primary">
                                        <i class="fas fa-user-plus me-2"></i>Request Clearance Form for Staff
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Show HR Clearances if HR --}}
                    @if (isset($isHR) && $isHR && isset($hrClearances) && $hrClearances->count() > 0)
                        <div class="card shadow-sm mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Clearance Forms for HR
                                    Approval</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Employee Name</th>
                                                <th>Date of Hire</th>
                                                <th>Last Working Day</th>
                                                <th>Status</th>
                                                <th>Current Step</th>
                                                <th>Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($hrClearances as $hrClearance)
                                                @php
                                                    $hrWorkflow = $hrClearance->workflow;
                                                    $hrCurrentHistory = null;
                                                    $hrCurrentStep = 'N/A';
                                                    $hrCurrentApprover = null;
                                                    $hrStatus = 'pending';

                                                    if ($hrWorkflow) {
                                                        // Get the latest history for this HR user
                                                        $hrCurrentHistory = \App\Models\Clearance_work_flow_history::where(
                                                            'work_flow_id',
                                                            $hrWorkflow->id,
                                                        )
                                                            ->where('attended_by', Auth::id())
                                                            ->orderBy('created_at', 'desc')
                                                            ->first();

                                                        if ($hrCurrentHistory) {
                                                            $hrCurrentApprover = \App\Models\User::find(
                                                                $hrCurrentHistory->attended_by,
                                                            );
                                                            $hrCurrentStep = $hrCurrentHistory->step_name;
                                                            $hrStatus =
                                                                $hrCurrentHistory->status == 1
                                                                    ? 'approved'
                                                                    : ($hrCurrentHistory->status == 2
                                                                        ? 'rejected'
                                                                        : 'pending');
                                                        } else {
                                                            // Get current pending step
                                                            $hrCurrentHistory = \App\Models\Clearance_work_flow_history::where(
                                                                'work_flow_id',
                                                                $hrWorkflow->id,
                                                            )
                                                                ->where('status', 0)
                                                                ->orderBy('created_at', 'desc')
                                                                ->first();
                                                            if ($hrCurrentHistory) {
                                                                $hrCurrentApprover = \App\Models\User::find(
                                                                    $hrCurrentHistory->attended_by,
                                                                );
                                                                $hrCurrentStep = $hrCurrentHistory->step_name;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <strong>{{ $hrClearance->user->fname ?? '' }}
                                                            {{ $hrClearance->user->lname ?? '' }}</strong>
                                                        <br><small
                                                            class="text-muted">{{ $hrClearance->user->email ?? 'N/A' }}</small>
                                                    </td>
                                                    <td>{{ $hrClearance->date_of_hire ? \Carbon\Carbon::parse($hrClearance->date_of_hire)->format('d M Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $hrClearance->last_working_day ? \Carbon\Carbon::parse($hrClearance->last_working_day)->format('d M Y') : 'N/A' }}
                                                    </td>
                                                    <td>
                                                        @if ($hrClearance->status === 'rejected')
                                                            <span class="badge bg-danger">Rejected</span>
                                                        @elseif($hrWorkflow && $hrWorkflow->work_flow_completed)
                                                            <span class="badge bg-success">Completed</span>
                                                        @elseif($hrStatus === 'approved')
                                                            <span class="badge bg-success">Approved</span>
                                                        @elseif($hrWorkflow)
                                                            <span
                                                                class="badge bg-warning text-dark">{{ $hrWorkflow->work_flow_status ?? 'Pending' }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">Submitted</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($hrCurrentStep !== 'N/A' && $hrCurrentApprover)
                                                            <small>{{ $hrCurrentStep }}<br><span
                                                                    class="text-muted">{{ trim(($hrCurrentApprover->fname ?? '') . ' ' . ($hrCurrentApprover->lname ?? '')) }}</span></small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td><small>{{ \Carbon\Carbon::parse($hrClearance->created_at)->format('d M Y, h:i A') }}</small>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('exit_forms.show', $hrClearance->id) }}"
                                                            class="btn btn-sm btn-primary" title="View Details">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Show Finance Officer Clearances if Finance Officer --}}
                    @if (isset($isFinanceOfficer) && $isFinanceOfficer && isset($financeClearances) && $financeClearances->count() > 0)
                        <div class="card shadow-sm mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Clearance Forms for Finance
                                    Officer
                                    Approval</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Employee Name</th>
                                                <th>Date of Hire</th>
                                                <th>Last Working Day</th>
                                                <th>Status</th>
                                                <th>Current Step</th>
                                                <th>Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($financeClearances as $financeClearance)
                                                @php
                                                    $financeWorkflow = $financeClearance->workflow;
                                                    $financeCurrentHistory = null;
                                                    $financeCurrentStep = 'N/A';
                                                    $financeCurrentApprover = null;
                                                    $financeStatus = 'pending';

                                                    if ($financeWorkflow) {
                                                        // Get the latest history for this Finance Officer
                                                        $financeCurrentHistory = \App\Models\Clearance_work_flow_history::where(
                                                            'work_flow_id',
                                                            $financeWorkflow->id,
                                                        )
                                                            ->where('attended_by', Auth::id())
                                                            ->where('step_name', 'Finance Officer')
                                                            ->orderBy('created_at', 'desc')
                                                            ->first();

                                                        if ($financeCurrentHistory) {
                                                            $financeCurrentApprover = \App\Models\User::find(
                                                                $financeCurrentHistory->attended_by,
                                                            );
                                                            $financeCurrentStep = $financeCurrentHistory->step_name;
                                                            $financeStatus =
                                                                $financeCurrentHistory->status == 1
                                                                    ? 'approved'
                                                                    : ($financeCurrentHistory->status == 2
                                                                        ? 'rejected'
                                                                        : 'pending');
                                                        } else {
                                                            // Get current pending step for Finance Officer
                                                            $financeCurrentHistory = \App\Models\Clearance_work_flow_history::where(
                                                                'work_flow_id',
                                                                $financeWorkflow->id,
                                                            )
                                                                ->where('status', 0)
                                                                ->where('step_name', 'Finance Officer')
                                                                ->orderBy('created_at', 'desc')
                                                                ->first();
                                                            if ($financeCurrentHistory) {
                                                                $financeCurrentApprover = \App\Models\User::find(
                                                                    $financeCurrentHistory->attended_by,
                                                                );
                                                                $financeCurrentStep = $financeCurrentHistory->step_name;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <strong>{{ $financeClearance->user->fname ?? '' }}
                                                            {{ $financeClearance->user->lname ?? '' }}</strong>
                                                        <br><small
                                                            class="text-muted">{{ $financeClearance->user->email ?? 'N/A' }}</small>
                                                    </td>
                                                    <td>{{ $financeClearance->date_of_hire ? \Carbon\Carbon::parse($financeClearance->date_of_hire)->format('d M Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $financeClearance->last_working_day ? \Carbon\Carbon::parse($financeClearance->last_working_day)->format('d M Y') : 'N/A' }}
                                                    </td>
                                                    <td>
                                                        @if ($financeClearance->status === 'rejected')
                                                            <span class="badge bg-danger">Rejected</span>
                                                        @elseif($financeWorkflow && $financeWorkflow->work_flow_completed)
                                                            <span class="badge bg-success">Completed</span>
                                                        @elseif($financeStatus === 'approved')
                                                            <span class="badge bg-success">Approved</span>
                                                        @elseif($financeWorkflow)
                                                            <span
                                                                class="badge bg-warning text-dark">{{ $financeWorkflow->work_flow_status ?? 'Pending' }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">Submitted</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($financeCurrentStep !== 'N/A' && $financeCurrentApprover)
                                                            <small>{{ $financeCurrentStep }}<br><span
                                                                    class="text-muted">{{ trim(($financeCurrentApprover->fname ?? '') . ' ' . ($financeCurrentApprover->lname ?? '')) }}</span></small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td><small>{{ \Carbon\Carbon::parse($financeClearance->created_at)->format('d M Y, h:i A') }}</small>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('exit_forms.show', $financeClearance->id) }}"
                                                            class="btn btn-sm btn-primary" title="View Details">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Show IT Officer Clearances if IT Officer --}}
                    @if (isset($isITOfficer) && $isITOfficer && isset($itClearances) && $itClearances->count() > 0)
                        <div class="card shadow-sm mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Clearance Forms for IT
                                    Officer
                                    Approval</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Employee Name</th>
                                                <th>Date of Hire</th>
                                                <th>Last Working Day</th>
                                                <th>Status</th>
                                                <th>Current Step</th>
                                                <th>Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($itClearances as $itClearance)
                                                @php
                                                    $itWorkflow = $itClearance->workflow;
                                                    $itCurrentHistory = null;
                                                    $itCurrentStep = 'N/A';
                                                    $itCurrentApprover = null;
                                                    $itStatus = 'pending';

                                                    if ($itWorkflow) {
                                                        // Get the latest history for this IT Officer
                                                        $itCurrentHistory = \App\Models\Clearance_work_flow_history::where(
                                                            'work_flow_id',
                                                            $itWorkflow->id,
                                                        )
                                                            ->where('attended_by', Auth::id())
                                                            ->where('step_name', 'IT Officer')
                                                            ->orderBy('created_at', 'desc')
                                                            ->first();

                                                        if ($itCurrentHistory) {
                                                            $itCurrentApprover = \App\Models\User::find(
                                                                $itCurrentHistory->attended_by,
                                                            );
                                                            $itCurrentStep = $itCurrentHistory->step_name;
                                                            $itStatus =
                                                                $itCurrentHistory->status == 1
                                                                    ? 'approved'
                                                                    : ($itCurrentHistory->status == 2
                                                                        ? 'rejected'
                                                                        : 'pending');
                                                        } else {
                                                            // Get current pending step for IT Officer
                                                            $itCurrentHistory = \App\Models\Clearance_work_flow_history::where(
                                                                'work_flow_id',
                                                                $itWorkflow->id,
                                                            )
                                                                ->where('status', 0)
                                                                ->where('step_name', 'IT Officer')
                                                                ->orderBy('created_at', 'desc')
                                                                ->first();
                                                            if ($itCurrentHistory) {
                                                                $itCurrentApprover = \App\Models\User::find(
                                                                    $itCurrentHistory->attended_by,
                                                                );
                                                                $itCurrentStep = $itCurrentHistory->step_name;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <strong>{{ $itClearance->user->fname ?? '' }}
                                                            {{ $itClearance->user->lname ?? '' }}</strong>
                                                        <br><small
                                                            class="text-muted">{{ $itClearance->user->email ?? 'N/A' }}</small>
                                                    </td>
                                                    <td>{{ $itClearance->date_of_hire ? \Carbon\Carbon::parse($itClearance->date_of_hire)->format('d M Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $itClearance->last_working_day ? \Carbon\Carbon::parse($itClearance->last_working_day)->format('d M Y') : 'N/A' }}
                                                    </td>
                                                    <td>
                                                        @if ($itClearance->status === 'rejected')
                                                            <span class="badge bg-danger">Rejected</span>
                                                        @elseif($itWorkflow && $itWorkflow->work_flow_completed)
                                                            <span class="badge bg-success">Completed</span>
                                                        @elseif($itStatus === 'approved')
                                                            <span class="badge bg-success">Approved</span>
                                                        @elseif($itWorkflow)
                                                            <span
                                                                class="badge bg-warning text-dark">{{ $itWorkflow->work_flow_status ?? 'Pending' }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">Submitted</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($itCurrentStep !== 'N/A' && $itCurrentApprover)
                                                            <small>{{ $itCurrentStep }}<br><span
                                                                    class="text-muted">{{ trim(($itCurrentApprover->fname ?? '') . ' ' . ($itCurrentApprover->lname ?? '')) }}</span></small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td><small>{{ \Carbon\Carbon::parse($itClearance->created_at)->format('d M Y, h:i A') }}</small>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('exit_forms.show', $itClearance->id) }}"
                                                            class="btn btn-sm btn-primary" title="View Details">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .badge {
            padding: 0.5em 0.75em;
            font-size: 0.875rem;
        }

        .info-item {
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #007A33;
        }

        .info-item strong {
            color: #495057;
            display: block;
            margin-bottom: 0.5rem;
        }

        .timeline {
            position: relative;
            padding-left: 0;
        }

        .timeline-item {
            position: relative;
            padding-left: 0;
        }

        .timeline-marker {
            font-size: 0.875rem;
        }

        .card {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
        }

        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
        }
    </style>
@endsection
