<?php

namespace App\Models\Songless;

use App\Models\Track;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailySong extends Model
{
    protected $table = 'songless_daily_songs';

    protected $fillable = ['play_date', 'position', 'track_id'];

    protected function casts(): array
    {
        return ['play_date' => 'date'];
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class, 'daily_song_id');
    }
}
