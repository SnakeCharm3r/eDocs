<?php

namespace App\Mail;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AnnouncementNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Announcement $announcement,
        public User $recipient,
        public bool $isNew = true
    ) {
        // Uses default queue so the main queue worker (e.g. Supervisor) processes it
    }

    public function build()
    {
        $subject = $this->isNew
            ? 'New Announcement: ' . $this->announcement->title
            : 'Announcement Updated: ' . $this->announcement->title;

        return $this->subject($subject)
            ->view('emails.announcement-notification')
            ->with([
                'announcement' => $this->announcement,
                'recipient' => $this->recipient,
                'isNew' => $this->isNew,
                'viewUrl' => route('announcements.index'),
            ]);
    }
}
