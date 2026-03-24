<?php

namespace App\Mail;

use App\Models\Sop;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SopNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Sop $sop,
        public User $recipient,
        public bool $isNew = true
    ) {
        $this->onQueue('mail');
    }

    public function build()
    {
        $subject = $this->isNew
            ? 'New SOP: ' . $this->sop->title
            : 'SOP Updated: ' . $this->sop->title;

        return $this->subject($subject)
            ->view('emails.sop-notification')
            ->with([
                'sop' => $this->sop,
                'recipient' => $this->recipient,
                'isNew' => $this->isNew,
                'viewUrl' => route('sops.index'),
            ]);
    }
}
