<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Fix all existing locum agreements:
     *
     * 1. Correct start_date / end_date to the proper 29 Jan cycle based on created_at.
     * 2. Mark expired approved agreements (status=2, end_date < today) as status=5 (Expired).
     * 3. Keep currently valid approved agreements as status=2 (Active/Approved).
     * 4. Leave pending (0,1) and rejected (3,4) agreements untouched status-wise.
     */
    public function up(): void
    {
        $today = Carbon::today();
        $agreements = DB::table('locum_agreements')->get();
        $fixedDates = 0;
        $markedExpired = 0;

        foreach ($agreements as $agreement) {
            $updates = [];
            $createdAt = Carbon::parse($agreement->created_at);
            $createdYear = (int) $createdAt->format('Y');

            // --- 1. Fix dates to correct 29 Jan cycle ---
            $jan29OfCreatedYear = Carbon::parse(sprintf('%d-01-29', $createdYear));

            if ($createdAt->lt($jan29OfCreatedYear)) {
                $correctStart = sprintf('%d-01-29', $createdYear - 1);
                $correctEnd = sprintf('%d-01-29', $createdYear);
            } else {
                $correctStart = sprintf('%d-01-29', $createdYear);
                $correctEnd = sprintf('%d-01-29', $createdYear + 1);
            }

            if ($agreement->start_date !== $correctStart || $agreement->end_date !== $correctEnd) {
                $updates['start_date'] = $correctStart;
                $updates['end_date'] = $correctEnd;
                $fixedDates++;
            }

            // Use the corrected end date for expiry check
            $endDate = Carbon::parse($updates['end_date'] ?? $agreement->end_date ?? $correctEnd);

            // --- 2. Mark expired approved agreements as status 5 ---
            if ((int) $agreement->status === 2 && $endDate->lt($today)) {
                $updates['status'] = 5; // Expired
                $markedExpired++;
            }

            if (!empty($updates)) {
                DB::table('locum_agreements')
                    ->where('id', $agreement->id)
                    ->update($updates);
            }
        }

        Log::info("Migration fix_locum_agreement_dates_and_status: Fixed {$fixedDates} dates, marked {$markedExpired} as expired out of {$agreements->count()} total agreements.");
    }

    /**
     * Reverse: set expired (status=5) back to approved (status=2).
     * Date corrections are not reversed as the corrected dates are the intended values.
     */
    public function down(): void
    {
        DB::table('locum_agreements')
            ->where('status', 5)
            ->update(['status' => 2]);
    }
};
