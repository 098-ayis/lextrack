<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdleTimeout
{
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $minutes = config('session.inactivity_timeout', 15);

        if (! Auth::check()) {
            return $next($request);
        }

        $lastActivity = $request->session()->get('last_activity_at');

        if (
            $lastActivity !== null &&
            (time() - $lastActivity) >= ($minutes * 60)
        ) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')
                ->with(
                    'status',
                    'You were logged out due to inactivity.'
                );
        }

        $request->session()->put(
            'last_activity_at',
            time()
        );

        return $next($request);
    }
}