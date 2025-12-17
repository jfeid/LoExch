<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(8, 1000, 100000);
        $amount = fake()->randomFloat(8, 0.001, 1);
        $volume = bcmul((string) $price, (string) $amount, 8);
        $makerFee = bcmul($volume, '0.005', 8); // 0.5% maker fee
        $takerFee = bcmul($volume, '0.01', 8);  // 1.0% taker fee

        return [
            'buy_order_id' => Order::factory()->buy()->filled(),
            'sell_order_id' => Order::factory()->sell()->filled(),
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
            'symbol' => fake()->randomElement(['BTC', 'ETH']),
            'price' => $price,
            'amount' => $amount,
            'volume' => $volume,
            'maker_fee' => $makerFee,
            'taker_fee' => $takerFee,
        ];
    }

    /**
     * Create a BTC trade.
     */
    public function btc(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => 'BTC',
        ]);
    }

    /**
     * Create an ETH trade.
     */
    public function eth(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => 'ETH',
        ]);
    }
}
