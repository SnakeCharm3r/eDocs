<?php

namespace App\Mail;

use App\Models\DepartmentPolicy;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepartmentPolicyNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public DepartmentPolicy $policy,
        public User $recipient,
        public bool $isNew = true
    ) {
        $this->onQueue('mail');
    }

    public function build()
    {
        $subject = $this->isNew
            ? 'New Department Policy: ' . $this->policy->title
            : 'Department Policy Updated: ' . $this->policy->title;

        return $this->subject($subject)
            ->view('emails.department-policy-notification')
            ->with([
                'policy' => $this->policy,
                'recipient' => $this->recipient,
                'isNew' => $this->isNew,
                'viewUrl' => route('department-policies.index'),
            ]);
    }
}
