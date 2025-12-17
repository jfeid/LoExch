<?php

namespace App\Providers;

use App\Services\DemoTwoFactorAuthenticationProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider as TwoFactorAuthenticationProviderContract;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // In local environment, use demo 2FA provider that accepts code 111111
        if ($this->app->environment('local')) {
            $this->app->singleton(TwoFactorAuthenticationProviderContract::class, function ($app) {
                return new DemoTwoFactorAuthenticationProvider(
                    $app->make(TwoFactorAuthenticationProvider::class)
                );
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
