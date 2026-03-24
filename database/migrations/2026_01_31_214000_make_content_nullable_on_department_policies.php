<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Department policies are PDF-only; content column should be nullable.
     */
    public function up(): void
    {
        if (Schema::hasTable('department_policies') && Schema::hasColumn('department_policies', 'content')) {
            DB::statement('ALTER TABLE department_policies MODIFY content LONGTEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('department_policies') && Schema::hasColumn('department_policies', 'content')) {
            DB::statement('ALTER TABLE department_policies MODIFY content LONGTEXT NOT NULL');
        }
    }
};
