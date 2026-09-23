<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use App\Models\Conversation;
use App\Models\User;
use App\Support\RoleSecurity;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            LogoutResponseContract::class,
            LogoutResponse::class,
        );
    }

    public function boot(): void
    {
        Role::creating(function (Role $role): bool {
            if (app()->runningInConsole() || ! RoleSecurity::isProtectedRoleName($role->name)) {
                return true;
            }

            throw ValidationException::withMessages([
                'data.name' => 'This role name is reserved.',
            ]);
        });

        Role::updating(function (Role $role): bool {
            if (RoleSecurity::isSuperAdminRole($role)) {
                return false;
            }

            return ! RoleSecurity::isProtectedRole($role)
                || ! $role->isDirty(['name', 'guard_name']);
        });
        Role::deleting(fn (Role $role): bool => ! RoleSecurity::isProtectedRole($role));

        FilamentShield::buildPermissionKeyUsing(
            function (
                string $entity,
                ?string $affix,
                string $subject,
                string $case,
                string $separator,
            ): ?string {
                if (! str_starts_with($entity, 'App\\Filament\\Client\\Pages\\')) {
                    return null;
                }

                return FilamentShield::defaultPermissionKeyBuilder(
                    affix: $affix,
                    separator: $separator,
                    subject: 'Client' . $subject,
                    case: $case,
                );
            }
        );

        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if (! $user->hasRole(RoleSecurity::SUPER_ADMIN)) {
                return null;
            }

            if (in_array($ability, [
                'view_shared_messages',
                'reply_shared_messages',
                'close_conversations',
            ], true)) {
                return false;
            }

            if (
                ($arguments[0] ?? null) instanceof Conversation
                && in_array($ability, ['view', 'sendMessage', 'close'], true)
            ) {
                return false;
            }

            return RoleSecurity::shouldDeferRoleMutation($ability, $arguments)
                ? null
                : true;
        });

        RateLimiter::for('google-login', function (Request $request) {
        return Limit::perMinute(10)
            ->by($request->ip());
        });

        RateLimiter::for('document-submit', function (Request $request) {
            $id = $request->user()?->id ?? $request->ip();

            return [
                Limit::perMinute(3)
                    ->by('document-minute:'.$id),

                Limit::perDay(30)
                    ->by('document-day:'.$id),
            ];
        });

        RateLimiter::for('messages', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?? $request->ip());
        });

        RateLimiter::for('chatbot', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?? $request->ip());
        });

        RateLimiter::for('downloads', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?? $request->ip());
        });

        RateLimiter::for('qr-tracking', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip());
        });
    }
}
