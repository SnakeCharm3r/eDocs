<?php

namespace App\Mail;

use App\Models\ClearanceForm;
use App\Models\Clearance_work_flow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClearanceFormApprovalRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClearanceForm $clearanceForm,
        public Clearance_work_flow $workflow,
        public User $submitter,
        public ?User $approver = null,
        public string $stepName = ''
    ) {
        // Using default queue via ShouldQueue
    }

    public function build()
    {
        $subject = 'Employee Clearance Form Awaiting Your Approval';
        if ($this->stepName) {
            $subject = "Employee Clearance Form - {$this->stepName} Approval Required";
        }

        return $this->subject($subject)
            ->view('emails.clearance-approval-request')
            ->with([
                'clearanceForm' => $this->clearanceForm,
                'workflow'      => $this->workflow,
                'submitter'     => $this->submitter,
                'approver'      => $this->approver,
                'stepName'      => $this->stepName,
                'approvalUrl'   => route('requestapprove.index'),
            ]);
    }
}

