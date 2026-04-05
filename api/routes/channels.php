<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('game.{code}', function ($user, $code) {
    $room = \App\Models\GameRoom::where('code', $code)
        ->whereIn('status', ['waiting', 'playing'])
        ->first();
    if (!$room) return false;
    return $room->host_user_id === $user->id || $room->guest_user_id === $user->id;
});
