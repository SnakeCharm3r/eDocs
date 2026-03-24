<?php

namespace App\Console\Commands;

use App\Models\Requisition;
use App\Mail\RequisitionExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifyExpiringRequisitions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requisitions:notify-expiring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users about requisitions expiring in 2 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting expiring requisitions notification process...');

        // Find requisitions that are rejected_for_editing and will expire in 2 days
        $expiringRequisitions = Requisition::where('status', 'rejected_for_editing')
            ->where('can_edit_until', '>', now())
            ->where('can_edit_until', '<=', now()->addDays(2))
            ->where('can_edit_until', '>', now())
            ->get();

        $notificationsSent = 0;

        foreach ($expiringRequisitions as $requisition) {
            try {
                // Send notification to the initiator
                Mail::to($requisition->user->email)->send(new RequisitionExpiringNotification($requisition));
                
                $this->info("Notification sent to {$requisition->user->fname} {$requisition->user->lname} for requisition {$requisition->access_id}");
                
                // Log the notification
                Log::info('Expiring requisition notification sent', [
                    'requisition_id' => $requisition->id,
                    'access_id' => $requisition->access_id,
                    'user_id' => $requisition->user_id,
                    'user_email' => $requisition->user->email,
                    'expires_at' => $requisition->can_edit_until,
                ]);

                $notificationsSent++;
            } catch (\Exception $e) {
                $this->error("Failed to send notification for requisition {$requisition->access_id}: " . $e->getMessage());
                
                Log::error('Failed to send expiring requisition notification', [
                    'requisition_id' => $requisition->id,
                    'access_id' => $requisition->access_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Process completed. Sent {$notificationsSent} expiring requisition notifications.");
        
        // Log summary
        Log::info('Expiring requisitions notification process completed', [
            'total_notifications_sent' => $notificationsSent,
            'processed_at' => now(),
        ]);

        return 0;
    }
}
