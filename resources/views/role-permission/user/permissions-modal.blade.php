<div class="text-start">
    <div class="alert alert-info mb-3">
        <i class="fas fa-info-circle me-2"></i>
        <strong>User:</strong> {{ $user->username }} ({{ $user->fname }} {{ $user->lname }})
    </div>
    
    @php
        $allUserPermissions = $user->getAllPermissions()->pluck('name')->toArray();
    @endphp
    
    <div class="mb-3">
        <label class="form-check-label fw-bold">
            <input type="checkbox" id="selectAllPermissions" class="form-check-input me-2">
            Select All Permissions
        </label>
    </div>
    
    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px;">
        @if($userRoles->count() > 0)
            @foreach($userRoles as $role)
                <div class="role-permission-group mb-4" data-role="{{ $role->name }}">
                    <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
                        <label class="form-check-label fw-bold mb-0 flex-grow-1">
                            <input type="checkbox" class="form-check-input select-all-role me-2">
                            <i class="fas fa-shield-alt text-primary me-2"></i>
                            <strong>{{ ucwords(str_replace('-', ' ', $role->name)) }}</strong>
                        </label>
                    </div>
                    <div class="row ms-3">
                        @php
                            $rolePermissions = $permissionsByRole[$role->name] ?? [];
                        @endphp
                        @foreach($allPermissions as $permission)
                            @php
                                $hasPermissionViaRole = in_array($permission->name, $rolePermissions);
                                $hasDirectPermission = in_array($permission->name, $userDirectPermissions);
                                $userHasPermission = in_array($permission->name, $allUserPermissions);
                            @endphp
                            <div class="col-md-6 mb-2">
                                <label class="form-check-label small">
                                    <input type="checkbox" 
                                        class="form-check-input permission-checkbox me-2" 
                                        value="{{ $permission->name }}"
                                        {{ $userHasPermission ? 'checked' : '' }}
                                        data-role="{{ $role->name }}">
                                    {{ $permission->name }}
                                    @if($hasPermissionViaRole && !$hasDirectPermission)
                                        <small class="text-muted">(via role)</small>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <hr class="my-3">
                </div>
            @endforeach
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                This user has no roles assigned. Please assign roles first.
            </div>
        @endif
    </div>
    
    <style>
        .role-permission-group {
            border-left: 3px solid #007bff;
            padding-left: 10px;
        }
        .permission-checkbox {
            cursor: pointer;
        }
    </style>
</div>

