<?php

namespace App\Mail;

use App\Models\LocumRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LocumRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $locumRequest;
    public $recipient;
    public $action;
    public $step;
    public $rejectionReason;

    public function __construct(LocumRequest $locumRequest, User $recipient, string $action, string $step = null, string $rejectionReason = null)
    {
        $this->locumRequest = $locumRequest;
        $this->recipient = $recipient;
        $this->action = $action;
        $this->step = $step;
        $this->rejectionReason = $rejectionReason;
    }

    public function build()
    {
        $subject = '';
        $view = 'emails.locum_request_notification';

        if ($this->action === 'approve') {
            $subject = "Locum Request #{$this->locumRequest->id} Awaiting {$this->step} Approval";
        } elseif ($this->action === 'final_approve') {
            $subject = "Locum Request #{$this->locumRequest->id} Approved";
        } elseif ($this->action === 'reject') {
            $subject = "Locum Request #{$this->locumRequest->id} Rejected";
        }

        return $this->subject($subject)
                    ->view($view)
                    ->with([
                        'locumRequest' => $this->locumRequest,
                        'recipient' => $this->recipient,
                        'action' => $this->action,
                        'step' => $this->step,
                        'rejectionReason' => $this->rejectionReason,
                    ]);
    }
}
