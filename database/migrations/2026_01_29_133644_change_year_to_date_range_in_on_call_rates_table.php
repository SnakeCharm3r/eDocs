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
        // First, add the new columns
        Schema::table('on_call_rates', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('education_level');
            $table->date('end_date')->nullable()->after('start_date');
        });
        
        // Convert existing year values to date ranges (if year column exists)
        if (Schema::hasColumn('on_call_rates', 'year')) {
            $rates = \DB::table('on_call_rates')->whereNotNull('year')->get();
            foreach ($rates as $rate) {
                $year = (int)$rate->year;
                \DB::table('on_call_rates')
                    ->where('id', $rate->id)
                    ->update([
                        'start_date' => "{$year}-01-01",
                        'end_date' => "{$year}-12-31",
                    ]);
            }
        }
        
        // Set default dates for any records without dates
        \DB::table('on_call_rates')
            ->whereNull('start_date')
            ->orWhereNull('end_date')
            ->update([
                'start_date' => now()->startOfYear()->format('Y-m-d'),
                'end_date' => now()->endOfYear()->format('Y-m-d'),
            ]);
        
        // Make columns required and drop year column
        Schema::table('on_call_rates', function (Blueprint $table) {
            // Make columns required
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
            
            // Drop unique constraint on (education_level, year) if it exists
            try {
                $table->dropUnique(['education_level', 'year']);
            } catch (\Exception $e) {
                // Constraint might not exist, continue
            }
            
            // Drop year column if it exists
            if (Schema::hasColumn('on_call_rates', 'year')) {
                $table->dropColumn('year');
            }
            
            // Add unique constraint on (education_level, start_date, end_date)
            $table->unique(['education_level', 'start_date', 'end_date'], 'on_call_rates_education_start_end_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('on_call_rates', function (Blueprint $table) {
            // Drop the unique constraint on (education_level, start_date, end_date)
            try {
                $table->dropUnique('on_call_rates_education_start_end_unique');
            } catch (\Exception $e) {
                // Constraint might not exist, continue
            }
        });
        
        // Extract year from start_date and add year column
        Schema::table('on_call_rates', function (Blueprint $table) {
            $table->year('year')->nullable()->after('education_level');
        });
        
        // Populate year from start_date
        $rates = \DB::table('on_call_rates')->whereNotNull('start_date')->get();
        foreach ($rates as $rate) {
            $year = \Carbon\Carbon::parse($rate->start_date)->year;
            \DB::table('on_call_rates')
                ->where('id', $rate->id)
                ->update(['year' => $year]);
        }
        
        Schema::table('on_call_rates', function (Blueprint $table) {
            // Make year required
            $table->year('year')->nullable(false)->change();
            
            // Drop start_date and end_date columns
            $table->dropColumn(['start_date', 'end_date']);
            
            // Restore unique constraint on (education_level, year)
            $table->unique(['education_level', 'year']);
        });
    }
};
