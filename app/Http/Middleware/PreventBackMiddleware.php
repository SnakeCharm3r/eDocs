<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class PreventBackMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is authenticated and trying to go back to a certain page
        if (Auth::check() && $request->is('login')) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
