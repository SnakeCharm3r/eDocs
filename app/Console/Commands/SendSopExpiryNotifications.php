<?php

namespace App\Console\Commands;

use App\Mail\SopExpiryNotification;
use App\Models\Departments;
use App\Models\Sop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSopExpiryNotifications extends Command
{
    protected $signature = 'sops:notify-expiry';

    protected $description = 'Send SOP expiry emails: 30 days before → Line Manager (owner); 7 days before → QA team + Line Manager (queued)';

    public function handle(): int
    {
        $today = Carbon::now()->startOfDay();
        $notificationsSent = 0;
        $notificationsFailed = 0;

        // Active SOPs with expiry date and owner department (for line manager)
        $sops = Sop::with(['ownerDepartment.head', 'division'])
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->get();

        foreach ($sops as $sop) {
            $daysUntilExpiry = (int) $today->diffInDays(Carbon::parse($sop->expiry_date)->startOfDay(), false);

            // Already expired: skip (or add separate "expired" notice if needed)
            if ($daysUntilExpiry < 0) {
                continue;
            }

            // Owner department's line manager (owner of this SOP)
            $ownerDept = $sop->ownerDepartment;
            $lineManagers = collect();
            if ($ownerDept) {
                $head = $ownerDept->head;
                if ($head && $head->email) {
                    $lineManagers->push($head);
                }
                if ($lineManagers->isEmpty()) {
                    $fallback = User::role('line-manager')
                        ->where('deptId', $ownerDept->id)
                        ->whereNotNull('email')
                        ->where('email', '!=', '')
                        ->get();
                    $lineManagers = $lineManagers->merge($fallback);
                }
            }

            // ---- 30 days before expiry: email to Line Manager (owner) only ----
            if ($daysUntilExpiry >= 28 && $daysUntilExpiry <= 30) {
                foreach ($lineManagers->unique('id') as $lm) {
                    try {
                        Mail::to($lm->email)->queue(new SopExpiryNotification(
                            $sop,
                            $daysUntilExpiry,
                            $lm,
                            '30day'
                        ));
                        $this->info("30-day notice queued to Line Manager: {$lm->email} for SOP: {$sop->title}");
                        $notificationsSent++;
                    } catch (\Throwable $e) {
                        Log::error('SOP 30-day expiry notification failed', [
                            'sop_id' => $sop->id,
                            'email' => $lm->email,
                            'error' => $e->getMessage(),
                        ]);
                        $this->warn("Failed to queue 30-day notice to {$lm->email}: " . $e->getMessage());
                        $notificationsFailed++;
                    }
                }
            }

            // ---- 7 days (or less) before expiry: email to Quality Assurance + Line Manager ----
            if ($daysUntilExpiry >= 1 && $daysUntilExpiry <= 7) {
                $qaUsers = User::role('quality_assurance')
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->get();
                $recipients = $qaUsers->merge($lineManagers)->unique('id')->filter(fn ($u) => ! empty($u->email));

                foreach ($recipients as $recipient) {
                    try {
                        Mail::to($recipient->email)->queue(new SopExpiryNotification(
                            $sop,
                            $daysUntilExpiry,
                            $lineManagers->first(),
                            '7day'
                        ));
                        $this->info("7-day notice queued to {$recipient->email} for SOP: {$sop->title}");
                        $notificationsSent++;
                    } catch (\Throwable $e) {
                        Log::error('SOP 7-day expiry notification failed', [
                            'sop_id' => $sop->id,
                            'email' => $recipient->email,
                            'error' => $e->getMessage(),
                        ]);
                        $this->warn("Failed to queue 7-day notice to {$recipient->email}: " . $e->getMessage());
                        $notificationsFailed++;
                    }
                }
            }
        }

        $this->info("SOP expiry notifications finished. Queued: {$notificationsSent}, Failed: {$notificationsFailed}");

        return Command::SUCCESS;
    }
}
