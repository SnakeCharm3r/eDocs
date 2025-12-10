<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HrFormApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public $user;
    public $workflow;

    // Inject the necessary data for the email
    public function __construct($user, $workflow)
    {
        $this->user = $user;
        $this->workflow = $workflow;
    }

    public function via($notifiable)
    {
        return ['mail']; // Send email notification
    }

    public function toMail($notifiable)
    {
        // // You can customize the email template here, or pass a view
        // return (new MailMessage)
        //     ->subject('HR Form Approval Notification')
        //     ->greeting('Hello ' . $this->user->fname)
        //     ->line('Your HR form has been approved.')
        //     ->line('Form details:')
        //     ->line('Requester: ' . $this->workflow->user->fname)
        //     ->line('Status: ' . $this->workflow->work_flow_status)
        //     ->action('View Request', url('/')) // Put the appropriate URL here
        //     ->line('Thank you for using our system.');

  // Prepare the data to pass to the view
  $data = [
    'user' => $this->user,
    'workflow' => $this->workflow,
    // 'approver' => $this->approver,
    'approvalDate' => \Carbon\Carbon::parse($this->workflow->updated_at)->format('F j, Y'), 
];

// Send the email using the hr_form_approve.blade.php view
return (new MailMessage)
    ->subject('HR Form Approval Notification')
    ->view('emails.hr_form_approve', $data);
            
    }
}
