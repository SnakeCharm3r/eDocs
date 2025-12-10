<?php

namespace App\Notifications;

use App\Models\RecruitmentRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecruitmentRequisitionApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public $requisition;
    public $approvedBy;
    public $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(RecruitmentRequisition $requisition, User $approvedBy, string $newStatus)
    {
        $this->requisition = $requisition;
        $this->approvedBy = $approvedBy;
        $this->newStatus = $newStatus;
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
        $statusLabel = $this->requisition->getStatusLabel();

        return (new MailMessage)
            ->subject('Recruitment Requisition Status Update')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('The recruitment requisition has been reviewed and the status has been updated.')
            ->line('**Requisition Details:**')
            ->line('- Job Title: ' . $this->requisition->job_title)
            ->line('- Department: ' . $this->requisition->department->dept_name)
            ->line('- Reviewed by: ' . $this->approvedBy->name)
            ->line('- New Status: ' . $statusLabel)
            ->action('View Requisition', $url)
            ->line('Thank you for your attention.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'requisition_id' => $this->requisition->id,
            'requisition_title' => $this->requisition->job_title,
            'approved_by' => $this->approvedBy->name,
            'approved_by_id' => $this->approvedBy->id,
            'new_status' => $this->newStatus,
            'status_label' => $this->requisition->getStatusLabel(),
            'message' => 'Recruitment requisition status has been updated to: ' . $this->requisition->getStatusLabel(),
            'url' => route('recruitment-requisitions.show', $this->requisition->id),
        ];
    }
}







