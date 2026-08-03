<?php

namespace App\Models\BlindTest;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRoomPlayer extends Model
{
    protected $fillable = [
        'game_room_id',
        'user_id',
        'score',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
