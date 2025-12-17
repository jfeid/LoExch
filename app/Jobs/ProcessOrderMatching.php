<?php

namespace App\Jobs;

use App\Events\OrderMatched;
use App\Models\Order;
use App\Services\OrderMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOrderMatching implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function handle(OrderMatchingService $matchingService): void
    {
        $trade = $matchingService->matchOrder($this->order);

        if ($trade) {
            OrderMatched::dispatch($trade);
        }
    }
}
