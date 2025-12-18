<?php

use App\Http\Controllers\Api\MatchingController;
use App\Http\Controllers\Api\TradingController;
use App\Http\Middleware\ValidateInternalJobSecret;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [TradingController::class, 'profile']);
    Route::get('/orders', [TradingController::class, 'orders']);
    Route::get('/user/orders', [TradingController::class, 'userOrders']);
    Route::post('/orders', [TradingController::class, 'createOrder']);
    Route::post('/orders/{order}/cancel', [TradingController::class, 'cancelOrder']);
});

// Internal job endpoint for triggering order matching (requires secret)
Route::post('/internal/job', [MatchingController::class, 'trigger'])
    ->middleware(ValidateInternalJobSecret::class);
