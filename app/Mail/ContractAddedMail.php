<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class ContractAddedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The contract instance passed from the controller.
     *
     * @var \App\Models\CcbrtContract
     */
    public $contract;

    /**
     * The recipient user (optional)
     */
    public $recipient;

    /**
     * Custom message (optional)
     */
    public $customMessage;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\CcbrtContract $contract
     * @param \App\Models\User|null $recipient
     * @param string|null $customMessage
     */
    public function __construct($contract, $recipient = null, $customMessage = null)
    {
        // Store the contract instance so the view can access it
        $this->contract = $contract;
        $this->recipient = $recipient;
        $this->customMessage = $customMessage;
        $this->onQueue('mail');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New Contract Added')
                    ->view('emails.contract_creation')
                    ->with([
                        'contract' => $this->contract,
                    ]);
    }
}
