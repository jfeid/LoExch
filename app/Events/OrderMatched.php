<?php

namespace App\Events;

use App\Models\Trade;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Trade $trade) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->trade->buyer_id),
            new PrivateChannel('user.'.$this->trade->seller_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'trade' => [
                'id' => $this->trade->id,
                'symbol' => $this->trade->symbol,
                'price' => $this->trade->price,
                'amount' => $this->trade->amount,
                'volume' => $this->trade->volume,
                'maker_fee' => $this->trade->maker_fee,
                'taker_fee' => $this->trade->taker_fee,
                'buyer_id' => $this->trade->buyer_id,
                'seller_id' => $this->trade->seller_id,
                'created_at' => $this->trade->created_at->toISOString(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderMatched';
    }
}
