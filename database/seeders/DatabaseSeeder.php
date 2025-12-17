<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create main test user with USD and crypto
        $alice = User::factory()->create([
            'name' => 'Alice Trader',
            'email' => 'alice@example.com',
            'balance' => '50000.00000000',
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

        // Create second test user
        $bob = User::factory()->create([
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
