<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

         // Check if the user's profile is complete
         if ($user && !$user->isProfileComplete()) {
            // Redirect them to their profile page or show an error message
            return redirect()->route('profile.edit')->with('error', 'Please complete your profile before making requests.');
        }

        return $next($request);
    }
}
