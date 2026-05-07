<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestApprovalNotification extends Notification
{
    use Queueable;
    protected $request;
    protected $role;

     /**
     * Create a new notification instance.
     *
     */
    public function __construct($request, $role)
    {
        $this->request = $request;
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail','database'];
    }

    /**
     * Get the mail representation of the notification.
     * *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    
    {
        return (new MailMessage)
        ->line('A new request requires your approval.')
        ->action('View Request', url('/requests/'.$this->request->id))
        ->line('Thank you for using our application!');
    }
/**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'request_id' => $this->request->id,
            'role' => $this->role,
            'message' => 'A new request requires your approval.'
        ];
    }
}
