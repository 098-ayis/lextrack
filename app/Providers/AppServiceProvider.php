<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
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
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('Super Admin')
                ? true
                : null;
        });
    }
}
