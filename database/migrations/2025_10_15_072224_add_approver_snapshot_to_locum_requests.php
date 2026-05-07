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
        Schema::table('locum_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('locum_requests', 'unit_ids')) {
                $table->json('unit_ids')->nullable()->after('worked_days');
            }
            if (!Schema::hasColumn('locum_requests', 'platform_ids')) {
                $table->json('platform_ids')->nullable()->after('unit_ids');
            }
            if (!Schema::hasColumn('locum_requests', 'incharge_user_ids')) {
                $table->json('incharge_user_ids')->nullable()->after('platform_ids');
            }
            if (!Schema::hasColumn('locum_requests', 'platform_manager_ids')) {
                $table->json('platform_manager_ids')->nullable()->after('incharge_user_ids');
            }
            if (!Schema::hasColumn('locum_requests', 'approval_flow')) {
                $table->string('approval_flow', 40)->nullable()->after('platform_manager_ids');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locum_requests', function (Blueprint $table) {
            foreach (['approval_flow', 'platform_manager_ids', 'incharge_user_ids', 'platform_ids', 'unit_ids'] as $c) {
                if (Schema::hasColumn('locum_requests', $c)) $table->dropColumn($c);
            }
        });
    }
};
