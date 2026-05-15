<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffContractExpiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public $staffList;
    public $milestone;
    public $milestoneLabel;

    public function __construct($staffList, string $milestone)
    {
        $this->staffList = $staffList;
        $this->milestone = $milestone;
        $this->milestoneLabel = match ($milestone) {
            '3_months' => '3 Months',
            '2_months' => '2 Months',
            '1_month'  => '1 Month',
            '1_week'   => '1 Week',
            'day_of'   => 'Today',
            default    => $milestone,
        };
    }

    public function build()
    {
        $subject = $this->milestone === 'day_of'
            ? 'URGENT: Staff Contract(s) Expiring Today'
            : "Staff Contract(s) Expiring in {$this->milestoneLabel}";

        return $this->subject($subject)
            ->view('emails.staff_contract_expiry');
    }
}
