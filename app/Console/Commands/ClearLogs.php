<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use File;

class ClearLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Laravel logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, ''); // Clear the log file
            $this->info('Logs have been cleared!');
        } else {
            $this->info('Log file does not exist.');
        }
    }
}
