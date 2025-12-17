<?php

namespace App\Services;

use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider as TwoFactorAuthenticationProviderContract;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

/**
 * Demo 2FA provider that accepts a fixed code (111111) in local environment.
 * This is for demonstration purposes only - never use in production.
 */
class DemoTwoFactorAuthenticationProvider implements TwoFactorAuthenticationProviderContract
{
    public const DEMO_CODE = '111111';

    public function __construct(
        protected TwoFactorAuthenticationProvider $provider
    ) {}

    public function generateSecretKey(): string
    {
        return $this->provider->generateSecretKey();
    }

    public function qrCodeUrl($companyName, $companyEmail, $secret): string
    {
        return $this->provider->qrCodeUrl($companyName, $companyEmail, $secret);
    }

    public function verify($secret, $code): bool
    {
        // In local environment, accept the demo code
        if (app()->environment('local') && $code === self::DEMO_CODE) {
            return true;
        }

        return $this->provider->verify($secret, $code);
    }
}
