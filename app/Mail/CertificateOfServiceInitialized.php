<?php

namespace App\Mail;

use App\Models\CertificateOfService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CertificateOfServiceInitialized extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public CertificateOfService $certificate,
        public User $initializer,
        public string $viewUrl,
    ) {
        $this->onQueue('mail');
    }

    public function build()
    {
        $staffName = trim(($this->certificate->staff?->fname ?? '') . ' ' . ($this->certificate->staff?->lname ?? ''));

        return $this->subject('Certificate of Service Initialized – ' . $staffName . ' [' . $this->certificate->certificate_number . ']')
            ->view('emails.certificate-of-service-initialized')
            ->with([
                'certificate' => $this->certificate,
                'initializer' => $this->initializer,
                'viewUrl'     => $this->viewUrl,
            ]);
    }
}
