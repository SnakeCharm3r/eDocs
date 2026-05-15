@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .page-wrapper { background: #f8f9fb; min-height: 100vh; }
        .page-header-bar {
            background: #fff; border-radius: 12px; padding: 1rem 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 1.25rem;
        }

        /* Role selector card */
        .role-selector-card {
            background: #fff; border-radius: 12px; padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 1.25rem;
        }
        .role-selector-card select {
            border-radius: 8px; border: 1px solid #e5e7eb;
            padding: .55rem .75rem; font-size: .9rem;
        }
        .role-selector-card select:focus {
            border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,.12);
        }

        /* Role info banner */
        .role-info-banner {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid #bbf7d0; border-radius: 10px;
            padding: .75rem 1rem; display: flex; align-items: center; gap: .75rem;
        }
        .role-info-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: #059669; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .role-info-name { font-weight: 700; color: #065f46; font-size: .95rem; }
        .role-info-meta { font-size: .78rem; color: #6b7280; }

        /* Permissions grid */
        .perm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: .75rem;
        }
        .perm-item {
            background: #fff; border: 1px solid #f0f0f0; border-radius: 10px;
            padding: .75rem 1rem; display: flex; align-items: center; gap: .75rem;
            cursor: pointer; transition: all .15s;
        }
        .perm-item:hover {
            border-color: #a7f3d0; background: #f0fdf4;
            box-shadow: 0 2px 6px rgba(0,0,0,.04);
        }
        .perm-item.active {
            border-color: #6ee7b7; background: #ecfdf5;
        }
        .perm-item .form-check-input {
            width: 18px; height: 18px; flex-shrink: 0;
            margin: 0; cursor: pointer;
        }
        .perm-item .form-check-input:checked {
            background-color: #059669; border-color: #059669;
        }
        .perm-name {
            font-size: .84rem; font-weight: 500; color: #374151;
            flex-grow: 1; line-height: 1.3;
        }
        .perm-status {
            font-size: .7rem; font-weight: 600; padding: .15rem .45rem;
            border-radius: 12px; flex-shrink: 0; white-space: nowrap;
        }
        .perm-status-on { background: #d1fae5; color: #065f46; }
        .perm-status-off { background: #f3f4f6; color: #9ca3af; }

        /* Stats bar */
        .perm-stats {
            display: flex; gap: 1.5rem; align-items: center;
            font-size: .82rem; color: #6b7280;
        }
        .perm-stats .stat-num { font-weight: 700; color: #1f2937; }

        /* Search */
        .perm-search {
            border-radius: 8px; border: 1px solid #e5e7eb;
            padding: .45rem .75rem; font-size: .85rem; max-width: 280px;
        }
        .perm-search:focus {
            border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,.12);
        }

        /* Empty / no role state */
        .empty-state {
            text-align: center; padding: 3rem 1rem;
            color: #9ca3af;
        }
        .empty-state i { font-size: 3rem; margin-bottom: .75rem; color: #d1d5db; }

        /* Actions bar */
        .actions-bar {
            background: #fff; border-radius: 12px; padding: 1rem 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-top: 1rem;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; bottom: 1rem; z-index: 10;
        }

        /* Fade animation */
        .fade-in { animation: fadeIn .3s ease-in; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            {{-- Header --}}
            <div class="page-header-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0" style="font-size:.82rem;">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('role.index') }}">Roles & Permissions</a></li>
                            <li class="breadcrumb-item active">Manage Permissions</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('role.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                        <i class="fas fa-arrow-left me-1"></i> Back to Roles
                    </a>
                    <a href="{{ route('permission.create') }}" class="btn btn-sm btn-primary" style="border-radius:8px;">
                        <i class="fas fa-plus me-1"></i> Add Permission
                    </a>
                </div>
            </div>

            {{-- Role Selector --}}
            <div class="role-selector-card">
                <div class="row align-items-center g-3">
                    <div class="col-md-5">
                        <label for="role-select" class="form-label fw-semibold mb-1" style="font-size:.82rem;color:#374151;">
                            <i class="fas fa-user-tag me-1 text-primary"></i> Select Role
                        </label>
                        <select id="role-select" class="form-select">
                            <option value="">-- Choose a role --</option>
                            @foreach ($roles as $role)
                                @if(strtolower($role->name) !== 'acting-line-manager')
                                    <option value="{{ $role->id }}" {{ request()->get('role') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7" id="role-info-col" style="display:none;">
                        <div class="role-info-banner fade-in">
                            <div class="role-info-icon"><i class="fas fa-shield-alt"></i></div>
                            <div>
                                <div class="role-info-name" id="role-info-name"></div>
                                <div class="role-info-meta" id="role-info-meta"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Permissions Form --}}
            <form id="permissions-form" method="POST" action="{{ route('role.updatePermissions') }}">
                @csrf
                <input type="hidden" name="role_id" id="role-id">

                {{-- No role selected --}}
                <div id="no-role-selected">
                    <div class="empty-state">
                        <i class="fas fa-shield-alt d-block"></i>
                        <h5 style="color:#6b7280;font-weight:600;">Select a Role</h5>
                        <p style="font-size:.88rem;">Choose a role from the dropdown above to manage its permissions</p>
                    </div>
                </div>

                {{-- Permissions container --}}
                <div id="permissions-container" style="display:none;" class="fade-in">
                    {{-- Toolbar --}}
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div class="perm-stats">
                            <span><span class="stat-num" id="assigned-count">0</span> assigned</span>
                            <span><span class="stat-num" id="total-count">{{ $permissions->count() }}</span> total</span>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text" class="form-control perm-search" id="perm-search" placeholder="Search permissions...">
                            <div class="form-check form-switch mb-0" style="white-space:nowrap;">
                                <input class="form-check-input" type="checkbox" id="select-all-permissions" style="width:18px;height:18px;">
                                <label class="form-check-label" for="select-all-permissions" style="font-size:.82rem;font-weight:600;">All</label>
                            </div>
                        </div>
                    </div>

                    {{-- Grid --}}
                    <div class="perm-grid" id="perm-grid">
                        @foreach ($permissions->sortBy('name') as $permission)
                            <div class="perm-item" data-perm-id="{{ $permission->id }}" data-perm-name="{{ strtolower($permission->name) }}">
                                <input type="checkbox" class="form-check-input permission-checkbox"
                                    name="permissions[]" value="{{ $permission->name }}"
                                    id="permission-{{ $permission->id }}">
                                <span class="perm-name">{{ $permission->name }}</span>
                                <span class="perm-status perm-status-off" id="status-{{ $permission->id }}">Off</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- No results --}}
                    <div id="no-search-results" style="display:none;" class="text-center py-4 text-muted">
                        <i class="fas fa-search d-block mb-2" style="font-size:1.5rem;color:#d1d5db;"></i>
                        <p style="font-size:.88rem;">No permissions match your search</p>
                    </div>
                </div>

                {{-- Sticky save bar --}}
                <div id="actions-bar" class="actions-bar" style="display:none;">
                    <div class="perm-stats">
                        <span><span class="stat-num" id="assigned-count-bar">0</span> of {{ $permissions->count() }} permissions assigned</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;" onclick="resetForm()">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary" id="save-btn" style="border-radius:8px;" disabled>
                            <i class="fas fa-save me-1"></i> Save Permissions
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var roleSelect = document.getElementById('role-select');
            var roleIdInput = document.getElementById('role-id');
            var permContainer = document.getElementById('permissions-container');
            var noRoleSelected = document.getElementById('no-role-selected');
            var roleInfoCol = document.getElementById('role-info-col');
            var roleInfoName = document.getElementById('role-info-name');
            var roleInfoMeta = document.getElementById('role-info-meta');
            var saveBtn = document.getElementById('save-btn');
            var actionsBar = document.getElementById('actions-bar');
            var selectAll = document.getElementById('select-all-permissions');
            var permCheckboxes = document.querySelectorAll('.permission-checkbox');
            var permItems = document.querySelectorAll('.perm-item');
            var searchInput = document.getElementById('perm-search');
            var noSearchResults = document.getElementById('no-search-results');
            var assignedCount = document.getElementById('assigned-count');
            var assignedCountBar = document.getElementById('assigned-count-bar');

            // Click on perm-item toggles checkbox
            permItems.forEach(function(item) {
                item.addEventListener('click', function(e) {
                    if (e.target.tagName === 'INPUT') return;
                    var cb = item.querySelector('.permission-checkbox');
                    cb.checked = !cb.checked;
                    cb.dispatchEvent(new Event('change'));
                });
            });

            // Checkbox change
            permCheckboxes.forEach(function(cb) {
                cb.addEventListener('change', function() {
                    var id = this.id.replace('permission-', '');
                    var item = this.closest('.perm-item');
                    var status = document.getElementById('status-' + id);
                    if (this.checked) {
                        item.classList.add('active');
                        status.textContent = 'Assigned';
                        status.className = 'perm-status perm-status-on';
                    } else {
                        item.classList.remove('active');
                        status.textContent = 'Off';
                        status.className = 'perm-status perm-status-off';
                    }
                    updateCounts();
                    updateSelectAllState();
                });
            });

            // Select all
            selectAll.addEventListener('change', function() {
                var checked = this.checked;
                permCheckboxes.forEach(function(cb) {
                    // Only toggle visible items
                    var item = cb.closest('.perm-item');
                    if (item.style.display !== 'none') {
                        cb.checked = checked;
                        cb.dispatchEvent(new Event('change'));
                    }
                });
            });

            // Search
            searchInput.addEventListener('input', function() {
                var q = this.value.toLowerCase().trim();
                var visible = 0;
                permItems.forEach(function(item) {
                    var name = item.getAttribute('data-perm-name');
                    if (!q || name.indexOf(q) !== -1) {
                        item.style.display = '';
                        visible++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                noSearchResults.style.display = visible === 0 ? '' : 'none';
            });

            function updateCounts() {
                var count = Array.from(permCheckboxes).filter(function(cb) { return cb.checked; }).length;
                assignedCount.textContent = count;
                assignedCountBar.textContent = count;
            }

            function updateSelectAllState() {
                var all = permCheckboxes.length;
                var checked = Array.from(permCheckboxes).filter(function(cb) { return cb.checked; }).length;
                selectAll.checked = checked === all;
                selectAll.indeterminate = checked > 0 && checked < all;
            }

            function loadPermissions(roleId) {
                roleIdInput.value = roleId;
                var opt = roleSelect.options[roleSelect.selectedIndex];

                if (!roleId) {
                    permContainer.style.display = 'none';
                    noRoleSelected.style.display = '';
                    roleInfoCol.style.display = 'none';
                    actionsBar.style.display = 'none';
                    saveBtn.disabled = true;
                    permCheckboxes.forEach(function(cb) {
                        cb.checked = false;
                        cb.dispatchEvent(new Event('change'));
                    });
                    return;
                }

                roleInfoName.textContent = opt.text;
                roleInfoCol.style.display = '';
                permContainer.style.display = '';
                noRoleSelected.style.display = 'none';
                actionsBar.style.display = '';
                saveBtn.disabled = false;

                // Fetch permissions
                fetch('/role/' + roleId + '/permissions', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) {
                    if (!r.ok) throw new Error('Failed to load permissions');
                    return r.json();
                })
                .then(function(data) {
                    if (!Array.isArray(data)) throw new Error('Invalid response');

                    // Reset all
                    permCheckboxes.forEach(function(cb) {
                        cb.checked = false;
                        var item = cb.closest('.perm-item');
                        var id = cb.id.replace('permission-', '');
                        item.classList.remove('active');
                        var st = document.getElementById('status-' + id);
                        st.textContent = 'Off';
                        st.className = 'perm-status perm-status-off';
                    });

                    // Set active
                    var activeCount = 0;
                    data.forEach(function(p) {
                        var cb = document.getElementById('permission-' + p.id);
                        if (cb && p.active) {
                            cb.checked = true;
                            var item = cb.closest('.perm-item');
                            item.classList.add('active');
                            var st = document.getElementById('status-' + p.id);
                            st.textContent = 'Assigned';
                            st.className = 'perm-status perm-status-on';
                            activeCount++;
                        }
                    });

                    roleInfoMeta.textContent = activeCount + ' of ' + permCheckboxes.length + ' permissions assigned';
                    updateCounts();
                    updateSelectAllState();
                })
                .catch(function(err) {
                    console.error(err);
                    Swal.fire('Error', 'Failed to load permissions: ' + err.message, 'error');
                    permContainer.style.display = 'none';
                    noRoleSelected.style.display = '';
                    roleInfoCol.style.display = 'none';
                    actionsBar.style.display = 'none';
                    roleSelect.value = '';
                });
            }

            // Role change
            roleSelect.addEventListener('change', function() {
                loadPermissions(this.value);
            });

            // Auto-load from URL
            @if(request()->has('role'))
                loadPermissions('{{ request()->get('role') }}');
            @endif

            // Form submit
            document.getElementById('permissions-form').addEventListener('submit', function(e) {
                e.preventDefault();

                var roleId = roleIdInput.value;
                if (!roleId) {
                    Swal.fire('Error', 'Please select a role first.', 'warning');
                    return;
                }

                var checked = Array.from(permCheckboxes).filter(function(cb) { return cb.checked; }).map(function(cb) { return cb.value; });

                if (checked.length === 0) {
                    Swal.fire({
                        title: 'Remove All Permissions?',
                        text: 'This will remove all permissions from the role.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Remove All',
                        confirmButtonColor: '#dc3545'
                    }).then(function(result) {
                        if (result.isConfirmed) submitPermissions(roleId, checked);
                    });
                    return;
                }

                submitPermissions(roleId, checked);
            });

            function submitPermissions(roleId, permissions) {
                saveBtn.disabled = true;
                var origText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

                var formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('role_id', roleId);
                permissions.forEach(function(p) { formData.append('permissions[]', p); });

                fetch('{{ route("role.updatePermissions") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(function(r) {
                    if (!r.ok) return r.json().then(function(d) { throw new Error(d.error || 'Failed'); });
                    return r.json();
                })
                .then(function(data) {
                    if (data.success) {
                        var msg = data.message || 'Permissions updated successfully!';
                        if (data.recovered) msg += ' (Some invalid permissions were filtered out)';
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: msg,
                            timer: 2000,
                            showConfirmButton: false,
                            timerProgressBar: true
                        }).then(function() { window.location.reload(); });
                    } else {
                        throw new Error(data.error || 'Failed');
                    }
                })
                .catch(function(err) {
                    Swal.fire('Error', err.message || 'Failed to save. Please try again.', 'error');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = origText;
                });
            }

            // Toasts
            @if (session('status'))
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: @json(session('status')), showConfirmButton: false, timer: 2500, timerProgressBar: true });
            @endif
            @if (session('error'))
                Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: @json(session('error')), showConfirmButton: false, timer: 3000, timerProgressBar: true });
            @endif
        });

        function resetForm() {
            document.getElementById('role-select').value = '';
            document.getElementById('role-select').dispatchEvent(new Event('change'));
        }

        function deletePermission(permissionId) {
            Swal.fire({
                title: 'Delete Permission?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#dc3545'
            }).then(function(result) {
                if (result.isConfirmed) {
                    fetch('/permission/' + permissionId, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    })
                    .then(function(r) {
                        if (r.ok) {
                            Swal.fire({ icon: 'success', title: 'Deleted!', timer: 1500, showConfirmButton: false }).then(function() { window.location.reload(); });
                        } else {
                            return r.json().then(function(d) { throw new Error(d.message || 'Failed'); });
                        }
                    })
                    .catch(function(err) {
                        Swal.fire('Error', err.message, 'error');
                    });
                }
            });
        }
    </script>
@endpush
@endsection
