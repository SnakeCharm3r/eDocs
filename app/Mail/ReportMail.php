<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractsReport extends Mailable
{
    use Queueable, SerializesModels;

    public $contracts;

    /**
     * Create a new message instance.
     *
     * @param \Illuminate\Support\Collection $contracts
     */
    public function __construct($contracts)
    {
        $this->contracts = $contracts;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Expired Contracts Notification')
                    ->view('emails.ContractsReport')
                    ->with([
                        'contracts' => $this->contracts,
                    ]);
    }
}
