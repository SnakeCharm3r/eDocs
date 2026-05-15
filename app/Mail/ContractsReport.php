<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use App\Models\CcbrtContract;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ContractsReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $contracts;
    public $customMessage;

    /** Optional: when set (e.g. test email), log recipient for debugging */
    public $logRecipient;

    /**
     * Create a new message instance.
     *
     * @param \Illuminate\Support\Collection|array $contracts
     * @param string|null $customMessage
     * @param string|null $logRecipient Optional email to log when building (for test emails)
     */
    public function __construct($contracts, $customMessage = null, $logRecipient = null)
    {
        $this->contracts = $contracts;
        $this->customMessage = $customMessage ?? 'Contract(s) have expired and require attention.';
        $this->logRecipient = $logRecipient;
        // Uses default queue so php artisan queue:work processes these (same as Organization Policy)
    }

    /**
     * Build the message.
     */
    public function build()
    {
        if ($this->logRecipient) {
            Log::info('ContractsReport: building email for recipient', ['email' => $this->logRecipient]);
        }

        // Use a subject that avoids spam triggers ("Expired", "Notification")
        return $this->subject('Contract renewal reminder – ' . config('app.name'))
                    ->markdown('emails.ContractsReport')
                    ->with([
                        'contracts' => $this->contracts,
                        'customMessage' => $this->customMessage,
                    ]);
    }
}
