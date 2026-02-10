@extends('layouts.template')
@include('sweetalert::alert')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">
                            <i class="fas fa-user-tie me-2"></i>Recruitment Requisitions
                        </h3>
                    </div>
                    <div class="col-auto">
                        @if (auth()->user()->hasRole('hod'))
                            <a href="{{ route('recruitment-requisitions.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> New Requisition
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        @if (auth()->user()->hasRole('hod'))
                            My Requisitions
                        @elseif(auth()->user()->hasAnyRole(['hec-cfo', 'hec-coo', 'hec-cms', 'hec-ccd']))
                            Requisitions Pending HEC Review
                        @elseif(auth()->user()->hasRole('cfo'))
                            Requisitions Pending CFO Finance Review
                        @elseif(auth()->user()->hasRole('ceo'))
                            Requisitions Pending CEO Decision
                        @elseif(auth()->user()->hasRole('hr'))
                            All Recruitment Requisitions
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if ($requisitions->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No requisitions found.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Ref No</th>
                                        <th>Job Title</th>
                                        <th>Department</th>
                                        <th>HOD</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($requisitions as $index => $requisition)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>RR-{{ str_pad($requisition->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                            </td>
                                            <td>{{ $requisition->job_title }}</td>
                                            <td>{{ $requisition->department->dept_name ?? 'N/A' }}</td>
                                            <td>{{ $requisition->hod->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $requisition->getStatusBadgeClass() }}">
                                                    {{ $requisition->getStatusLabel() }}
                                                </span>
                                            </td>
                                            <td>{{ $requisition->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <a href="{{ route('recruitment-requisitions.show', $requisition->id) }}"
                                                    class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if (auth()->user()->hasRole('hr'))
                                                    <a href="{{ route('recruitment-requisitions.export-pdf', $requisition->id) }}"
                                                        class="btn btn-sm btn-secondary" title="Export PDF">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
