<?php

namespace App\Mail;

use App\Models\NightShiftClaim;
use App\Models\Workflow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NightShiftApprovalRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public NightShiftClaim $nightShiftClaim,
        public Workflow $workflow,
        public User $submitter,
        public ?User $approver = null
    ) {
    }

    public function build()
    {
        return $this->subject('Night Allowance Claim Awaiting Your Approval')
            ->view('emails.nightshift-approval')
            ->with([
                'nightShiftClaim' => $this->nightShiftClaim,
                'workflow'        => $this->workflow,
                'submitter'       => $this->submitter,
                'approver'        => $this->approver,
                'approvalUrl'     => route('night-shift.approve.index'),
            ]);
    }
}
