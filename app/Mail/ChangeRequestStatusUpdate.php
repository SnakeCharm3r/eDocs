<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChangeRequestStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var ChangeRequest */
    public $changeRequest;

    /** @var string 'approved' | 'rejected' */
    public $status;

    /** @var User|null The approver who took the action */
    public $approver;

    /** @var string|null Rejection reason (if any) */
    public $reason;

    /** @var string|null The approval step (e.g., 'Line Manager', 'HEC Member') */
    public $approvalStep;

    /**
     * @param ChangeRequest $changeRequest
     * @param string       $status     'approved' or 'rejected'
     * @param User|null    $approver
     * @param string|null  $reason
     * @param string|null  $approvalStep
     */
    public function __construct(ChangeRequest $changeRequest, string $status, ?User $approver = null, ?string $reason = null, ?string $approvalStep = null)
    {
        $this->changeRequest = $changeRequest;
        $this->status       = strtolower($status);
        $this->approver     = $approver;
        $this->reason       = $reason;
        $this->approvalStep = $approvalStep;
    }

    public function build()
    {
        $subject = $this->status === 'approved'
            ? 'Change Request Approved' . ($this->approvalStep ? ' by ' . $this->approvalStep : '')
            : 'Change Request Rejected';

        return $this->subject($subject)
            ->view('emails.change-request-status-update')
            ->with([
                'changeRequest' => $this->changeRequest,
                'status'       => $this->status,
                'approver'     => $this->approver,
                'reason'       => $this->reason,
                'approvalStep' => $this->approvalStep,
                'portalUrl'    => route('change_request.show', $this->changeRequest->id),
            ]);
    }
}

