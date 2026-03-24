<?php

namespace App\Mail;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisitionStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Requisition $requisition,
        public User $submitter,
        public string $status, // 'approved' or 'rejected'
        public ?User $approver = null,
        public ?string $reason = null,
        public ?string $approvalStep = null
    ) {
    }

    public function build()
    {
        // Build a more descriptive subject line
        $accessId = $this->requisition->access_id;
        $jobTitle = $this->requisition->new_job_title ?? ($this->requisition->jobTitle->job_title ?? 'Position');
        
        if ($this->status === 'approved') {
            if ($this->approvalStep === 'HR Review') {
                $subject = "✅ Requisition #{$accessId} Fully Approved - {$jobTitle}";
            } else {
                $subject = "✅ Requisition #{$accessId} Approved at {$this->approvalStep} - {$jobTitle}";
            }
        } elseif (in_array($this->status, ['rejected', 'rejected_for_editing'])) {
            $subject = "❌ Requisition #{$accessId} Rejected at {$this->approvalStep} - {$jobTitle}";
        } else {
            // Status update (pending stages)
            $subject = "📋 Requisition #{$accessId} Status Update - {$this->approvalStep}";
        }

        return $this->subject($subject)
            ->view('emails.requisition-status-update')
            ->with([
                'requisition' => $this->requisition,
                'submitter' => $this->submitter,
                'status' => $this->status,
                'approver' => $this->approver,
                'reason' => $this->reason,
                'approvalStep' => $this->approvalStep,
                'viewUrl' => route('requisitions.show', $this->requisition->access_id),
            ]);
    }
}

