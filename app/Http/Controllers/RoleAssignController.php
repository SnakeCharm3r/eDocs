<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAssignController extends Controller
{
    //
    public function assignRoles($userId){
        $user = User::findOrFail($userId);
        $roles = Role::all();
        $permission = Permission::all();

        return view('admin-assign');
    }
}
