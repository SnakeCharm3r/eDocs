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
        Schema::table('requisitions', function (Blueprint $table) {
            // Change max_monthly_budget from DECIMAL(10, 2) to DECIMAL(15, 2) to support larger values
            $table->decimal('max_monthly_budget', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            // Revert back to DECIMAL(10, 2)
            $table->decimal('max_monthly_budget', 10, 2)->nullable()->change();
        });
    }
};
