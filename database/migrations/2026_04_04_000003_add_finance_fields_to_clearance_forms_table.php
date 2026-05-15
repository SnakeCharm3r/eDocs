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
        Schema::table('clearance_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('clearance_forms', 'has_bonding_agreement')) {
                $table->string('has_bonding_agreement')->nullable()->after('repaid_salary_advance');
            }
            if (!Schema::hasColumn('clearance_forms', 'bonding_agreement_amount')) {
                $table->decimal('bonding_agreement_amount', 15, 2)->nullable()->after('has_bonding_agreement');
            }
            if (!Schema::hasColumn('clearance_forms', 'cleared_imprest_or_business_advance')) {
                $table->string('cleared_imprest_or_business_advance')->nullable()->after('repaid_outstanding_imprest');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            if (Schema::hasColumn('clearance_forms', 'cleared_imprest_or_business_advance')) {
                $table->dropColumn('cleared_imprest_or_business_advance');
            }
            if (Schema::hasColumn('clearance_forms', 'bonding_agreement_amount')) {
                $table->dropColumn('bonding_agreement_amount');
            }
            if (Schema::hasColumn('clearance_forms', 'has_bonding_agreement')) {
                $table->dropColumn('has_bonding_agreement');
            }
        });
    }
};
