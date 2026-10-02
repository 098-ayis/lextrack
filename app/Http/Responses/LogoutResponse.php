<?php

namespace App\Http\Responses;

use App\Services\AuditLogService;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        app(AuditLogService::class)->record(
            'User logged out',
            'Administrator logged out.',
            $request->user(),
        );

        return redirect('/');
    }
}
