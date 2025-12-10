<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Audit;
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view logs');
    }

    public function index(Request $request)
    {
        // Get all users for filter dropdown
        $users = \App\Models\User::orderBy('username')->get();

        // Get cleanup job status
        $cleanupStatus = $this->getCleanupStatus();
        $lastRun = $this->getLastCleanupRun();

        // Filter audits by model, user, date, event
        $audits = \App\Models\Audit::with('user')
            ->when($request->model, fn($query) => $query->where('auditable_type', $request->model))
            ->when($request->user_id, fn($query) => $query->where('user_id', $request->user_id))
            ->when($request->event, fn($query) => $query->where('event', $request->event))
            ->when($request->start_date, fn($query) => $query->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($query) => $query->whereDate('created_at', '<=', $request->end_date))
            ->when($request->search, function($query) use ($request) {
                $query->where(function($q) use ($request) {
                    $q->where('description', 'like', '%' . $request->search . '%')
                      ->orWhere('ip_address', 'like', '%' . $request->search . '%')
                      ->orWhereHas('user', function($userQuery) use ($request) {
                          $userQuery->where('username', 'like', '%' . $request->search . '%')
                                    ->orWhere('email', 'like', '%' . $request->search . '%');
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('audits.index', compact('audits', 'users', 'cleanupStatus', 'lastRun'));
    }
    
    /**
     * Get cleanup job status
     */
    private function getCleanupStatus(): bool
    {
        $setting = \Illuminate\Support\Facades\DB::table('system_settings')
            ->whereRaw('`key` = ?', ['audit_cleanup_paused'])
            ->first();
        
        return !($setting && $setting->value === '1');
    }
    
    /**
     * Get last cleanup run time
     */
    private function getLastCleanupRun()
    {
        $setting = \Illuminate\Support\Facades\DB::table('system_settings')
            ->whereRaw('`key` = ?', ['audit_cleanup_last_run'])
            ->first();
        
        return $setting ? $setting->value : null;
    }
    
    /**
     * Toggle cleanup job status (activate/pause)
     */
    public function toggleCleanupStatus(Request $request)
    {
        try {
            $status = $request->input('status'); // 'active' or 'paused'
            $isPaused = $status === 'paused' ? '1' : '0';
            
            // Update or create the setting
            $exists = \Illuminate\Support\Facades\DB::table('system_settings')
                ->whereRaw('`key` = ?', ['audit_cleanup_paused'])
                ->exists();
            
            if ($exists) {
                \Illuminate\Support\Facades\DB::table('system_settings')
                    ->whereRaw('`key` = ?', ['audit_cleanup_paused'])
                    ->update(['value' => $isPaused, 'updated_at' => now()]);
            } else {
                \Illuminate\Support\Facades\DB::table('system_settings')->insert([
                    'key' => 'audit_cleanup_paused',
                    'value' => $isPaused,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Log the action
            \App\Models\Audit::create([
                'user_id' => Auth::id(),
                'event' => 'audit_cleanup_' . $status,
                'description' => 'Audit cleanup job ' . $status . ' by ' . Auth::user()->username,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);
            
            $message = $status === 'active' 
                ? 'Audit cleanup job has been activated. It will run automatically on the 1st of each month.' 
                : 'Audit cleanup job has been paused. It will not run automatically.';
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'status' => $status
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update cleanup status: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to update cleanup status: ' . $e->getMessage());
        }
    }
}
