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
            if (!Schema::hasColumn('locum_requests', 'previous_month_reason')) {
                $table->text('previous_month_reason')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locum_requests', function (Blueprint $table) {
            if (Schema::hasColumn('locum_requests', 'previous_month_reason')) {
                $table->dropColumn('previous_month_reason');
            }
        });
    }
};
