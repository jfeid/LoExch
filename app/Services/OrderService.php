<?php

namespace App\Services;

use App\Exceptions\InsufficientAssetException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderNotCancellableException;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    // Fee buffer multiplier: 1 + max fee rate (taker fee 1%)
    // This ensures sufficient funds are locked to cover worst-case fee scenario
    public const FEE_BUFFER_MULTIPLIER = '1.01';

    /**
     * Create a buy order.
     *
     * @throws InsufficientBalanceException
     */
    public function createBuyOrder(User $user, string $symbol, string $price, string $amount): Order
    {
        return DB::transaction(function () use ($user, $symbol, $price, $amount) {
            // Lock the user row to prevent race conditions
            $user = User::lockForUpdate()->find($user->id);

            // Calculate total cost including fee buffer
            // Lock price × amount × 1.01 to cover worst-case taker fee
            $volume = bcmul($price, $amount, 8);
            $totalCost = bcmul($volume, self::FEE_BUFFER_MULTIPLIER, 8);

            // Verify sufficient balance
            if (bccomp($user->balance, $totalCost, 8) < 0) {
                throw new InsufficientBalanceException;
            }

            // Deduct balance (including fee buffer)
            $user->balance = bcsub($user->balance, $totalCost, 8);
            $user->save();

            // Create the order
            return Order::create([
                'user_id' => $user->id,
                'symbol' => $symbol,
                'side' => 'buy',
                'price' => $price,
                'amount' => $amount,
                'status' => Order::STATUS_OPEN,
            ]);
        });
    }

    /**
     * Create a sell order.
     *
     * @throws InsufficientAssetException
     */
    public function createSellOrder(User $user, string $symbol, string $price, string $amount): Order
    {
        return DB::transaction(function () use ($user, $symbol, $price, $amount) {
            // Lock the asset row to prevent race conditions
            $asset = Asset::lockForUpdate()
                ->where('user_id', $user->id)
                ->where('symbol', $symbol)
                ->first();

            if (! $asset) {
                throw new InsufficientAssetException($symbol);
            }

            // Calculate available amount (amount - locked_amount)
            $available = bcsub($asset->amount, $asset->locked_amount, 8);

            // Verify sufficient available balance
            if (bccomp($available, $amount, 8) < 0) {
                throw new InsufficientAssetException($symbol);
            }

            // Lock the amount
            $asset->locked_amount = bcadd($asset->locked_amount, $amount, 8);
            $asset->save();

            // Create the order
            return Order::create([
                'user_id' => $user->id,
                'symbol' => $symbol,
                'side' => 'sell',
                'price' => $price,
                'amount' => $amount,
                'status' => Order::STATUS_OPEN,
            ]);
        });
    }

    /**
     * Cancel an open order.
     *
     * @throws OrderNotCancellableException
     */
    public function cancelOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            // Lock the order row
            $order = Order::lockForUpdate()->find($order->id);

            // Verify order is open
            if (! $order->isOpen()) {
                throw new OrderNotCancellableException('Only open orders can be cancelled');
            }

            if ($order->isBuy()) {
                // Release locked USD back to user (including fee buffer)
                $user = User::lockForUpdate()->find($order->user_id);
                $volume = bcmul($order->price, $order->amount, 8);
                $totalLocked = bcmul($volume, self::FEE_BUFFER_MULTIPLIER, 8);
                $user->balance = bcadd($user->balance, $totalLocked, 8);
                $user->save();
            } else {
                // Release locked asset back to available
                $asset = Asset::lockForUpdate()
                    ->where('user_id', $order->user_id)
                    ->where('symbol', $order->symbol)
                    ->first();

                if ($asset) {
                    $asset->locked_amount = bcsub($asset->locked_amount, $order->amount, 8);
                    $asset->save();
                }
            }

            // Mark order as cancelled
            $order->status = Order::STATUS_CANCELLED;
            $order->save();

            return $order;
        });
    }
}
