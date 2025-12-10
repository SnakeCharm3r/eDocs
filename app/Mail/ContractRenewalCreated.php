<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use App\Models\ContractRenewal;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractRenewalCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $contractRenewal;

    public function __construct(ContractRenewal $contractRenewal)
    {
        $this->contractRenewal = $contractRenewal;
    }

    public function build()
    {
        return $this->subject('New Contract Renewal Created')
                    ->view('emails.contract_renewal_created')
                    ->with([
                        'contractRenewal' => $this->contractRenewal,
                    ]);
    }
}
