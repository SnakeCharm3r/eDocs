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
                        <h3 class="page-title">Contracts</h3>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            {{-- <a href="{{ route('contracts.create') }}" class="btn btn-primary btn-sm mb-3">
                                Create New Contract
                            </a> --}}

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <a href="{{ route('contracts.fixed_flex.index') }}" class="btn btn-outline-primary btn-sm">
                                    Fixed/Flex Contract
                                </a>

                                <a href="{{ route('contracts.consultant.index') }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    Consultant Contract
                                </a>
                                <a href="{{ route('contracts.volunteer.index') }}" class="btn btn-outline-success btn-sm">
                                    Health Volunteer Contract
                                </a>
                                <a href="{{ route('contracts.output_based.index') }}" class="btn btn-outline-info btn-sm">
                                    Output-Based Contract
                                </a>
                                <a href="{{ route('contracts.exposure_replacement.index') }}"
                                    class="btn btn-outline-warning btn-sm">
                                    Exposure Replacement Contract
                                </a>
                                <a href="{{ route('contracts.locum.index') }}" class="btn btn-outline-danger btn-sm">
                                    Locum Agreement Form
                                </a>
                                <a href="{{ route('contracts.govt_intern.index') }}" class="btn btn-outline-dark btn-sm">
                                    Government Intern Agreement
                                </a>
                            </div>


                            <div class="table-responsive">
                                <table id="contractsTable" class="table table-hover table-bordered">
                                    <thead class="table-success">
                                        <tr>
                                            <th>#</th>
                                            <th>Employee</th>
                                            <th>Job Title</th>
                                            <th>Department</th>
                                            <th>Start Date</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($contracts as $contract)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $contract->user->fname }} {{ $contract->user->mname }}
                                                    {{ $contract->user->lname }}</td>
                                                <td>{{ $contract->user->jobTitle->job_title ?? 'N/A' }}</td>
                                                <td>{{ $contract->user->department->dept_name ?? 'N/A' }}</td>
                                                <td>{{ $contract->start_date ?? 'N/A' }}</td>
                                                <td>{{ $contract->duration ?? 'N/A' }}</td>
                                                <td>
                                                    <span
                                                        class="badge {{ $contract->status == 'active' ? 'bg-success' : 'bg-danger' }}">
                                                        {{ ucfirst($contract->status ?? 'N/A') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('contracts.show', $contract->id) }}"
                                                        class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('contracts.edit', $contract->id) }}"
                                                        class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('contracts.destroy', $contract->id) }}"
                                                        method="POST" style="display: inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this contract?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
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

        .badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.9em;
        }

        .bg-success {
            background-color: #28a745 !important;
            color: white;
        }

        .bg-danger {
            background-color: #dc3545 !important;
            color: white;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            try {
                $('#contractsTable').DataTable({
                    pageLength: 10,
                    ordering: true,
                    searching: true,
                    paging: true,
                    info: true,
                    language: {
                        emptyTable: "No contracts found",
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
