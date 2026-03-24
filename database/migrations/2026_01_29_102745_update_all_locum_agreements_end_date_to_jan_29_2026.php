<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Updates all existing locum agreements to have an end date of January 29, 2026.
     * This aligns all previous contracts with the new yearly renewal rule.
     */
    public function up(): void
    {
        $endDate = Carbon::create(2026, 1, 29)->format('Y-m-d');
        
        DB::table('locum_agreements')
            ->where('has_contract', true)
            ->update([
                'end_date' => $endDate,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     * Note: This cannot be safely reversed as we don't know the original end dates.
     */
    public function down(): void
    {
        // This migration cannot be safely reversed
        // Original end dates are not preserved
    }
};
