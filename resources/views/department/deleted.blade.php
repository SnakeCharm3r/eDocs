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
                            <i class="fas fa-trash me-2"></i>Deleted Departments
                        </h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('department.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Departments
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="deletedDepartmentsTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Head of Department</th>
                                            <th>HEC Level</th>
                                            <th>HEC Member</th>
                                            <th>Entities</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($deletedDepartments as $index => $department)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-building text-muted me-2"></i>
                                                        <strong>{{ $department->dept_name }}</strong>
                                                    </div>
                                                    @if ($department->description)
                                                        <small class="text-muted d-block mt-1">{{ Str::limit($department->description, 50) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($department->head_of_department)
                                                        <i class="fas fa-user me-1 text-muted"></i>{{ $department->head_of_department }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($department->hec_level_name)
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-level-up-alt me-1"></i>{{ $department->hec_level_name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">Not mapped</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($department->hec_member_name)
                                                        <i class="fas fa-user-shield me-1 text-muted"></i>{{ $department->hec_member_name }}
                                                    @else
                                                        <span class="text-muted">Not mapped</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column gap-1">
                                                        @php
                                                            $userCount = \App\Models\User::where('deptId', $department->dept_id)->count();
                                                            $jobTitlesCount = \App\Models\JobTitle::where('deptId', $department->dept_id)->count();
                                                        @endphp
                                                        @if ($userCount > 0)
                                                            <span class="badge bg-secondary text-white px-2 py-1">
                                                                <i class="fas fa-users me-1"></i>{{ $userCount }} staff
                                                            </span>
                                                        @endif
                                                        @if ($jobTitlesCount > 0)
                                                            <span class="badge bg-secondary text-white px-2 py-1">
                                                                <i class="fas fa-briefcase me-1"></i>{{ $jobTitlesCount }} job titles
                                                            </span>
                                                        @endif
                                                        @if ($userCount == 0 && $jobTitlesCount == 0)
                                                            <span class="text-muted">No data</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger force-delete-dept-btn"
                                                            data-id="{{ $department->dept_id }}"
                                                            data-name="{{ $department->dept_name }}"
                                                            title="Permanently Delete">
                                                            <i class="fas fa-trash-alt"></i> Delete Permanently
                                                        </button>
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

    @push('scripts')
        <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
        <script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.print.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize DataTable
                const table = new DataTable('#deletedDepartmentsTable', {
                    responsive: true,
                    pageLength: 25,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, 'All']
                    ],
                    order: [
                        [1, 'asc'] // Order by Name
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: [0, 6] // # and Actions columns are not sortable
                    }],
                    language: {
                        searchPlaceholder: 'Search deleted departments...',
                        emptyTable: 'No deleted departments found',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        search: 'Search:',
                        lengthMenu: 'Show _MENU_ entries'
                    }
                });

                // Handle permanent delete
                $(document).on('click', '.force-delete-dept-btn', function() {
                    const deptId = $(this).data('id');
                    const deptName = $(this).data('name');
                    const button = $(this);

                    Swal.fire({
                        title: 'Permanently Delete Department?',
                        html: `<strong>${deptName}</strong><br><br>This action cannot be undone. The department will be permanently removed from the database.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, Delete Permanently',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading state
                            button.prop('disabled', true);
                            button.html('<i class="fas fa-spinner fa-spin"></i> Deleting...');

                            $.ajax({
                                url: `/departments/${deptId}/force-delete`,
                                type: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(response) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: response.message || 'Department permanently deleted successfully.',
                                        icon: 'success',
                                        confirmButtonText: 'OK',
                                        timer: 2000
                                    }).then(() => {
                                        // Reload the page to refresh the table
                                        location.reload();
                                    });
                                },
                                error: function(xhr) {
                                    let errorMessage = 'An error occurred while deleting the department.';
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }
                                    Swal.fire({
                                        title: 'Error!',
                                        text: errorMessage,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                    // Reset button state
                                    button.prop('disabled', false);
                                    button.html('<i class="fas fa-trash-alt"></i> Delete Permanently');
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection

