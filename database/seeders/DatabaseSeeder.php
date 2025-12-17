<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Fixed 2FA secret for test users (use any TOTP app with this secret)
        // Or use code: 000000 in development when TOTP validation is bypassed
        $twoFactorSecret = encrypt('JBSWY3DPEHPK3PXP'); // Standard test secret

        // Create main test user with USD and crypto
        $alice = User::factory()->create([
            'name' => 'Alice Trader',
            'email' => 'alice@example.com',
            'balance' => '50000.00000000',
            'two_factor_secret' => $twoFactorSecret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode([
                'AAAAA-BBBBB-11111',
                'CCCCC-DDDDD-22222',
                'EEEEE-FFFFF-33333',
                'GGGGG-HHHHH-44444',
            ])),
        ]);

        Asset::factory()->for($alice)->create([
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ]);

        Asset::factory()->for($alice)->create([
            'symbol' => 'ETH',
            'amount' => '10.00000000',
            'locked_amount' => '0.00000000',
        ]);

        // Create second test user (no 2FA)
        $bob = User::factory()->withoutTwoFactor()->create([
            'name' => 'Bob Trader',
            'email' => 'bob@example.com',
            'balance' => '100000.00000000',
        ]);

        Asset::factory()->for($bob)->create([
            'symbol' => 'BTC',
            'amount' => '2.00000000',
            'locked_amount' => '0.00000000',
        ]);

        Asset::factory()->for($bob)->create([
            'symbol' => 'ETH',
            'amount' => '20.00000000',
            'locked_amount' => '0.00000000',
        ]);
    }
}
