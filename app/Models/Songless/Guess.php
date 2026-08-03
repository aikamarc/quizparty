<?php

namespace App\Models\Songless;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guess extends Model
{
    protected $table = 'songless_guesses';

    protected $fillable = ['attempt_id', 'stage', 'guess', 'result'];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }
}
