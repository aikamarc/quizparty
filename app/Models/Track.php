<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Track extends Model
{
    protected $fillable = [
        'title',
        'artist',
        'answer_mode',
        'custom_answer',
        'album',
        'cover_url',
        'preview_url',
        'source',
        'source_id',
        'duration_seconds',
        'added_by',
    ];

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
}
