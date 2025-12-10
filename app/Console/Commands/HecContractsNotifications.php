<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HecContract;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class HecContractsNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hec-contracts:sync-and-notify {--days=30 : Number of days before expiry to send reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update HEC contracts status automatically and send near-expiry/expired email notifications to owners';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();
        $daysBeforeExpiry = (int) $this->option('days');
        $nearExpiryThreshold = $today->copy()->addDays($daysBeforeExpiry);

        $this->info("Running HEC contracts sync: today={$today->toDateString()}, near-expiry threshold={$nearExpiryThreshold->toDateString()}.");

        // 1) Auto-mark contracts as expired when end_date has passed
        $expiredUpdated = HecContract::whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expired']);

        $this->info("Auto-updated {$expiredUpdated} HEC contract(s) to expired based on end_date.");

        // 2) Find contracts that are near expiry (active or in_progress) and send reminders
        $nearExpiryContracts = HecContract::whereNotNull('end_date')
            ->whereIn('status', ['active', 'in_progress'])
            ->whereBetween('end_date', [$today, $nearExpiryThreshold])
            ->get();

        if ($nearExpiryContracts->isEmpty()) {
            $this->info('No HEC contracts nearing expiry within the configured window.');
            return Command::SUCCESS;
        }

        $sentCount = 0;

        foreach ($nearExpiryContracts as $contract) {
            // Decide recipient: owner_email first, fallback to HEC member email
            $recipientEmail = $contract->owner_email ?: optional($contract->contractOwner)->email;

            if (!$recipientEmail) {
                $this->warn("Skipping contract ID {$contract->id} ({$contract->contract_number}) - no owner email found.");
                continue;
            }

            try {
                $subject = 'HEC Contract Nearing Expiry: ' . ($contract->contract_number ?? $contract->title);
                $daysLeft = Carbon::parse($contract->end_date)->diffInDays($today);

                $body = "Dear Contract Owner,\n\n"
                    . "This is a reminder that the following HEC contract is nearing its end date:\n\n"
                    . "Contract: " . ($contract->contract_number ?? 'N/A') . " - " . ($contract->title ?? '') . "\n"
                    . "Type: " . ($contract->contract_type ?? 'N/A') . "\n"
                    . "End Date: " . ($contract->end_date ? $contract->end_date->format('Y-m-d') : 'N/A') . "\n"
                    . "Days remaining: {$daysLeft}\n\n"
                    . "Please review this contract and take any necessary actions (renewal, termination, etc.).\n\n"
                    . "This email was generated automatically by the HEC Contracts module.";

                Mail::raw($body, function ($message) use ($recipientEmail, $subject) {
                    $message->to($recipientEmail)
                        ->subject($subject);
                });

                $this->info("Sent near-expiry reminder to {$recipientEmail} for contract {$contract->contract_number}.");
                $sentCount++;
            } catch (\Exception $e) {
                $this->error("Failed to send near-expiry reminder for contract ID {$contract->id}: " . $e->getMessage());
            }
        }

        $this->info("Completed HEC contracts notifications. Total reminders sent: {$sentCount}.");

        return Command::SUCCESS;
    }
}
