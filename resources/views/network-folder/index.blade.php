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
                            <i class="fas fa-folder-open me-2 text-success"></i>Network Folders
                        </h3>
                    </div>
                    <div class="col-auto">
                        @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#folderModal">
                                <i class="fas fa-plus"></i> Add New Network Folder
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
                                <i class="fas fa-list me-2 text-success"></i>Network Folders List
                            </h5>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="folderTable" class="table table-hover table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">#</th>
                                            <th style="width: 35%;">
                                                <i class="fas fa-tag me-1 text-success"></i>Folder Name
                                            </th>
                                            <th style="width: 25%;">
                                                <i class="fas fa-info-circle me-1 text-success"></i>Description
                                            </th>
                                            <th style="width: 15%;">
                                                <i class="fas fa-info-circle me-1 text-success"></i>Status
                                            </th>
                                            <th style="width: 10%;">
                                                <i class="fas fa-link me-1 text-success"></i>Usage
                                            </th>
                                            <th style="width: 10%;" class="text-center">
                                                <i class="fas fa-cog me-1 text-success"></i>Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($folders as $index => $folder)
                                            @php
                                                // Check usage in IctAccessResource
                                                $usageCount = \App\Models\IctAccessResource::where('network_folder', $folder->folder_name)
                                                    ->where('delete_status', '!=', '1')
                                                    ->count();
                                                $isInUse = $usageCount > 0;
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><strong>{{ $folder->folder_name }}</strong></td>
                                                <td>
                                                    <span class="text-muted">{{ $folder->description ?? '—' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $folder->folder_status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $folder->folder_status ?? 'active')) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($isInUse)
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            {{ $usageCount }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">Not in use</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('network-folder.edit', $folder->id) }}"
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
                                                                    class="btn btn-sm btn-outline-danger delete-folder-btn"
                                                                    data-id="{{ $folder->id }}"
                                                                    data-name="{{ $folder->folder_name }}"
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
                <div class="modal fade" id="folderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="folderModalLabel">Add Network Folder</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="{{ route('network-folder.store') }}" id="folderForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group mb-3">
                                                <label for="folder_name">Folder Name <span class="text-danger">*</span></label>
                                                <input type="text" name="folder_name" id="folder_name" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group mb-3">
                                                <label for="description">Description</label>
                                                <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group mb-3">
                                                <label for="folder_status">Status <span class="text-danger">*</span></label>
                                                <select name="folder_status" id="folder_status" class="form-control" required>
                                                    <option value="">Select Status</option>
                                                    <option value="active" {{ old('folder_status') == 'active' ? 'selected' : '' }}>Active</option>
                                                    <option value="not_active" {{ old('folder_status') == 'not_active' ? 'selected' : '' }}>Not Active</option>
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
            var table = $('#folderTable').DataTable({
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                columnDefs: [{
                    orderable: false,
                    targets: -1
                }]
            });

            // Handle delete button click
            $(document).on('click', '.delete-folder-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var button = $(this);
                var folderId = button.data('id');
                var folderName = button.data('name');
                var usageCount = parseInt(button.data('usage-count')) || 0;

                if (usageCount > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Delete',
                        html: `Cannot delete <strong>${folderName}</strong>.<br><br>This Network Folder is currently being used by <strong>${usageCount}</strong> ICT access resource(s). Please remove all references first before deleting.`,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    html: `Do you want to delete <strong>${folderName}</strong>?<br><br>This action will soft delete the Network Folder.`,
                    icon: 'question',
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
                            didOpen: () => { Swal.showLoading(); }
                        });

                        $.ajax({
                            url: `/network-folder/${folderId}`,
                            type: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message || 'Network Folder deleted successfully!',
                                    confirmButtonColor: '#28a745',
                                    timer: 2000
                                }).then(() => { location.reload(); });
                            },
                            error: function(xhr) {
                                var errorMessage = 'An error occurred while deleting the Network Folder.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
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

