<?php

namespace App\Mail;

use App\Models\LocumRequest;
use App\Models\Workflow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class LocumRejectionNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public LocumRequest $locumRequest,
        public Workflow $workflow,
        public User $submitter,         // the requester (recipient)
        public string $rejectionReason, // why rejected
        public ?User $rejectedBy = null // approver who rejected
    ) {
        $this->onQueue('mail'); // optional
    }

    public function build()
    {
        return $this->subject('Your Locum Claim Was Rejected')
            ->view('emails.locum-rejection')
            ->with([
                'locumRequest'    => $this->locumRequest,
                'workflow'        => $this->workflow,
                'submitter'       => $this->submitter,
                'rejectionReason' => $this->rejectionReason,
                'rejectedBy'      => $this->rejectedBy,
                'detailsUrl'      => route('locum-requests.show', $this->locumRequest->id), // Adjust
            ]);
    }
}
