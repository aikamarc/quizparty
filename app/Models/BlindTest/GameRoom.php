<?php

namespace App\Models\BlindTest;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GameRoom extends Model
{
    protected $fillable = [
        'code',
        'public_id',
        'host_id',
        'status',
        'is_public',
        'rounds_total',
        'seconds_per_round',
        'current_round',
        'started_at',
        'host_last_seen_at',
        'anti_cheat',
        'lives_per_round',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'host_last_seen_at' => 'datetime',
            'is_public' => 'boolean',
            'anti_cheat' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        static::creating(function (GameRoom $room) {
            $room->public_id ??= (string) Str::uuid();
        });
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(GameRoomPlayer::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_game_room');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(GameRound::class);
    }

    public function currentRound(): ?GameRound
    {
        return $this->rounds()->where('round_number', $this->current_round)->first();
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
