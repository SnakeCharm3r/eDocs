<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage permissions', ['only' => ['index', 'create', 'store', 'updateRolePermissions', 'destroy', 'getRolePermissions', 'getPermissionsByRole']]);
    }

    public function index()
    {
        $roles = Role::where('name', '!=', 'acting-line-manager')->get();
        $permissions = Permission::all();
        return view('role-permission.permission.index', compact('roles', 'permissions'));
    }

    public function getRolePermissions($roleId)
    {
        try {
            $role = Role::with('permissions')->find($roleId);
            
            if (!$role) {
                return response()->json([
                    'error' => 'Role not found'
                ], 404);
            }

            $permissions = Permission::all();
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            
            $permissionsData = $permissions->map(function ($permission) use ($rolePermissions) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'active' => in_array($permission->name, $rolePermissions)
                ];
            });

            return response()->json($permissionsData);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateRolePermissions(Request $request)
    {
        try {
            $request->validate([
                'role_id' => 'required|exists:roles,id',
                'permissions' => 'nullable|array',
                'permissions.*' => 'string'
            ]);

            $role = Role::findOrFail($request->role_id);
            
            // Handle permissions array - can be null or empty array
            $permissionNames = $request->permissions ?? [];
            
            // Ensure we have an array and filter out empty values
            if (!is_array($permissionNames)) {
                $permissionNames = [];
            }
            $permissionNames = array_filter($permissionNames, function($name) {
                return !empty($name) && is_string($name);
            });
            
            // Filter out any permissions that don't exist in the database
            // Use a fresh query to ensure we have the latest permissions
            $validPermissions = Permission::whereIn('name', $permissionNames)
                ->pluck('name')
                ->toArray();
            
            // Log if any permissions were filtered out
            $invalidPermissions = array_diff($permissionNames, $validPermissions);
            if (!empty($invalidPermissions)) {
                \Log::warning('Invalid permissions filtered out', [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'invalid_permissions' => $invalidPermissions,
                    'valid_permissions' => $validPermissions
                ]);
            }
            
            // Get old permissions for audit
            $oldPermissions = $role->permissions->pluck('name')->toArray();
            
            // Sync only valid permissions (empty array is fine - removes all permissions)
            $role->syncPermissions($validPermissions);
            
            // Clear the permission cache to ensure fresh data
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permissions updated successfully',
                    'permissions_count' => count($validPermissions),
                    'invalid_permissions' => $invalidPermissions
                ]);
            }

            $message = 'Permissions updated successfully';
            if (!empty($invalidPermissions)) {
                $message .= '. Note: Some permissions were invalid and were not assigned.';
            }

            return redirect()->back()->with('status', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database errors specifically
            \Log::error('Permission sync database error', [
                'role_id' => $request->role_id,
                'error' => $e->getMessage(),
                'permissions_sent' => $request->permissions ?? []
            ]);
            
            // If it's a foreign key constraint, try to recover by getting only valid permissions
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                try {
                    $permissionNames = $request->permissions ?? [];
                    $validPermissions = Permission::whereIn('name', $permissionNames)
                        ->pluck('name')
                        ->toArray();
                    
                    $role = Role::findOrFail($request->role_id);
                    $role->syncPermissions($validPermissions);
                    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                    
                    $message = 'Permissions updated successfully';
                    if (count($validPermissions) < count($permissionNames)) {
                        $message .= '. Note: Some invalid permissions were automatically filtered out.';
                    }
                    
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => true,
                            'message' => $message,
                            'permissions_count' => count($validPermissions),
                            'recovered' => true
                        ]);
                    }
                    return redirect()->back()->with('status', $message);
                } catch (\Exception $recoveryException) {
                    \Log::error('Failed to recover from permission sync error', [
                        'original_error' => $e->getMessage(),
                        'recovery_error' => $recoveryException->getMessage()
                    ]);
                }
            }
            
            $errorMessage = 'Database error occurred while updating permissions. Please refresh the page and try again.';
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                    'refresh_required' => true
                ], 500);
            }
            return redirect()->back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update permissions: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to update permissions: ' . $e->getMessage());
        }
    }



    public function getPermissionsByRole($roleId)
    {
        $role = Role::find($roleId);
        if ($role) {
            $permissions = Permission::all()->map(function ($permission) use ($role) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'active' => $role->hasPermissionTo($permission->name),
                ];
            });
            return response()->json($permissions);
        }
        return response()->json([], 404);
    }

    public function create()
    {
        return view('role-permission.permission.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'unique:permissions,name'
            ]
        ]);

        Permission::create([
            'name' => $request->name
        ]);

        return redirect('permission')->with('status', 'Permission Created Successfully');
    }

    public function edit(Permission $permission)
    {
        return view('role-permission.permission.edit', ['permission' => $permission]);
    }

    // public function update(Request $request, Permission $permission)
    // {
    //     $request->validate([
    //         'name' => [
    //             'required',
    //             'string',
    //             'unique:permissions,name,'.$permission->id
    //         ]
    //     ]);

    //      $permission->update([
    //         'name' => $request->name
    //     ]);

    //     return redirect('permission')->with('status', 'Permission Updated Successfully');
    // }

    public function destroy($permissionId)
    {
        $permission = Permission::find($permissionId);

        if ($permission) {
            $permission->delete();
            return response()->json(['status' => 'success', 'message' => 'Permission deleted successfully']);
        }

        return response()->json(['status' => 'error', 'message' => 'Permission not found'], 404);
    }
}
