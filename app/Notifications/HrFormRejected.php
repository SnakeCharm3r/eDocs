<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HrFormRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public $user;
    public $workflow;
    public $rejectionReason;

    public function __construct($user, $workflow, $rejectionReason)
    {
        $this->user = $user;
        $this->workflow = $workflow;
        $this->rejectionReason = $rejectionReason;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('HR Form')
        ->markdown('emails.hr_form_rejected', [
            'user' => $this->user,
            'workflow' => $this->workflow,
            'rejectionReason' => $this->rejectionReason,
        ]);
}

}