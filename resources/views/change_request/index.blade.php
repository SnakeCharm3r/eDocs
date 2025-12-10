@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12 mb-3">
                        <h3 class="page-title">Change Request Reports</h3>
                        <p class="text-muted">View all change requests and their approval process</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm" style="border: 1px solid #d3d3d3;">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped" id="changeRequestsTable"
                                    style="width:100%">
                                    <thead style="background-color: #007A33; color: white;">
                                        <tr>
                                            <th>ID</th>
                                            <th>Requester</th>
                                            <th>Department</th>
                                            <th>Change Type</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Submitted Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($changeRequests as $changeRequest)
                                            <tr>
                                                <td>#{{ $changeRequest->id }}</td>
                                                <td>
                                                    {{ $changeRequest->user->fname ?? '' }}
                                                    {{ $changeRequest->user->lname ?? '' }}
                                                </td>
                                                <td>{{ $changeRequest->user->department->dept_name ?? 'N/A' }}</td>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $changeRequest->change_type === 'price' ? 'warning' : 'info' }}">
                                                        {{ $changeRequest->change_type === 'price' ? 'Price Change' : 'Non-Price Change' }}
                                                    </span>
                                                </td>
                                                <td>{{ $changeRequest->change_category ?? 'N/A' }}</td>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $changeRequest->priority === 'P1-High' ? 'danger' : ($changeRequest->priority === 'P2-Medium' ? 'warning' : 'secondary') }}">
                                                        {{ $changeRequest->priority ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $status =
                                                            $changeRequest->workflow->work_flow_status ?? 'Pending';
                                                        $statusClass = 'secondary';
                                                        if (
                                                            str_contains($status, 'Approved') ||
                                                            str_contains($status, 'Fully')
                                                        ) {
                                                            $statusClass = 'success';
                                                        } elseif (str_contains($status, 'Rejected')) {
                                                            $statusClass = 'danger';
                                                        } elseif (str_contains($status, 'Pending')) {
                                                            $statusClass = 'warning';
                                                        }
                                                    @endphp
                                                    <span class="badge badge-{{ $statusClass }}">
                                                        {{ $status }}
                                                    </span>
                                                </td>
                                                <td>{{ $changeRequest->created_at->format('d M Y, H:i') }}</td>
                                                <td>
                                                    <a href="{{ route('change_request.show', $changeRequest->id) }}"
                                                        class="btn btn-sm btn-primary" title="View Details">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted">No change requests found.</p>
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
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#changeRequestsTable').DataTable({
                responsive: true,
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                order: [
                    [7, 'desc']
                ], // Sort by Submitted Date descending (newest first)
                columnDefs: [{
                    orderable: false,
                    targets: [8] // Actions column
                }],
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-sm btn-success',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-sm btn-danger',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7]
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Print',
                        className: 'btn btn-sm btn-secondary',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7]
                        }
                    }
                ],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        });
    </script>
@endpush

@push('styles')
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        .dataTables_wrapper .dataTables_filter {
            float: right;
            text-align: right;
        }

        .dataTables_wrapper .dataTables_length {
            float: left;
        }

        .dataTables_wrapper .dataTables_paginate {
            float: right;
            text-align: right;
            margin-top: 0.5em;
        }

        .dt-buttons {
            margin-bottom: 1em;
        }

        .dt-buttons .btn {
            margin-right: 5px;
        }
        
        /* Ensure table header text is white */
        #changeRequestsTable thead th {
            color: white !important;
            background-color: #007A33 !important;
        }
        
        /* Ensure sorting icons are white */
        #changeRequestsTable thead th.sorting:before,
        #changeRequestsTable thead th.sorting:after,
        #changeRequestsTable thead th.sorting_asc:before,
        #changeRequestsTable thead th.sorting_asc:after,
        #changeRequestsTable thead th.sorting_desc:before,
        #changeRequestsTable thead th.sorting_desc:after {
            color: white !important;
            opacity: 1 !important;
        }
    </style>
@endpush
