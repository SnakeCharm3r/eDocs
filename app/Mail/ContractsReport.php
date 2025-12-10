<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use App\Models\CcbrtContract;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class ContractsReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $contracts;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct($contracts, $customMessage = null)
    {
        $this->contracts = $contracts;
        $this->customMessage = $customMessage ?? 'Contract(s) have expired and require attention.';
        $this->onQueue('mail');
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Expired Contracts Notification')
                    ->markdown('emails.ContractsReport')
                    ->with([
                        'contracts' => $this->contracts,
                        'customMessage' => $this->customMessage,
                    ]);
    }
}
