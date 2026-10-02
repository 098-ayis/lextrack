<?php

namespace App\Http\Middleware;

use App\Support\RoleSecurity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Allow only Super Admin to access administrative account data.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->hasRole(RoleSecurity::SUPER_ADMIN),
            403,
            'Only Super Admin can access this area.'
        );

        return $next($request);
    }
}
