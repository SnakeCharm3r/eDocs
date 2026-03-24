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
        Schema::table('on_call_rates', function (Blueprint $table) {
            // Drop the unique constraint on education_level
            $table->dropUnique(['education_level']);
            
            // Add year column (default to current year)
            $table->year('year')->default(date('Y'))->after('education_level');
            
            // Add unique constraint on (education_level, year)
            $table->unique(['education_level', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('on_call_rates', function (Blueprint $table) {
            // Drop the unique constraint on (education_level, year)
            $table->dropUnique(['education_level', 'year']);
            
            // Remove year column
            $table->dropColumn('year');
            
            // Restore unique constraint on education_level
            $table->unique('education_level');
        });
    }
};
