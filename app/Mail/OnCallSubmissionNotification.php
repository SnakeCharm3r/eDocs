<?php

namespace App\Mail;

use App\Models\OnCallRequest;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnCallSubmissionNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public OnCallRequest $onCallRequest,
        public Workflow $workflow,
        public User $submitter,
    ) {}

    public function build()
    {
        return $this->subject('Your On-Call Claim Has Been Submitted')
            ->view('emails.oncall-submission')
            ->with([
                'onCallRequest' => $this->onCallRequest,
                'workflow'      => $this->workflow,
                'submitter'     => $this->submitter,
                'viewUrl'       => route('oncall_requests.show', $this->onCallRequest->id),
            ]);
    }
}
