<?php

namespace App\Mail;

use App\Models\ClearanceForm;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClearanceCompletedCosRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClearanceForm $clearance,
        public User $staff,
        public User $notifiedBy,
        public string $createUrl,
    ) {
        $this->onQueue('mail');
    }

    public function build()
    {
        $staffName = trim(($this->staff->fname ?? '') . ' ' . ($this->staff->lname ?? ''));

        return $this->subject('Action Required: Create Certificate of Service for ' . $staffName)
            ->view('emails.clearance-completed-cos-request')
            ->with([
                'clearance'   => $this->clearance,
                'staff'       => $this->staff,
                'notifiedBy'  => $this->notifiedBy,
                'createUrl'   => $this->createUrl,
            ]);
    }
}
