<?php

namespace App\Mail;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisitionApprovalRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Requisition $requisition,
        public User $approver,
        public string $stepName,
        public ?User $forwardedBy = null
    ) {
    }

    public function build()
    {
        // Build a more descriptive subject line
        $accessId = $this->requisition->access_id;
        $jobTitle = $this->requisition->new_job_title ?? ($this->requisition->jobTitle->job_title ?? 'Position');
        $positionType = match($this->requisition->position_type ?? 'new_position') {
            'new_position' => 'New Position',
            'replacement' => 'Replacement',
            'contract_renewal' => 'Contract Renewal',
            default => 'Position'
        };
        
        $subject = "⚠️ Action Required: Requisition #{$accessId} - {$positionType} ({$jobTitle}) - {$this->stepName}";

        return $this->subject($subject)
            ->view('emails.requisition-approval-request')
            ->with([
                'requisition' => $this->requisition,
                'approver' => $this->approver,
                'stepName' => $this->stepName,
                'forwardedBy' => $this->forwardedBy,
                'viewUrl' => route('requisitions.show', $this->requisition->access_id),
            ]);
    }
}

