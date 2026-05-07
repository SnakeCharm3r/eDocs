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
                            <i class="fas fa-user-shield me-2 text-success"></i>Privilege Levels
                        </h3>
                    </div>
                    <div class="col-auto">
                        @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#privilegeModal">
                                <i class="fas fa-plus"></i> Add New Privilege Level
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
                                <i class="fas fa-list me-2 text-success"></i>Privilege Levels List
                            </h5>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="privilegeTable" class="table table-hover table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">#</th>
                                            <th style="width: 25%;">
                                                <i class="fas fa-tag me-1 text-success"></i>Name
                                            </th>
                                            <th style="width: 15%;">
                                                <i class="fas fa-info-circle me-1 text-success"></i>Status
                                            </th>
                                            <th style="width: 35%;">
                                                <i class="fas fa-check-circle me-1 text-success"></i>Available For
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
                                        @foreach ($priv as $index => $privilege)
                                            @php
                                                // Check usage in IctAccessResource (used in multiple fields)
                                                // Note: aruti, VPN, pbax, active_drt, email are stored as strings, so we need to cast
                                                $usageCount = \App\Models\IctAccessResource::where('delete_status', '!=', '1')
                                                    ->where(function($query) use ($privilege) {
                                                        $query->where('privilegeId', $privilege->id)
                                                            ->orWhere('folder_privilege', $privilege->id)
                                                            ->orWhere('aruti', (string)$privilege->id)
                                                            ->orWhere('VPN', (string)$privilege->id)
                                                            ->orWhere('pbax', (string)$privilege->id)
                                                            ->orWhere('active_drt', (string)$privilege->id)
                                                            ->orWhere('email', (string)$privilege->id);
                                                    })
                                                    ->count();
                                                $isInUse = $usageCount > 0;
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><strong>{{ $privilege->prv_name }}</strong></td>
                                                <td>
                                                    <span class="badge {{ $privilege->prv_status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $privilege->prv_status ?? 'active')) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @if ($privilege->can_use_for_domain_access)
                                                            <span class="badge bg-info" title="Domain Access">Domain</span>
                                                        @endif
                                                        @if ($privilege->can_use_for_email_access)
                                                            <span class="badge bg-primary" title="Email Access">Email</span>
                                                        @endif
                                                        @if ($privilege->can_use_for_vpn_access)
                                                            <span class="badge bg-warning text-dark" title="VPN Access">VPN</span>
                                                        @endif
                                                        @if ($privilege->can_use_for_pbax_access)
                                                            <span class="badge bg-secondary" title="PABX Access">PABX</span>
                                                        @endif
                                                        @if (!$privilege->can_use_for_domain_access && !$privilege->can_use_for_email_access && !$privilege->can_use_for_vpn_access && !$privilege->can_use_for_pbax_access)
                                                            <span class="text-muted small">None</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($isInUse)
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            {{ $usageCount }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if (auth()->user()->hasAnyRole(['super-admin', 'it']))
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('privilege.edit', $privilege->id) }}"
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
                                                                    class="btn btn-sm btn-outline-danger delete-privilege-btn"
                                                                    data-id="{{ $privilege->id }}"
                                                                    data-name="{{ $privilege->prv_name }}"
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
                <div class="modal fade" id="privilegeModal" tabindex="-1" aria-labelledby="privilegeModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="privilegeModalLabel">Add Privilege Level</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="{{ route('privilege.store') }}" id="privilegeForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-12 col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="prv_name">Name <span class="text-danger">*</span></label>
                                                <input type="text" name="prv_name" id="prv_name" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="prv_status">Status <span class="text-danger">*</span></label>
                                                <select name="prv_status" id="prv_status" class="form-control" required>
                                                    <option value="">Select Status</option>
                                                    <option value="active" {{ old('prv_status') == 'active' ? 'selected' : '' }}>Active</option>
                                                    <option value="not_active" {{ old('prv_status') == 'not_active' ? 'selected' : '' }}>Not Active</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group mb-3">
                                                <label class="mb-2">Available For <small class="text-muted">(Select where this privilege can be used)</small></label>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="can_use_for_domain_access" id="can_use_for_domain_access" value="1" {{ old('can_use_for_domain_access') ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="can_use_for_domain_access">
                                                                Domain Access Level
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="can_use_for_email_access" id="can_use_for_email_access" value="1" {{ old('can_use_for_email_access') ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="can_use_for_email_access">
                                                                Email Access Level
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="can_use_for_vpn_access" id="can_use_for_vpn_access" value="1" {{ old('can_use_for_vpn_access') ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="can_use_for_vpn_access">
                                                                Network Access VPN
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="can_use_for_pbax_access" id="can_use_for_pbax_access" value="1" {{ old('can_use_for_pbax_access') ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="can_use_for_pbax_access">
                                                                Call Manager-PABX
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
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
            var table = $('#privilegeTable').DataTable({
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
            $(document).on('click', '.delete-privilege-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var button = $(this);
                var privilegeId = button.data('id');
                var privilegeName = button.data('name');
                var usageCount = parseInt(button.data('usage-count')) || 0;

                if (!privilegeId) {
                    console.error('Privilege ID not found');
                    alert('Privilege ID not found. Please refresh the page and try again.');
                    return;
                }

                // Prevent deletion if Privilege Level is in use
                if (usageCount > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Delete',
                        html: `Cannot delete <strong>${privilegeName}</strong>.<br><br>This Privilege Level is currently being used by <strong>${usageCount}</strong> ICT access resource(s). Please remove all references first before deleting.`,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                // Show confirmation dialog
                Swal.fire({
                    title: 'Are you sure?',
                    html: `Do you want to delete <strong>${privilegeName}</strong>?<br><br>This action will soft delete the Privilege Level.`,
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
                            url: `/privilege/${privilegeId}`,
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
                                    text: response.message || 'Privilege Level deleted successfully!',
                                    confirmButtonColor: '#28a745',
                                    timer: 2000
                                }).then(() => {
                                    // Reload the page to refresh the table
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                var errorMessage = 'An error occurred while deleting the Privilege Level.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.status === 403) {
                                    errorMessage = 'You do not have permission to delete this Privilege Level.';
                                } else if (xhr.status === 404) {
                                    errorMessage = 'Privilege Level not found.';
                                } else if (xhr.status === 500) {
                                    errorMessage = 'Server error. Please try again.';
                                } else if (xhr.responseText) {
                                    try {
                                        var response = JSON.parse(xhr.responseText);
                                        if (response.message) {
                                            errorMessage = response.message;
                                        }
                                    } catch (e) {
                                        errorMessage = xhr.responseText.substring(0, 200);
                                    }
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
