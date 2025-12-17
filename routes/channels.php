<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for user-specific trading updates (order matches, balance changes)
Broadcast::channel('user.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

// Public channel for orderbook updates
Broadcast::channel('orderbook.{symbol}', function () {
    return true;
});
