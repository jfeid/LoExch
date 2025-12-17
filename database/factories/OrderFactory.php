<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
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
            'side' => fake()->randomElement(['buy', 'sell']),
            'price' => fake()->randomFloat(8, 1000, 100000),
            'amount' => fake()->randomFloat(8, 0.001, 1),
            'status' => Order::STATUS_OPEN,
        ];
    }

    /**
     * Create a buy order.
     */
    public function buy(): static
    {
        return $this->state(fn (array $attributes) => [
            'side' => 'buy',
        ]);
    }

    /**
     * Create a sell order.
     */
    public function sell(): static
    {
        return $this->state(fn (array $attributes) => [
            'side' => 'sell',
        ]);
    }

    /**
     * Create a BTC order.
     */
    public function btc(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => 'BTC',
        ]);
    }

    /**
     * Create an ETH order.
     */
    public function eth(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => 'ETH',
        ]);
    }

    /**
     * Create an open order.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_OPEN,
        ]);
    }

    /**
     * Create a filled order.
     */
    public function filled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_FILLED,
        ]);
    }

    /**
     * Create a cancelled order.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_CANCELLED,
        ]);
    }

    /**
     * Set specific price and amount.
     */
    public function withPriceAndAmount(string $price, string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
            'amount' => $amount,
        ]);
    }
}
