<?php

namespace App\Mail;

use App\Models\ClearanceForm;
use App\Models\Clearance_work_flow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClearanceFormStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClearanceForm $clearanceForm,
        public Clearance_work_flow $workflow,
        public User $submitter,
        public string $status,
        public ?string $message = null,
        public ?User $approver = null
    ) {
        // Using default queue via ShouldQueue
    }

    public function build()
    {
        $subject = 'Employee Clearance Form Status Update';
        if ($this->status === 'approved') {
            $subject = 'Employee Clearance Form Approved';
        } elseif ($this->status === 'rejected') {
            $subject = 'Employee Clearance Form Rejected';
        }

        return $this->subject($subject)
            ->view('emails.clearance-status-update')
            ->with([
                'clearanceForm' => $this->clearanceForm,
                'workflow'      => $this->workflow,
                'submitter'     => $this->submitter,
                'status'        => $this->status,
                'message'       => $this->message,
                'approver'      => $this->approver,
                'viewUrl'       => route('myrequest.index'),
            ]);
    }
}

