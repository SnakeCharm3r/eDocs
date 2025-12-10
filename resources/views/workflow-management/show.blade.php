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

        <!-- Workflow Info -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Workflow Information</h5>
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
                        @php
                            $formType = 'Unknown';
                            $formTypeId = null;
                            $formTypeName = null;
                            if ($workflow->ict_request_resource_id) {
                                $formType = 'ICT Access';
                                $formTypeId = $workflow->ict_request_resource_id;
                                $formTypeName = $workflow->ictAccessResource ? 'ICT Access Resource #' . $formTypeId : null;
                            } elseif ($workflow->hr_form) {
                                $formType = 'HR Form';
                                $formTypeId = $workflow->hr_form;
                            } elseif ($workflow->requisition_id) {
                                $formType = 'Requisition';
                                $formTypeId = $workflow->requisition_id;
                            } elseif ($workflow->locum_request_id) {
                                $formType = 'Locum Request';
                                $formTypeId = $workflow->locum_request_id;
                                $formTypeName = $workflow->locumRequest ? 'Locum Request #' . $formTypeId : null;
                            } elseif ($workflow->on_call_request_id) {
                                $formType = 'On-Call Request';
                                $formTypeId = $workflow->on_call_request_id;
                                $formTypeName = $workflow->onCallRequest ? 'On-Call Request #' . $formTypeId : null;
                            }
                        @endphp
                        <p><strong>Form Type:</strong> 
                            <span class="badge bg-secondary">{{ $formType }}</span>
                            @if($formTypeId)
                                @if($formTypeName)
                                    <br><small class="text-muted">{{ $formTypeName }}</small>
                                @else
                                    <br><small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Related form (ID: {{ $formTypeId }}) not found or deleted</small>
                                @endif
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
                                        @if($history->status == 0 && ($hasError || Auth::user()->can('manage workflows')))
                                            <a href="{{ route('workflow-management.edit-history', $history->id) }}" class="btn btn-sm btn-warning" title="Edit/Reassign">
                                                <i class="fas fa-edit"></i>
                                            </a>
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

