<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use App\Mail\QueueTestMail;

class SettingsController extends Controller
{
    public function emailSettings()
    {
        $mailSettings = [
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'password' => env('MAIL_PASSWORD'),
            'encryption' => config('mail.mailers.smtp.encryption'),
        ];

        return view('setting.index', compact('mailSettings'));
    }


    public function updateMailSettings(Request $request)
    {
        $data = $request->only(['host', 'port', 'username', 'password', 'encryption']);

        $this->setEnv([
            'MAIL_HOST' => $data['host'],
            'MAIL_PORT' => $data['port'],
            'MAIL_USERNAME' => $data['username'],
            'MAIL_PASSWORD' => $data['password'],
            'MAIL_ENCRYPTION' => $data['encryption'],
        ]);

        Artisan::call('config:clear'); // Refresh the config cache
        Artisan::call('config:cache');
        return back()->with('success', 'Mail settings updated successfully.');
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email'
        ]);

        try {
            Mail::raw('This is a test email from your eDoc app.', function ($message) use ($request) {
                $message->to($request->test_email)
                    ->subject('Test Email');
            });

            return back()->with('success', 'Test email sent to ' . $request->test_email);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    /**
     * Queue a test email to verify queue/worker is working.
     */
    public function sendTestQueueEmail(Request $request)
    {
        $request->validate([
            'test_queue_email' => 'required|email',
        ]);

        try {
            Mail::to($request->test_queue_email)->queue(new QueueTestMail($request->test_queue_email));

            return back()->with(
                'success',
                'Test email has been queued. Ensure a queue worker is running (e.g. php artisan queue:work). You should receive the email shortly.'
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to queue test email: ' . $e->getMessage());
        }
    }

    private function setEnv(array $values)
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        foreach ($values as $key => $value) {
            $escapedValue = preg_quote(env($key), '/');
            $envContent = preg_replace(
                "/^{$key}=.*$/m",
                "{$key}=\"{$value}\"",
                $envContent
            );
        }

        File::put($envPath, $envContent);
    }

    /**
     * Display maintenance mode settings page
     */
    public function maintenanceMode()
    {
        $maintenanceMode = $this->getMaintenanceModeStatus();
        return view('setting.maintenance', compact('maintenanceMode'));
    }

    /**
     * Update maintenance mode status
     */
    public function updateMaintenanceMode(Request $request)
    {
        try {
            // Checkbox sends "1" if checked, null if unchecked
            $status = $request->has('maintenance_mode') && $request->input('maintenance_mode') ? '1' : '0';

            // Use whereRaw for the key column since 'key' is a MySQL reserved word
            $exists = DB::table('system_settings')
                ->whereRaw('`key` = ?', ['maintenance_mode'])
                ->exists();

            if ($exists) {
                DB::table('system_settings')
                    ->whereRaw('`key` = ?', ['maintenance_mode'])
                    ->update(['value' => $status, 'updated_at' => now()]);
            } else {
                DB::table('system_settings')->insert([
                    'key' => 'maintenance_mode',
                    'value' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Clear cache
            Cache::forget('maintenance_mode');

            $message = $status === '1'
                ? 'Maintenance mode has been enabled. Only super-admin and hr users can login.'
                : 'Maintenance mode has been disabled. All users can login.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update maintenance mode: ' . $e->getMessage());
        }
    }

    /**
     * Get maintenance mode status
     */
    public static function getMaintenanceModeStatus(): bool
    {
        return Cache::remember('maintenance_mode', 3600, function () {
            $setting = DB::table('system_settings')
                ->whereRaw('`key` = ?', ['maintenance_mode'])
                ->first();

            return $setting && $setting->value === '1';
        });
    }

    /**
     * Display deadline settings page
     */
    public function deadlineSettings()
    {
        $locumDeadline = self::getSetting('locum_submission_deadline', 5);
        $oncallDeadline = self::getSetting('oncall_submission_deadline', 5);
        $nightShiftDeadline = self::getSetting('night_shift_submission_deadline', 5);
        $locumExpiredAgreementUseUntil = self::getSetting('locum_expired_agreement_use_until', '');
        $nightAllowanceAmountPerDay = self::getSetting('night_allowance_amount_per_day', 0);

        return view('setting.deadlines', compact('locumDeadline', 'oncallDeadline', 'nightShiftDeadline', 'locumExpiredAgreementUseUntil', 'nightAllowanceAmountPerDay'));
    }

    /**
     * Update deadline settings
     */
    public function updateDeadlineSettings(Request $request)
    {
        $request->validate([
            'locum_submission_deadline'       => 'required|integer|min:1|max:28',
            'oncall_submission_deadline'      => 'required|integer|min:1|max:28',
            'night_shift_submission_deadline' => 'required|integer|min:1|max:28',
            'locum_expired_agreement_use_until' => 'nullable|date',
            'night_allowance_amount_per_day'    => 'required|numeric|min:0',
        ]);

        try {
            $this->setSetting('locum_submission_deadline', $request->locum_submission_deadline);
            $this->setSetting('oncall_submission_deadline', $request->oncall_submission_deadline);
            $this->setSetting('night_shift_submission_deadline', $request->night_shift_submission_deadline);
            $this->setSetting('locum_expired_agreement_use_until', $request->filled('locum_expired_agreement_use_until')
                ? $request->locum_expired_agreement_use_until
                : '');
            $this->setSetting('night_allowance_amount_per_day', $request->night_allowance_amount_per_day);

            // Clear cache
            Cache::forget('locum_submission_deadline');
            Cache::forget('oncall_submission_deadline');
            Cache::forget('night_shift_submission_deadline');
            Cache::forget('locum_expired_agreement_use_until');
            Cache::forget('night_allowance_amount_per_day');

            return back()->with('success', 'Deadline settings updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update deadline settings: ' . $e->getMessage());
        }
    }

    /**
     * Get a system setting value
     */
    public static function getSetting(string $key, $default = null)
    {
        return Cache::remember($key, 3600, function () use ($key, $default) {
            $setting = DB::table('system_settings')
                ->whereRaw('`key` = ?', [$key])
                ->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a system setting value
     */
    private function setSetting(string $key, $value): void
    {
        $exists = DB::table('system_settings')
            ->whereRaw('`key` = ?', [$key])
            ->exists();

        if ($exists) {
            DB::table('system_settings')
                ->whereRaw('`key` = ?', [$key])
                ->update(['value' => $value, 'updated_at' => now()]);
        } else {
            DB::table('system_settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Display cron/queue job monitor (pending jobs, failed jobs, scheduled tasks).
     */
    public function jobMonitor()
    {
        $queueDriver = config('queue.default');
        $pendingCount = 0;
        if ($queueDriver === 'database' && Schema::hasTable('jobs')) {
            $pendingCount = DB::table('jobs')->count();
        }

        $failedJobs = [];
        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(100)
                ->get()
                ->map(function ($job) {
                    $displayName = $job->queue;
                    try {
                        $payload = json_decode($job->payload, true);
                        if (isset($payload['displayName'])) {
                            $displayName = $payload['displayName'];
                        }
                    } catch (\Throwable $e) {
                        // keep queue as display
                    }
                    return (object) [
                        'id' => $job->id,
                        'uuid' => $job->uuid,
                        'queue' => $job->queue,
                        'connection' => $job->connection,
                        'display_name' => $displayName,
                        'failed_at' => $job->failed_at,
                    ];
                });
        }

        $scheduleListOutput = '';
        try {
            Artisan::call('schedule:list');
            $scheduleListOutput = trim(Artisan::output());
        } catch (\Throwable $e) {
            $scheduleListOutput = 'Unable to list schedule: ' . $e->getMessage();
        }

        return view('setting.jobs', compact('queueDriver', 'pendingCount', 'failedJobs', 'scheduleListOutput'));
    }

    /**
     * Retry a single failed job by UUID.
     */
    public function retryFailedJob(string $uuid)
    {
        try {
            Artisan::call('queue:retry', ['id' => $uuid]);
            $output = trim(Artisan::output());
            return back()->with('success', $output ?: 'Job pushed back onto the queue.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to retry job: ' . $e->getMessage());
        }
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAllFailedJobs(Request $request)
    {
        try {
            Artisan::call('queue:retry', ['id' => ['all']]);
            $output = trim(Artisan::output());
            return back()->with('success', $output ?: 'All failed jobs have been pushed back onto the queue.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to retry jobs: ' . $e->getMessage());
        }
    }
}
