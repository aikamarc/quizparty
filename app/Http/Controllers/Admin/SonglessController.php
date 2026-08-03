<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Songless\DailySong;
use App\Services\Songless\DailyChallenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SonglessController extends Controller
{
    public function resetToday(): RedirectResponse
    {
        $previousTrackIds = DB::transaction(function () {
            $songs = DailySong::whereDate('play_date', today())->lockForUpdate()->get();
            $trackIds = $songs->pluck('track_id')->all();

            DailySong::whereKey($songs->pluck('id'))->delete();

            return $trackIds;
        });

        DailyChallenge::forToday($previousTrackIds);

        return redirect()->route('songless.index')->with('flash', [
            'songless_reset' => true,
        ]);
    }
}
