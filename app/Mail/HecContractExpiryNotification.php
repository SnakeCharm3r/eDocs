<?php

namespace App\Mail;

use App\Models\HecContract;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class HecContractExpiryNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const TYPE_NEAR_EXPIRY = 'near_expiry';
    public const TYPE_EXPIRED = 'expired';

    public ?User $recipient;

    public function __construct(
        public HecContract $contract,
        public string $recipientEmail,
        public string $type = self::TYPE_NEAR_EXPIRY,
        ?User $recipient = null
    ) {
        // Resolve recipient user: use passed user, or contract owner, or look up by email
        $this->recipient = $recipient
            ?? $contract->contractOwner
            ?? User::where('email', $recipientEmail)->first();
    }

    public function build()
    {
        $subject = $this->type === self::TYPE_EXPIRED
            ? 'HEC Contract Expired (No Action): ' . ($this->contract->contract_number ?? $this->contract->title)
            : 'HEC Contract Nearing Expiry: ' . ($this->contract->contract_number ?? $this->contract->title);

        return $this->to($this->recipientEmail)
            ->subject($subject)
            ->view('emails.hec-contract-expiry-notification')
            ->with([
                'contract'  => $this->contract,
                'type'      => $this->type,
                'recipient' => $this->recipient,
                'viewUrl'   => route('hec-contracts.index'),
            ]);
    }
}
