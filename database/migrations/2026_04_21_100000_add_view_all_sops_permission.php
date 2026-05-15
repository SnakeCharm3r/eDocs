<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'view all sops', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Permission::where('name', 'view all sops')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};