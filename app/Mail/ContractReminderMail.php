<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contract;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct($contract, $customMessage = null)
    {
        $this->contract = $contract;
        $this->customMessage = $customMessage ?? 'Contract reminder notification.';
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Contract Renewal Reminder')
                    ->markdown('emails.contract_reminder')
                    ->with([
                        'contract' => $this->contract,
                        'customMessage' => $this->customMessage,
                    ]);
    }
}

