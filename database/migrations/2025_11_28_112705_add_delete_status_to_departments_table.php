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
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'delete_status')) {
                $table->tinyInteger('delete_status')->default(0)->after('has_oncall_three_level_approval');
                $table->index('delete_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'delete_status')) {
                $table->dropIndex(['delete_status']);
                $table->dropColumn('delete_status');
            }
        });
    }
};
