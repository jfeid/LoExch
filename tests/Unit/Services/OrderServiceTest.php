<?php

namespace Tests\Unit\Services;

use App\Exceptions\InsufficientAssetException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderNotCancellableException;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderService;
    }

    public function test_create_buy_order_deducts_balance(): void
    {
        $user = User::factory()->withBalance('10000.00000000')->create();

        $order = $this->service->createBuyOrder($user, 'BTC', '50000.00000000', '0.10000000');

        $this->assertEquals('buy', $order->side);
        $this->assertEquals('BTC', $order->symbol);
        $this->assertEquals(Order::STATUS_OPEN, $order->status);

        // Volume: $50,000 × 0.1 = $5,000
        // With fee buffer (1.01): $5,050 locked
        // Balance: $10,000 - $5,050 = $4,950
        $user->refresh();
        $this->assertEquals('4950.00000000', $user->balance);
    }

    public function test_create_buy_order_fails_with_insufficient_balance(): void
    {
        $user = User::factory()->withBalance('1000.00000000')->create();

        $this->expectException(InsufficientBalanceException::class);

        $this->service->createBuyOrder($user, 'BTC', '50000.00000000', '0.10000000');
    }

    public function test_create_sell_order_locks_asset(): void
    {
        $user = User::factory()->create();
        Asset::factory()->for($user)->btc()->withAmount('1.00000000')->create();

        $order = $this->service->createSellOrder($user, 'BTC', '50000.00000000', '0.50000000');

        $this->assertEquals('sell', $order->side);
        $this->assertEquals(Order::STATUS_OPEN, $order->status);

        $asset = $user->assets()->where('symbol', 'BTC')->first();
        $this->assertEquals('0.50000000', $asset->locked_amount);
    }

    public function test_create_sell_order_fails_with_insufficient_asset(): void
    {
        $user = User::factory()->create();
        Asset::factory()->for($user)->btc()->withAmount('0.10000000')->create();

        $this->expectException(InsufficientAssetException::class);

        $this->service->createSellOrder($user, 'BTC', '50000.00000000', '0.50000000');
    }

    public function test_create_sell_order_fails_without_asset(): void
    {
        $user = User::factory()->create();

        $this->expectException(InsufficientAssetException::class);

        $this->service->createSellOrder($user, 'BTC', '50000.00000000', '0.50000000');
    }

    public function test_cancel_buy_order_refunds_balance(): void
    {
        $user = User::factory()->withBalance('10000.00000000')->create();
        $order = $this->service->createBuyOrder($user, 'BTC', '50000.00000000', '0.10000000');

        // After order: $10,000 - $5,050 (with fee buffer) = $4,950
        $user->refresh();
        $this->assertEquals('4950.00000000', $user->balance);

        $this->service->cancelOrder($order);

        // After cancel: full refund including fee buffer
        $user->refresh();
        $order->refresh();

        $this->assertEquals('10000.00000000', $user->balance);
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);
    }

    public function test_cancel_sell_order_releases_locked_asset(): void
    {
        $user = User::factory()->create();
        Asset::factory()->for($user)->btc()->withAmount('1.00000000')->create();

        $order = $this->service->createSellOrder($user, 'BTC', '50000.00000000', '0.50000000');

        $asset = $user->assets()->where('symbol', 'BTC')->first();
        $this->assertEquals('0.50000000', $asset->locked_amount);

        $this->service->cancelOrder($order);

        $asset->refresh();
        $order->refresh();

        $this->assertEquals('0.00000000', $asset->locked_amount);
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);
    }

    public function test_cannot_cancel_filled_order(): void
    {
        $user = User::factory()->withBalance('10000.00000000')->create();
        $order = Order::factory()->for($user)->buy()->filled()->create();

        $this->expectException(OrderNotCancellableException::class);

        $this->service->cancelOrder($order);
    }
}
