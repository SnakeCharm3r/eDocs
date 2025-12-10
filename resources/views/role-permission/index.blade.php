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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Roles & Permissions</h3>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#roleModal">
                            <i class="fas fa-plus me-1"></i> Add Role
                        </button>
                        <a href="{{ route('permission.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add Permission
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="rolesPermissionsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button" role="tab" aria-controls="roles" aria-selected="true">
                        Roles
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions" type="button" role="tab" aria-controls="permissions" aria-selected="false">
                        Permissions
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="rolesPermissionsTabsContent">
                <!-- Roles Tab -->
                <div class="tab-pane fade show active" id="roles" role="tabpanel" aria-labelledby="roles-tab">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="table-responsive">
                                @php
                                    $sortedRoles = $roles->filter(function($role) {
                                        return strtolower($role->name) !== 'acting-line-manager';
                                    })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->load('permissions');
                                @endphp
                                <table id="rolesTable" class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 200px;">Role</th>
                                            <th>Permissions</th>
                                            <th style="width: 250px;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($sortedRoles as $role)
                                            @php
                                                $usersWithRole = \App\Models\User::role($role->name)->count();
                                                $canDelete = $usersWithRole === 0;
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold">
                                                    {{ $role->name }}
                                                    @if($usersWithRole > 0)
                                                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                            <i class="fas fa-users me-1"></i>{{ $usersWithRole }} user(s) assigned
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @forelse ($role->permissions as $permission)
                                                            <span class="badge bg-secondary text-white" style="font-size: 0.75rem; padding: 0.35em 0.65em;">
                                                                {{ $permission->name }}
                                                            </span>
                                                        @empty
                                                            <span class="text-muted fst-italic">No permissions assigned</span>
                                                        @endforelse
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <a href="{{ route('role.edit', $role->id) }}" class="btn btn-sm btn-outline-success" title="Edit Role Name">
                                                            <i class="fas fa-edit me-1"></i> Edit Name
                                                        </a>
                                                        <a href="{{ route('permission.index') }}?role={{ $role->id }}" class="btn btn-sm btn-primary" title="Edit Permissions">
                                                            <i class="fas fa-shield-alt me-1"></i> Permissions
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger"
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
                                                    <i class="far fa-folder-open d-block mb-2" style="font-size: 1.6rem;"></i>
                                                    No roles found. Click <span class="fw-semibold">Add Role</span> to create one.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permissions Tab -->
                <div class="tab-pane fade" id="permissions" role="tabpanel" aria-labelledby="permissions-tab">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="permissionsTable" class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Permission</th>
                                            <th style="width: 120px;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($permissions as $permission)
                                            <tr>
                                                <td>
                                                    <code class="text-dark">{{ $permission->name }}</code>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="editPermission({{ $permission->id }})" title="Edit Permission">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center py-5 text-muted">
                                                    <i class="far fa-folder-open d-block mb-2" style="font-size: 1.6rem;"></i>
                                                    No permissions found. Click <span class="fw-semibold">Add Permission</span> to create one.
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

            <!-- Create Role Modal -->
            <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold" id="roleModalLabel">Add Role</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('role.store') }}" id="createRoleForm">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fas fa-tag text-muted"></i></span>
                                        <input type="text" name="name" id="name" class="form-control" required
                                            placeholder="e.g., manager, hr, it" minlength="2" maxlength="50" autocomplete="off">
                                    </div>
                                    <small class="text-muted">Use a concise, lowercase name where possible.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" id="btnCreateRole" class="btn btn-primary">
                                    <span class="me-1"><i class="fas fa-save"></i></span> Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.75rem 1.5rem;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            background-color: transparent;
            border-color: transparent;
            border-bottom-color: #0d6efd;
            font-weight: 600;
        }

        .badge {
            white-space: nowrap;
        }

        code {
            background-color: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
    </style>

    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('#rolesTable').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                columnDefs: [
                    { targets: 2, orderable: false }
                ],
                language: {
                    searchPlaceholder: 'Search roles…'
                }
            });

            $('#permissionsTable').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                columnDefs: [
                    { targets: 1, orderable: false }
                ],
                language: {
                    searchPlaceholder: 'Search permissions…'
                }
            });
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

        // Create role form handler
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
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
            });

            document.getElementById('roleModal')?.addEventListener('shown.bs.modal', () => {
                document.getElementById('name')?.focus();
            });
        })();

        function editPermission(permissionId) {
            // Redirect to permissions management page
            window.location.href = '{{ route("permission.index") }}';
        }

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

        // Handle role selection from URL parameter
        @if(request()->has('role'))
            document.addEventListener('DOMContentLoaded', function() {
                // Switch to permissions tab and select the role
                const permissionsTab = document.getElementById('permissions-tab');
                if (permissionsTab) {
                    permissionsTab.click();
                    // Small delay to ensure tab is switched
                    setTimeout(function() {
                        window.location.href = '{{ route("permission.index") }}?role=' + {{ request()->get('role') }};
                    }, 100);
                }
            });
        @endif
    </script>
@endsection

