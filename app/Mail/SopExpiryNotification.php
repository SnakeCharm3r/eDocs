<?php

namespace App\Mail;

use App\Models\Sop;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SopExpiryNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Sop $sop;
    public int $daysUntilExpiry;
    public ?User $lineManager;
    /** @var string '30day' | '7day' | 'urgent' */
    public string $noticeType;

    /**
     * Create a new message instance.
     *
     * @param  string  $noticeType  '30day' = one month before (to LM), '7day' = one week before (to QA + LM), 'urgent' = expired or very soon
     */
    public function __construct(Sop $sop, int $daysUntilExpiry, ?User $lineManager = null, string $noticeType = 'urgent')
    {
        $this->sop = $sop;
        $this->daysUntilExpiry = $daysUntilExpiry;
        $this->lineManager = $lineManager;
        $this->noticeType = $noticeType;
    }

    public function build()
    {
        if ($this->noticeType === '30day') {
            $subject = "SOP Expiring in About One Month: {$this->sop->title}";
        } elseif ($this->noticeType === '7day') {
            $subject = "URGENT: SOP Expiring in {$this->daysUntilExpiry} Days - {$this->sop->title}";
        } elseif ($this->daysUntilExpiry <= 0) {
            $subject = "URGENT: SOP Expired - {$this->sop->title}";
        } else {
            $subject = "SOP Expiring Soon: {$this->sop->title}";
        }

        return $this->subject($subject)
            ->view('emails.sop_expiry_notification')
            ->with([
                'sop' => $this->sop,
                'daysUntilExpiry' => $this->daysUntilExpiry,
                'lineManager' => $this->lineManager,
                'noticeType' => $this->noticeType,
            ]);
    }
}
