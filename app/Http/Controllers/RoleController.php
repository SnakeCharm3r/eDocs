<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use RealRashid\SweetAlert\Facades\Alert;


class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage roles', ['only' => ['index', 'create', 'store', 'edit', 'update', 'destroy']]);
        $this->middleware('permission:assign roles', ['only' => ['addPermissionToRole', 'givePermissionToRole', 'showEditForm']]);
    }

    public function index()
    {
        $roles = Role::with('permissions')
            ->where('name', '!=', 'acting-line-manager')
            ->get();
        $permissions = \Spatie\Permission\Models\Permission::all();
        return view('role-permission.index', compact('roles', 'permissions'));
    }

    public function create()
    {
        $roles = Role::all(); // Fetch all roles
        return view('role-permission.role.create', ['roles' => $roles]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'unique:roles,name'
            ]
        ]);

        $role = Role::create([
            'name' => $request->name
        ]);
        

        return redirect('role')->with('status', 'Role Created Successfully');
    }

    public function edit(Role $role)
    {
        return view('role-permission.role.edit', [
            'role' => $role
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'unique:roles,name,' . $role->id
            ]
        ]);

        $oldName = $role->name;
        $role->update([
            'name' => $request->name
        ]);
        

        return redirect()->route('role.index')->with('status', 'Role Updated Successfully');
    }

    public function destroy($roleId)
    {
        try {
            $role = Role::findOrFail($roleId);
            $roleName = $role->name;
            
            // Check if any users have this role assigned
            $usersWithRole = User::role($role->name)->count();
            
            if ($usersWithRole > 0) {
                return redirect()->route('role.index')->with('error', "Cannot delete role '{$roleName}'. There are {$usersWithRole} user(s) assigned to this role. Please remove the role from all users first.");
            }
            
            $role->delete();
            
            return redirect()->route('role.index')->with('status', 'Role Deleted Successfully');
        } catch (\Exception $e) {
            return redirect()->route('role.index')->with('error', 'Failed to delete the role: ' . $e->getMessage());
        }
    }


    public function addPermissionToRole($roleId)
    {
        $permissions = Permission::get();
        $role = Role::findOrFail($roleId);
        $rolePermissions = DB::table('role_has_permissions')
            ->where('role_has_permissions.role_id', $role->id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return view('role-permission.role.add-permissions', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions
        ]);
    }

    public function givePermissionToRole(Request $request, $roleId)
    {
        $request->validate([
            'permission' => 'required'
        ]);

        $role = Role::findOrFail($roleId);
        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $role->syncPermissions($request->permission);
        $newPermissions = is_array($request->permission) ? $request->permission : [$request->permission];
        

        return redirect()->back()->with('status', 'Permissions added to role');
    }

    public function showEditForm($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::all(); // Fetch all roles
        $userRoles = $user->roles->pluck('name')->toArray(); // Get roles assigned to the user

        return view('role-permission.user.edit', compact('user', 'roles', 'userRoles'));
    }

}
