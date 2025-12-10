<?php

namespace App\Notifications;

use App\Models\RecruitmentRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecruitmentRequisitionRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public $requisition;
    public $rejectedBy;
    public $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(RecruitmentRequisition $requisition, User $rejectedBy, ?string $reason = null)
    {
        $this->requisition = $requisition;
        $this->rejectedBy = $rejectedBy;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = route('recruitment-requisitions.show', $this->requisition->id);

        $message = (new MailMessage)
            ->subject('Recruitment Requisition Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Unfortunately, your recruitment requisition has been rejected.')
            ->line('**Requisition Details:**')
            ->line('- Job Title: ' . $this->requisition->job_title)
            ->line('- Department: ' . $this->requisition->department->dept_name)
            ->line('- Rejected by: ' . $this->rejectedBy->name);

        if ($this->reason) {
            $message->line('**Reason:**')
                    ->line($this->reason);
        }

        $message->action('View Requisition', $url)
                ->line('Please review the feedback and take appropriate action.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'requisition_id' => $this->requisition->id,
            'requisition_title' => $this->requisition->job_title,
            'rejected_by' => $this->rejectedBy->name,
            'rejected_by_id' => $this->rejectedBy->id,
            'reason' => $this->reason,
            'message' => 'Your recruitment requisition has been rejected.',
            'url' => route('recruitment-requisitions.show', $this->requisition->id),
        ];
    }
}







