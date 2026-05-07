<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestrictToAuditViewers
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->can('view logs')) {
            return $next($request);
        }

        return redirect()->route('home')->with('error', 'Unauthorized access.');
    }
}