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
        $minutes = config('session.inactivity_timeout', 60);

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

            return redirect()->route('login')
                ->with(
                    'status',
                    'You were logged out due to inactivity.'
                );
        }

        // Livewire polling runs in the background even when the user is not
        // interacting with the page. Do not let those requests keep an idle
        // session alive, while still counting normal Livewire actions as use.
        if (! $this->isBackgroundPolling($request)) {
            $request->session()->put('last_activity_at', time());
        }

        return $next($request);
    }

    private function isBackgroundPolling(Request $request): bool
    {
        if ($request->path() !== 'livewire/update') {
            return false;
        }

        $components = $request->input('components');

        if (! is_array($components) || $components === []) {
            return false;
        }

        foreach ($components as $component) {
            if (! is_array($component)) {
                return false;
            }

            if (! empty($component['updates'])) {
                return false;
            }

            foreach ($component['calls'] ?? [] as $call) {
                if (! is_array($call)) {
                    return false;
                }

                if (($call['method'] ?? null) !== 'refreshConversation') {
                    return false;
                }
            }
        }

        return true;
    }
}
