<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Update all locum agreements created in January 2026 to be valid from creation date until same date in January 2027.
     */
    public function up(): void
    {
        if (Schema::hasTable('locum_agreements')) {
            $jan2026Agreements = DB::table('locum_agreements')
                ->whereMonth('created_at', '1')
                ->whereYear('created_at', '2026')
                ->get(['id', 'created_at']);

            foreach ($jan2026Agreements as $agreement) {
                // Extract day from creation date
                $creationDate = new \DateTime($agreement->created_at);
                $day = $creationDate->format('d');
                
                // Set start date to creation date
                $startDate = $creationDate->format('Y-m-d');
                
                // Set end date to same day in January 2027
                $endDate = "2027-01-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                
                DB::table('locum_agreements')
                    ->where('id', $agreement->id)
                    ->update([
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible as we don't know the original dates
        // In a real scenario, you might want to backup the original data first
    }
};
