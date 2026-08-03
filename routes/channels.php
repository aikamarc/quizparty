<?php

use App\Models\BlindTest\GameRoom;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('blindtest.room.{roomId}', function ($user, $roomId) {
    return GameRoom::whereKey($roomId)->whereHas('players', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })->exists();
});
