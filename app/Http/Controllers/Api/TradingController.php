<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Exceptions\InsufficientAssetException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderNotCancellableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProfileResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TradingController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function profile(Request $request): ProfileResource
    {
        $user = $request->user()->load('assets');

        return new ProfileResource($user);
    }

    public function orders(Request $request): AnonymousResourceCollection
    {
        $symbol = $request->query('symbol');

        $query = Order::query()->open();

        if ($symbol) {
            $query->bySymbol($symbol);
        }

        $orders = $query->orderBy('price')->get();

        return OrderResource::collection($orders);
    }

    public function createOrder(CreateOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        try {
            $order = $data['side'] === 'buy'
                ? $this->orderService->createBuyOrder($user, $data['symbol'], $data['price'], $data['amount'])
                : $this->orderService->createSellOrder($user, $data['symbol'], $data['price'], $data['amount']);

            // Broadcast order created
            OrderCreated::dispatch($order);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => new OrderResource($order),
            ], 201);
        } catch (InsufficientBalanceException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (InsufficientAssetException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancelOrder(Request $request, Order $order): JsonResponse
    {
        // Ensure user owns the order
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $order = $this->orderService->cancelOrder($order);

            // Broadcast order cancelled
            OrderCancelled::dispatch($order);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => new OrderResource($order),
            ]);
        } catch (OrderNotCancellableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function userOrders(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()
            ->orders()
            ->orderByDesc('created_at')
            ->get();

        return OrderResource::collection($orders);
    }
}
