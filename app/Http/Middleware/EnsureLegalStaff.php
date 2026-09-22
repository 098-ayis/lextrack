<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLegalStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->hasRole('Admin')
            && ! $user->hasRole('Super Admin'),
            403,
            'Only authorized Legal Staff can access original documents.'
        );

        return $next($request);
    }
}