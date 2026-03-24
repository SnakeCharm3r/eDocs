<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('department_policies') && !Schema::hasColumn('department_policies', 'view_count')) {
            Schema::table('department_policies', function (Blueprint $table) {
                $table->unsignedInteger('view_count')->default(0)->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('department_policies') && Schema::hasColumn('department_policies', 'view_count')) {
            Schema::table('department_policies', function (Blueprint $table) {
                $table->dropColumn('view_count');
            });
        }
    }
};
