<?php

namespace App\Models\BlindTest;

use App\Models\Category;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRound extends Model
{
    protected $fillable = [
        'game_room_id',
        'track_id',
        'category_id',
        'round_number',
        'started_at',
        'ends_at',
        'artist_found_by',
        'artist_found_at',
        'title_found_by',
        'title_found_at',
        'revealed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'artist_found_at' => 'datetime',
            'title_found_at' => 'datetime',
            'revealed_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function playerStates(): HasMany
    {
        return $this->hasMany(GameRoundPlayerState::class);
    }

    public function artistFoundBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_found_by');
    }

    public function titleFoundBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'title_found_by');
    }

    public function isFullyFound(): bool
    {
        return $this->artist_found_by !== null && $this->title_found_by !== null;
    }
}
