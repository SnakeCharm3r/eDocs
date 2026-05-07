<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('contracts:notify-expired')
            ->weeklyOn(1, '08:00') // Monday
            ->weeklyOn(4, '08:00'); // Thursday

        // Send near-expiry notifications daily at 8 AM
        $schedule->command('contracts:notify-near-expiry --days=90')
            ->dailyAt('08:00')
            ->timezone('Africa/Dar_es_Salaam');

        // Clean up old failed login attempts (older than 30 days) daily at 2 AM
        $schedule->command('login-attempts:cleanup --days=30')
            ->dailyAt('02:00')
            ->timezone('Africa/Dar_es_Salaam');

        // HEC contracts: auto-expire + near-expiry notifications (daily at 7:30 AM)
        $schedule->command('hec-contracts:sync-and-notify --days=30')
            ->dailyAt('07:30')
            ->timezone('Africa/Dar_es_Salaam');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        \App\Console\Commands\ClearLogs::class,
        \App\Console\Commands\HecContractsNotifications::class,
    ];
}
