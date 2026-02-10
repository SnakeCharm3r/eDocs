@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.1.0/css/buttons.dataTables.min.css">
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-building me-2"></i>Departments
                        </h3>
                    </div>
                    <div class="col-auto">
                        @php
                            $user = auth()->user();
                            $roles = ['hr', 'Admin', 'super-admin', 'it'];
                        @endphp

                        <div class="btn-group">
                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="fas fa-cog"></i> Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('job_titles.index') }}">
                                        <i class="fas fa-briefcase me-2"></i> Job Titles
                                    </a>
                                </li>

                                @if ($user && $user->hasAnyRole($roles))
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                            data-bs-target="#createDepartmentModal">
                                            <i class="fas fa-plus me-2"></i> Add Department
                                        </button>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                @endif

                                @if ($user && $user->hasAnyRole($roles))
                                    <li>
                                        <a class="dropdown-item" href="{{ route('platforms.index') }}">
                                            <i class="fas fa-layer-group me-2"></i> Platforms
                                        </a>
                                    </li>
                                @endif

                                @can('view hec level')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('hec.index') }}">
                                            <i class="fas fa-level-up-alt me-2"></i> HEC Level
                                        </a>
                                    </li>
                                @endcan

                                @can('view employment type')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('employment.index') }}">
                                            <i class="fas fa-briefcase me-2"></i> Employment Type
                                        </a>
                                    </li>
                                @endcan

                                @if ($user && $user->hasAnyRole($roles))
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('departments.locum-settings') }}">
                                            <i class="fas fa-cog me-2"></i> Locum Settings
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('departments.oncall-settings') }}">
                                            <i class="fas fa-cog me-2"></i> On-Call Settings
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('departments.deleted') }}">
                                            <i class="fas fa-trash-restore me-2"></i> Deleted Departments
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $totalDepartments = count($departments);

                // Count unique entities (divisions) that have departments assigned
                $entitiesCount = collect($departments)
                    ->flatMap(function ($dept) {
                        return $dept->divisions ?? collect();
                    })
                    ->pluck('id')
                    ->unique()
                    ->count();
            @endphp

            {{-- Summary Cards --}}
            <div class="row g-2 mb-3">
                <div class="col-md-3 col-sm-6">
                    <div class="card border h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">Entities</div>
                                    <div class="h5 fw-bold mb-0">{{ number_format($entitiesCount) }}</div>
                                </div>
                                <i class="fas fa-sitemap text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">Total Departments</div>
                                    <div class="h5 fw-bold mb-0">{{ number_format($totalDepartments) }}</div>
                                </div>
                                <i class="fas fa-building text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-sitemap me-1 text-success"></i>Filter by Entity
                            </label>
                            <select id="filterEntity" class="form-select form-select-sm">
                                <option value="">All Entities</option>
                                @foreach ($entities as $entity)
                                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-search me-1 text-success"></i>Search
                            </label>
                            <input type="text" id="searchInput" class="form-control form-control-sm"
                                placeholder="Search by name...">
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

            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-building me-2 text-success"></i>Department List
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="showExportOptions()">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="locumTable" class="display nowrap table table-hover" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">#</th>
                                            <th style="width: 25%;">
                                                <i class="fas fa-building me-1 text-success"></i>Name
                                            </th>
                                            <th style="width: 15%;">
                                                <i class="fas fa-user-tie me-1 text-success"></i>Head of Department
                                            </th>
                                            <th style="width: 12%;">
                                                <i class="fas fa-level-up-alt me-1 text-success"></i>HEC Level
                                            </th>
                                            {{-- <th style="width: 15%;">
                                                <i class="fas fa-user-shield me-1 text-success"></i>HEC Member
                                            </th> --}}
                                            <th style="width: 25%;">
                                                <i class="fas fa-sitemap me-1 text-success"></i>Entities
                                            </th>
                                            <th style="width: 10%;" class="text-center text-nowrap">
                                                <i class="fas fa-cog me-1 text-success"></i>Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($departments as $index => $department)
                                            @php
                                                $divisions = $department->divisions ?? collect();
                                                $entityIds = $divisions->pluck('id')->toArray();
                                            @endphp
                                            <tr data-id="{{ $department->dept_id }}"
                                                data-entity-ids="{{ implode(',', $entityIds) }}">
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $department->dept_name }}</td>
                                                <td>{{ $department->head_of_department ?? 'N/A' }}</td>
                                                <td>{{ $department->hec_level_name ?? 'N/A' }}</td>
                                                {{-- <td>
                                                    @if (!empty($department->hec_member_name))
                                                        <span class="badge bg-info text-white">
                                                            <i
                                                                class="fas fa-user-shield me-1"></i>{{ $department->hec_member_name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">Not mapped</span>
                                                    @endif
                                                </td> --}}
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                                        @if ($divisions && $divisions->count() > 0)
                                                            @foreach ($divisions as $division)
                                                                <span class="badge bg-success text-white px-2 py-1">
                                                                    <i
                                                                        class="fas fa-building me-1"></i>{{ trim($division->name) }}
                                                                </span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">No entities assigned</span>
                                                        @endif
                                                        @php
                                                            // Get actual counts directly from database to ensure accuracy
                                                            $userCount = \App\Models\User::where(
                                                                'deptId',
                                                                $department->dept_id,
                                                            )->count();
                                                            $jobTitlesCount = \App\Models\JobTitle::where(
                                                                'deptId',
                                                                $department->dept_id,
                                                            )->count();
                                                            // Check for active staff specifically
                                                            $activeStaffCount = \App\Models\User::where(
                                                                'deptId',
                                                                $department->dept_id,
                                                            )
                                                                ->where('status', 'active')
                                                                ->count();
                                                        @endphp
                                                        @if ($userCount > 0)
                                                            <span class="badge bg-secondary text-white px-2 py-1">
                                                                <i class="fas fa-users me-1"></i>{{ $userCount }} staff
                                                                @if ($activeStaffCount < $userCount)
                                                                    <small>({{ $activeStaffCount }} active)</small>
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('department.show', $department->dept_id) }}"
                                                            class="btn btn-sm btn-outline-secondary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('department.edit', $department->dept_id) }}"
                                                            class="btn btn-sm btn-outline-success" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @php
                                                            // Allow delete only if no staff (users)
                                                            // HEC mapping and job titles do not prevent deletion - only staff do
                                                            // Use strict comparison and ensure we're checking actual counts
$canDelete = (int) $userCount === 0;
$deleteMessage = 'Cannot delete: ';
if ($userCount > 0) {
    $deleteMessage .=
        $userCount . ' staff member(s) assigned';
} else {
    $deleteMessage = 'Delete Department';
                                                            }
                                                        @endphp
                                                        @if ($canDelete)
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger delete-dept-btn"
                                                                data-id="{{ $department->dept_id }}"
                                                                data-name="{{ $department->dept_name }}"
                                                                title="Delete Department">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        @else
                                                            <button class="btn btn-sm btn-outline-danger" disabled
                                                                title="{{ $deleteMessage }}">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        @endif
                                                    </div>
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

    <!-- Platforms Modal -->
    <div class="modal fade" id="deptPlatformsModal" tabindex="-1" aria-labelledby="deptPlatformsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-layer-group me-2"></i>Platforms
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="deptPlatformsBody">
                    <div class="text-center py-4">Loading...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Department Modal -->
    <div class="modal fade" id="createDepartmentModal" tabindex="-1" aria-labelledby="createDepartmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createDepartmentModalLabel">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createDepartmentForm" action="{{ route('department.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="dept_name" class="form-label">Department Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dept_name" name="dept_name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="description" name="description" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="clinical_or_non_clinical" class="form-label">Clinical or Non-Clinical <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="clinical_or_non_clinical" name="clinical_or_non_clinical"
                                required>
                                <option value="">Select Type</option>
                                <option value="Clinical">Clinical</option>
                                <option value="Non-Clinical">Non-Clinical</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="hec_id" class="form-label">Department HEC Level <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="hec_id" name="hec_id" required>
                                <option value="">Select HEC Level</option>
                                @foreach ($hec as $hecs)
                                    <option value="{{ $hecs->id }}">{{ $hecs->hec_level_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function() {
            'use strict';

            let departmentTable;

            // Initialize DataTable with export buttons
            $(document).ready(function() {
                departmentTable = $('#locumTable').DataTable({
                    pageLength: 25,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                    language: {
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)"
                    }
                });

                // Store reference globally
                window.table = departmentTable;

                // Create export buttons (hidden)
                new $.fn.dataTable.Buttons(departmentTable, {
                    buttons: [{
                            extend: 'excel',
                            text: 'Excel',
                            className: 'buttons-excel',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4]
                            }
                        },
                        {
                            extend: 'csv',
                            text: 'CSV',
                            className: 'buttons-csv',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4]
                            }
                        },
                        {
                            extend: 'pdf',
                            text: 'PDF',
                            className: 'buttons-pdf',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4]
                            }
                        },
                        {
                            extend: 'print',
                            text: 'Print',
                            className: 'buttons-print',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4]
                            }
                        }
                    ]
                }).container().appendTo($('#exportButtonContainer'));

                // Filter by type
                $('#filterType').on('change', function() {
                    departmentTable.draw();
                });

                // Filter by entity
                $('#filterEntity').on('change', function() {
                    departmentTable.draw();
                });

                // Search
                $('#searchInput').on('keyup', function() {
                    departmentTable.search(this.value).draw();
                });

                // Custom filter for entity
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        var entityId = $('#filterEntity').val();

                        // Filter by entity
                        if (entityId) {
                            var row = departmentTable.row(dataIndex).node();
                            var entityIds = $(row).attr('data-entity-ids') || '';
                            var entityIdsArray = entityIds ? entityIds.split(',') : [];

                            if (entityIdsArray.indexOf(entityId) === -1) {
                                return false;
                            }
                        }

                        return true;
                    }
                );
            });

            function clearFilters() {
                $('#filterEntity').val('');
                $('#searchInput').val('');
                if (departmentTable) {
                    departmentTable.search('').draw();
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
                                    <button id="export-csv-btn" class="btn btn-info w-100 p-2 export-option-btn">
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
                            departmentTable.button('.buttons-excel').trigger();
                        });
                        document.getElementById('export-pdf-btn')?.addEventListener('click', () => {
                            Swal.close();
                            departmentTable.button('.buttons-pdf').trigger();
                        });
                        document.getElementById('export-csv-btn')?.addEventListener('click', () => {
                            Swal.close();
                            departmentTable.button('.buttons-csv').trigger();
                        });
                        document.getElementById('export-print-btn')?.addEventListener('click', () => {
                            Swal.close();
                            departmentTable.button('.buttons-print').trigger();
                        });
                    }
                });
            }

            window.clearFilters = clearFilters;
            window.showExportOptions = showExportOptions;

            // ---- CSRF for all AJAX ----
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // ---- Helpers ----
            function clearErrors(form) {
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
            }

            // ================================
            // Departments: Create (modal form)
            // ================================
            $('#createDepartmentForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                clearErrors(form);

                // form.serialize() already contains _token thanks to @csrf in the form
                $.post(form.attr('action'), form.serialize())
                    .done(function(response) {
                        if (response.status === 200) {
                            $('#createDepartmentModal').modal('hide');

                            if (window.table && typeof window.table.row === 'function') {
                                window.table.row.add([
                                    window.table.rows().count() + 1,
                                    response.data.dept_name,
                                    response.data.head_of_department || 'N/A',
                                    response.data.hec_level_name || 'N/A',
                                    response.data.description,
                                    response.data.user_count || 0,
                                    `
              <div class="btn-group" role="group">
                <a href="{{ route('department.edit', '') }}/${response.data.dept_id}" class="btn btn-sm btn-outline-success" title="Edit">
                  <i class="fas fa-edit"></i>
                </a>
                <form action="{{ route('department.destroy', '') }}/${response.data.dept_id}" method="POST" style="display:inline;" class="delete-form">
                  <input type="hidden" name="_token" value="{{ csrf_token() }}">
                  <input type="hidden" name="_method" value="DELETE">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </form>
              </div>
              `
                                ]).draw(false);
                            }

                            Swal.fire('Success', response.message, 'success');
                            form[0].reset();
                        }
                    })
                    .fail(function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        $.each(errors, function(key, value) {
                            form.find(`[name="${key}"]`).addClass('is-invalid').next(
                                '.invalid-feedback').text(value[0]);
                        });
                        if (xhr.responseJSON?.message) Swal.fire('Error', xhr.responseJSON.message,
                            'error');
                    });
            });

            // =========================
            // Departments: Delete row (button click)
            // =========================
            $(document).on('click', '.delete-dept-btn', function(e) {
                e.preventDefault();
                const btn = $(this);
                const deptId = btn.data('id');
                const deptName = btn.data('name');

                Swal.fire({
                    title: 'Are you sure?',
                    html: `Do you want to delete the department <strong>${deptName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: `/department/${deptId}`,
                        type: 'DELETE',
                        data: {
                            _token: csrf()
                        },
                        success: function(response) {
                            if (departmentTable) {
                                departmentTable.row(btn.closest('tr')).remove().draw();
                            } else {
                                btn.closest('tr').remove();
                            }
                            Swal.fire('Deleted!', response.message ||
                                'Department deleted successfully.', 'success');
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON?.message ||
                                'An error occurred while deleting the department.';
                            Swal.fire('Error', message, 'error');
                        }
                    });
                });
            });

            // =========================
            // Departments: Delete row (form submit)
            // =========================
            $(document).on('submit', '.delete-form', function(e) {
                e.preventDefault();
                const form = $(this);

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    // form.serialize() includes _token and _method
                    $.post(form.attr('action'), form.serialize())
                        .done(function(response) {
                            form.closest('tr').remove();
                            if (window.table && typeof window.table.rows === 'function') {
                                window.table.rows().every(function(index) {
                                    $(this.node()).find('td:first').text(index + 1);
                                });
                            }
                            Swal.fire('Deleted!', response.message, 'success');
                        })
                        .fail(function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message || 'An error occurred',
                                'error');
                        });
                });
            });

            // ==========================================
            // Dept Platforms: open modal + load content
            // ==========================================
            $(document).on('click', '.btn-platforms', function(e) {
                e.preventDefault();
                const deptId = $(this).data('dept-id');
                openDeptPlatformsModal(deptId);
            });

            function openDeptPlatformsModal(deptId) {
                const $modal = $('#deptPlatformsModal');
                const $body = $('#deptPlatformsBody');

                if (!deptId) {
                    Swal.fire('Error', 'Missing department id.', 'error');
                    return;
                }

                $body.attr('data-dept-id', deptId).html('<div class="text-center py-4">Loading...</div>');
                $modal.modal('show');

                $.get(`/departments/${encodeURIComponent(deptId)}/platforms/modal`)
                    .done(function(html) {
                        $body.html(html);
                        const $wrap = $('#deptPlatformList');
                        if ($wrap.length && !$wrap.attr('data-dept-id')) {
                            $wrap.attr('data-dept-id', deptId);
                        }
                    })
                    .fail(function() {
                        $body.html('<div class="alert alert-danger">Failed to load platforms.</div>');
                    });
            }

            // ===================================================
            // Dept Platforms: toggle platform attach/detach (AJAX)
            // ===================================================
            $(document).on('change', '.platform-switch', function() {
                const $el = $(this);
                $el.prop('disabled', true); // avoid double-click race

                let deptId = $el.data('dept-id') ||
                    $('#deptPlatformsBody').data('dept-id') ||
                    $('#deptPlatformList').data('dept-id');

                const platformId = $el.data('platform-id');
                const attach = $el.is(':checked') ? 1 : 0;

                if (!deptId || !platformId) {
                    $el.prop({
                        checked: !attach,
                        disabled: false
                    });
                    Swal.fire('Error', 'Missing department or platform id.', 'error');
                    return;
                }

                $.post(`/departments/${encodeURIComponent(deptId)}/platforms/toggle`, {
                        platform_id: platformId,
                        attach: attach,
                        _token: csrf() // <-- include token explicitly
                    })
                    .done(function(resp) {
                        // optional success toast
                        // Swal.fire({toast:true, position:'top-end', icon:'success', title: resp?.message || 'Updated', showConfirmButton:false, timer:1200});
                    })
                    .fail(function(xhr) {
                        $el.prop('checked', !attach); // revert UI on failure
                        Swal.fire('Error', xhr.responseJSON?.message || 'Unable to update.', 'error');
                    })
                    .always(function() {
                        $el.prop('disabled', false);
                    });
            });

            // ==========================
            // Units: Create (inside modal)
            // ==========================
            $(document).on('submit', '#createUnitForm', function(e) {
                e.preventDefault();
                const form = $(this);
                const platformId = form.data('platform-id');

                const checked = form.find('input[name="is_active"]').is(':checked');
                const payload = form.serializeArray();
                payload.push({
                    name: 'is_active',
                    value: checked ? 1 : 0
                });
                payload.push({
                    name: '_token',
                    value: csrf()
                }); // safe even if already present

                $.post(`/platforms/${platformId}/units`, $.param(payload), function(resp) {
                    // Reload the units panel where this form lives
                    // (If you also keep currentPlatformName in scope elsewhere, you can use it)
                    const panel = $('#unitsPanel');
                    if (panel.length) {
                        $.get(`/platforms/${platformId}/units/modal`).done(function(html) {
                            panel.html(html);
                        });
                    }
                    Swal.fire('Success', resp.message || 'Unit created.', 'success');
                }).fail(function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Failed to create unit.';
                    Swal.fire('Error', msg, 'error');
                });
            });

            // ==========================
            // Units: Start Edit
            // ==========================
            $(document).on('click', '.btn-edit-unit', function() {
                const row = $(this).closest('tr');
                $('#edit_unit_id').val($(this).data('id'));
                $('#edit_name').val(row.find('.unit-name').text().trim());
                $('#edit_description').val(row.find('.unit-description').text().trim());
                $('#edit_is_active').prop('checked', row.find('.unit-active').data('active') == 1);
                $('#unitEditBlock').removeClass('d-none');
                document.getElementById('unitEditBlock').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });

            // ==========================
            // Units: Update
            // ==========================
            $(document).on('submit', '#editUnitForm', function(e) {
                e.preventDefault();
                const form = $(this);
                const platformId = form.data('platform-id');
                const unitId = $('#edit_unit_id').val();

                const checked = $('#edit_is_active').is(':checked');
                const payload = form.serializeArray();
                payload.push({
                    name: 'is_active',
                    value: checked ? 1 : 0
                });
                payload.push({
                    name: '_method',
                    value: 'PUT'
                });
                payload.push({
                    name: '_token',
                    value: csrf()
                });

                $.post(`/platforms/${platformId}/units/${unitId}`, $.param(payload), function(resp) {
                    $.get(`/platforms/${platformId}/units/modal`).done(function(html) {
                        $('#unitsPanel').html(html);
                    });
                    Swal.fire('Success', resp.message || 'Unit updated.', 'success');
                }).fail(function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Failed to update unit.';
                    Swal.fire('Error', msg, 'error');
                });
            });

            // ==========================
            // Units: Delete
            // ==========================
            $(document).on('click', '.btn-delete-unit', function() {
                const platformId = $(this).data('platform-id');
                const unitId = $(this).data('id');

                Swal.fire({
                    title: 'Delete this unit?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33'
                }).then((r) => {
                    if (!r.isConfirmed) return;

                    $.post(`/platforms/${platformId}/units/${unitId}`, {
                        _method: 'DELETE',
                        _token: csrf() // <-- include token here too
                    }, function(resp) {
                        $.get(`/platforms/${platformId}/units/modal`).done(function(html) {
                            $('#unitsPanel').html(html);
                        });
                        Swal.fire('Deleted', resp.message || 'Unit deleted.', 'success');
                    }).fail(function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Failed to delete unit.';
                        Swal.fire('Error', msg, 'error');
                    });
                });
            });

        })();
    </script>
@endsection
