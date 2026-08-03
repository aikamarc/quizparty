<?php

namespace App\Models\Songless;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    public const STAGES = [0.1, 0.5, 1, 2, 5, 10];

    protected $table = 'songless_attempts';

    protected $fillable = ['daily_song_id', 'user_id', 'current_stage', 'status', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function dailySong(): BelongsTo
    {
        return $this->belongsTo(DailySong::class, 'daily_song_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guesses(): HasMany
    {
        return $this->hasMany(Guess::class)->oldest();
    }

    public function isComplete(): bool
    {
        return in_array($this->status, ['won', 'lost'], true);
    }
}
