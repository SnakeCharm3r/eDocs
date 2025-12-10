<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClearanceRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $approver;       
    public $requestDetails;

    // Accept data in the constructor
    public function __construct($approver, $requestDetails)
    {
        $this->approver = $approver;
        $this->requestDetails = $requestDetails;
    }

    public function build()
    {
        return $this->subject('New Clearance Request Notification')
                    ->view('emails.clearance_request'); // your Blade template
    }
}
