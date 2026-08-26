<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Restricted (non-admin) accounts only get the Overview page -- send
     * them back there rather than exposing Devices/Alerts/Settings.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
