<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use RealRashid\SweetAlert\Facades\Alert;

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

        return view('setting.deadlines', compact('locumDeadline', 'oncallDeadline'));
    }

    /**
     * Update deadline settings
     */
    public function updateDeadlineSettings(Request $request)
    {
        $request->validate([
            'locum_submission_deadline' => 'required|integer|min:1|max:28',
            'oncall_submission_deadline' => 'required|integer|min:1|max:28',
        ]);

        try {
            $this->setSetting('locum_submission_deadline', $request->locum_submission_deadline);
            $this->setSetting('oncall_submission_deadline', $request->oncall_submission_deadline);

            // Clear cache
            Cache::forget('locum_submission_deadline');
            Cache::forget('oncall_submission_deadline');

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
}
