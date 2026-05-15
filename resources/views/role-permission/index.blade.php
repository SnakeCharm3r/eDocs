@extends('layouts.template')

@section('breadcrumb')
    @include('includes.loader')
    @include('sweetalert::alert')
@endsection

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />

    <style>
        .page-wrapper { background: #f8f9fb; min-height: 100vh; }
        .page-header-bar {
            background: #fff; border-radius: 12px; padding: 1rem 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 1.25rem;
        }

        /* Stat cards */
        .stat-card {
            background: #fff; border-radius: 12px; padding: 1rem 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06); display: flex;
            align-items: center; gap: .75rem; transition: transform .15s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .stat-value { font-size: 1.4rem; font-weight: 700; line-height: 1.2; }
        .stat-label { font-size: .78rem; color: #6b7280; }

        /* Tabs */
        .role-tabs .nav-link {
            font-size: .88rem; font-weight: 600; color: #6b7280;
            padding: .65rem 1.25rem; border: none;
            border-bottom: 2px solid transparent; transition: all .2s;
        }
        .role-tabs .nav-link:hover { color: #374151; }
        .role-tabs .nav-link.active {
            color: #0d6efd; background: transparent;
            border-bottom: 2px solid #0d6efd;
        }

        /* Role card */
        .role-card {
            background: #fff; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            border: 1px solid #f0f0f0; padding: 1.15rem;
            transition: all .2s; height: 100%;
        }
        .role-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            border-color: #dbeafe;
        }
        .role-name {
            font-size: 1rem; font-weight: 700; color: #1f2937;
            margin-bottom: .25rem;
        }
        .role-meta {
            font-size: .78rem; color: #9ca3af; margin-bottom: .65rem;
        }

        /* Permission pills */
        .perm-pill {
            display: inline-block; padding: .18rem .5rem; border-radius: 6px;
            font-size: .72rem; font-weight: 500; margin: 2px;
            background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb;
        }

        /* Role icon colors */
        .role-icon-super-admin { background: #fee2e2; color: #dc2626; }
        .role-icon-admin { background: #dbeafe; color: #2563eb; }
        .role-icon-hr { background: #d1fae5; color: #059669; }
        .role-icon-it { background: #e0e7ff; color: #4f46e5; }
        .role-icon-coo { background: #fef3c7; color: #d97706; }
        .role-icon-cfo { background: #fce7f3; color: #db2777; }
        .role-icon-cms { background: #f3e8ff; color: #7c3aed; }
        .role-icon-finance { background: #ccfbf1; color: #0d9488; }
        .role-icon-default { background: #f3f4f6; color: #6b7280; }

        /* Actions */
        .role-actions { display: flex; gap: .4rem; flex-wrap: wrap; }
        .role-actions .btn { font-size: .78rem; padding: .3rem .6rem; border-radius: 6px; }

        /* Perm table */
        .perm-table code {
            background: #f0f4ff; padding: .15rem .4rem; border-radius: 4px;
            font-size: .82rem; color: #1e40af;
        }

        /* Badge counts */
        .count-badge {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 22px; height: 22px; border-radius: 12px;
            font-size: .72rem; font-weight: 700; padding: 0 .4rem;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            {{-- Header --}}
            <div class="page-header-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1" style="font-size:.82rem;">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Roles & Permissions</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal" style="border-radius:8px;">
                        <i class="fas fa-plus me-1"></i> Add Role
                    </button>
                    <a href="{{ route('permission.create') }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                        <i class="fas fa-plus me-1"></i> Add Permission
                    </a>
                </div>
            </div>

            @php
                $sortedRoles = $roles->filter(fn($r) => strtolower($r->name) !== 'acting-line-manager')
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->load('permissions');
                $totalRoles = $sortedRoles->count();
                $totalPermissions = $permissions->count();
                $totalAssigned = $sortedRoles->sum(fn($r) => \App\Models\User::role($r->name)->count());
            @endphp

            {{-- Stats --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#dbeafe;color:#2563eb;">
                            <i class="fas fa-user-tag"></i>
                        </div>
                        <div>
                            <div class="stat-value">{{ $totalRoles }}</div>
                            <div class="stat-label">Total Roles</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#d1fae5;color:#059669;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <div class="stat-value">{{ $totalPermissions }}</div>
                            <div class="stat-label">Total Permissions</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="stat-value">{{ $totalAssigned }}</div>
                            <div class="stat-label">Role Assignments</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-tabs role-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-roles" role="tab">
                        <i class="fas fa-user-tag me-1"></i> Roles
                        <span class="count-badge bg-primary text-white ms-1">{{ $totalRoles }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-permissions" role="tab">
                        <i class="fas fa-shield-alt me-1"></i> Permissions
                        <span class="count-badge bg-success text-white ms-1">{{ $totalPermissions }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Roles Tab --}}
                <div class="tab-pane fade show active" id="tab-roles" role="tabpanel">
                    <div class="row g-3">
                        @forelse ($sortedRoles as $role)
                            @php
                                $usersWithRole = \App\Models\User::role($role->name)->count();
                                $canDelete = $usersWithRole === 0;
                                $rl = strtolower($role->name);
                                $iconClass = match(true) {
                                    str_contains($rl, 'super-admin') => 'role-icon-super-admin',
                                    str_contains($rl, 'admin') => 'role-icon-admin',
                                    $rl === 'hr' => 'role-icon-hr',
                                    $rl === 'it' => 'role-icon-it',
                                    $rl === 'coo' => 'role-icon-coo',
                                    $rl === 'cfo' => 'role-icon-cfo',
                                    $rl === 'cms' => 'role-icon-cms',
                                    str_contains($rl, 'finance') => 'role-icon-finance',
                                    default => 'role-icon-default',
                                };
                                $iconSymbol = match(true) {
                                    str_contains($rl, 'super-admin') => 'fa-crown',
                                    str_contains($rl, 'admin') => 'fa-user-shield',
                                    $rl === 'hr' => 'fa-users',
                                    $rl === 'it' => 'fa-laptop-code',
                                    $rl === 'coo' => 'fa-briefcase',
                                    $rl === 'cfo' => 'fa-chart-line',
                                    $rl === 'cms' => 'fa-cogs',
                                    str_contains($rl, 'finance') => 'fa-money-bill-wave',
                                    default => 'fa-user-tag',
                                };
                                $displayName = $role->name;
                                if (str_starts_with($rl, 'finance-officer-')) {
                                    $entityCode = strtoupper(substr($role->name, strlen('finance-officer-')));
                                    $displayName = 'Finance Officer (' . $entityCode . ')';
                                }
                            @endphp
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="role-card">
                                    <div class="d-flex align-items-start gap-3 mb-2">
                                        <div class="stat-icon {{ $iconClass }}" style="width:38px;height:38px;font-size:.95rem;">
                                            <i class="fas {{ $iconSymbol }}"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="role-name">{{ $displayName }}</div>
                                            <div class="role-meta">
                                                <i class="fas fa-users me-1"></i>{{ $usersWithRole }} user{{ $usersWithRole !== 1 ? 's' : '' }}
                                                &middot;
                                                <i class="fas fa-shield-alt me-1"></i>{{ $role->permissions->count() }} permission{{ $role->permissions->count() !== 1 ? 's' : '' }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Permissions preview --}}
                                    <div class="mb-3" style="min-height:40px;">
                                        @if($role->permissions->count() > 0)
                                            @foreach($role->permissions->sortBy('name')->take(6) as $perm)
                                                <span class="perm-pill">{{ $perm->name }}</span>
                                            @endforeach
                                            @if($role->permissions->count() > 6)
                                                <span class="perm-pill" style="background:#dbeafe;color:#2563eb;border-color:#bfdbfe;">
                                                    +{{ $role->permissions->count() - 6 }} more
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted" style="font-size:.82rem;font-style:italic;">No permissions assigned</span>
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div class="role-actions border-top pt-2">
                                        <a href="{{ route('role.edit', $role->id) }}" class="btn btn-outline-secondary" title="Edit Name">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="{{ route('permission.index') }}?role={{ $role->id }}" class="btn btn-outline-primary" title="Manage Permissions">
                                            <i class="fas fa-shield-alt"></i> Permissions
                                        </a>
                                        <button type="button" class="btn btn-outline-danger"
                                            onclick="deleteConfirmation('{{ $role->id }}', '{{ addslashes($role->name) }}', {{ $canDelete ? 'true' : 'false' }}, {{ $usersWithRole }})"
                                            @if(!$canDelete) disabled title="Role has {{ $usersWithRole }} assigned user(s)" @else title="Delete" @endif>
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <form id="delete-form-{{ $role->id }}"
                                            action="{{ route('role.destroy', $role->id) }}" method="POST"
                                            style="display:none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open d-block mb-2" style="font-size:2rem;color:#d1d5db;"></i>
                                    <p>No roles found. Click <strong>Add Role</strong> to create one.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Permissions Tab --}}
                <div class="tab-pane fade" id="tab-permissions" role="tabpanel">
                    <div class="card shadow-sm border-0" style="border-radius:12px;">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="permissionsTable" class="table table-hover align-middle perm-table">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;">No</th>
                                            <th>Permission</th>
                                            <th style="width:120px;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($permissions->sortBy('name') as $permission)
                                            <tr>
                                                <td class="text-muted"></td>
                                                <td><code>{{ $permission->name }}</code></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editPermission({{ $permission->id }})" style="border-radius:6px;">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-5 text-muted">
                                                    <i class="fas fa-folder-open d-block mb-2" style="font-size:2rem;color:#d1d5db;"></i>
                                                    No permissions found.
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

            {{-- Create Role Modal --}}
            <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0" style="border-radius:12px;">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold" id="roleModalLabel">
                                <i class="fas fa-user-tag text-primary me-2"></i>Add Role
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('role.store') }}" id="createRoleForm">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fas fa-tag text-muted"></i></span>
                                        <input type="text" name="name" id="name" class="form-control" required
                                            placeholder="e.g., manager, hr, it" minlength="2" maxlength="50" autocomplete="off"
                                            style="border-radius:0 8px 8px 0;">
                                    </div>
                                    <small class="text-muted">Use a concise, lowercase name where possible.</small>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Cancel</button>
                                <button type="submit" id="btnCreateRole" class="btn btn-primary" style="border-radius:8px;">
                                    <i class="fas fa-save me-1"></i> Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            var permTable = $('#permissionsTable').DataTable({
                order: [[1, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                columnDefs: [
                    { orderable: false, targets: [0, 2] }
                ],
                language: { searchPlaceholder: 'Search permissions…', search: '' },
                drawCallback: function() {
                    var api = this.api();
                    api.column(0, { search: 'applied', order: 'applied' }).nodes().each(function(cell, i) {
                        cell.innerHTML = (api.page() * api.page.len()) + i + 1;
                    });
                }
            });
        });

        // Toasts
        @if (session('success'))
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: @json(session('success')), showConfirmButton: false, timer: 2500, timerProgressBar: true });
        @endif
        @if (session('error'))
            Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: @json(session('error')), showConfirmButton: false, timer: 3000, timerProgressBar: true });
        @endif

        // Create role form
        (function() {
            var form = document.getElementById('createRoleForm');
            var btn = document.getElementById('btnCreateRole');
            if (!form) return;
            form.addEventListener('submit', function(e) {
                var name = document.getElementById('name');
                name.value = (name.value || '').trim().replace(/\s+/g, '-');
                if (name.value.length < 2) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Role name too short', text: 'Please enter at least 2 characters.' });
                    return;
                }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            });
            document.getElementById('roleModal')?.addEventListener('shown.bs.modal', function() {
                document.getElementById('name')?.focus();
            });
        })();

        function editPermission(permissionId) {
            window.location.href = '{{ route("permission.index") }}';
        }

        function deleteConfirmation(roleId, roleName, canDelete, userCount) {
            if (!canDelete) {
                Swal.fire({
                    title: 'Cannot Delete',
                    html: 'Cannot delete <b>' + roleName + '</b>.<br><br><small class="text-danger">Assigned to <strong>' + userCount + '</strong> user(s). Remove the role from all users first.</small>',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
                return;
            }
            Swal.fire({
                title: 'Delete Role?',
                html: 'Delete <b>' + roleName + '</b>?<br><small class="text-muted">This cannot be undone.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var form = document.getElementById('delete-form-' + roleId);
                    if (form) form.submit();
                }
            });
        }

        @if(request()->has('role'))
            document.addEventListener('DOMContentLoaded', function() {
                var permTab = document.querySelector('[href="#tab-permissions"]');
                if (permTab) {
                    permTab.click();
                    setTimeout(function() {
                        window.location.href = '{{ route("permission.index") }}?role=' + {{ request()->get('role') }};
                    }, 100);
                }
            });
        @endif
    </script>
@endpush
@endsection

