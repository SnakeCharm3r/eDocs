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
     * Expires all existing locum agreements by setting their end_date to yesterday.
     * This forces all users to renew their contracts with the new rates.
     */
    public function up(): void
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        
        DB::table('locum_agreements')
            ->where('has_contract', true)
            ->where(function ($query) use ($yesterday) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>', $yesterday);
            })
            ->update([
                'end_date' => $yesterday,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be safely reversed as we don't know the original end dates
        // Users will need to renew their agreements manually
    }
};
