<?php

namespace App\Mail;

use App\Models\IctAccessResource;
use App\Models\Workflow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class IctAccessRejectionNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public IctAccessResource $ictAccessResource,
        public Workflow $workflow,
        public User $submitter,
        public string $rejectionReason,
        public ?User $rejectedBy = null
    ) {
        $this->onQueue('mail');
    }

    public function build()
    {
        return $this->subject('Your ICT Access Request Has Been Rejected')
            ->view('emails.ict-access-rejection')
            ->with([
                'ictAccessResource' => $this->ictAccessResource,
                'workflow'          => $this->workflow,
                'submitter'         => $this->submitter,
                'rejectionReason'   => $this->rejectionReason,
                'rejectedBy'        => $this->rejectedBy,
                'viewUrl'           => route('form.getform', ['id' => $this->ictAccessResource->id]),
            ]);
    }
}

