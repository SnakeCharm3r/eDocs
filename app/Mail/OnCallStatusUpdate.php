<?php

namespace App\Mail;

use App\Models\OnCallRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnCallStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var OnCallRequest */
    public $onCallRequest;

    /** @var string 'approved' | 'rejected' */
    public $status;

    /** @var User|null The approver who took the action */
    public $approver;

    /** @var string|null Rejection reason (if any) */
    public $reason;

    /** @var string|null The approval step (e.g., 'HR Approval', 'Line Manager Approval') */
    public $approvalStep;

    /**
     * @param OnCallRequest $onCallRequest
     * @param string       $status     'approved' or 'rejected'
     * @param User|null    $approver
     * @param string|null  $reason
     * @param string|null  $approvalStep
     */
    public function __construct(OnCallRequest $onCallRequest, string $status, ?User $approver = null, ?string $reason = null, ?string $approvalStep = null)
    {
        $this->onCallRequest = $onCallRequest;
        $this->status       = strtolower($status);
        $this->approver     = $approver;
        $this->reason       = $reason;
        $this->approvalStep = $approvalStep;
    }

    public function build()
    {
        $subject = $this->status === 'approved'
            ? 'On-Call Claim Approved' . ($this->approvalStep ? ' by ' . $this->approvalStep : '')
            : 'On-Call Claim Rejected';

        return $this->subject($subject)
            ->view('emails.oncall-status-update')
            ->with([
                'onCallRequest' => $this->onCallRequest,
                'status'       => $this->status,
                'approver'     => $this->approver,
                'reason'       => $this->reason,
                'approvalStep' => $this->approvalStep,
                // where the requester can view their claim
                'portalUrl'    => route('oncall_requests.index'),
            ]);
    }
}

















