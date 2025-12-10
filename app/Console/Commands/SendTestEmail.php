<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify mail configuration';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Mail::raw('This is a test email from eDoc.', function ($message) {
            $message->to('cleokajetani@gmail.com')
                    ->subject('eDoc Test Email');
        });

        $this->info('✅ Test email sent!');
    }
}
