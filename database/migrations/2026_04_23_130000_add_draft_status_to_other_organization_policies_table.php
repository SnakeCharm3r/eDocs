<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE other_organization_policies MODIFY COLUMN status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("UPDATE other_organization_policies SET status = 'active' WHERE status = 'draft'");
        DB::statement("ALTER TABLE other_organization_policies MODIFY COLUMN status ENUM('active', 'archived') NOT NULL DEFAULT 'active'");
    }
};