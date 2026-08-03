<?php

namespace App\Services\BlindTest;

use App\Events\BlindTest\GameEnded;
use App\Events\BlindTest\RoundEnded;
use App\Events\BlindTest\RoundStarted;
use App\Jobs\BlindTest\EndRoundIfNotRevealed;
use App\Jobs\BlindTest\FinishGameJob;
use App\Jobs\BlindTest\StartNextRoundJob;
use App\Models\BlindTest\GameRoom;
use App\Models\BlindTest\GameRound;
use App\Models\Track;
use App\Services\DeezerClient;
use App\Support\SafeBroadcast;

class RoundManager
{
    // How long the reveal (song, artist, title) stays on screen before the game
    // moves on, so everyone has time to actually read the result.
    private const REVEAL_DELAY_SECONDS = 5;

    // Nudges a room forward if it's due for a transition (round timed out, or the
    // reveal delay has elapsed). Called from the state-polling endpoint on every
    // request, so the game keeps progressing even if the queue worker isn't
    // running to process the delayed jobs — those jobs are still the fast path
    // when the queue does work, this is the resilient backstop.
    public static function tick(GameRoom $room): void
    {
        if ($room->status !== 'playing') {
            return;
        }

        $round = $room->currentRound();

        if (! $round) {
            return;
        }

        if ($round->revealed_at === null) {
            if (now()->greaterThanOrEqualTo($round->ends_at)) {
                self::endRound($round);
            }

            return;
        }

        if (now()->greaterThanOrEqualTo($round->revealed_at->copy()->addSeconds(self::REVEAL_DELAY_SECONDS))) {
            if ($round->round_number >= $room->rounds_total) {
                self::finishGame($room);
            } else {
                self::startNextRound($room->fresh());
            }
        }
    }

    public static function startNextRound(GameRoom $room): void
    {
        $expectedRoundNumber = $room->current_round;

        $usedTrackIds = $room->rounds()->pluck('track_id');
        $categoryIds = $room->categories()->pluck('categories.id');

        $track = Track::whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds))
            ->whereNotIn('id', $usedTrackIds)
            ->inRandomOrder()
            ->first();

        if (! $track) {
            self::finishGame($room);

            return;
        }

        self::refreshPreviewUrl($track);
        $categoryId = $track->categories()
            ->whereIn('categories.id', $categoryIds)
            ->value('categories.id');

        // Atomic guard: state-polling (from every connected player) and the delayed
        // job can both try to start the next round around the same time — only the
        // first one to land this conditional update actually creates the round.
        $updated = GameRoom::whereKey($room->id)->where('current_round', $expectedRoundNumber)
            ->update(['current_round' => $expectedRoundNumber + 1]);

        if ($updated === 0) {
            return;
        }

        $roundNumber = $expectedRoundNumber + 1;
        $startedAt = now();

        $round = $room->rounds()->create([
            'track_id' => $track->id,
            'category_id' => $categoryId,
            'round_number' => $roundNumber,
            'started_at' => $startedAt,
            'ends_at' => $startedAt->copy()->addSeconds($room->seconds_per_round),
        ]);

        SafeBroadcast::send(new RoundStarted($round));

        EndRoundIfNotRevealed::dispatch($round)->delay(now()->addSeconds($room->seconds_per_round + 1));
    }

    // Deezer preview URLs are signed and expire within a day or so, so a track
    // imported yesterday would otherwise be served with a dead link today. Refresh
    // it right before it's needed rather than trusting whatever was stored at import
    // time. Keeps the old URL (and lets the round proceed) if Deezer can't be reached.
    private static function refreshPreviewUrl(Track $track): void
    {
        if ($track->source !== 'deezer') {
            return;
        }

        $fresh = app(DeezerClient::class)->fetchPreviewUrl($track->source_id);

        if ($fresh) {
            $track->update(['preview_url' => $fresh]);
        }
    }

    public static function endRound(GameRound $round): void
    {
        $updated = GameRound::whereKey($round->id)->whereNull('revealed_at')->update(['revealed_at' => now()]);

        if ($updated === 0) {
            return;
        }

        $round->refresh();

        SafeBroadcast::send(new RoundEnded($round));

        $room = $round->room;

        if ($round->round_number >= $room->rounds_total) {
            FinishGameJob::dispatch($room)->delay(now()->addSeconds(self::REVEAL_DELAY_SECONDS));
        } else {
            StartNextRoundJob::dispatch($room)->delay(now()->addSeconds(self::REVEAL_DELAY_SECONDS));
        }
    }

    public static function finishGame(GameRoom $room): void
    {
        // Same atomic-guard idea as startNextRound: polling and the delayed job
        // can race to finish the same room.
        $updated = GameRoom::whereKey($room->id)->where('status', 'playing')->update(['status' => 'finished']);

        if ($updated === 0) {
            return;
        }

        $room->refresh();

        SafeBroadcast::send(new GameEnded($room));
    }
}
