<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RrfPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the view_all_rrf permission
        Permission::firstOrCreate(['name' => 'view_all_rrf', 'guard_name' => 'web']);

        // Assign to Super-Admin and admin by default
        foreach (['Super-Admin', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo('view_all_rrf');
            }
        }

        // HR also gets this permission by default
        $hr = Role::where('name', 'hr')->first();
        if ($hr) {
            $hr->givePermissionTo('view_all_rrf');
        }
    }
}
