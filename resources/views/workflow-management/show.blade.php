@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
<style>
    .workflow-timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .workflow-timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -24px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #007A33;
        border: 2px solid white;
        box-shadow: 0 0 0 2px #007A33;
    }
    
    .timeline-item.completed::before {
        background: #28a745;
        box-shadow: 0 0 0 2px #28a745;
    }
    
    .timeline-item.rejected::before {
        background: #dc3545;
        box-shadow: 0 0 0 2px #dc3545;
    }
    
    .timeline-item.pending::before {
        background: #ffc107;
        box-shadow: 0 0 0 2px #ffc107;
    }

    /* Summary Cards */
    .summary-card {
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .summary-card .card-header {
        border-radius: 12px 12px 0 0;
        font-weight: 600;
    }
    .summary-card.submitted-by .card-header {
        background: linear-gradient(135deg, #007A33 0%, #00a344 100%);
        color: white;
    }
    .summary-card.pending-with .card-header {
        background: linear-gradient(135deg, #ffc107 0%, #ffca2c 100%);
        color: #333;
    }
    .summary-card.approval-flow .card-header {
        background: linear-gradient(135deg, #0d6efd 0%, #3d8bfd 100%);
        color: white;
    }
    
    /* Approval Flow Visual */
    .approval-flow-visual {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 15px 0;
    }
    .flow-step {
        display: flex;
        align-items: center;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px 15px;
        border: 2px solid #dee2e6;
        min-width: 150px;
    }
    .flow-step.approved {
        background: #d4edda;
        border-color: #28a745;
    }
    .flow-step.rejected {
        background: #f8d7da;
        border-color: #dc3545;
    }
    .flow-step.pending {
        background: #fff3cd;
        border-color: #ffc107;
        animation: pulse 2s infinite;
    }
    .flow-step.current {
        border-width: 3px;
        box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .flow-arrow {
        color: #6c757d;
        font-size: 1.5rem;
    }
    .flow-step-icon {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        font-size: 1rem;
    }
    .flow-step.approved .flow-step-icon {
        background: #28a745;
        color: white;
    }
    .flow-step.rejected .flow-step-icon {
        background: #dc3545;
        color: white;
    }
    .flow-step.pending .flow-step-icon {
        background: #ffc107;
        color: #333;
    }
    .flow-step-content {
        flex: 1;
    }
    .flow-step-name {
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 2px;
    }
    .flow-step-user {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    /* Custom Badge Colors */
    .bg-purple { background-color: #6f42c1 !important; color: white !important; }
    .bg-teal { background-color: #20c997 !important; color: white !important; }
    .bg-orange { background-color: #fd7e14 !important; color: white !important; }
    .bg-indigo { background-color: #6610f2 !important; color: white !important; }
    .bg-cyan { background-color: #0dcaf0 !important; color: black !important; }
</style>
<div class="page-wrapper">
    <div class="content container-fluid">
        <br>
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header d-flex justify-content-between align-items-center">
                        <h3 class="page-title mb-0"><i class="fas fa-project-diagram me-2"></i>Workflow Details</h3>
                        <a href="{{ route('workflow-management.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to Workflows
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            // Get form type and submitter info
            $formType = 'Unknown';
            $formTypeIcon = 'fa-question-circle';
            $formTypeBadgeClass = 'bg-secondary';
            $formTypeId = null;
            
            if ($workflow->ict_request_resource_id) {
                $formType = 'ICT Access';
                $formTypeIcon = 'fa-server';
                $formTypeBadgeClass = 'bg-primary';
                $formTypeId = $workflow->ict_request_resource_id;
            } elseif ($workflow->hr_form) {
                $formType = 'HR Form';
                $formTypeIcon = 'fa-file-alt';
                $formTypeBadgeClass = 'bg-success';
                $formTypeId = $workflow->hr_form;
            } elseif ($workflow->bank_form) {
                $formType = 'Bank Form';
                $formTypeIcon = 'fa-university';
                $formTypeBadgeClass = 'bg-dark';
                $formTypeId = $workflow->bank_form;
            } elseif ($workflow->heslb_form) {
                $formType = 'HESLB Form';
                $formTypeIcon = 'fa-graduation-cap';
                $formTypeBadgeClass = 'bg-warning text-dark';
                $formTypeId = $workflow->heslb_form;
            } elseif ($workflow->nhif_form) {
                $formType = 'NHIF Form';
                $formTypeIcon = 'fa-hospital';
                $formTypeBadgeClass = 'bg-danger';
                $formTypeId = $workflow->nhif_form;
            } elseif ($workflow->requisition_id) {
                $formType = 'Requisition';
                $formTypeIcon = 'fa-shopping-cart';
                $formTypeBadgeClass = 'bg-purple';
                $formTypeId = $workflow->requisition_id;
            } elseif ($workflow->job_description_id) {
                $formType = 'Job Description';
                $formTypeIcon = 'fa-briefcase';
                $formTypeBadgeClass = 'bg-teal';
                $formTypeId = $workflow->job_description_id;
            } elseif ($workflow->locum_agreement_id) {
                $formType = 'Locum Agreement';
                $formTypeIcon = 'fa-handshake';
                $formTypeBadgeClass = 'bg-orange';
                $formTypeId = $workflow->locum_agreement_id;
            } elseif ($workflow->locum_request_id) {
                $formType = 'Locum Request';
                $formTypeIcon = 'fa-user-md';
                $formTypeBadgeClass = 'bg-indigo';
                $formTypeId = $workflow->locum_request_id;
            } elseif ($workflow->on_call_request_id) {
                $formType = 'On-Call Request';
                $formTypeIcon = 'fa-phone';
                $formTypeBadgeClass = 'bg-cyan';
                $formTypeId = $workflow->on_call_request_id;
            } elseif ($workflow->ccbrt_contract_id) {
                $formType = 'CCBRT Contract';
                $formTypeIcon = 'fa-file-contract';
                $formTypeBadgeClass = 'bg-success';
                $formTypeId = $workflow->ccbrt_contract_id;
            } elseif ($workflow->contract_renewal_id) {
                $formType = 'Contract Renewal';
                $formTypeIcon = 'fa-sync-alt';
                $formTypeBadgeClass = 'bg-info';
                $formTypeId = $workflow->contract_renewal_id;
            } elseif ($workflow->change_request_id) {
                $formType = 'Change Request';
                $formTypeIcon = 'fa-exchange-alt';
                $formTypeBadgeClass = 'bg-warning text-dark';
                $formTypeId = $workflow->change_request_id;
            }
            
            // Get current pending step(s)
            $pendingSteps = $histories->where('status', 0);
            $currentPendingStep = $pendingSteps->first();
            
            // Get completed steps
            $completedSteps = $histories->where('status', 1);
            $rejectedSteps = $histories->where('status', 2);
        @endphp

        <!-- Summary Cards Row -->
        <div class="row mb-4">
            <!-- Submitted By Card -->
            <div class="col-md-4 mb-3">
                <div class="card summary-card submitted-by shadow-sm h-100">
                    <div class="card-header">
                        <i class="fas fa-user-edit me-2"></i>Submitted By
                    </div>
                    <div class="card-body">
                        @if($workflow->user)
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <div class="bg-light rounded-circle p-3">
                                        <i class="fas fa-user fa-2x text-success"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-1">{{ $workflow->user->fname ?? '' }} {{ $workflow->user->lname ?? '' }}</h5>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-id-badge me-1"></i>{{ $workflow->user->username ?? 'N/A' }}
                                    </p>
                                    @if($workflow->user->email)
                                        <p class="mb-1 text-muted small">
                                            <i class="fas fa-envelope me-1"></i>{{ $workflow->user->email }}
                                        </p>
                                    @endif
                                    @if($workflow->user->department)
                                        <p class="mb-1 text-info small">
                                            <i class="fas fa-building me-1"></i>{{ $workflow->user->department->dept_name ?? 'N/A' }}
                                        </p>
                                    @endif
                                    @if($workflow->user->roles && $workflow->user->roles->count() > 0)
                                        <div class="mt-2">
                                            @foreach($workflow->user->roles as $role)
                                                <span class="badge bg-secondary badge-sm me-1">{{ $role->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <p class="mb-0 text-muted small mt-2">
                                        <i class="fas fa-calendar me-1"></i>Submitted: {{ \Carbon\Carbon::parse($workflow->created_at)->format('d M Y, H:i') }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                User Not Found (ID: {{ $workflow->user_id }})
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Currently Pending With Card -->
            <div class="col-md-4 mb-3">
                <div class="card summary-card pending-with shadow-sm h-100">
                    <div class="card-header">
                        <i class="fas fa-hourglass-half me-2"></i>Currently Pending With
                    </div>
                    <div class="card-body">
                        @if($workflow->work_flow_completed)
                            <div class="text-center py-3">
                                <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                                <h5 class="text-success">Workflow Completed</h5>
                                <p class="text-muted mb-0">No pending approvals</p>
                            </div>
                        @elseif($currentPendingStep)
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <div class="bg-warning rounded-circle p-3">
                                        <i class="fas fa-user-clock fa-2x text-dark"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-warning fw-bold">{{ $currentPendingStep->step_name ?? 'Pending Approval' }}</h6>
                                    @if($currentPendingStep->attendedBy)
                                        <h5 class="mb-1">{{ $currentPendingStep->attendedBy->fname ?? '' }} {{ $currentPendingStep->attendedBy->lname ?? '' }}</h5>
                                        <p class="mb-1 text-muted small">
                                            <i class="fas fa-id-badge me-1"></i>{{ $currentPendingStep->attendedBy->username ?? 'N/A' }}
                                        </p>
                                        @if($currentPendingStep->attendedBy->email)
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-envelope me-1"></i>{{ $currentPendingStep->attendedBy->email }}
                                            </p>
                                        @endif
                                        @if($currentPendingStep->attendedBy->department)
                                            <p class="mb-1 text-info small">
                                                <i class="fas fa-building me-1"></i>{{ $currentPendingStep->attendedBy->department->dept_name ?? 'N/A' }}
                                            </p>
                                        @endif
                                        @if($currentPendingStep->attendedBy->roles && $currentPendingStep->attendedBy->roles->count() > 0)
                                            <div class="mt-2">
                                                @foreach($currentPendingStep->attendedBy->roles as $role)
                                                    <span class="badge bg-secondary badge-sm me-1">{{ $role->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($currentPendingStep->attendedBy->status != 'active')
                                            <span class="badge bg-danger mt-2"><i class="fas fa-exclamation-triangle me-1"></i>Inactive User</span>
                                        @endif
                                    @else
                                        <div class="alert alert-danger mb-0 mt-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            User Not Found (ID: {{ $currentPendingStep->attended_by }})
                                        </div>
                                    @endif
                                    <p class="mb-0 text-muted small mt-2">
                                        <i class="fas fa-clock me-1"></i>Pending since: {{ \Carbon\Carbon::parse($currentPendingStep->created_at)->diffForHumans() }}
                                    </p>
                                    @if(Auth::user()->can('manage workflows'))
                                        <a href="{{ route('workflow-management.edit-history', $currentPendingStep->id) }}" class="btn btn-warning btn-sm mt-2">
                                            <i class="fas fa-edit me-1"></i>Edit/Reassign
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-question-circle fa-3x text-muted mb-2"></i>
                                <h5 class="text-muted">No Pending Steps</h5>
                                <p class="text-muted mb-0">Workflow may be stuck or incomplete</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Form Type & Status Card -->
            <div class="col-md-4 mb-3">
                <div class="card summary-card approval-flow shadow-sm h-100">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>Form Details
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Form Type</label>
                            <div>
                                <span class="badge {{ $formTypeBadgeClass }} fs-6">
                                    <i class="fas {{ $formTypeIcon }} me-1"></i>{{ $formType }}
                                </span>
                                @if($formTypeId)
                                    <span class="text-muted ms-2">#{{ $formTypeId }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Workflow Status</label>
                            <div>
                                @if($workflow->work_flow_completed)
                                    <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>Completed</span>
                                @else
                                    <span class="badge bg-warning text-dark fs-6"><i class="fas fa-clock me-1"></i>Pending</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Progress</label>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1" style="height: 10px;">
                                    @php
                                        $totalSteps = $histories->count();
                                        $completedCount = $completedSteps->count();
                                        $percentage = $totalSteps > 0 ? round(($completedCount / $totalSteps) * 100) : 0;
                                    @endphp
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentage }}%"></div>
                                </div>
                                <span class="ms-2 small fw-bold">{{ $completedCount }}/{{ $totalSteps }}</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-muted small mb-1">Workflow ID</label>
                            <div class="fw-bold">#{{ $workflow->id }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visual Approval Flow -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="fas fa-route me-2 text-primary"></i>Approval Flow</h5>
            </div>
            <div class="card-body">
                @if($histories->count() > 0)
                    <div class="approval-flow-visual">
                        <!-- Submitter -->
                        <div class="flow-step approved">
                            <div class="flow-step-icon">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div class="flow-step-content">
                                <div class="flow-step-name">Submitted</div>
                                <div class="flow-step-user">{{ $workflow->user->fname ?? 'Unknown' }} {{ $workflow->user->lname ?? '' }}</div>
                            </div>
                        </div>
                        
                        @foreach($histories->sortBy('created_at') as $index => $history)
                            <i class="fas fa-arrow-right flow-arrow"></i>
                            <div class="flow-step {{ $history->status == 1 ? 'approved' : ($history->status == 2 ? 'rejected' : 'pending') }} {{ $history->status == 0 && $loop->first ? 'current' : '' }}">
                                <div class="flow-step-icon">
                                    @if($history->status == 1)
                                        <i class="fas fa-check"></i>
                                    @elseif($history->status == 2)
                                        <i class="fas fa-times"></i>
                                    @else
                                        <i class="fas fa-hourglass-half"></i>
                                    @endif
                                </div>
                                <div class="flow-step-content">
                                    <div class="flow-step-name">{{ $history->step_name ?? 'Step ' . ($index + 1) }}</div>
                                    <div class="flow-step-user">
                                        @if($history->attendedBy)
                                            {{ $history->attendedBy->fname ?? '' }} {{ $history->attendedBy->lname ?? '' }}
                                        @else
                                            <span class="text-danger">User Not Found</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        @if($workflow->work_flow_completed)
                            <i class="fas fa-arrow-right flow-arrow"></i>
                            <div class="flow-step approved">
                                <div class="flow-step-icon">
                                    <i class="fas fa-flag-checkered"></i>
                                </div>
                                <div class="flow-step-content">
                                    <div class="flow-step-name">Completed</div>
                                    <div class="flow-step-user">{{ \Carbon\Carbon::parse($workflow->updated_at)->format('d M Y') }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    @if($formType === 'Unknown')
                        <div class="alert alert-danger mb-0">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>This Request Has an Error</h5>
                            <hr>
                            <p class="mb-2"><strong>Problem:</strong> No workflow history found and form type is unknown.</p>
                            <p class="mb-2"><strong>Form Type:</strong> <span class="badge {{ $formTypeBadgeClass }}"><i class="fas {{ $formTypeIcon }} me-1"></i>{{ $formType }}</span></p>
                            <p class="mb-2"><strong>Submitted By:</strong> {{ $workflow->user->fname ?? 'Unknown' }} {{ $workflow->user->lname ?? '' }} ({{ $workflow->user->username ?? 'N/A' }})</p>
                            <p class="mb-0"><strong>Created:</strong> {{ \Carbon\Carbon::parse($workflow->created_at)->format('d M Y, H:i') }}</p>
                            <hr>
                            <p class="mb-0 small text-muted">
                                <i class="fas fa-info-circle me-1"></i>This workflow was created but no approval steps were configured and the source form cannot be identified. 
                                This is usually caused by a system error during form submission or the original form was deleted.
                                You can delete this orphaned workflow.
                            </p>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>No Approval Steps Yet</h5>
                            <hr>
                            <p class="mb-2"><strong>Form Type:</strong> <span class="badge {{ $formTypeBadgeClass }}"><i class="fas {{ $formTypeIcon }} me-1"></i>{{ $formType }}</span> @if($formTypeId) #{{ $formTypeId }} @endif</p>
                            <p class="mb-2"><strong>Submitted By:</strong> {{ $workflow->user->fname ?? 'Unknown' }} {{ $workflow->user->lname ?? '' }} ({{ $workflow->user->username ?? 'N/A' }})</p>
                            <p class="mb-0"><strong>Created:</strong> {{ \Carbon\Carbon::parse($workflow->created_at)->format('d M Y, H:i') }}</p>
                            <hr>
                            <p class="mb-0 small text-muted">
                                <i class="fas fa-info-circle me-1"></i>This workflow has no approval history yet. It may be newly created or the approval flow hasn't started.
                            </p>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        @if($formType === 'Unknown')
        <!-- Unknown Form Type Error -->
        <div class="card shadow-sm mb-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0"><i class="fas fa-exclamation-circle me-2"></i>Unknown Form Type Error</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger mb-0">
                    <h5 class="alert-heading"><i class="fas fa-question-circle me-2"></i>This Request Has an Error - Unknown Form Type</h5>
                    <hr>
                    <p class="mb-2"><strong>Problem:</strong> The source form for this workflow cannot be identified.</p>
                    <p class="mb-2"><strong>Possible Causes:</strong></p>
                    <ul class="mb-2">
                        <li>The original form/request was deleted from the database</li>
                        <li>The form type is not recognized by the system</li>
                        <li>Data corruption or migration issue</li>
                    </ul>
                    <p class="mb-2"><strong>Workflow Details:</strong></p>
                    <ul class="mb-2">
                        <li><strong>Workflow ID:</strong> #{{ $workflow->id }}</li>
                        <li><strong>User ID:</strong> {{ $workflow->user_id }}</li>
                        <li><strong>Created:</strong> {{ \Carbon\Carbon::parse($workflow->created_at)->format('d M Y, H:i') }}</li>
                        <li><strong>ICT Request ID:</strong> {{ $workflow->ict_request_resource_id ?? 'NULL' }}</li>
                        <li><strong>HR Form:</strong> {{ $workflow->hr_form ?? 'NULL' }}</li>
                        <li><strong>Bank Form:</strong> {{ $workflow->bank_form ?? 'NULL' }}</li>
                        <li><strong>HESLB Form:</strong> {{ $workflow->heslb_form ?? 'NULL' }}</li>
                        <li><strong>NHIF Form:</strong> {{ $workflow->nhif_form ?? 'NULL' }}</li>
                        <li><strong>Requisition ID:</strong> {{ $workflow->requisition_id ?? 'NULL' }}</li>
                        <li><strong>Job Description ID:</strong> {{ $workflow->job_description_id ?? 'NULL' }}</li>
                        <li><strong>Locum Agreement ID:</strong> {{ $workflow->locum_agreement_id ?? 'NULL' }}</li>
                        <li><strong>Locum Request ID:</strong> {{ $workflow->locum_request_id ?? 'NULL' }}</li>
                        <li><strong>On-Call Request ID:</strong> {{ $workflow->on_call_request_id ?? 'NULL' }}</li>
                        <li><strong>CCBRT Contract ID:</strong> {{ $workflow->ccbrt_contract_id ?? 'NULL' }}</li>
                        <li><strong>Contract Renewal ID:</strong> {{ $workflow->contract_renewal_id ?? 'NULL' }}</li>
                        <li><strong>Change Request ID:</strong> {{ $workflow->change_request_id ?? 'NULL' }}</li>
                    </ul>
                    <hr>
                    <p class="mb-0 small text-muted">
                        <i class="fas fa-trash me-1"></i>Recommended action: Delete this orphaned workflow record if it's no longer needed.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <!-- Workflow Info -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-cog me-2"></i>Workflow Management</h5>
                @if(Auth::user()->can('manage workflows'))
                    <form action="{{ route('workflow-management.destroy', $workflow->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this workflow? This will also delete all associated workflow history. This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash me-1"></i> Delete Workflow
                        </button>
                    </form>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Workflow ID:</strong> #{{ $workflow->id }}</p>
                        <p><strong>User:</strong> 
                            @if($workflow->user)
                                <i class="fas fa-user me-1"></i>{{ $workflow->user->fname ?? '' }} {{ $workflow->user->lname ?? '' }} ({{ $workflow->user->username ?? 'N/A' }})
                                @if($workflow->user->roles && $workflow->user->roles->count() > 0)
                                    <br><small class="ms-3">
                                        @foreach($workflow->user->roles as $role)
                                            <span class="badge bg-secondary badge-sm me-1"><i class="fas fa-user-tag me-1"></i>{{ $role->name }}</span>
                                        @endforeach
                                    </small>
                                @endif
                                @if($workflow->user->department)
                                    <br><small class="text-info ms-3"><i class="fas fa-building me-1"></i>{{ $workflow->user->department->dept_name ?? 'N/A' }}</small>
                                @endif
                            @else
                                <span class="text-danger">User Not Found (ID: {{ $workflow->user_id }})</span>
                            @endif
                        </p>
                        <p><strong>Status:</strong> 
                            @if($workflow->work_flow_completed)
                                <span class="badge bg-success">Completed</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </p>
                        <p><strong>Form Type:</strong> 
                            <span class="badge {{ $formTypeBadgeClass }}"><i class="fas {{ $formTypeIcon }} me-1"></i>{{ $formType }}</span>
                            @if($formTypeId)
                                <span class="text-muted">#{{ $formTypeId }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Current Status:</strong> {{ $workflow->work_flow_status ?? 'N/A' }}</p>
                        <p><strong>Created:</strong> {{ \Carbon\Carbon::parse($workflow->created_at)->format('d F Y, h:i A') }}</p>
                        <p><strong>Updated:</strong> {{ \Carbon\Carbon::parse($workflow->updated_at)->format('d F Y, h:i A') }}</p>
                        <p><strong>History Count:</strong> {{ $histories->count() }} step(s)</p>
                        @if($histories->count() == 0)
                            <p class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>This workflow has no history records</p>
                        @endif
                    </div>
                </div>
                
                @if(Auth::user()->can('manage workflows'))
                <hr>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6 class="mb-3"><i class="fas fa-edit me-2"></i>Change Status</h6>
                        
                        {{-- Workflow Status Form --}}
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">
                                <strong>Workflow Status</strong>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('workflow-management.update-status', $workflow->id) }}" method="POST" class="row g-3">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-md-3">
                                        <label class="form-label"><strong>Completion Status</strong></label>
                                        <select name="work_flow_completed" class="form-select" required>
                                            <option value="0" {{ $workflow->work_flow_completed == 0 ? 'selected' : '' }}>Pending</option>
                                            <option value="1" {{ $workflow->work_flow_completed == 1 ? 'selected' : '' }}>Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><strong>Workflow Status</strong></label>
                                        <select name="work_flow_status" class="form-select" required>
                                            <option value="0" {{ $workflow->work_flow_status == 0 ? 'selected' : '' }}>Pending</option>
                                            <option value="1" {{ $workflow->work_flow_status == 1 ? 'selected' : '' }}>Approved</option>
                                            <option value="2" {{ $workflow->work_flow_status == 2 ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label"><strong>&nbsp;</strong></label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Update Workflow Status
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        {{-- Form-Specific Status --}}
                        @php
                            $latestHistory = $histories->sortByDesc('created_at')->first();
                            $hasFormStatus = false;
                            $formStatusField = null;
                            $formStatusValue = null;
                            
                            if ($workflow->locum_request_id && $latestHistory) {
                                $hasFormStatus = true;
                                $formStatusField = 'locum_request_status';
                                $formStatusValue = $latestHistory->locum_request_status ?? 0;
                            } elseif ($workflow->on_call_request_id && $latestHistory) {
                                $hasFormStatus = true;
                                $formStatusField = 'on_call_request_status';
                                $formStatusValue = $latestHistory->on_call_request_status ?? 0;
                            } elseif ($workflow->on_call_request_id && $workflow->onCallRequest) {
                                $hasFormStatus = true;
                                $formStatusField = 'on_call_request_status_direct';
                                $formStatusValue = $workflow->onCallRequest->status ?? 'pending';
                            }
                        @endphp
                        
                        @if($hasFormStatus && $latestHistory)
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <strong>Form Status ({{ $formType }})</strong>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('workflow-management.update-form-status', $workflow->id) }}" method="POST" class="row g-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="history_id" value="{{ $latestHistory->id }}">
                                    <input type="hidden" name="form_type" value="{{ $formType }}">
                                    <input type="hidden" name="status_field" value="{{ $formStatusField }}">
                                    
                                    @if($workflow->locum_request_id)
                                    <div class="col-md-4">
                                        <label class="form-label"><strong>Locum Request Status</strong></label>
                                        <select name="locum_request_status" class="form-select" required>
                                            <option value="0" {{ $formStatusValue == 0 ? 'selected' : '' }}>Pending</option>
                                            <option value="1" {{ $formStatusValue == 1 ? 'selected' : '' }}>Approved (Incharge)</option>
                                            <option value="2" {{ $formStatusValue == 2 ? 'selected' : '' }}>Approved (Platform Manager)</option>
                                            <option value="3" {{ $formStatusValue == 3 ? 'selected' : '' }}>Approved (Line Manager)</option>
                                            <option value="4" {{ $formStatusValue == 4 ? 'selected' : '' }}>Approved (HEC)</option>
                                            <option value="5" {{ $formStatusValue == 5 ? 'selected' : '' }}>Rejected</option>
                                            <option value="6" {{ $formStatusValue == 6 ? 'selected' : '' }}>Approved (HR)</option>
                                        </select>
                                    </div>
                                    @elseif($workflow->on_call_request_id)
                                    <div class="col-md-4">
                                        <label class="form-label"><strong>On-Call Request Status</strong></label>
                                        <select name="on_call_request_status" class="form-select" required>
                                            <option value="0" {{ $formStatusValue == 0 ? 'selected' : '' }}>Pending</option>
                                            <option value="1" {{ $formStatusValue == 1 ? 'selected' : '' }}>Approved (Incharge)</option>
                                            <option value="2" {{ $formStatusValue == 2 ? 'selected' : '' }}>Approved (Platform Manager)</option>
                                            <option value="3" {{ $formStatusValue == 3 ? 'selected' : '' }}>Approved (HEC)</option>
                                            <option value="4" {{ $formStatusValue == 4 ? 'selected' : '' }}>Approved (HR)</option>
                                            <option value="5" {{ $formStatusValue == 5 ? 'selected' : '' }}>Rejected</option>
                                            <option value="6" {{ $formStatusValue == 6 ? 'selected' : '' }}>Approved Final</option>
                                        </select>
                                    </div>
                                    @endif
                                    
                                    @if($workflow->on_call_request_id && $workflow->onCallRequest)
                                    <div class="col-md-4">
                                        <label class="form-label"><strong>On-Call Request Direct Status</strong></label>
                                        <select name="on_call_status" class="form-select">
                                            <option value="pending" {{ ($workflow->onCallRequest->status ?? 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ ($workflow->onCallRequest->status ?? 'pending') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ ($workflow->onCallRequest->status ?? 'pending') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </div>
                                    @endif
                                    
                                    <div class="col-md-4">
                                        <label class="form-label"><strong>&nbsp;</strong></label>
                                        <div>
                                            <button type="submit" class="btn btn-info text-white">
                                                <i class="fas fa-save me-1"></i> Update Form Status
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Manage Flow Section -->
        @if(!$workflow->work_flow_completed)
        <div class="card shadow-sm mb-4" id="manage-flow">
            <div class="card-header bg-white border-bottom" style="background-color: #007A33 !important;">
                <h5 class="card-title mb-0" style="color: white !important;"><i class="fas fa-route me-2"></i>Manage Workflow Flow</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">You can reassign the current pending step or skip/change the workflow flow. Use with caution.</p>
                @php
                    $currentPending = $histories->where('status', 0)->first();
                    $allPending = $histories->where('status', 0);
                @endphp
                @if($allPending->count() > 0)
                    <div class="row">
                        @foreach($allPending as $pending)
                            <div class="col-md-6 mb-3">
                                <div class="card border {{ !$pending->attendedBy || ($pending->attendedBy && $pending->attendedBy->status != 'active') ? 'border-danger' : 'border-warning' }}">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-flag me-1 text-primary"></i>
                                            <strong>{{ $pending->step_name ?? 'N/A' }}</strong>
                                            @if($loop->first)
                                                <span class="badge bg-primary ms-2">Current</span>
                                            @endif
                                        </h6>
                                        <hr>
                                        @if($pending->attendedBy)
                                            <div class="mb-2">
                                                <strong><i class="fas fa-user-tie me-1"></i>Assigned To:</strong>
                                                <div class="mt-1">
                                                    {{ $pending->attendedBy->fname ?? '' }} {{ $pending->attendedBy->lname ?? '' }}
                                                    <br><small class="text-muted"><i class="fas fa-envelope me-1"></i>{{ $pending->attendedBy->email ?? 'N/A' }}</small>
                                                    @if($pending->attendedBy->roles && $pending->attendedBy->roles->count() > 0)
                                                        <br><small class="ms-2">
                                                            @foreach($pending->attendedBy->roles as $role)
                                                                <span class="badge bg-secondary badge-sm me-1"><i class="fas fa-user-tag me-1"></i>{{ $role->name }}</span>
                                                            @endforeach
                                                        </small>
                                                    @endif
                                                    @if($pending->attendedBy->department)
                                                        <br><small class="text-info"><i class="fas fa-building me-1"></i>{{ $pending->attendedBy->department->dept_name ?? 'N/A' }}</small>
                                                    @endif
                                                    @if($pending->attendedBy->status != 'active')
                                                        <br><span class="badge bg-danger mt-1">Inactive User</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($pending->forwardedBy)
                                                <div class="mb-2">
                                                    <strong><i class="fas fa-user me-1"></i>Forwarded By:</strong>
                                                    <div class="mt-1">
                                                        {{ $pending->forwardedBy->fname ?? '' }} {{ $pending->forwardedBy->lname ?? '' }}
                                                        @if($pending->forwardedBy->roles && $pending->forwardedBy->roles->count() > 0)
                                                            <br><small class="ms-2">
                                                                @foreach($pending->forwardedBy->roles as $role)
                                                                    <span class="badge bg-secondary badge-sm me-1"><i class="fas fa-user-tag me-1"></i>{{ $role->name }}</span>
                                                                @endforeach
                                                            </small>
                                                        @endif
                                                        @if($pending->forwardedBy->department)
                                                            <br><small class="text-info"><i class="fas fa-building me-1"></i>{{ $pending->forwardedBy->department->dept_name ?? 'N/A' }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                            @if($pending->remark)
                                                <div class="mb-2">
                                                    <strong><i class="fas fa-comment me-1"></i>Remark:</strong>
                                                    <div class="mt-1">
                                                        <small class="text-muted">{{ $pending->remark }}</small>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="mt-3">
                                                <a href="{{ route('workflow-management.edit-history', $pending->id) }}" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-wrench me-1"></i> Reassign This Step
                                                </a>
                                            </div>
                                        @else
                                            <div class="alert alert-danger mb-0">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <strong>User Not Found</strong>
                                                <br><small>User ID: {{ $pending->attended_by }}</small>
                                                <div class="mt-2">
                                                    <a href="{{ route('workflow-management.edit-history', $pending->id) }}" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-wrench me-1"></i> Fix This Error
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-1"></i>No pending steps found. This workflow may be completed or has no active history.
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Workflow History -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Workflow History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead style="background-color: #007A33;">
                            <tr>
                                <th style="color: white !important;">Step</th>
                                <th style="color: white !important;">Forwarded By</th>
                                <th style="color: white !important;">Attended By</th>
                                <th style="color: white !important;">Status</th>
                                <th style="color: white !important;">Date</th>
                                <th style="color: white !important;">Remark</th>
                                <th style="color: white !important;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($histories as $history)
                                @php
                                    $hasError = !$history->attendedBy || ($history->attendedBy && $history->attendedBy->status != 'active');
                                @endphp
                                <tr class="{{ $hasError && $history->status == 0 ? 'table-danger' : '' }}">
                                    <td>
                                        <strong>{{ $history->step_name ?? 'N/A' }}</strong>
                                        @if($history->status == 0)
                                            <br><small class="badge bg-warning text-dark">Pending</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($history->forwardedBy)
                                            <i class="fas fa-user me-1"></i>{{ $history->forwardedBy->fname ?? '' }} {{ $history->forwardedBy->lname ?? '' }}
                                            <br><small class="text-muted"><i class="fas fa-envelope me-1"></i>{{ $history->forwardedBy->email ?? '' }}</small>
                                            @if($history->forwardedBy->roles && $history->forwardedBy->roles->count() > 0)
                                                <br><small class="ms-2">
                                                    @foreach($history->forwardedBy->roles as $role)
                                                        <span class="badge bg-secondary badge-sm me-1"><i class="fas fa-user-tag me-1"></i>{{ $role->name }}</span>
                                                    @endforeach
                                                </small>
                                            @endif
                                            @if($history->forwardedBy->department)
                                                <br><small class="text-info"><i class="fas fa-building me-1"></i>{{ $history->forwardedBy->department->dept_name ?? 'N/A' }}</small>
                                            @endif
                                        @elseif($history->forwarded_by)
                                            <span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>User Not Found (ID: {{ $history->forwarded_by }})</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($history->attendedBy)
                                            <i class="fas fa-user-tie me-1"></i><strong>{{ $history->attendedBy->fname ?? '' }} {{ $history->attendedBy->lname ?? '' }}</strong>
                                            <br><small class="text-muted"><i class="fas fa-envelope me-1"></i>{{ $history->attendedBy->email ?? '' }}</small>
                                            @if($history->attendedBy->roles && $history->attendedBy->roles->count() > 0)
                                                <br><small class="ms-2">
                                                    @foreach($history->attendedBy->roles as $role)
                                                        <span class="badge bg-secondary badge-sm me-1"><i class="fas fa-user-tag me-1"></i>{{ $role->name }}</span>
                                                    @endforeach
                                                </small>
                                            @endif
                                            @if($history->attendedBy->department)
                                                <br><small class="text-info"><i class="fas fa-building me-1"></i>{{ $history->attendedBy->department->dept_name ?? 'N/A' }}</small>
                                            @endif
                                            @if($history->attendedBy->status != 'active')
                                                <br><span class="badge bg-danger">Inactive</span>
                                            @endif
                                        @elseif($history->attended_by)
                                            <span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>User Not Found (ID: {{ $history->attended_by }})</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($history->status == 1)
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Approved</span>
                                        @elseif($history->status == 2)
                                            <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Rejected</span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $history->attend_date ?? \Carbon\Carbon::parse($history->created_at)->format('d M Y') }}
                                        <br><small class="text-muted">{{ \Carbon\Carbon::parse($history->created_at)->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        @if($history->remark)
                                            <span title="{{ $history->remark }}">{{ \Illuminate\Support\Str::limit($history->remark, 50) }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(Auth::user()->can('manage workflows'))
                                            <div class="btn-group" role="group">
                                                @if($history->status == 0 && ($hasError || Auth::user()->can('manage workflows')))
                                                    <a href="{{ route('workflow-management.edit-history', $history->id) }}" class="btn btn-sm btn-warning" title="Edit/Reassign">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#statusModal{{ $history->id }}" title="Change Status">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Status Change Modal -->
                                            <div class="modal fade" id="statusModal{{ $history->id }}" tabindex="-1" aria-labelledby="statusModalLabel{{ $history->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="statusModalLabel{{ $history->id }}">Change Status - {{ $history->step_name ?? 'N/A' }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('workflow-management.update-history-status', $history->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label"><strong>Status</strong></label>
                                                                    <select name="status" class="form-select" required>
                                                                        <option value="0" {{ $history->status == 0 ? 'selected' : '' }}>Pending</option>
                                                                        <option value="1" {{ $history->status == 1 ? 'selected' : '' }}>Approved</option>
                                                                        <option value="2" {{ $history->status == 2 ? 'selected' : '' }}>Rejected</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label"><strong>Remark (Optional)</strong></label>
                                                                    <textarea name="remark" class="form-control" rows="3" placeholder="Add a remark about this status change...">{{ $history->remark }}</textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Update Status</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No workflow history found.</p>
                                        <p class="text-warning mt-2"><i class="fas fa-exclamation-triangle me-1"></i>This workflow appears to be orphaned or incomplete.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

