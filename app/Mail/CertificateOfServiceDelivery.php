<?php

namespace App\Mail;

use App\Models\CertificateOfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CertificateOfServiceDelivery extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const DEFAULT_HR_DELIVERY_EMAIL = 'HRTeam@ccbrt.org';

    public function __construct(
        public CertificateOfService $certificate,
        public string $pdfPath,
        public string $recipientEmail,
    ) {
        $this->onQueue('mail');
    }

    public function build()
    {
        $staffName = trim(($this->certificate->staff?->fname ?? '') . ' ' . ($this->certificate->staff?->lname ?? ''));
        $filename  = 'Certificate-of-Service-' . ($this->certificate->staff?->ccbrt_code ?? $this->certificate->certificate_number) . '.pdf';
        $recipientName = strcasecmp($this->recipientEmail, self::DEFAULT_HR_DELIVERY_EMAIL) === 0
            ? 'HR Team'
            : ($staffName !== '' ? $staffName : 'Colleague');

        return $this->subject('Certificate of Service – ' . $staffName)
            ->view('emails.certificate-of-service-delivery')
            ->with([
                'certificate' => $this->certificate,
                'staffName'   => $staffName,
                'recipientName' => $recipientName,
                'recipientEmail' => $this->recipientEmail,
                'viewUrl' => route('certificate-of-service.show', $this->certificate),
            ])
            ->attachFromStorageDisk('local', $this->pdfPath, $filename, [
                'mime' => 'application/pdf',
            ]);
    }
}
