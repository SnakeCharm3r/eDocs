@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.1.0/css/buttons.dataTables.min.css">
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        {{-- Page Header --}}
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-briefcase me-2 text-success"></i>Job Titles
                    </h3>
                    <p class="text-muted mb-0 mt-1">Manage job titles and their assignments</p>
                </div>
                <div class="col-auto d-flex gap-2">
                    @if (auth()->user()->hasAnyRole(['hr', 'super-admin', 'Admin', 'it']))
                        <a href="{{ route('job_titles.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Add Job Title
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{!! session('error') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{!! session('success') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @php
            $totalJobTitles = count($jobTitles);
            $totalStaffInJobTitles = collect($jobTitles)->sum('user_count');
            $clinicalJobTitles = collect($jobTitles)->where('clinical_or_non_clinical', 'Clinical')->count();
            $nonClinicalJobTitles = collect($jobTitles)->where('clinical_or_non_clinical', 'Non-Clinical')->count();
        @endphp

        {{-- Summary Cards --}}
        <div class="row g-2 mb-3">
            <div class="col-md-4 col-sm-6">
                <div class="card border h-100">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted" style="font-size: 0.75rem;">Total Job Titles</div>
                                <div class="h6 fw-bold mb-0 text-success">{{ number_format($totalJobTitles) }}</div>
                            </div>
                            <i class="fas fa-briefcase text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card border h-100">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted" style="font-size: 0.75rem;">Total Staff</div>
                                <div class="h6 fw-bold mb-0 text-success">{{ number_format($totalStaffInJobTitles) }}</div>
                            </div>
                            <i class="fas fa-users text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="card border h-100">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted" style="font-size: 0.75rem;">Departments</div>
                                <div class="h6 fw-bold mb-0 text-success">{{ number_format($departments->count()) }}</div>
                            </div>
                            <i class="fas fa-building text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border h-100" style="border-left: 4px solid #28a745 !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted" style="font-size: 0.75rem;">Clinical Job Titles</div>
                                <div class="h6 fw-bold mb-0" style="color: #28a745;">{{ number_format($clinicalJobTitles) }}</div>
                            </div>
                            <i class="fas fa-stethoscope" style="color: #28a745;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border h-100" style="border-left: 4px solid #17a2b8 !important;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted" style="font-size: 0.75rem;">Non-Clinical Job Titles</div>
                                <div class="h6 fw-bold mb-0" style="color: #17a2b8;">{{ number_format($nonClinicalJobTitles) }}</div>
                            </div>
                            <i class="fas fa-briefcase" style="color: #17a2b8;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-building me-1 text-success"></i>Filter by Department
                        </label>
                        <select id="filterDepartment" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->dept_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-search me-1 text-success"></i>Search
                        </label>
                        <input type="text" id="searchInput" class="form-control form-control-sm"
                            placeholder="Search by job title...">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-secondary flex-fill" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div id="exportButtonContainer" style="display: none;"></div>

        {{-- Job Titles Table --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-semibold">
                                <i class="fas fa-list me-2 text-success"></i>Job Titles List
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="showExportOptions()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="job-titles-table" class="display nowrap table table-hover mb-0" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 35%;">
                                            <i class="fas fa-briefcase me-1 text-success"></i>Job Title
                                        </th>
                                        <th style="width: 30%;">
                                            <i class="fas fa-building me-1 text-success"></i>Department
                                        </th>
                                        <th style="width: 20%;">
                                            <i class="fas fa-tag me-1 text-success"></i>Type
                                        </th>
                                        @if (auth()->user()->hasAnyRole(['hr', 'super-admin', 'it', 'Admin']))
                                            <th style="width: 10%;" class="text-center text-nowrap">
                                                <i class="fas fa-cog me-1 text-success"></i>Actions
                                            </th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($jobTitles as $jobTitle)
                                        <tr data-dept-id="{{ $jobTitle->deptId }}">
                                            <td class="align-middle">{{ $loop->iteration }}</td>
                                            <td class="align-middle">
                                                <div>
                                                    <strong class="text-dark">{{ $jobTitle->job_title }}</strong>
                                                    @php
                                                        $staffCount = $jobTitle->user_count ?? $jobTitle->user()->count();
                                                    @endphp
                                                    @if($staffCount > 0)
                                                        <div class="mt-1">
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-user-tie me-1"></i>{{ $staffCount }} staff
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div>
                                                    <span class="badge bg-secondary px-3 py-2">
                                                        <i class="fas fa-building me-1"></i>{{ $jobTitle->department->dept_name ?? 'N/A' }}
                                                    </span>
                                                    @php
                                                        $deptStaffCount = $jobTitle->department->user_count ?? $jobTitle->department->user()->count() ?? 0;
                                                    @endphp
                                                    @if($deptStaffCount > 0)
                                                        <div class="mt-1">
                                                            <small class="text-muted">
                                                                <i class="fas fa-users me-1"></i>{{ $deptStaffCount }} staff in department
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                @if ($jobTitle->clinical_or_non_clinical)
                                                    <span class="badge {{ $jobTitle->clinical_or_non_clinical == 'Clinical' ? 'bg-success' : 'bg-info' }} px-3 py-2">
                                                        <i class="fas fa-{{ $jobTitle->clinical_or_non_clinical == 'Clinical' ? 'stethoscope' : 'briefcase' }} me-1"></i>
                                                        {{ $jobTitle->clinical_or_non_clinical }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary px-3 py-2">
                                                        <i class="fas fa-question-circle me-1"></i>Not Set
                                                    </span>
                                                @endif
                                            </td>
                                            @if (auth()->user()->hasAnyRole(['hr', 'super-admin', 'it', 'Admin']))
                                                <td class="text-center align-middle">
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('job_titles.edit', $jobTitle) }}"
                                                            class="btn btn-sm btn-outline-success" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @php
                                                            $userCount = $jobTitle->user_count ?? $jobTitle->user()->count();
                                                        @endphp
                                                        @if ($userCount == 0)
                                                            <button type="button" class="btn btn-sm btn-outline-danger delete-job-title-btn"
                                                                data-id="{{ $jobTitle->id }}"
                                                                data-name="{{ $jobTitle->job_title }}"
                                                                title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @else
                                                            <button class="btn btn-sm btn-outline-danger" disabled
                                                                title="Cannot delete: {{ $userCount }} user(s) assigned to this job title">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endif
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

{{-- jQuery & DataTables JS --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function() {
    'use strict';

    let jobTitlesTable;

    $(document).ready(function() {
        jobTitlesTable = $('#job-titles-table').DataTable({
            pageLength: 50,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)"
            },
            order: [[1, 'asc']],
            columnDefs: [
                { orderable: false, targets: 0 },
                @if (auth()->user()->hasAnyRole(['hr', 'super-admin', 'it', 'Admin']))
                { orderable: false, targets: -1 }
                @endif
            ]
        });

        window.jobTitlesTable = jobTitlesTable;

        new $.fn.dataTable.Buttons(jobTitlesTable, {
            buttons: [
                {
                    extend: 'excel',
                    text: 'Excel',
                    className: 'buttons-excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3]
                    }
                },
                {
                    extend: 'csv',
                    text: 'CSV',
                    className: 'buttons-csv',
                    exportOptions: {
                        columns: [0, 1, 2, 3]
                    }
                },
                {
                    extend: 'pdf',
                    text: 'PDF',
                    className: 'buttons-pdf',
                    exportOptions: {
                        columns: [0, 1, 2, 3]
                    }
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'buttons-print',
                    exportOptions: {
                        columns: [0, 1, 2, 3]
                    }
                }
            ]
        }).container().appendTo($('#exportButtonContainer'));

        $('#filterDepartment').on('change', function() {
            jobTitlesTable.draw();
        });

        $('#searchInput').on('keyup', function() {
            jobTitlesTable.search(this.value).draw();
        });

        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var deptId = $('#filterDepartment').val();
                if (!deptId) return true;
                
                var row = jobTitlesTable.row(dataIndex).node();
                var rowDeptId = $(row).attr('data-dept-id');
                
                return rowDeptId == deptId;
            }
        );
    });

    function clearFilters() {
        $('#filterDepartment').val('');
        $('#searchInput').val('');
        if (jobTitlesTable) {
            jobTitlesTable.search('').draw();
        }
    }

    function showExportOptions() {
        Swal.fire({
            title: '<i class="fas fa-download text-success me-2"></i>Export Data',
            html: `
                <div class="text-center mb-3">
                    <p class="text-muted mb-3">Choose your preferred export format:</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <button id="export-excel-btn" class="btn btn-success w-100 p-2 export-option-btn">
                                <i class="fas fa-file-excel me-2"></i>
                                <span>Excel</span>
                            </button>
                        </div>
                        <div class="col-6">
                            <button id="export-pdf-btn" class="btn btn-danger w-100 p-2 export-option-btn">
                                <i class="fas fa-file-pdf me-2"></i>
                                <span>PDF</span>
                            </button>
                        </div>
                        <div class="col-6">
                            <button id="export-csv-btn" class="btn btn-success w-100 p-2 export-option-btn">
                                <i class="fas fa-file-csv me-2"></i>
                                <span>CSV</span>
                            </button>
                        </div>
                        <div class="col-6">
                            <button id="export-print-btn" class="btn btn-secondary w-100 p-2 export-option-btn">
                                <i class="fas fa-print me-2"></i>
                                <span>Print</span>
                            </button>
                        </div>
                    </div>
                </div>
            `,
            icon: null,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: '<i class="fas fa-times me-2"></i> Cancel',
            cancelButtonColor: '#6c757d',
            showCloseButton: true,
            width: '400px',
            didOpen: () => {
                document.getElementById('export-excel-btn')?.addEventListener('click', () => {
                    Swal.close();
                    jobTitlesTable.button('.buttons-excel').trigger();
                });
                document.getElementById('export-pdf-btn')?.addEventListener('click', () => {
                    Swal.close();
                    jobTitlesTable.button('.buttons-pdf').trigger();
                });
                document.getElementById('export-csv-btn')?.addEventListener('click', () => {
                    Swal.close();
                    jobTitlesTable.button('.buttons-csv').trigger();
                });
                document.getElementById('export-print-btn')?.addEventListener('click', () => {
                    Swal.close();
                    jobTitlesTable.button('.buttons-print').trigger();
                });
            }
        });
    }

    $(document).on('click', '.delete-job-title-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var jobTitleId = button.data('id');
        var jobTitleName = button.data('name');

        Swal.fire({
            title: 'Are you sure?',
            html: `Do you want to delete the job title <strong>${jobTitleName}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                var form = $('<form>', {
                    'method': 'POST',
                    'action': '/job_titles/' + jobTitleId
                });
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': '{{ csrf_token() }}'
                }));
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_method',
                    'value': 'DELETE'
                }));
                $('body').append(form);
                form.submit();
            }
        });
    });

    window.clearFilters = clearFilters;
    window.showExportOptions = showExportOptions;
})();
</script>
@endsection
