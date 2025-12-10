<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Workflow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChangeRequestApprovalRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChangeRequest $changeRequest,
        public Workflow $workflow,
        public User $submitter,
        public ?User $approver = null
    ) {
        // Using default queue via ShouldQueue
    }

    public function build()
    {
        return $this->subject('Change Request Awaiting Your Approval')
            ->view('emails.change-request-approval')
            ->with([
                'changeRequest' => $this->changeRequest,
                'workflow'      => $this->workflow,
                'submitter'     => $this->submitter,
                'approver'      => $this->approver,
                'approvalUrl'   => route('change_request.show', $this->changeRequest->id),
            ]);
    }
}

