<?php

namespace App\Jobs;

use App\Events\OrderMatched;
use App\Models\Order;
use App\Services\OrderMatchingService;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessOrderMatching
{
    use Dispatchable;

    public function __construct(public Order $order) {}

    public function handle(OrderMatchingService $matchingService): void
    {
        $trade = $matchingService->matchOrder($this->order);

        if ($trade) {
            OrderMatched::dispatch($trade);
        }
    }
}
