<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderMatchingService
{
    public const MAKER_FEE_RATE = '0.005'; // 0.5%

    public const TAKER_FEE_RATE = '0.01';  // 1.0%

    /**
     * Attempt to match an order with a counter order.
     * Returns the Trade if matched, null otherwise.
     */
    public function matchOrder(Order $takerOrder): ?Trade
    {
        return DB::transaction(function () use ($takerOrder) {
            // Lock the taker order to ensure it's still open
            $takerOrder = Order::lockForUpdate()->find($takerOrder->id);

            if (! $takerOrder || ! $takerOrder->isOpen()) {
                return null;
            }

            // Find a matching counter order (maker)
            $makerOrder = $this->findMatchingOrder($takerOrder);

            if (! $makerOrder) {
                return null;
            }

            // Execute the trade
            return $this->executeTrade($makerOrder, $takerOrder);
        });
    }

    /**
     * Find a matching counter order for the given order.
     * For BUY: find SELL where sell.price <= buy.price (lowest price first)
     * For SELL: find BUY where buy.price >= sell.price (highest price first)
     */
    protected function findMatchingOrder(Order $takerOrder): ?Order
    {
        $query = Order::lockForUpdate()
            ->open()
            ->bySymbol($takerOrder->symbol)
            ->where('amount', $takerOrder->amount) // Full match only - exact amount
            ->where('user_id', '!=', $takerOrder->user_id); // Can't match own orders

        if ($takerOrder->isBuy()) {
            // Find sell orders with price <= buy price
            $query->sells()
                ->where('price', '<=', $takerOrder->price)
                ->orderBy('price', 'asc') // Best price (lowest) first
                ->orderBy('created_at', 'asc'); // FIFO for same price
        } else {
            // Find buy orders with price >= sell price
            $query->buys()
                ->where('price', '>=', $takerOrder->price)
                ->orderBy('price', 'desc') // Best price (highest) first
                ->orderBy('created_at', 'asc'); // FIFO for same price
        }

        return $query->first();
    }

    /**
     * Execute a trade between maker and taker orders.
     * Trade executes at the maker's (resting) price.
     */
    protected function executeTrade(Order $makerOrder, Order $takerOrder): Trade
    {
        // Determine which order is buy/sell
        $buyOrder = $takerOrder->isBuy() ? $takerOrder : $makerOrder;
        $sellOrder = $takerOrder->isSell() ? $takerOrder : $makerOrder;

        $buyer = User::lockForUpdate()->find($buyOrder->user_id);
        $seller = User::lockForUpdate()->find($sellOrder->user_id);

        // Trade executes at maker's price
        $tradePrice = $makerOrder->price;
        $tradeAmount = $makerOrder->amount;
        $volume = bcmul($tradePrice, $tradeAmount, 8);

        // Calculate fees
        $makerFee = bcmul($volume, self::MAKER_FEE_RATE, 8);
        $takerFee = bcmul($volume, self::TAKER_FEE_RATE, 8);

        // Determine which party is maker/taker for fee assignment
        $buyerIsTaker = $takerOrder->isBuy();
        $buyerFee = $buyerIsTaker ? $takerFee : $makerFee;
        $sellerFee = $buyerIsTaker ? $makerFee : $takerFee;

        // Settlement for buyer
        // Buyer already locked: buyOrder.price * amount
        // Needs to pay: volume + buyerFee
        // Refund: (buyOrder.price * amount) - volume - buyerFee
        $buyerLocked = bcmul($buyOrder->price, $buyOrder->amount, 8);
        $buyerOwes = bcadd($volume, $buyerFee, 8);
        $buyerRefund = bcsub($buyerLocked, $buyerOwes, 8);

        if (bccomp($buyerRefund, '0', 8) > 0) {
            $buyer->balance = bcadd($buyer->balance, $buyerRefund, 8);
        }
        $buyer->save();

        // Settlement for seller
        // Seller receives: volume - sellerFee
        $sellerReceives = bcsub($volume, $sellerFee, 8);
        $seller->balance = bcadd($seller->balance, $sellerReceives, 8);
        $seller->save();

        // Transfer crypto asset
        $sellerAsset = Asset::lockForUpdate()
            ->where('user_id', $seller->id)
            ->where('symbol', $sellOrder->symbol)
            ->first();

        // Deduct from seller's locked and total amount
        $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $tradeAmount, 8);
        $sellerAsset->amount = bcsub($sellerAsset->amount, $tradeAmount, 8);
        $sellerAsset->save();

        // Add to buyer's asset (create if doesn't exist)
        $buyerAsset = Asset::lockForUpdate()
            ->where('user_id', $buyer->id)
            ->where('symbol', $buyOrder->symbol)
            ->first();

        if ($buyerAsset) {
            $buyerAsset->amount = bcadd($buyerAsset->amount, $tradeAmount, 8);
            $buyerAsset->save();
        } else {
            Asset::create([
                'user_id' => $buyer->id,
                'symbol' => $buyOrder->symbol,
                'amount' => $tradeAmount,
                'locked_amount' => '0.00000000',
            ]);
        }

        // Mark both orders as filled
        $makerOrder->status = Order::STATUS_FILLED;
        $makerOrder->save();

        $takerOrder->status = Order::STATUS_FILLED;
        $takerOrder->save();

        // Create trade record
        return Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol' => $makerOrder->symbol,
            'price' => $tradePrice,
            'amount' => $tradeAmount,
            'volume' => $volume,
            'maker_fee' => $makerFee,
            'taker_fee' => $takerFee,
        ]);
    }
}
