<?php

namespace App\Mail;

use App\Models\NightShiftClaim;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NightShiftStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var NightShiftClaim */
    public $nightShiftClaim;

    /** @var string 'approved' | 'rejected' */
    public $status;

    /** @var User|null The approver who took the action */
    public $approver;

    /** @var string|null Rejection reason (if any) */
    public $reason;

    /** @var string|null The approval step (e.g., 'HR Approval', 'Line Manager Approval') */
    public $approvalStep;

    public function __construct(NightShiftClaim $nightShiftClaim, string $status, ?User $approver = null, ?string $reason = null, ?string $approvalStep = null)
    {
        $this->nightShiftClaim = $nightShiftClaim;
        $this->status          = strtolower($status);
        $this->approver        = $approver;
        $this->reason          = $reason;
        $this->approvalStep    = $approvalStep;
    }

    public function build()
    {
        $subject = $this->status === 'approved'
            ? 'Night Allowance Claim Approved' . ($this->approvalStep ? ' by ' . $this->approvalStep : '')
            : 'Night Allowance Claim Rejected';

        return $this->subject($subject)
            ->view('emails.nightshift-status-update')
            ->with([
                'nightShiftClaim' => $this->nightShiftClaim,
                'status'          => $this->status,
                'approver'        => $this->approver,
                'reason'          => $this->reason,
                'approvalStep'    => $this->approvalStep,
                'portalUrl'       => route('night-shift.index'),
            ]);
    }
}
