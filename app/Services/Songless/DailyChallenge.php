<?php

namespace App\Services\Songless;

use App\Models\Songless\DailySong;
use App\Models\Track;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyChallenge
{
    /** @return Collection<int, DailySong> */
    public static function forToday(array $excludedTrackIds = []): Collection
    {
        $date = today()->toDateString();

        DB::transaction(function () use ($date, $excludedTrackIds) {
            $existing = DailySong::whereDate('play_date', $date)->lockForUpdate()->count();

            if ($existing === 0) {
                $query = Track::whereNotNull('preview_url');

                if ($excludedTrackIds !== [] && (clone $query)->whereNotIn('id', $excludedTrackIds)->count() >= 3) {
                    $query->whereNotIn('id', $excludedTrackIds);
                }

                $tracks = $query->inRandomOrder()->limit(3)->get();

                if ($tracks->count() < 3) return;

                foreach ($tracks as $index => $track) {
                    DailySong::create(['play_date' => $date, 'position' => $index + 1, 'track_id' => $track->id]);
                }
            }
        }, 3);

        return DailySong::whereDate('play_date', $date)
            ->with(['track.categories.parent'])
            ->orderBy('position')
            ->get();
    }
}
