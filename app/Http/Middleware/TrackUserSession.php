<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\UserSession;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $sessionId = Session::getId();
            $sessionLifetime = 2; // 2 minutes (120 seconds) for session validation
            
            // Update last activity for current session
            if ($sessionId) {
                // Check if session exists in database
                $sessionExists = UserSession::where('user_id', $user->id)
                    ->where('session_id', $sessionId)
                    ->exists();
                
                if ($sessionExists) {
                    // Update existing session
                    UserSession::where('user_id', $user->id)
                        ->where('session_id', $sessionId)
                        ->update([
                            'last_activity' => now(),
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ]);
                    
                    // Check if current session is still valid (not removed due to new login from another device)
                    $currentSession = UserSession::where('user_id', $user->id)
                        ->where('session_id', $sessionId)
                        ->first();
                    
                    // If session was removed (user logged in from another browser/device), logout
                    if (!$currentSession) {
                        // Session was deleted - user logged in from another device
                        Auth::logout();
                        Session::flush();
                        
                        // For AJAX requests, return JSON response
                        if ($request->expectsJson() || $request->ajax()) {
                            return response()->json([
                                'session_expired' => true,
                                'message' => 'Your session has expired because you logged in from another browser or device. Only one active session is allowed at a time.',
                                'redirect' => route('login')
                            ], 401);
                        }
                        
                        // Redirect to login for regular requests
                        return redirect()->route('login')->with('error', 'Your session has expired because you logged in from another browser or device. Only one active session is allowed at a time.');
                    }
                } else {
                    // Session doesn't exist in database - might be a new login
                    // Create it if it doesn't exist (shouldn't happen, but handle gracefully)
                    UserSession::create([
                        'user_id' => $user->id,
                        'session_id' => $sessionId,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'last_activity' => now(),
                    ]);
                }
            }
        }

        return $next($request);
    }
}
