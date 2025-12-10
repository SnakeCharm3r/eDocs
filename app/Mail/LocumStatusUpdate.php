<?php

namespace App\Mail;

use App\Models\LocumRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LocumStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var LocumRequest */
    public $locumRequest;

    /** @var string 'approved' | 'rejected' */
    public $status;

    /** @var User|null The approver who took the action */
    public $approver;

    /** @var string|null Rejection reason (if any) */
    public $reason;

    /**
     * @param LocumRequest $locumRequest
     * @param string       $status     'approved' or 'rejected'
     * @param User|null    $approver
     * @param string|null  $reason
     */
    public function __construct(LocumRequest $locumRequest, string $status, ?User $approver = null, ?string $reason = null)
    {
        $this->locumRequest = $locumRequest;
        $this->status       = strtolower($status);
        $this->approver     = $approver;
        $this->reason       = $reason;
    }

    public function build()
    {
        $subject = $this->status === 'approved'
            ? 'Locum Claim Approved'
            : 'Locum Claim Rejected';

        return $this->subject($subject)
            ->view('emails.locum-status-update')
            ->with([
                'locumRequest' => $this->locumRequest,
                'status'       => $this->status,
                'approver'     => $this->approver,
                'reason'       => $this->reason,
                // where the requester can view their claim
                'portalUrl'    => route('locum-requests.index'),
            ]);
    }
}
