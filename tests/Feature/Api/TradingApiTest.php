<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }

    public function test_profile_returns_user_balance_and_assets(): void
    {
        $user = User::factory()->withBalance('10000.00000000')->create();
        Asset::factory()->for($user)->btc()->withAmount('1.50000000')->create();
        Asset::factory()->for($user)->eth()->withAmount('5.00000000')->create();

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'balance' => '10000.00000000',
                    'assets' => [
                        ['symbol' => 'BTC', 'amount' => '1.50000000'],
                        ['symbol' => 'ETH', 'amount' => '5.00000000'],
                    ],
                ],
            ]);
    }

    public function test_orders_returns_open_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->btc()->buy()->open()->create(['price' => '50000', 'amount' => '0.1']);
        Order::factory()->for($user)->btc()->sell()->open()->create(['price' => '51000', 'amount' => '0.2']);
        Order::factory()->for($user)->btc()->buy()->filled()->create(); // Should not appear

        $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_create_buy_order(): void
    {
        $user = User::factory()->withBalance('10000.00000000')->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.10000000',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('order.symbol', 'BTC')
            ->assertJsonPath('order.side', 'buy')
            ->assertJsonPath('order.status', 'open');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => 'buy',
            'status' => Order::STATUS_OPEN,
        ]);

        $user->refresh();
        $this->assertEquals('5000.00000000', $user->balance);
    }

    public function test_create_sell_order(): void
    {
        $user = User::factory()->create();
        Asset::factory()->for($user)->btc()->withAmount('1.00000000')->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '50000.00000000',
            'amount' => '0.50000000',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('order.side', 'sell');

        $asset = $user->assets()->where('symbol', 'BTC')->first();
        $this->assertEquals('0.50000000', $asset->locked_amount);
    }

    public function test_create_order_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'INVALID',
            'side' => 'invalid',
            'price' => '-100',
            'amount' => '0',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['symbol', 'side', 'price', 'amount']);
    }

    public function test_create_order_insufficient_balance(): void
    {
        $user = User::factory()->withBalance('100.00000000')->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.10000000',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Insufficient USD balance');
    }

    public function test_cancel_order(): void
    {
        $user = User::factory()->withBalance('5000.00000000')->create();
        $order = Order::factory()->for($user)->buy()->btc()->open()->create([
            'price' => '50000.00000000',
            'amount' => '0.10000000',
        ]);

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('order.status', 'cancelled');

        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);
    }

    public function test_cannot_cancel_other_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->for($owner)->buy()->open()->create();

        $response = $this->actingAs($other)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_user_orders_returns_all_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->open()->count(2)->create();
        Order::factory()->for($user)->filled()->count(1)->create();
        Order::factory()->for($user)->cancelled()->count(1)->create();

        $response = $this->actingAs($user)->getJson('/api/user/orders');

        $response->assertStatus(200);
        $this->assertCount(4, $response->json('data'));
    }
}
