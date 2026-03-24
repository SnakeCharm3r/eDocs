<?php

namespace App\Mail;

use App\Models\OtherOrganizationPolicy;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class OrganizationPolicyNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public OtherOrganizationPolicy $policy,
        public User $recipient,
        public bool $isNew = true
    ) {
        // Uses default queue so queue:work (e.g. Supervisor) processes these emails
    }

    public function build()
    {
        $subject = $this->isNew
            ? 'New Organization Policy: ' . $this->policy->title
            : 'Organization Policy Updated: ' . $this->policy->title;

        return $this->subject($subject)
            ->view('emails.organization-policy-notification')
            ->with([
                'policy' => $this->policy,
                'recipient' => $this->recipient,
                'viewUrl' => route('policies.index', ['type' => 'other_organization']),
            ]);
    }
}
