@extends('layouts.template')

@section('breadcrumb')
    @include('includes.loader')
    @include('sweetalert::alert')
@endsection

@section('content')
    {{-- DataTables + SweetAlert2 --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-7">
                        <div class="page-sub-header">
                            <h3 class="page-title mb-1">Roles</h3>
                        </div>
                    </div>
                    <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
                        <a href="{{ route('permission.index') }}" class="btn btn-outline-primary me-2">
                            <i class="fas fa-shield-alt me-1"></i> Manage Permissions
                        </a>
                        <button type="button" class="btn btn-primary create-btn" data-bs-toggle="modal"
                            data-bs-target="#roleModal">
                            <i class="fas fa-plus me-1"></i> Add Role
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                @php
                                    // Server-side alphabetical sort by name (case-insensitive, natural)
                                    // Filter out acting-line-manager role
                                    $sortedRoles = $roles->filter(function($role) {
                                        return strtolower($role->name) !== 'acting-line-manager';
                                    })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
                                @endphp
                                <table id="rolesTable" class="table table-striped table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:70px;">No</th>
                                            <th>Name</th>
                                            <th style="width: 220px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($sortedRoles as $role)
                                            @php
                                                $usersWithRole = \App\Models\User::role($role->name)->count();
                                                $canDelete = $usersWithRole === 0;
                                            @endphp
                                            <tr>
                                                <!-- blade index is fine as fallback; DataTables will overwrite with paged numbering -->
                                                <td class="text-muted">{{ $loop->iteration }}</td>
                                                <td class="fw-semibold">
                                                    {{ $role->name }}
                                                    @if($usersWithRole > 0)
                                                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                            <i class="fas fa-users me-1"></i>{{ $usersWithRole }} user(s) assigned
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="{{ route('role.edit', $role->id) }}"
                                                            class="btn btn-outline-success btn-sm"
                                                            title="Edit {{ $role->name }}">
                                                            <i class="fas fa-edit me-1"></i> Edit
                                                        </a>

                                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                            onclick="deleteConfirmation('{{ $role->id }}', '{{ addslashes($role->name) }}', {{ $canDelete ? 'true' : 'false' }}, {{ $usersWithRole }})"
                                                            title="Delete {{ $role->name }}"
                                                            @if(!$canDelete) disabled @endif>
                                                            <i class="fas fa-trash-alt me-1"></i> Delete
                                                        </button>

                                                        <form id="delete-form-{{ $role->id }}"
                                                            action="{{ route('role.destroy', $role->id) }}" method="POST"
                                                            style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-5 text-muted">
                                                    <i class="far fa-folder-open d-block mb-2"
                                                        style="font-size: 1.6rem;"></i>
                                                    No roles found. Click <span class="fw-semibold">Add Role</span> to
                                                    create one.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div> <!-- /table-responsive -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold" id="roleModalLabel">Add Role</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form method="POST" action="{{ route('role.store') }}" enctype="multipart/form-data"
                            id="createRoleForm">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Role Name <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fas fa-tag text-muted"></i></span>
                                        <input type="text" name="name" id="name" class="form-control" required
                                            placeholder="e.g., manager, hr, it" minlength="2" maxlength="50"
                                            autocomplete="off">
                                    </div>
                                    <small class="text-muted">Use a concise, lowercase name where possible.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" id="btnCreateRole" class="btn btn-primary">
                                    <span class="me-1"><i class="fas fa-save"></i></span> Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div><!-- /modal -->
        </div>
    </div>

    <script>
        // DataTable:
        // - default sort by Name
        // - pageLength 20 (show 20 roles initially)
        // - dynamic "No" column that accounts for pagination
        new DataTable('#rolesTable', {
            responsive: true,
            order: [
                [1, 'asc']
            ], // Name column
            pageLength: 20,
            lengthMenu: [
                [10, 20, 50, 100, -1],
                [10, 20, 50, 100, 'All']
            ],
            columnDefs: [{
                    targets: 0, // No
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    targets: 2,
                    orderable: false
                } // Actions
            ],
            language: {
                searchPlaceholder: 'Search roles…'
            }
        });

        // Toasts from session
        @if (session('success'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });
        @endif

        @if (session('error'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: @json(session('error')),
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        @endif

        // Create form UX (trim + disable double submit)
        (function() {
            const form = document.getElementById('createRoleForm');
            const btn = document.getElementById('btnCreateRole');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                const name = document.getElementById('name');
                name.value = (name.value || '').trim().replace(/\s+/g, '-');
                if (name.value.length < 2) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Role name too short',
                        text: 'Please enter at least 2 characters.'
                    });
                    return;
                }
                btn.disabled = true;
                btn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
            });

            document.getElementById('roleModal')
                ?.addEventListener('shown.bs.modal', () => document.getElementById('name')?.focus());
        })();

        // Delete confirmation
        function deleteConfirmation(roleId, roleName = 'this role', canDelete = true, userCount = 0) {
            if (!canDelete) {
                Swal.fire({
                    title: 'Cannot Delete Role',
                    html: `Cannot delete <b>${roleName}</b>.<br><br><small class="text-danger">This role is assigned to <strong>${userCount}</strong> user(s). Please remove the role from all users first.</small>`,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
                return;
            }
            
            Swal.fire({
                title: 'Delete Role?',
                html: `You are about to delete <b>${roleName}</b>.<br><br><small class="text-muted">This action cannot be undone.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-form-' + roleId);
                    if (!form) return;
                    // prevent double
                    document.querySelectorAll('button, a').forEach(b => b.disabled = true);
                    form.submit();
                }
            });
        }
    </script>
@endsection
