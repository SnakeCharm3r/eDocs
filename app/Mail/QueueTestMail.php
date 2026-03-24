<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QueueTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientEmail;

    public function __construct(string $recipientEmail)
    {
        $this->recipientEmail = $recipientEmail;
    }

    public function build()
    {
        return $this->subject('Queue Test Email - eDoc')
            ->text('emails.queue_test_plain');
    }
}
