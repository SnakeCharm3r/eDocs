<?php

namespace App\Mail;

use App\Models\OnCallRequest;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnCallRejectionNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public OnCallRequest $onCallRequest,
        public Workflow $workflow,
        public User $submitter,
        public string $rejectionReason,
        public ?User $rejectedBy = null,
    ) {}

    public function build()
    {
        return $this->subject('Your On-Call Claim Has Been Rejected')
            ->view('emails.oncall-rejection')
            ->with([
                'onCallRequest'  => $this->onCallRequest,
                'workflow'       => $this->workflow,
                'submitter'      => $this->submitter,
                'rejectionReason'=> $this->rejectionReason,
                'rejectedBy'     => $this->rejectedBy,
                'viewUrl'        => route('oncall_requests.show', $this->onCallRequest->id),
            ]);
    }
}
