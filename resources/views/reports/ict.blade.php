@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <h3 class="page-title mb-0">
                                <i class="fas fa-server me-2"></i>ICT Access Form Reports
                            </h3>
                            <div class="d-flex gap-2">
                                <a href="{{ route('reports.selection') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm border-start border-primary border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1 small">Total Requests</h6>
                                    <h3 class="mb-0">{{ $totalCount }}</h3>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-list fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-start border-success border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1 small">Approved</h6>
                                    <h3 class="mb-0 text-success">{{ $approvedCount }}</h3>
                                    <small class="text-muted">{{ $totalCount > 0 ? round(($approvedCount / $totalCount) * 100, 1) : 0 }}%</small>
                                </div>
                                <div class="text-success">
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-start border-warning border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1 small">Pending</h6>
                                    <h3 class="mb-0 text-warning">{{ $pendingCount }}</h3>
                                    <small class="text-muted">{{ $totalCount > 0 ? round(($pendingCount / $totalCount) * 100, 1) : 0 }}%</small>
                                </div>
                                <div class="text-warning">
                                    <i class="fas fa-clock fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-start border-danger border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1 small">Rejected</h6>
                                    <h3 class="mb-0 text-danger">{{ $rejectedCount }}</h3>
                                    <small class="text-muted">{{ $totalCount > 0 ? round(($rejectedCount / $totalCount) * 100, 1) : 0 }}%</small>
                                </div>
                                <div class="text-danger">
                                    <i class="fas fa-times-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top Approvers Section --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Top Approvers</h5>
                            <small class="text-muted">Staff who approve most requests (for recognition/rewards)</small>
                        </div>
                        <div class="card-body">
                            @if($topApprovers->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Staff Name</th>
                                                <th class="text-end">Approvals</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topApprovers as $index => $approver)
                                                <tr>
                                                    <td>
                                                        @if($index < 3)
                                                            <span class="badge bg-warning text-dark">#{{ $index + 1 }}</span>
                                                        @else
                                                            <span class="text-muted">#{{ $index + 1 }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $approver->attendedBy ? ($approver->attendedBy->fname . ' ' . $approver->attendedBy->lname) : 'N/A' }}
                                                        <br><small class="text-muted">{{ $approver->attendedBy->username ?? 'N/A' }}</small>
                                                    </td>
                                                    <td class="text-end">
                                                        <strong class="text-success">{{ $approver->approval_count }}</strong>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0">No approval data available.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-chart-pie me-2 text-info"></i>Summary Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Approval Rate:</strong>
                                <div class="progress mt-1" style="height: 25px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: {{ $approvalRate }}%" 
                                         aria-valuenow="{{ $approvalRate }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        {{ $approvalRate }}%
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">Avg Processing Time</small>
                                        <strong>N/A</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">This Month</small>
                                        <strong>{{ IctAccessResource::whereHas('workflow')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count() }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h6>
                </div>
                <div class="card-body py-2 px-3">
                    <form method="GET" action="{{ route('reports.ict') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-0 small">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Status</option>
                                <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-0 small">Department</label>
                            <select name="department" class="form-select form-select-sm">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ $departmentFilter == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->dept_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-0 small">From Date</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-0 small">To Date</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-filter me-1"></i>Apply
                            </button>
                            <a href="{{ route('reports.ict') }}" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                                <i class="fas fa-redo me-1"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ICT Forms Table --}}
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">ICT Access Forms ({{ $ictForms->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="ictReportTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Requester</th>
                                    <th>Department</th>
                                    <th>Submitted Date</th>
                                    <th>Status</th>
                                    <th>Last Action</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ictForms as $index => $form)
                                    @php
                                        $workflow = $form->workflow;
                                        $status = 'Pending';
                                        $statusClass = 'warning';
                                        $statusIcon = 'clock';
                                        
                                        if ($workflow) {
                                            if ($workflow->work_flow_completed == 1) {
                                                if ($workflow->work_flow_status == 1) {
                                                    $status = 'Approved';
                                                    $statusClass = 'success';
                                                    $statusIcon = 'check-circle';
                                                } elseif ($workflow->work_flow_status == 2) {
                                                    $status = 'Rejected';
                                                    $statusClass = 'danger';
                                                    $statusIcon = 'times-circle';
                                                }
                                            }
                                        }
                                        
                                        $lastHistory = $workflow ? $workflow->histories->sortByDesc('id')->first() : null;
                                        $lastActionDate = $lastHistory ? \Carbon\Carbon::parse($lastHistory->updated_at)->format('d M Y H:i') : 'N/A';
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $form->user->fname ?? '' }} {{ $form->user->lname ?? '' }}</strong>
                                            <br><small class="text-muted">{{ $form->user->username ?? 'N/A' }}</small>
                                        </td>
                                        <td>{{ $form->user->department->dept_name ?? 'N/A' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($form->created_at)->format('d M Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}">
                                                <i class="fas fa-{{ $statusIcon }} me-1"></i>{{ $status }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ $lastActionDate }}</small>
                                            @if($lastHistory && $lastHistory->attendedBy)
                                                <br><small class="text-muted">By: {{ $lastHistory->attendedBy->fname ?? '' }} {{ $lastHistory->attendedBy->lname ?? '' }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('form.getform', ['id' => $form->id]) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               target="_blank">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No ICT Access Forms found matching the filters.
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

    @push('scripts')
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
    <script>
        $(document).ready(function() {
            $('#ictReportTable').DataTable({
                order: [[3, 'desc']], // Sort by submitted date
                pageLength: 25,
                language: {
                    emptyTable: "No ICT Access Forms found"
                }
            });
        });
    </script>
    @endpush
@endsection

