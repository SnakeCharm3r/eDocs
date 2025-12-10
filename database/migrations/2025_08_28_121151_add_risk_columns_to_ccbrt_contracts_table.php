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
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            $table->string('likelihood_rating')->nullable()->after('status');
            $table->string('impact_if_not_requested')->nullable()->after('likelihood_rating');
            $table->string('overall_risk')->nullable()->after('impact_if_not_requested');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            $table->dropColumn(['likelihood_rating', 'impact_if_not_requested', 'overall_risk']);
        });
    }
};
