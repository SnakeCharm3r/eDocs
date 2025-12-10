@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    {{-- Page goes here --}}

    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Title -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-building me-2 text-success"></i>CCBRT Entities
                        </h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('division.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Add New Entity
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <!-- Card Header -->
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2 text-success"></i>Entities List
                            </h5>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="divisionsTable" class="table table-hover table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">#</th>
                                            <th style="width: 20%;">
                                                <i class="fas fa-building me-1 text-success"></i>Name
                                            </th>
                                            <th style="width: 15%;">
                                                <i class="fas fa-code me-1 text-success"></i>Code
                                            </th>
                                            <th style="width: 30%;">
                                                <i class="fas fa-info-circle me-1 text-success"></i>Description
                                            </th>
                                            <th style="width: 25%;">
                                                <i class="fas fa-sitemap me-1 text-success"></i>Departments
                                            </th>
                                            <th style="width: 10%;">
                                                <i class="fas fa-tag me-1 text-success"></i>Status
                                            </th>
                                            <th style="width: 5%;" class="text-center">
                                                <i class="fas fa-cog me-1 text-success"></i>Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($divisions as $index => $division)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><strong>{{ $division->name }}</strong></td>
                                                <td><code>{{ $division->code }}</code></td>
                                                <td>
                                                    <span class="text-muted">{{ $division->description ?? '—' }}</span>
                                                </td>
                                                <td>
                                                    @if ($division->departments && $division->departments->count() > 0)
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach ($division->departments as $dept)
                                                                <span
                                                                    class="badge bg-secondary">{{ $dept->dept_name }}</span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted">No departments assigned</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge {{ $division->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ ucfirst($division->status ?? 'active') }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('division.edit', $division->id) }}"
                                                                class="btn btn-sm btn-outline-success" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            @php
                                                                $hasDepartments =
                                                                    $division->departments &&
                                                                    $division->departments->count() > 0;
                                                                $departmentsCount = $division->departments
                                                                    ? $division->departments->count()
                                                                    : 0;
                                                            @endphp
                                                            @if ($hasDepartments)
                                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                                    disabled
                                                                    title="Cannot delete: {{ $departmentsCount }} department(s) assigned">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            @else
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-danger delete-entity-btn"
                                                                    data-id="{{ $division->id }}"
                                                                    data-name="{{ $division->name }}"
                                                                    data-departments-count="0" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted">—</span>
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
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            var table = $('#divisionsTable').DataTable({
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
                },
                columnDefs: [{
                        orderable: false,
                        targets: -1
                    } // Disable sorting on actions column
                ]
            });

            // Verify SweetAlert2 is loaded
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 is not loaded!');
            }

            // Debug: Check if buttons exist
            console.log('Delete buttons found:', $('.delete-entity-btn').length);

            // Handle delete button click - using event delegation for DataTables
            // Also bind to document as fallback
            $(document).on('click', '.delete-entity-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                console.log('Delete button clicked', $(this).data()); // Debug log

                var button = $(this);
                var entityId = button.data('id');
                var entityName = button.data('name');
                var departmentsCount = parseInt(button.data('departments-count')) || 0;

                if (!entityId) {
                    console.error('Entity ID not found');
                    alert('Entity ID not found. Please refresh the page and try again.');
                    return;
                }

                // Check if SweetAlert2 is available
                if (typeof Swal === 'undefined') {
                    if (confirm('Are you sure you want to delete ' + entityName + '?')) {
                        // Fallback to basic form submission if SweetAlert2 is not available
                        console.log('SweetAlert2 not available, using basic confirm');
                    }
                    return;
                }

                // Prevent deletion if entity has departments assigned
                if (departmentsCount > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Delete',
                        html: `Cannot delete <strong>${entityName}</strong>.<br><br>This entity has <strong>${departmentsCount}</strong> department(s) assigned to it. Please remove all departments first before deleting.`,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                // Show confirmation dialog
                Swal.fire({
                    title: 'Are you sure?',
                    html: `Do you want to delete <strong>${entityName}</strong>?<br><br>This action will soft delete the entity.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Make AJAX request to delete
                        $.ajax({
                            url: `/Division/${entityId}`,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message ||
                                        'Entity deleted successfully!',
                                    confirmButtonColor: '#28a745',
                                    timer: 2000
                                }).then(() => {
                                    // Reload the page to refresh the table
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                var errorMessage =
                                    'An error occurred while deleting the entity.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.status === 403) {
                                    errorMessage =
                                        'You do not have permission to delete this entity.';
                                } else if (xhr.status === 404) {
                                    errorMessage = 'Entity not found.';
                                } else if (xhr.status === 500) {
                                    errorMessage = 'Server error. Please try again.';
                                } else if (xhr.responseText) {
                                    // Try to extract error message from response
                                    try {
                                        var response = JSON.parse(xhr.responseText);
                                        if (response.message) {
                                            errorMessage = response.message;
                                        }
                                    } catch (e) {
                                        errorMessage = xhr.responseText.substring(0,
                                            200);
                                    }
                                }
                                console.error('Delete error:', xhr.status, xhr
                                    .responseText);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    html: `<strong>Status:</strong> ${xhr.status}<br><strong>Message:</strong> ${errorMessage}`,
                                    confirmButtonColor: '#dc3545'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
