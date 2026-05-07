<?php

namespace App\Notifications;

use App\Models\RecruitmentRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecruitmentRequisitionSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public $requisition;
    public $submittedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(RecruitmentRequisition $requisition, User $submittedBy)
    {
        $this->requisition = $requisition;
        $this->submittedBy = $submittedBy;
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

        return (new MailMessage)
            ->subject('New Recruitment Requisition Requires Your Review')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new recruitment requisition has been submitted and requires your review.')
            ->line('**Requisition Details:**')
            ->line('- Job Title: ' . $this->requisition->job_title)
            ->line('- Department: ' . $this->requisition->department->dept_name)
            ->line('- Submitted by: ' . $this->submittedBy->name)
            ->line('- Status: ' . $this->requisition->getStatusLabel())
            ->action('Review Requisition', $url)
            ->line('Please review and take appropriate action.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'requisition_id' => $this->requisition->id,
            'requisition_title' => $this->requisition->job_title,
            'submitted_by' => $this->submittedBy->name,
            'submitted_by_id' => $this->submittedBy->id,
            'status' => $this->requisition->status,
            'message' => 'A new recruitment requisition requires your review.',
            'url' => route('recruitment-requisitions.show', $this->requisition->id),
        ];
    }
}







