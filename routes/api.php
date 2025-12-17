<?php

use App\Http\Controllers\Api\TradingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [TradingController::class, 'profile']);
    Route::get('/orders', [TradingController::class, 'orders']);
    Route::get('/user/orders', [TradingController::class, 'userOrders']);
    Route::post('/orders', [TradingController::class, 'createOrder']);
    Route::post('/orders/{order}/cancel', [TradingController::class, 'cancelOrder']);
});
