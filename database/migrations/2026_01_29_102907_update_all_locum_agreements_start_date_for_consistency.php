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
     * Updates start_date for all agreements to maintain one-year validity period.
     * For agreements created before Jan 29, 2025: set start_date to Jan 29, 2025
     * For agreements created after Jan 29, 2025: set start_date to creation date
     */
    public function up(): void
    {
        $standardStartDate = Carbon::create(2025, 1, 29)->format('Y-m-d');
        $cutoffDate = Carbon::create(2025, 1, 29);
        
        DB::table('locum_agreements')
            ->where('has_contract', true)
            ->where(function ($query) use ($standardStartDate, $cutoffDate) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<', $standardStartDate);
            })
            ->chunkById(100, function ($agreements) use ($standardStartDate, $cutoffDate) {
                foreach ($agreements as $agreement) {
                    $createdAt = Carbon::parse($agreement->created_at);
                    
                    // If created before Jan 29, 2025, use standard start date
                    // Otherwise, use creation date as start date
                    $startDate = $createdAt->lt($cutoffDate) 
                        ? $standardStartDate 
                        : $createdAt->format('Y-m-d');
                    
                    DB::table('locum_agreements')
                        ->where('id', $agreement->id)
                        ->update([
                            'start_date' => $startDate,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be safely reversed
    }
};
