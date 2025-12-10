@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Title -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-file-alt me-2 text-success"></i>eDocs Levels
                        </h3>
                    </div>
                    <div class="col-auto">
                        @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#edocsModal">
                                <i class="fas fa-plus"></i> Add New eDocs Level
                            </button>
                        @endif
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
                                <i class="fas fa-list me-2 text-success"></i>eDocs Levels List
                            </h5>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="edocsTable" class="table table-hover table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">#</th>
                                            <th style="width: 40%;">
                                                <i class="fas fa-tag me-1 text-success"></i>Name
                                            </th>
                                            <th style="width: 20%;">
                                                <i class="fas fa-info-circle me-1 text-success"></i>Status
                                            </th>
                                            <th style="width: 25%;">
                                                <i class="fas fa-link me-1 text-success"></i>Usage
                                            </th>
                                            <th style="width: 10%;" class="text-center">
                                                <i class="fas fa-cog me-1 text-success"></i>Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($edocs as $index => $edocsLevel)
                                            @php
                                                // Check usage in IctAccessResource
                                                $usageCount = \App\Models\IctAccessResource::whereJsonContains('edocs', (string)$edocsLevel->id)
                                                    ->where('delete_status', '!=', '1')
                                                    ->count();
                                                $isInUse = $usageCount > 0;
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><strong>{{ $edocsLevel->edocs_name }}</strong></td>
                                                <td>
                                                    <span class="badge {{ $edocsLevel->edocs_status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $edocsLevel->edocs_status ?? 'active')) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($isInUse)
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            Used by {{ $usageCount }} resource(s)
                                                        </span>
                                                    @else
                                                        <span class="text-muted">Not in use</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('edocs.edit', $edocsLevel->id) }}"
                                                                class="btn btn-sm btn-outline-success" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            @if ($isInUse)
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    disabled
                                                                    title="Cannot delete: Used by {{ $usageCount }} resource(s)">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            @else
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-danger delete-edocs-btn"
                                                                    data-id="{{ $edocsLevel->id }}"
                                                                    data-name="{{ $edocsLevel->edocs_name }}"
                                                                    data-usage-count="{{ $usageCount }}"
                                                                    title="Delete">
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

            <!-- Modal for Add -->
            @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                <div class="modal fade" id="edocsModal" tabindex="-1" aria-labelledby="edocsModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="edocsModalLabel">Add eDocs Level</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="{{ route('edocs.store') }}" id="edocsForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-12 col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="edocs_name">Name <span class="text-danger">*</span></label>
                                                <input type="text" name="edocs_name" id="edocs_name" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="edocs_status">Status <span class="text-danger">*</span></label>
                                                <select name="edocs_status" id="edocs_status" class="form-control" required>
                                                    <option value="">Select Status</option>
                                                    <option value="active" {{ old('edocs_status') == 'active' ? 'selected' : '' }}>Active</option>
                                                    <option value="not_active" {{ old('edocs_status') == 'not_active' ? 'selected' : '' }}>Not Active</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 text-end">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            var table = $('#edocsTable').DataTable({
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
                }]
            });

            // Handle delete button click
            $(document).on('click', '.delete-edocs-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var button = $(this);
                var edocsId = button.data('id');
                var edocsName = button.data('name');
                var usageCount = parseInt(button.data('usage-count')) || 0;

                if (!edocsId) {
                    console.error('eDocs ID not found');
                    alert('eDocs ID not found. Please refresh the page and try again.');
                    return;
                }

                // Prevent deletion if eDocs Level is in use
                if (usageCount > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Delete',
                        html: `Cannot delete <strong>${edocsName}</strong>.<br><br>This eDocs Level is currently being used by <strong>${usageCount}</strong> ICT access resource(s). Please remove all references first before deleting.`,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                // Show confirmation dialog
                Swal.fire({
                    title: 'Are you sure?',
                    html: `Do you want to delete <strong>${edocsName}</strong>?<br><br>This action will soft delete the eDocs Level.`,
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
                            url: `/edocs/${edocsId}`,
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
                                    text: response.message || 'eDocs Level deleted successfully!',
                                    confirmButtonColor: '#28a745',
                                    timer: 2000
                                }).then(() => {
                                    // Reload the page to refresh the table
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                var errorMessage = 'An error occurred while deleting the eDocs Level.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.status === 403) {
                                    errorMessage = 'You do not have permission to delete this eDocs Level.';
                                } else if (xhr.status === 404) {
                                    errorMessage = 'eDocs Level not found.';
                                } else if (xhr.status === 500) {
                                    errorMessage = 'Server error. Please try again.';
                                }
                                console.error('Delete error:', xhr.status, xhr.responseText);
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

