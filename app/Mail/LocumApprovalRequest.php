<?php

namespace App\Mail;

use App\Models\LocumRequest;
use App\Models\Workflow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LocumApprovalRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public LocumRequest $locumRequest,
        public Workflow $workflow,
        public User $submitter,
        public ?User $approver = null
    ) {
        // nothing special here since we’re not queuing
    }

    public function build()
    {
        return $this->subject('Locum Claim Awaiting Your Approval')
            ->view('emails.locum-approval')
            ->with([
                'locumRequest' => $this->locumRequest,
                'workflow'     => $this->workflow,
                'submitter'    => $this->submitter,
                'approver'     => $this->approver,
                'approvalUrl'  => route('requestapprove.index'),
            ]);
    }
}
