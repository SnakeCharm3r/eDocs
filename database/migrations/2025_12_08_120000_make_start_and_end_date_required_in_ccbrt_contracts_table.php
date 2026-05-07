<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any NULL values to prevent errors when making columns NOT NULL
        // Set start_date to creation_date if NULL, or to today's date
        DB::statement("UPDATE ccbrt_contracts SET start_date = COALESCE(creation_date, CURDATE()) WHERE start_date IS NULL");

        // Set end_date based on start_date + duration_months if NULL
        DB::statement("UPDATE ccbrt_contracts SET end_date = DATE_ADD(start_date, INTERVAL COALESCE(duration_months, 12) MONTH) WHERE end_date IS NULL");

        // Now make the columns NOT NULL
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }
};
