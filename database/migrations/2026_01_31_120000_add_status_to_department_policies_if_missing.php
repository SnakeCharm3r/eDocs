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
        if (!Schema::hasTable('department_policies')) {
            return;
        }

        Schema::table('department_policies', function (Blueprint $table) {
            if (!Schema::hasColumn('department_policies', 'visible_to_all_staff')) {
                $table->boolean('visible_to_all_staff')->default(false);
            }
            if (!Schema::hasColumn('department_policies', 'status')) {
                $table->enum('status', ['active', 'archived'])->default('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('department_policies')) {
            return;
        }
        Schema::table('department_policies', function (Blueprint $table) {
            if (Schema::hasColumn('department_policies', 'visible_to_all_staff')) {
                $table->dropColumn('visible_to_all_staff');
            }
            if (Schema::hasColumn('department_policies', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
