<?php

namespace Tests\Unit\Services;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderMatchingService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    private OrderMatchingService $matchingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService;
        $this->matchingService = new OrderMatchingService;
    }

    public function test_buy_order_matches_sell_order(): void
    {
        // Seller with BTC
        $seller = User::factory()->withBalance('0.00000000')->create();
        Asset::factory()->for($seller)->btc()->withAmount('1.00000000')->create();

        // Buyer with USD
        $buyer = User::factory()->withBalance('100000.00000000')->create();

        // Create sell order first (maker)
        $sellOrder = $this->orderService->createSellOrder($seller, 'BTC', '50000.00000000', '0.10000000');

        // Create buy order (taker)
        $buyOrder = $this->orderService->createBuyOrder($buyer, 'BTC', '50000.00000000', '0.10000000');

        // Match
        $trade = $this->matchingService->matchOrder($buyOrder);

        $this->assertNotNull($trade);
        $this->assertEquals('50000.00000000', $trade->price);
        $this->assertEquals('0.10000000', $trade->amount);
        $this->assertEquals('5000.00000000', $trade->volume);

        // Verify fees (maker 0.5%, taker 1.0%)
        $this->assertEquals('25.00000000', $trade->maker_fee); // 5000 * 0.005
        $this->assertEquals('50.00000000', $trade->taker_fee); // 5000 * 0.01

        // Verify orders are filled
        $sellOrder->refresh();
        $buyOrder->refresh();
        $this->assertEquals(Order::STATUS_FILLED, $sellOrder->status);
        $this->assertEquals(Order::STATUS_FILLED, $buyOrder->status);

        // Verify balances
        $seller->refresh();
        $buyer->refresh();

        // Seller receives: 5000 - 25 (maker fee) = 4975
        $this->assertEquals('4975.00000000', $seller->balance);

        // Buyer started with 100000, locked 5000 when creating order (balance = 95000)
        // Trade execution: volume = 5000, taker fee = 50
        // buyerLocked = 5000, buyerOwes = 5050, buyerRefund = -50
        // The -50 is deducted from buyer's remaining balance: 95000 - 50 = 94950
        $this->assertEquals('94950.00000000', $buyer->balance);

        // Verify asset transfer
        $sellerAsset = $seller->assets()->where('symbol', 'BTC')->first();
        $buyerAsset = $buyer->assets()->where('symbol', 'BTC')->first();

        $this->assertEquals('0.90000000', $sellerAsset->amount);
        $this->assertEquals('0.00000000', $sellerAsset->locked_amount);
        $this->assertEquals('0.10000000', $buyerAsset->amount);
    }

    public function test_sell_order_matches_buy_order(): void
    {
        // Buyer with USD (creates order first = maker)
        $buyer = User::factory()->withBalance('100000.00000000')->create();

        // Seller with BTC
        $seller = User::factory()->withBalance('0.00000000')->create();
        Asset::factory()->for($seller)->btc()->withAmount('1.00000000')->create();

        // Create buy order first (maker)
        $buyOrder = $this->orderService->createBuyOrder($buyer, 'BTC', '50000.00000000', '0.10000000');

        // Create sell order (taker)
        $sellOrder = $this->orderService->createSellOrder($seller, 'BTC', '50000.00000000', '0.10000000');

        // Match
        $trade = $this->matchingService->matchOrder($sellOrder);

        $this->assertNotNull($trade);

        // Verify fees are swapped (seller is now taker)
        $this->assertEquals('25.00000000', $trade->maker_fee); // buyer is maker
        $this->assertEquals('50.00000000', $trade->taker_fee); // seller is taker
    }

    public function test_no_match_when_prices_dont_cross(): void
    {
        $seller = User::factory()->withBalance('0.00000000')->create();
        Asset::factory()->for($seller)->btc()->withAmount('1.00000000')->create();

        $buyer = User::factory()->withBalance('100000.00000000')->create();

        // Sell at 60000
        $this->orderService->createSellOrder($seller, 'BTC', '60000.00000000', '0.10000000');

        // Buy at 50000 (lower than sell, no cross)
        $buyOrder = $this->orderService->createBuyOrder($buyer, 'BTC', '50000.00000000', '0.10000000');

        $trade = $this->matchingService->matchOrder($buyOrder);

        $this->assertNull($trade);
    }

    public function test_no_match_when_amounts_differ(): void
    {
        $seller = User::factory()->withBalance('0.00000000')->create();
        Asset::factory()->for($seller)->btc()->withAmount('1.00000000')->create();

        $buyer = User::factory()->withBalance('100000.00000000')->create();

        // Sell 0.20 BTC
        $this->orderService->createSellOrder($seller, 'BTC', '50000.00000000', '0.20000000');

        // Buy 0.10 BTC (different amount)
        $buyOrder = $this->orderService->createBuyOrder($buyer, 'BTC', '50000.00000000', '0.10000000');

        $trade = $this->matchingService->matchOrder($buyOrder);

        $this->assertNull($trade);
    }

    public function test_cannot_match_own_order(): void
    {
        $user = User::factory()->withBalance('100000.00000000')->create();
        Asset::factory()->for($user)->btc()->withAmount('1.00000000')->create();

        // Create sell order
        $this->orderService->createSellOrder($user, 'BTC', '50000.00000000', '0.10000000');

        // Create buy order from same user
        $buyOrder = $this->orderService->createBuyOrder($user, 'BTC', '50000.00000000', '0.10000000');

        $trade = $this->matchingService->matchOrder($buyOrder);

        $this->assertNull($trade);
    }

    public function test_trade_executes_at_maker_price(): void
    {
        $seller = User::factory()->withBalance('0.00000000')->create();
        Asset::factory()->for($seller)->btc()->withAmount('1.00000000')->create();

        $buyer = User::factory()->withBalance('100000.00000000')->create();

        // Sell at 48000 (maker)
        $this->orderService->createSellOrder($seller, 'BTC', '48000.00000000', '0.10000000');

        // Buy at 50000 (taker) - willing to pay more
        $buyOrder = $this->orderService->createBuyOrder($buyer, 'BTC', '50000.00000000', '0.10000000');

        $trade = $this->matchingService->matchOrder($buyOrder);

        $this->assertNotNull($trade);
        // Trade should execute at maker's price (48000)
        $this->assertEquals('48000.00000000', $trade->price);
        $this->assertEquals('4800.00000000', $trade->volume);

        // Buyer should get refund for price difference
        $buyer->refresh();
        // Locked: 5000, Used: 4800 + 48 (taker fee) = 4848, Refund: 152
        $this->assertEquals('95152.00000000', $buyer->balance);
    }
}
