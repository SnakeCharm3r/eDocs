<?php

namespace App\Notifications;

use App\Models\Requisition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequisitionSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Requisition $requisition;
    protected string $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(Requisition $requisition, string $action = 'submitted')
    {
        $this->requisition = $requisition;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getSubject();
        $message = $this->getMessage();

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->fname . '!')
            ->line($message)
            ->line('**Reference:** ' . $this->requisition->access_id)
            ->line('**Position Type:** ' . $this->requisition->getPositionTypeLabel())
            ->line('**Department:** ' . $this->requisition->dept_name)
            ->line('**Initiated By:** ' . $this->requisition->initiator_name)
            ->action('View Requisition', route('requisitions.show', $this->requisition->access_id))
            ->line('Thank you for using CCBRT E-Docs System.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'requisition_id' => $this->requisition->id,
            'access_id' => $this->requisition->access_id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'initiated_by' => $this->requisition->initiator_name,
            'position_type' => $this->requisition->position_type,
        ];
    }

    private function getSubject(): string
    {
        return match ($this->action) {
            'submitted' => 'New Recruitment Requisition - ' . $this->requisition->access_id,
            'pending_payroll' => 'Requisition Awaiting Your Review - ' . $this->requisition->access_id,
            'pending_hec' => 'Requisition Awaiting HEC Review - ' . $this->requisition->access_id,
            'pending_cfo' => 'Requisition Awaiting CFO Approval - ' . $this->requisition->access_id,
            'pending_ceo' => 'Requisition Awaiting CEO Approval - ' . $this->requisition->access_id,
            'pending_hr' => 'Requisition Awaiting HR Approval - ' . $this->requisition->access_id,
            'approved' => 'Requisition Approved - ' . $this->requisition->access_id,
            'rejected' => 'Requisition Rejected - ' . $this->requisition->access_id,
            default => 'Recruitment Requisition Update - ' . $this->requisition->access_id,
        };
    }

    private function getMessage(): string
    {
        return match ($this->action) {
            'submitted' => 'A new recruitment requisition has been submitted and requires your attention.',
            'pending_payroll' => 'A recruitment requisition is awaiting your review as Payroll Accountant.',
            'pending_hec' => 'A recruitment requisition is awaiting your review as HEC Member.',
            'pending_cfo' => 'A recruitment requisition requires your approval as CFO.',
            'pending_ceo' => 'A recruitment requisition requires your approval as CEO.',
            'pending_hr' => 'A recruitment requisition is awaiting your final approval as HR.',
            'approved' => 'Your recruitment requisition has been approved.',
            'rejected' => 'Your recruitment requisition has been rejected.',
            default => 'There is an update on a recruitment requisition.',
        };
    }
}
