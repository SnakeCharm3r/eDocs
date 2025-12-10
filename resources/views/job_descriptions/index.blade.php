@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Job Descriptions</h3>
                    </div>
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @elseif(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    <div class="card">
                        <div class="card-body">
                            @if (Auth::user()->hasRole('hr'))
                                <a href="{{ route('job.description.create') }}" class="btn btn-primary mb-3">Create New
                                    JD</a>
                            @endif
                            <div class="table-responsive">
                                <table id="jobDescriptionsTable" class="table table-hover table-bordered">
                                    <thead class="table-success">
                                        <tr>
                                            <th>#</th>
                                            <th>Job Holder Name</th>
                                            <th>Job Title</th>
                                            <th>Employee Type</th>
                                            <th>Reports To</th>
                                            {{-- <th>Approval Level</th> --}}
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($jobDescriptions as $jd)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    @if ($jd->user)
                                                        {{ $jd->user->fname }} {{ $jd->user->mname }} {{ $jd->user->lname }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>{{ $jd->job_title ?? 'N/A' }}</td>
                                                <td>{{ ucfirst($jd->employee_type) ?? 'N/A' }}</td>
                                                <td>{{ $jd->reports_to ?? 'N/A' }}</td>
                                                {{-- <td>Line Manager</td> --}}
                                                <td>
                                                    @php
                                                        $latestHistory = $jd->workflow?->histories
                                                            ?->sortByDesc('created_at')
                                                            ->first();
                                                        $status = $latestHistory?->jd_status ?? null;
                                                    @endphp

                                                    @if ($status === 0)
                                                        <span class="badge badge-warning">Pending</span>
                                                    @elseif($status === 1)
                                                        <span class="badge badge-success">Approved</span>
                                                    @elseif($status === 2)
                                                        <span class="badge badge-danger">Rejected</span>
                                                    @else
                                                        <span class="badge badge-secondary">Unknown</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('job.description.show', $jd->id) }}"
                                                        class="btn btn-sm" style="background-color: #20c997; color: white;"
                                                        title="View">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>

                                                    @role('hr|line-manager')
                                                        @if ($status === 1)
                                                            <a href="{{ url('job-descriptions/' . $jd->id . '/download') }}"
                                                                class="btn btn-sm btn-success" title="Download">
                                                                <i class="fas fa-download"></i> <!-- Download icon -->
                                                            </a>
                                                        @endif
                                                    @endrole
                                                    @if ($status === 0)
                                                        @role('hr')
                                                            <form action="{{ route('job.description.destroy', $jd->id) }}"
                                                                method="POST" style="display: inline;"
                                                                onsubmit="return confirm('Are you sure you want to delete this job description?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm"
                                                                    style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;"
                                                                    title="Delete">
                                                                    <i class="fas fa-trash-alt"></i> <!-- Trash icon -->
                                                                </button>
                                                            </form>
                                                        @endrole
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            try {
                $('#jobDescriptionsTable').DataTable({
                    pageLength: 10,
                    ordering: true,
                    searching: true,
                    paging: true,
                    info: true,
                    language: {
                        emptyTable: "No job descriptions found",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries"
                    }
                });
                console.log('DataTables initialized successfully');
            } catch (error) {
                console.error('DataTables initialization failed:', error);
            }
        });
    </script>
@endsection
