<?php

namespace App\Mail;

use App\Models\LocumAgreement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LocumRateChangeNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $agreement;
    public $user;
    public $oldEducationLevel;
    public $newEducationLevel;
    public $oldLocumRate;
    public $newLocumRate;
    public $changedBy;
    public $isLineManager;

    /**
     * Create a new message instance.
     *
     * @param LocumAgreement $agreement
     * @param User $user The staff member whose rate was changed
     * @param string $oldEducationLevel
     * @param string $newEducationLevel
     * @param float $oldLocumRate
     * @param float $newLocumRate
     * @param User $changedBy The HR/admin who made the change
     * @param bool $isLineManager Whether this email is for the line manager
     */
    public function __construct(
        LocumAgreement $agreement,
        User $user,
        string $oldEducationLevel,
        string $newEducationLevel,
        float $oldLocumRate,
        float $newLocumRate,
        User $changedBy,
        bool $isLineManager = false
    ) {
        $this->agreement = $agreement;
        $this->user = $user;
        $this->oldEducationLevel = $oldEducationLevel;
        $this->newEducationLevel = $newEducationLevel;
        $this->oldLocumRate = $oldLocumRate;
        $this->newLocumRate = $newLocumRate;
        $this->changedBy = $changedBy;
        $this->isLineManager = $isLineManager;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->isLineManager
            ? 'Locum Rate Changed for Staff Member'
            : 'Your Locum Rate Has Been Changed';

        return $this->subject($subject)
            ->view('emails.locum-rate-change')
            ->with([
                'agreement' => $this->agreement,
                'user' => $this->user,
                'oldEducationLevel' => $this->oldEducationLevel,
                'newEducationLevel' => $this->newEducationLevel,
                'oldLocumRate' => $this->oldLocumRate,
                'newLocumRate' => $this->newLocumRate,
                'changedBy' => $this->changedBy,
                'isLineManager' => $this->isLineManager,
                'viewUrl' => route('locum-agreements.show', $this->agreement->id),
            ]);
    }
}

