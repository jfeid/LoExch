<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchingApiTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-internal-job-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.internal_job.secret' => $this->secret]);
    }

    public function test_matching_endpoint_requires_secret(): void
    {
        $response = $this->postJson('/api/internal/job');

        $response->assertStatus(401);
    }

    public function test_matching_endpoint_rejects_invalid_secret(): void
    {
        $response = $this->postJson('/api/internal/job', [], [
            'Authorization' => 'Bearer invalid-secret',
        ]);

        $response->assertStatus(401);
    }

    public function test_matching_endpoint_returns_success(): void
    {
        $response = $this->postJson('/api/internal/job', [], [
            'Authorization' => 'Bearer '.$this->secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'matches']);
    }

    public function test_matching_endpoint_matches_orders(): void
    {
        // Create buyer with USD balance
        $buyer = User::factory()->withBalance('10000.00000000')->create();

        // Create seller with BTC asset
        $seller = User::factory()->withBalance('0.00000000')->create();
        Asset::factory()->for($seller)->btc()->withAmount('1.00000000')->create();

        // Create matching orders
        // Seller places sell order first (maker)
        $sellOrder = Order::factory()->for($seller)->btc()->sell()->open()->create([
            'price' => '50000.00000000',
            'amount' => '0.10000000',
        ]);
        $seller->assets()->where('symbol', 'BTC')->update(['locked_amount' => '0.10000000']);

        // Buyer places buy order (taker) - price >= sell price
        $buyOrder = Order::factory()->for($buyer)->btc()->buy()->open()->create([
            'price' => '50000.00000000',
            'amount' => '0.10000000',
        ]);

        // Trigger matching
        $response = $this->postJson('/api/internal/job', [], [
            'Authorization' => 'Bearer '.$this->secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('matches', 1);

        // Verify orders are filled
        $sellOrder->refresh();
        $buyOrder->refresh();
        $this->assertEquals(Order::STATUS_FILLED, $sellOrder->status);
        $this->assertEquals(Order::STATUS_FILLED, $buyOrder->status);

        // Verify trade was created
        $this->assertDatabaseHas('trades', [
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'symbol' => 'BTC',
            'price' => '50000.00000000',
            'amount' => '0.10000000',
        ]);
    }

    public function test_matching_endpoint_returns_zero_when_no_matches(): void
    {
        // Create orders that don't match (buy price < sell price)
        $buyer = User::factory()->withBalance('10000.00000000')->create();
        $seller = User::factory()->create();
        Asset::factory()->for($seller)->btc()->withAmount('1.00000000')->create();

        Order::factory()->for($buyer)->btc()->buy()->open()->create([
            'price' => '45000.00000000',
            'amount' => '0.10000000',
        ]);

        Order::factory()->for($seller)->btc()->sell()->open()->create([
            'price' => '50000.00000000',
            'amount' => '0.10000000',
        ]);

        $response = $this->postJson('/api/internal/job', [], [
            'Authorization' => 'Bearer '.$this->secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('matches', 0)
            ->assertJsonPath('message', 'No matching orders found');
    }

    public function test_matching_processes_multiple_matches(): void
    {
        // Create two buyers
        $buyer1 = User::factory()->withBalance('10000.00000000')->create();
        $buyer2 = User::factory()->withBalance('10000.00000000')->create();

        // Create two sellers with assets
        $seller1 = User::factory()->create();
        $seller2 = User::factory()->create();
        Asset::factory()->for($seller1)->btc()->withAmount('1.00000000')->create();
        Asset::factory()->for($seller2)->btc()->withAmount('1.00000000')->create();

        // Create matching pairs
        Order::factory()->for($seller1)->btc()->sell()->open()->create([
            'price' => '50000.00000000',
            'amount' => '0.10000000',
        ]);
        $seller1->assets()->where('symbol', 'BTC')->update(['locked_amount' => '0.10000000']);

        Order::factory()->for($buyer1)->btc()->buy()->open()->create([
            'price' => '50000.00000000',
            'amount' => '0.10000000',
        ]);

        Order::factory()->for($seller2)->btc()->sell()->open()->create([
            'price' => '51000.00000000',
            'amount' => '0.20000000',
        ]);
        $seller2->assets()->where('symbol', 'BTC')->update(['locked_amount' => '0.20000000']);

        Order::factory()->for($buyer2)->btc()->buy()->open()->create([
            'price' => '52000.00000000',
            'amount' => '0.20000000',
        ]);

        $response = $this->postJson('/api/internal/job', [], [
            'Authorization' => 'Bearer '.$this->secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('matches', 2);

        $this->assertDatabaseCount('trades', 2);
    }
}
