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
        // Uses default queue so php artisan queue:work processes these (same as Organization Policy)
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->resolveSubject();

        return $this->subject($subject)
                    ->view('emails.contract_creation')
                    ->with([
                        'contract'      => $this->contract,
                        'recipient'     => $this->recipient,
                        'customMessage' => $this->customMessage,
                    ]);
    }

    /**
     * Derive a context-aware subject line from the custom message or contract state.
     */
    private function resolveSubject(): string
    {
        $title = $this->contract->title ?? $this->contract->contract_number ?? 'Contract';

        if ($this->customMessage) {
            $msg = strtolower($this->customMessage);

            if (str_contains($msg, 'renewal has been reviewed') || str_contains($msg, 'review and rate')) {
                return 'Contract Review Required: ' . $title;
            }
            if (str_contains($msg, 'renewal has been finalized') || str_contains($msg, 'now active')) {
                return 'Contract Activated: ' . $title;
            }
            if (str_contains($msg, 'renewal has been initiated') || str_contains($msg, 'renewal initiated')) {
                return 'Contract Renewal Initiated: ' . $title;
            }
            if (str_contains($msg, 'fully approved')) {
                return 'Contract Approved: ' . $title;
            }
            if (str_contains($msg, 'requires your') && str_contains($msg, 'review')) {
                return 'Contract Review Required: ' . $title;
            }
        }

        // Fallback: derive from contract state
        $stage = $this->contract->approval_stage ?? '';
        $lifecycle = $this->contract->lifecycle_stage ?? '';

        if ($lifecycle === 'renewal' || ($this->contract->renewal_status ?? '') === 'pending') {
            return 'Contract Renewal: ' . $title;
        }
        if (in_array($stage, ['line_manager', 'hec', 'procurement'])) {
            return 'Contract Action Required: ' . $title;
        }

        return 'Contract Notification: ' . $title;
    }
}
