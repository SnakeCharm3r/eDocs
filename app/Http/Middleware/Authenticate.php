<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Store the intended URL so user can be redirected back after login
        if (!$request->is('login') && !$request->is('register') && !$request->is('password/*')) {
            session(['url.intended' => $request->fullUrl()]);
        }

        return route('login');
    }
}
