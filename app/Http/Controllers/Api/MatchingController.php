<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderMatched;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderMatchingService;
use Illuminate\Http\JsonResponse;

class MatchingController extends Controller
{
    public function __construct(private readonly OrderMatchingService $matchingService) {}

    /**
     * Trigger order matching for all open orders.
     * This endpoint is called internally (e.g., by cron) to process pending matches.
     */
    public function trigger(): JsonResponse
    {
        $matchCount = 0;
        $processedOrders = [];

        // Get all open orders, ordered by ID for deterministic FIFO matching
        $openOrders = Order::query()
            ->open()
            ->orderBy('id', 'asc')
            ->get();

        foreach ($openOrders as $order) {
            // Skip if this order was already matched in this run
            if (in_array($order->id, $processedOrders)) {
                continue;
            }

            // Refresh to check if still open (may have been matched by previous iteration)
            $order->refresh();
            if (! $order->isOpen()) {
                continue;
            }

            $trade = $this->matchingService->matchOrder($order);

            if ($trade) {
                $matchCount++;
                // Track both orders as processed
                $processedOrders[] = $trade->buy_order_id;
                $processedOrders[] = $trade->sell_order_id;

                // Broadcast the match to both parties
                OrderMatched::dispatch($trade);
            }
        }

        return response()->json([
            'message' => $matchCount > 0
                ? "Successfully matched {$matchCount} order(s)"
                : 'No matching orders found',
            'matches' => $matchCount,
        ]);
    }
}
