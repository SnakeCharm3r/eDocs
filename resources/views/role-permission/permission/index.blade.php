@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Permissions Management</h3>
                        <p class="text-muted mb-0">Manage role permissions and access control</p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('role.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-users me-1"></i> Manage Roles
                        </a>
                        <a href="{{ route('permission.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add Permission
                        </a>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="role-select" class="form-label fw-semibold">Select Role</label>
                                    <select id="role-select" class="form-select form-select-lg">
                                        <option value="">-- Select a role to manage permissions --</option>
                                        @foreach ($roles as $role)
                                            @if(strtolower($role->name) !== 'acting-line-manager')
                                                <option value="{{ $role->id }}" {{ request()->get('role') == $role->id ? 'selected' : '' }}>
                                                    {{ $role->name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Choose a role to view and modify its permissions</small>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div id="selected-role-info" class="text-muted" style="display: none;">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <span id="selected-role-name"></span>
                                    </div>
                                </div>
                            </div>

                            <form id="permissions-form" method="POST" action="{{ route('role.updatePermissions') }}">
                                @csrf
                                <input type="hidden" name="role_id" id="role-id">

                                <div id="permissions-container" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Permissions</h5>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="select-all-permissions">
                                            <label class="form-check-label" for="select-all-permissions">
                                                Select All
                                            </label>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50px;">
                                                        <input type="checkbox" class="form-check-input" id="select-all-checkbox">
                                                    </th>
                                                    <th style="width: 80px;">ID</th>
                                                    <th>Permission Name</th>
                                                    <th style="width: 100px;" class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="permissions-table-body">
                                                @foreach ($permissions as $permission)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="form-check-input permission-checkbox"
                                                                name="permissions[]" value="{{ $permission->name }}"
                                                                id="permission-{{ $permission->id }}">
                                                        </td>
                                                        <td class="text-muted">{{ $permission->id }}</td>
                                                        <td>
                                                            <label for="permission-{{ $permission->id }}" class="mb-0 cursor-pointer">
                                                                {{ $permission->name }}
                                                            </label>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-secondary permission-status" id="status-{{ $permission->id }}">
                                                                Not Assigned
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="row mt-4">
                                        <div class="col-md-12 text-end">
                                            <button type="submit" class="btn btn-primary btn-lg" id="save-btn" disabled>
                                                <i class="fas fa-save me-2"></i> Save Permissions
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-lg ms-2" id="cancel-btn" onclick="resetForm()">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div id="no-role-selected" class="text-center py-5">
                                    <i class="fas fa-shield-alt text-muted" style="font-size: 4rem;"></i>
                                    <p class="text-muted mt-3 mb-0">Please select a role to manage permissions</p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .cursor-pointer {
            cursor: pointer;
        }

        .permission-checkbox:checked + label,
        label[for^="permission-"] {
            cursor: pointer;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge.bg-success {
            background-color: #198754 !important;
        }

        .badge.bg-secondary {
            background-color: #6c757d !important;
        }

        #permissions-container {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role-select');
            const roleIdInput = document.getElementById('role-id');
            const permissionsTableBody = document.getElementById('permissions-table-body');
            const permissionsContainer = document.getElementById('permissions-container');
            const noRoleSelected = document.getElementById('no-role-selected');
            const selectedRoleInfo = document.getElementById('selected-role-info');
            const selectedRoleName = document.getElementById('selected-role-name');
            const saveBtn = document.getElementById('save-btn');
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const selectAllPermissions = document.getElementById('select-all-permissions');
            const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');

            // Auto-select role if provided in URL and automatically load permissions
            @if(request()->has('role'))
                const urlRoleId = {{ request()->get('role') }};
                if (urlRoleId) {
                    // Set the role select value
                    roleSelect.value = urlRoleId;
                    
                    // Set the hidden role_id input
                    roleIdInput.value = urlRoleId;
                    
                    // Get the selected option text
                    const selectedOption = roleSelect.options[roleSelect.selectedIndex];
                    if (selectedOption) {
                        selectedRoleName.textContent = `Managing permissions for: ${selectedOption.text}`;
                        selectedRoleInfo.style.display = 'block';
                    }
                    
                    // Show permissions container and hide "no role selected" message
                    permissionsContainer.style.display = 'block';
                    noRoleSelected.style.display = 'none';
                    saveBtn.disabled = false;
                    
                    // Fetch and load permissions for the selected role
                    fetch(`/role/${urlRoleId}/permissions`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => {
                                    throw new Error(err.error || 'Failed to load permissions');
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Check if data is an array
                            if (!Array.isArray(data)) {
                                throw new Error('Invalid response format');
                            }

                            // Uncheck all first
                            permissionCheckboxes.forEach(checkbox => {
                                checkbox.checked = false;
                                updatePermissionStatus(checkbox.id.replace('permission-', ''), false);
                            });

                            // Check permissions that are active
                            data.forEach(permission => {
                                const checkbox = document.getElementById(`permission-${permission.id}`);
                                if (checkbox) {
                                    checkbox.checked = permission.active;
                                    updatePermissionStatus(permission.id, permission.active);
                                }
                            });

                            updateSelectAllState();
                            
                            // Scroll to permissions container smoothly
                            setTimeout(() => {
                                permissionsContainer.scrollIntoView({ 
                                    behavior: 'smooth', 
                                    block: 'start' 
                                });
                            }, 100);
                        })
                        .catch(error => {
                            console.error('Error fetching permissions:', error);
                            alert('Failed to load permissions: ' + error.message);
                            // Reset UI on error
                            permissionsContainer.style.display = 'none';
                            noRoleSelected.style.display = 'block';
                            selectedRoleInfo.style.display = 'none';
                            roleSelect.value = '';
                        });
                }
            @endif

            // Role selection handler
            roleSelect.addEventListener('change', function() {
                const roleId = this.value;
                roleIdInput.value = roleId;

                if (roleId) {
                    const selectedOption = this.options[this.selectedIndex];
                    selectedRoleName.textContent = `Managing permissions for: ${selectedOption.text}`;
                    selectedRoleInfo.style.display = 'block';
                    permissionsContainer.style.display = 'block';
                    noRoleSelected.style.display = 'none';
                    saveBtn.disabled = false;

                    // Fetch permissions for selected role
                    fetch(`/role/${roleId}/permissions`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => {
                                    throw new Error(err.error || 'Failed to load permissions');
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Check if data is an array
                            if (!Array.isArray(data)) {
                                throw new Error('Invalid response format');
                            }

                            // Uncheck all first
                            permissionCheckboxes.forEach(checkbox => {
                                checkbox.checked = false;
                                updatePermissionStatus(checkbox.id.replace('permission-', ''), false);
                            });

                            // Check permissions that are active
                            data.forEach(permission => {
                                const checkbox = document.getElementById(`permission-${permission.id}`);
                                if (checkbox) {
                                    checkbox.checked = permission.active;
                                    updatePermissionStatus(permission.id, permission.active);
                                }
                            });

                            updateSelectAllState();
                        })
                        .catch(error => {
                            console.error('Error fetching permissions:', error);
                            alert('Failed to load permissions: ' + error.message);
                            // Reset UI on error
                            permissionsContainer.style.display = 'none';
                            noRoleSelected.style.display = 'block';
                            selectedRoleInfo.style.display = 'none';
                            roleSelect.value = '';
                        });
                } else {
                    permissionsContainer.style.display = 'none';
                    noRoleSelected.style.display = 'block';
                    selectedRoleInfo.style.display = 'none';
                    saveBtn.disabled = true;
                    
                    // Uncheck all
                    permissionCheckboxes.forEach(checkbox => {
                        checkbox.checked = false;
                        updatePermissionStatus(checkbox.id.replace('permission-', ''), false);
                    });
                }
            });

            // Select all checkbox handler
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                permissionCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                    const permissionId = checkbox.id.replace('permission-', '');
                    updatePermissionStatus(permissionId, isChecked);
                });
                selectAllPermissions.checked = isChecked;
            });

            // Select all permissions switch handler
            selectAllPermissions.addEventListener('change', function() {
                const isChecked = this.checked;
                permissionCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                    const permissionId = checkbox.id.replace('permission-', '');
                    updatePermissionStatus(permissionId, isChecked);
                });
                selectAllCheckbox.checked = isChecked;
            });

            // Individual checkbox handler
            permissionCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const permissionId = this.id.replace('permission-', '');
                    updatePermissionStatus(permissionId, this.checked);
                    updateSelectAllState();
                });
            });

            function updatePermissionStatus(permissionId, isActive) {
                const statusBadge = document.getElementById(`status-${permissionId}`);
                if (statusBadge) {
                    if (isActive) {
                        statusBadge.textContent = 'Assigned';
                        statusBadge.className = 'badge bg-success permission-status';
                    } else {
                        statusBadge.textContent = 'Not Assigned';
                        statusBadge.className = 'badge bg-secondary permission-status';
                    }
                }
            }

            function updateSelectAllState() {
                const checkedCount = Array.from(permissionCheckboxes).filter(cb => cb.checked).length;
                const allChecked = checkedCount === permissionCheckboxes.length;
                const someChecked = checkedCount > 0 && checkedCount < permissionCheckboxes.length;

                selectAllCheckbox.checked = allChecked;
                selectAllPermissions.checked = allChecked;
                
                if (someChecked) {
                    selectAllCheckbox.indeterminate = true;
                } else {
                    selectAllCheckbox.indeterminate = false;
                }
            }

            // Form submission handler
            document.getElementById('permissions-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const roleId = roleIdInput.value;
                if (!roleId) {
                    alert('Please select a role first.');
                    return false;
                }

                const checkedPermissions = Array.from(permissionCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (checkedPermissions.length === 0) {
                    if (!confirm('No permissions selected. This will remove all permissions from the role. Continue?')) {
                        return false;
                    }
                }

                // Disable button and show loading
                saveBtn.disabled = true;
                const originalBtnText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

                // Prepare form data
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('role_id', roleId);
                checkedPermissions.forEach(permission => {
                    formData.append('permissions[]', permission);
                });

                // Submit via AJAX
                fetch('{{ route("role.updatePermissions") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'Failed to save permissions');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message using SweetAlert if available, otherwise use alert
                        let message = data.message || 'Permissions updated successfully!';
                        if (data.recovered) {
                            message += ' (Some invalid permissions were automatically filtered out)';
                        }
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: message,
                                timer: 3000,
                                showConfirmButton: true
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            alert(message);
                            window.location.reload();
                        }
                    } else {
                        throw new Error(data.error || 'Failed to save permissions');
                    }
                })
                .catch(error => {
                    console.error('Error saving permissions:', error);
                    
                    // Check if we need to refresh the page
                    let errorMessage = error.message || 'Failed to save permissions. Please try again.';
                    let needsRefresh = false;
                    
                    if (error.response) {
                        error.response.json().then(data => {
                            if (data.refresh_required) {
                                needsRefresh = true;
                            }
                            if (data.error) {
                                errorMessage = data.error;
                            }
                        }).catch(() => {
                            // If JSON parsing fails, use the original error message
                        });
                    }
                    
                    // Show error message using SweetAlert if available, otherwise use alert
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage,
                            confirmButtonText: needsRefresh ? 'Refresh Page' : 'OK'
                        }).then((result) => {
                            if (needsRefresh || result.isConfirmed) {
                                window.location.reload();
                            }
                        });
                    } else {
                        if (needsRefresh) {
                            if (confirm(errorMessage + '\n\nWould you like to refresh the page?')) {
                                window.location.reload();
                            }
                        } else {
                            alert('Error: ' + errorMessage);
                        }
                    }
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalBtnText;
                });
            });
        });

        function resetForm() {
            document.getElementById('role-select').value = '';
            document.getElementById('role-select').dispatchEvent(new Event('change'));
        }

        function deletePermission(permissionId) {
            if (confirm("Are you sure you want to delete this permission? This action cannot be undone.")) {
                fetch(`/permission/${permissionId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            window.location.reload();
                        } else {
                            return response.json().then(data => {
                                throw new Error(data.message || 'Failed to delete permission');
                            });
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                        console.error('Error deleting permission:', error);
                    });
            }
        }
    </script>
@endsection
