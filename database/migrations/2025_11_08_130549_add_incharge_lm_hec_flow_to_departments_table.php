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
            if (!Schema::hasColumn('departments', 'has_incharge_lm_hec_flow')) {
                $table->boolean('has_incharge_lm_hec_flow')->default(false)->after('has_incharge_platform_flow');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'has_incharge_lm_hec_flow')) {
                $table->dropColumn('has_incharge_lm_hec_flow');
            }
        });
    }
};
