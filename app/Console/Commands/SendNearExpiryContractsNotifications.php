<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CcbrtContract;
use App\Models\User;
use App\Models\Departments;
use App\Models\Hec;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Mail\ContractsReport;
use Illuminate\Support\Facades\Log;

class SendNearExpiryContractsNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:notify-near-expiry {--days=90 : Number of days before expiry to start notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for contracts nearing expiry with escalation logic';

    /**
     * Execute the console command.
     * Escalation flow: Line Manager (first) → HEC (if no action after 7 days) → Continue until action taken
     */
    public function handle(): int
    {
        $daysBeforeExpiry = (int) $this->option('days');
        $today = Carbon::now();
        $expiryThreshold = $today->copy()->addDays($daysBeforeExpiry);
        $escalationDays = 7; // Escalate to HEC if Line Manager hasn't acted in 7 days

        // Get contracts nearing expiry that are active
        $nearExpiryContracts = CcbrtContract::with(['department', 'vendor', 'creator'])
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today, $expiryThreshold])
            ->get();

        if ($nearExpiryContracts->isEmpty()) {
            $this->info('No contracts nearing expiry requiring notifications.');
            return Command::SUCCESS;
        }

        $contractsByDepartment = $nearExpiryContracts->groupBy('department_id');
        $notificationsSent = 0;

        foreach ($contractsByDepartment as $departmentId => $contracts) {
            $department = Departments::find($departmentId);
            
            if (!$department) {
                continue;
            }

            // Get Line Manager for the department
            $lineManagers = User::role('line-manager')
                ->where('deptId', $departmentId)
                ->where('status', 'active')
                ->get();

            if ($lineManagers->isEmpty()) {
                $this->warn("No Line Manager found for department ID: {$departmentId}");
                continue;
            }

            foreach ($contracts as $contract) {
                $lastNotification = $contract->last_notification_sent_at;
                $escalationLevel = $contract->notification_escalation_level ?? 'none';
                
                // Determine if we should send notification and to whom
                $shouldNotify = false;
                $notifyTo = 'line_manager'; // Default to Line Manager
                
                if (!$lastNotification) {
                    // First notification - send to Line Manager
                    $shouldNotify = true;
                    $notifyTo = 'line_manager';
                } else {
                    $daysSinceLastNotification = $today->diffInDays(Carbon::parse($lastNotification));
                    
                    if ($escalationLevel === 'none' || $escalationLevel === 'line_manager') {
                        // If Line Manager hasn't acted in escalationDays, escalate to HEC
                        if ($daysSinceLastNotification >= $escalationDays) {
                            $shouldNotify = true;
                            $notifyTo = 'hec';
                        }
                    } elseif ($escalationLevel === 'hec') {
                        // If HEC hasn't acted, continue notifying HEC periodically
                        if ($daysSinceLastNotification >= $escalationDays) {
                            $shouldNotify = true;
                            $notifyTo = 'hec';
                        }
                    }
                }

                if (!$shouldNotify) {
                    continue;
                }

                // Send notification to Line Manager
                if ($notifyTo === 'line_manager') {
                    foreach ($lineManagers as $lineManager) {
                        try {
                            $daysUntilExpiry = $today->diffInDays(Carbon::parse($contract->end_date));
                            $message = "Contract '{$contract->title}' is expiring in {$daysUntilExpiry} days. Please review and take action (Renew, Terminate, or Hold).";
                            
                            Mail::to($lineManager->email)->queue(new ContractsReport(
                                collect([$contract]), 
                                $message
                            ));
                            
                            // Update contract notification tracking
                            $contract->last_notification_sent_at = $today;
                            $contract->notification_escalation_level = 'line_manager';
                            $contract->save();
                            
                            $this->info("Near-expiry notification sent to Line Manager: {$lineManager->email} for contract: {$contract->title}");
                            $notificationsSent++;
                        } catch (\Exception $e) {
                            $this->error("Failed to send notification to Line Manager {$lineManager->email}: " . $e->getMessage());
                            Log::error('Failed to send near-expiry notification to Line Manager', [
                                'contract_id' => $contract->id,
                                'line_manager_email' => $lineManager->email,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                // Send notification to HEC (escalation)
                if ($notifyTo === 'hec' && $department->hec_id) {
                    $hec = Hec::find($department->hec_id);
                    if ($hec) {
                        $hecLevelName = strtoupper(trim($hec->hec_level_name));
                        $roleMap = ['COO' => 'coo', 'CFO' => 'cfo', 'CMS' => 'cms', 'CRHDO' => 'crhdo'];
                        $roleSlug = $roleMap[$hecLevelName] ?? 'cms';
                        
                        $hecMembers = User::role($roleSlug)->where('status', 'active')->get();
                        
                        foreach ($hecMembers as $hecMember) {
                            try {
                                $daysUntilExpiry = $today->diffInDays(Carbon::parse($contract->end_date));
                                $message = "URGENT: Contract '{$contract->title}' is expiring in {$daysUntilExpiry} days. Line Manager has not taken action. Please review and ensure appropriate action is taken (Renew, Terminate, or Hold).";
                                
                                Mail::to($hecMember->email)->queue(new ContractsReport(
                                    collect([$contract]), 
                                    $message
                                ));
                                
                                // Update contract notification tracking
                                $contract->last_notification_sent_at = $today;
                                $contract->notification_escalation_level = 'hec';
                                $contract->save();
                                
                                $this->info("Near-expiry escalation notification sent to HEC Member: {$hecMember->email} for contract: {$contract->title}");
                                $notificationsSent++;
                            } catch (\Exception $e) {
                                $this->error("Failed to send notification to HEC Member {$hecMember->email}: " . $e->getMessage());
                                Log::error('Failed to send near-expiry escalation notification to HEC', [
                                    'contract_id' => $contract->id,
                                    'hec_member_email' => $hecMember->email,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $this->info("Near-expiry contract notifications sent successfully. Total notifications: {$notificationsSent}");

        return Command::SUCCESS;
    }
}

