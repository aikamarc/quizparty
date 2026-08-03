<?php

namespace App\Models\BlindTest;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRoundPlayerState extends Model
{
    protected $fillable = ['game_round_id', 'user_id', 'lives_remaining', 'disqualified'];

    protected function casts(): array
    {
        return ['disqualified' => 'boolean'];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(GameRound::class, 'game_round_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
