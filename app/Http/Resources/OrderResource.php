<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'side' => $this->side,
            'price' => $this->price,
            'amount' => $this->amount,
            'volume' => $this->volume,
            'status' => $this->getStatusLabel(),
            'status_code' => $this->status,
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            Order::STATUS_OPEN => 'open',
            Order::STATUS_FILLED => 'filled',
            Order::STATUS_CANCELLED => 'cancelled',
            default => 'unknown',
        };
    }
}
