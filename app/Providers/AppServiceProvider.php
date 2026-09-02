<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;


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

        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('Super Admin')
                ? true
                : null;
        });
    }
}
