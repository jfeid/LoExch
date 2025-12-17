<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    public const STATUS_OPEN = 1;

    public const STATUS_FILLED = 2;

    public const STATUS_CANCELLED = 3;

    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:8',
            'amount' => 'decimal:8',
            'status' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeBySymbol(Builder $query, string $symbol): Builder
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeBuys(Builder $query): Builder
    {
        return $query->where('side', 'buy');
    }

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeSells(Builder $query): Builder
    {
        return $query->where('side', 'sell');
    }

    /**
     * Get order volume (price * amount).
     */
    public function getVolumeAttribute(): string
    {
        return bcmul($this->price, $this->amount, 8);
    }

    /**
     * Check if order is open.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if order is a buy order.
     */
    public function isBuy(): bool
    {
        return $this->side === 'buy';
    }

    /**
     * Check if order is a sell order.
     */
    public function isSell(): bool
    {
        return $this->side === 'sell';
    }
}
