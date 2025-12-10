@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <br>
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header d-flex justify-content-between align-items-center">
                        <h3 class="page-title mb-0"><i class="fas fa-edit me-2"></i>Edit Clearance Workflow History</h3>
                        <a href="{{ route('workflow-management.show-clearance', $history->work_flow_id) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Reassign Workflow</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('workflow-management.update-clearance-history', $history->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label"><strong>Current Assigned To:</strong></label>
                        <div class="p-3 border rounded bg-light">
                            @if($history->attendedBy)
                                <div>
                                    <i class="fas fa-user-tie me-1"></i><strong>{{ $history->attendedBy->fname ?? '' }} {{ $history->attendedBy->lname ?? '' }}</strong>
                                    <br><small class="text-muted"><i class="fas fa-user me-1"></i>{{ $history->attendedBy->username ?? 'N/A' }}</small>
                                    <br><small class="text-muted"><i class="fas fa-envelope me-1"></i>{{ $history->attendedBy->email ?? 'N/A' }}</small>
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
                                        <br><span class="badge bg-danger mt-1">Inactive User</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>User Not Found (ID: {{ $history->attended_by }})</span>
                            @endif
                        </div>
                    </div>
                    
                    @if($history->step_name)
                    <div class="mb-3">
                        <label class="form-label"><strong>Step Name:</strong></label>
                        <p class="form-control-plaintext">
                            <span class="badge bg-info">{{ $history->step_name }}</span>
                        </p>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="attended_by" class="form-label">Reassign To <span class="text-danger">*</span></label>
                        <select name="attended_by" id="attended_by" class="form-control @error('attended_by') is-invalid @enderror" required>
                            <option value="">Select User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('attended_by', $history->attended_by) == $user->id ? 'selected' : '' }}>
                                    {{ $user->fname }} {{ $user->lname }} ({{ $user->username }}) - {{ $user->email }}
                                    @if($user->department)
                                        - {{ $user->department->dept_name }}
                                    @endif
                                    @if($user->roles && $user->roles->count() > 0)
                                        - Roles: {{ $user->roles->pluck('name')->implode(', ') }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('attended_by')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="remark" class="form-label">Additional Remark</label>
                        <textarea name="remark" id="remark" class="form-control @error('remark') is-invalid @enderror" rows="3" placeholder="Enter reason for reassignment...">{{ old('remark') }}</textarea>
                        @error('remark')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This will reassign the workflow to the selected user. The original assignment will be logged in the remarks.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Assignment
                        </button>
                        <a href="{{ route('workflow-management.show-clearance', $history->work_flow_id) }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


