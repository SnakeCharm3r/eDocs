<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FailedLoginAttempt;
use Carbon\Carbon;

class CleanupOldFailedLoginAttempts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'login-attempts:cleanup {--days=30 : Number of days to keep (default: 30)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete failed login attempts older than specified days (default: 30 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up failed login attempts older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        $deletedCount = FailedLoginAttempt::where('attempted_at', '<', $cutoffDate)->delete();

        $this->info("Successfully deleted {$deletedCount} old failed login attempt(s).");

        return Command::SUCCESS;
    }
}
