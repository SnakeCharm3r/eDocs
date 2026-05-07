<?php

namespace App\Mail;

use App\Models\OnCallRequest;
use App\Models\Workflow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnCallApprovalRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public OnCallRequest $onCallRequest,
        public Workflow $workflow,
        public User $submitter,
        public ?User $approver = null
    ) {
        // Using default queue via ShouldQueue
    }

    public function build()
    {
        return $this->subject('On-Call Claim Awaiting Your Approval')
            ->view('emails.oncall-approval')
            ->with([
                'onCallRequest' => $this->onCallRequest,
                'workflow'      => $this->workflow,
                'submitter'     => $this->submitter,
                'approver'      => $this->approver,
                'approvalUrl'   => route('oncall_requests.index'),
            ]);
    }
}
