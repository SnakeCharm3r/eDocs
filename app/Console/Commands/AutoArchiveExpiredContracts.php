<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CcbrtContract;
use App\Models\HecContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoArchiveExpiredContracts extends Command
{
    protected $signature   = 'contracts:auto-archive {--months=2 : Number of months after expiry before archiving}';
    protected $description = 'Automatically archive contracts that have been expired for more than the specified months with no renewal action';

    public function handle(): int
    {
        $months     = (int) $this->option('months');
        $cutoff     = Carbon::now()->subMonths($months);
        $archived   = 0;
        $skipped    = 0;

        $this->info("Auto-archiving contracts expired before {$cutoff->toDateString()} with no renewal action...");

        // Find expired contracts where:
        // 1. end_date is more than $months ago
        // 2. Not already archived
        // 3. Not renewed (no child renewals, status != renewed, renewal_status != renewed)
        // 4. Status is expired OR end_date is in the past with active/no-action status
        $contracts = CcbrtContract::where('end_date', '<', $cutoff)
            ->where(function ($q) {
                $q->whereNull('is_archived')
                  ->orWhere('is_archived', false);
            })
            ->whereNotIn('status', ['archived', 'renewed', 'active'])
            ->where(function ($q) {
                $q->whereNull('renewal_status')
                  ->orWhereNotIn('renewal_status', ['renewed', 'pending']);
            })
            ->whereNotExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('ccbrt_contracts as renewals')
                  ->whereColumn('renewals.parent_contract_id', 'ccbrt_contracts.id');
            })
            ->get();

        foreach ($contracts as $contract) {
            try {
                $contract->is_archived = true;
                $contract->save();
                $archived++;

                Log::info('Contract auto-archived', [
                    'contract_id'     => $contract->id,
                    'contract_title'  => $contract->title,
                    'contract_number' => $contract->contract_number,
                    'end_date'        => $contract->end_date,
                    'months_since'    => Carbon::parse($contract->end_date)->diffInMonths(Carbon::now()),
                ]);
            } catch (\Exception $e) {
                $skipped++;
                Log::error('Failed to auto-archive contract', [
                    'contract_id' => $contract->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $this->info("Procurement contracts — Archived: {$archived}, Skipped: {$skipped}.");

        // ── HEC Contracts ──────────────────────────────────────────────────────
        $hecArchived = 0;
        $hecSkipped  = 0;

        $hecContracts = HecContract::where('end_date', '<', $cutoff)
            ->whereNotIn('status', ['archived', 'renewed', 'terminated'])
            ->where(function ($q) {
                $q->whereNull('renewal_status')
                  ->orWhereNotIn('renewal_status', ['renewed', 'pending']);
            })
            ->whereNotExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('hec_contracts as renewals')
                  ->whereColumn('renewals.parent_contract_id', 'hec_contracts.id');
            })
            ->get();

        foreach ($hecContracts as $hec) {
            try {
                $hec->status = 'archived';
                $hec->save();
                $hecArchived++;

                Log::info('HEC contract auto-archived', [
                    'id'              => $hec->id,
                    'contract_number' => $hec->contract_number,
                    'end_date'        => $hec->end_date,
                    'months_since'    => Carbon::parse($hec->end_date)->diffInMonths(Carbon::now()),
                ]);
            } catch (\Exception $e) {
                $hecSkipped++;
                Log::error('Failed to auto-archive HEC contract', [
                    'id'    => $hec->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("HEC contracts — Archived: {$hecArchived}, Skipped: {$hecSkipped}.");
        Log::info("contracts:auto-archive completed", [
            'procurement_archived' => $archived,
            'procurement_skipped'  => $skipped,
            'hec_archived'         => $hecArchived,
            'hec_skipped'          => $hecSkipped,
            'cutoff'               => $cutoff->toDateString(),
        ]);

        return self::SUCCESS;
    }
}
