<?php

namespace App\Console\Commands;

use App\Models\LocumAgreement;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixAgreementDates extends Command
{
    protected $signature = 'app:fix-agreement-dates';

    protected $description = 'Fix all existing locum agreement start_date/end_date to the correct 29 Jan cycle based on created_at';

    public function handle()
    {
        $agreements = LocumAgreement::all();
        $updated = 0;

        $this->info("Found {$agreements->count()} agreements. Processing...");

        foreach ($agreements as $agreement) {
            $createdAt = Carbon::parse($agreement->created_at);
            $createdYear = (int) $createdAt->format('Y');

            // Determine which 29 Jan cycle this agreement belongs to
            $jan29OfCreatedYear = Carbon::parse(sprintf('%d-01-29', $createdYear));

            if ($createdAt->lt($jan29OfCreatedYear)) {
                // Created before Jan 29 of that year → belongs to previous cycle
                $correctStart = sprintf('%d-01-29', $createdYear - 1);
                $correctEnd = sprintf('%d-01-29', $createdYear);
            } else {
                // Created on or after Jan 29 → belongs to current cycle
                $correctStart = sprintf('%d-01-29', $createdYear);
                $correctEnd = sprintf('%d-01-29', $createdYear + 1);
            }

            $oldStart = $agreement->start_date;
            $oldEnd = $agreement->end_date;

            if ($oldStart !== $correctStart || $oldEnd !== $correctEnd) {
                $agreement->update([
                    'start_date' => $correctStart,
                    'end_date' => $correctEnd,
                ]);
                $updated++;
                $this->line("  #{$agreement->id} | created: {$agreement->created_at} | {$oldStart} → {$correctStart} | {$oldEnd} → {$correctEnd}");
            } else {
                $this->line("  #{$agreement->id} | already correct ({$correctStart} – {$correctEnd})");
            }
        }

        $this->info("Done. Updated {$updated} of {$agreements->count()} agreements.");
    }
}
