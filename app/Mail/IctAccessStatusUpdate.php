<?php

namespace App\Mail;

use App\Models\IctAccessResource;
use App\Models\Workflow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class IctAccessStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public IctAccessResource $ictAccessResource,
        public Workflow $workflow,
        public User $submitter,
        public string $status, // 'approved' or 'rejected'
        public ?User $approver = null,
        public ?string $reason = null,
        public ?string $approvalStep = null
    ) {
        $this->onQueue('mail');
    }

    public function build()
    {
        $subject = $this->status === 'approved' 
            ? 'Your ICT Access Request Has Been Approved'
            : 'Your ICT Access Request Has Been Rejected';

        return $this->subject($subject)
            ->view('emails.ict-access-status-update')
            ->with([
                'ictAccessResource' => $this->ictAccessResource,
                'workflow'          => $this->workflow,
                'submitter'         => $this->submitter,
                'status'            => $this->status,
                'approver'          => $this->approver,
                'reason'            => $this->reason,
                'approvalStep'      => $this->approvalStep,
                'viewUrl'           => route('form.getform', ['id' => $this->ictAccessResource->id]),
            ]);
    }
}

