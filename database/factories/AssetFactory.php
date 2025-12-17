<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => fake()->randomElement(['BTC', 'ETH']),
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ];
    }

    /**
     * Create a BTC asset.
     */
    public function btc(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => 'BTC',
        ]);
    }

    /**
     * Create an ETH asset.
     */
    public function eth(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => 'ETH',
        ]);
    }

    /**
     * Set a specific amount.
     */
    public function withAmount(string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Set a specific locked amount.
     */
    public function withLockedAmount(string $lockedAmount): static
    {
        return $this->state(fn (array $attributes) => [
            'locked_amount' => $lockedAmount,
        ]);
    }
}
