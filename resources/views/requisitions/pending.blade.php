@extends('layouts.template')

@push('styles')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
@endpush

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white text-dark border-bottom">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">
                                <i class="fas fa-clock me-2 text-warning"></i>Pending Requests
                            </h4>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                @php
                                    $user = auth()->user();
                                    $userRole = '';
                                    if ($user->hasRole('payroll_accountant')) {
                                        $userRole = 'Payroll Review';
                                    } elseif ($user->hasAnyRole(['coo', 'cms', 'ccdro'])) {
                                        $userRole = 'HEC Review';
                                    } elseif ($user->hasRole('cfo')) {
                                        $userRole = 'CFO Review';
                                    } elseif ($user->hasRole('ceo')) {
                                        $userRole = 'CEO Review';
                                    } elseif ($user->hasRole('hr')) {
                                        $userRole = 'HR Review';
                                    }
                                @endphp
                                @if($userRole)
                                    Requisitions awaiting <strong>{{ $userRole }}</strong>: <strong>{{ $requisitions->count() }}</strong>
                                @else
                                    Requisitions awaiting your review: <strong>{{ $requisitions->count() }}</strong>
                                @endif
                            </small>
                        </div>
                        @php
                            $user = auth()->user();
                            // Only HR and initiators (Line Manager / HEC roles) can see "View All"
                            $canSeeViewAll =
                                $user->hasRole('hr') ||
                                $user->hasAnyRole(['line-manager', 'coo', 'cms', 'ccdro']);
                        @endphp
                        @if ($canSeeViewAll)
                            <div>
                                <a href="{{ route('requisitions.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-list me-1"></i> View All
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="pendingRequisitionsTable">
                            <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Department</th>
                                <th>Initiated By</th>
                                <th>Your Role</th>
                                <th>Submitted</th>
                                <th class="text-center">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($requisitions as $req)
                                @php
                                    $user = auth()->user();
                                    $userRoleText = '';
                                    $roleBadgeClass = '';

                                    if ($user->hasRole('payroll_accountant')) {
                                        $userRoleText = 'Payroll Review';
                                        $roleBadgeClass = 'bg-info';
                                    } elseif ($user->hasAnyRole(['coo', 'cms', 'ccdro'])) {
                                        $userRoleText = 'HEC Review';
                                        $roleBadgeClass = 'bg-warning';
                                    } elseif ($user->hasRole('cfo')) {
                                        $userRoleText = 'CFO Review';
                                        $roleBadgeClass = 'bg-warning text-dark';
                                    } elseif ($user->hasRole('ceo')) {
                                        $userRoleText = 'CEO Review';
                                        $roleBadgeClass = 'bg-danger';
                                    } elseif ($user->hasRole('hr')) {
                                        $userRoleText = 'HR Review';
                                        $roleBadgeClass = 'bg-success';
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('requisitions.show', $req->access_id) }}"
                                           class="fw-semibold text-primary">
                                            {{ $req->access_id }}
                                        </a>
                                    </td>
                                    <td>
                                        <div>{{ $req->dept_name ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $req->initiator_name }}</div>
                                        <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $req->initiator_type)) }}</small>
                                    </td>
                                    <td>
                                            <span class="badge {{ $roleBadgeClass }}">
                                                {{ $userRoleText }}
                                            </span>
                                    </td>
                                    <td>
                                        {{ $req->created_at->format('d M Y') }}
                                        <br>
                                        <small class="text-muted">{{ $req->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('requisitions.show', $req->access_id) }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>Review
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($requisitions->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-muted">No Pending {{ $userRole ?? 'Requisitions' }}</h5>
                            <p class="text-muted">
                                @if($userRole)
                                    You have no requisitions awaiting {{ $userRole }}.
                                @else
                                    You have no requisitions waiting for your review.
                                @endif
                            </p>
                            @if($userRole === 'HEC Review')
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Note: You only see requisitions from departments assigned to you.
                                </small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = $('#pendingRequisitionsTable').DataTable({
                pageLength: 20,
                lengthChange: true,
                lengthMenu: [
                    [10, 20, 50, 100],
                    [10, 20, 50, 100]
                ],
                order: [
                    [4, 'asc']
                ], // Order by submitted date ascending (oldest first)
                orderMulti: true, // Allow multi-column ordering
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ requisitions per page',
                    info: 'Showing _START_ to _END_ of _TOTAL_ pending requisitions',
                    infoEmpty: 'Showing 0 to 0 of 0 requisitions',
                    paginate: {
                        previous: '&laquo;',
                        next: '&raquo;'
                    }
                },
                columnDefs: [
                    { targets: [5], orderable: false }  // Disable sorting on Action column
                ]
            });
        });
    </script>
@endpush
