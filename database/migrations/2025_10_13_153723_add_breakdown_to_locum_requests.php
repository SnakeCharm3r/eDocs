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
            if (!Schema::hasColumn('locum_requests', 'locum_year')) {
                $table->unsignedSmallInteger('locum_year')->nullable()->after('locum_month');
            }
            if (!Schema::hasColumn('locum_requests', 'hours_per_locum')) {
                $table->unsignedTinyInteger('hours_per_locum')->nullable()->after('locum_year'); // 8 or 12
            }
            if (!Schema::hasColumn('locum_requests', 'grand_total_locums')) {
                $table->unsignedInteger('grand_total_locums')->default(0)->after('hours_per_locum');
            }
            if (!Schema::hasColumn('locum_requests', 'per_shift_breakdown')) {
                // structure: {required_hours:int, shifts:{shift_id:{name,total_hours,locums,remainder}}, grand:{total_hours,locums,amount}}
                $table->json('per_shift_breakdown')->nullable()->after('grand_total_locums');
            }
            if (!Schema::hasColumn('locum_requests', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->nullable()->after('total_hours');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locum_requests', function (Blueprint $table) {
            if (Schema::hasColumn('locum_requests', 'per_shift_breakdown')) {
                $table->dropColumn('per_shift_breakdown');
            }
            if (Schema::hasColumn('locum_requests', 'grand_total_locums')) {
                $table->dropColumn('grand_total_locums');
            }
            if (Schema::hasColumn('locum_requests', 'hours_per_locum')) {
                $table->dropColumn('hours_per_locum');
            }
            if (Schema::hasColumn('locum_requests', 'locum_year')) {
                $table->dropColumn('locum_year');
            }
            if (Schema::hasColumn('locum_requests', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
        });
    }
};
