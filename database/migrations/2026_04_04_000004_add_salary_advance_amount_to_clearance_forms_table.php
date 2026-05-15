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
            if (!Schema::hasColumn('clearance_forms', 'repaid_salary_advance_amount')) {
                $table->decimal('repaid_salary_advance_amount', 15, 2)->nullable()->after('repaid_salary_advance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            if (Schema::hasColumn('clearance_forms', 'repaid_salary_advance_amount')) {
                $table->dropColumn('repaid_salary_advance_amount');
            }
        });
    }
};
