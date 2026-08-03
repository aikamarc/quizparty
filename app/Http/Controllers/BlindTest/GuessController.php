<?php

namespace App\Http\Controllers\BlindTest;

use App\Events\BlindTest\GuessRevealed;
use App\Http\Controllers\Controller;
use App\Models\BlindTest\GameRoom;
use App\Models\BlindTest\GameRoundPlayerState;
use App\Services\BlindTest\GuessChecker;
use App\Services\BlindTest\RoundManager;
use App\Support\SafeBroadcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuessController extends Controller
{
    public function store(Request $request, GameRoom $room): JsonResponse
    {
        abort_unless($room->players()->where('user_id', $request->user()->id)->exists(), 403);

        $validated = $request->validate([
            'guess' => ['required', 'string', 'max:255'],
        ]);

        if ($room->status !== 'playing') {
            return response()->json(['status' => 'inactive'], 422);
        }

        $round = $room->currentRound();

        if (! $round || $round->revealed_at !== null) {
            return response()->json(['status' => 'no_active_round'], 422);
        }

        $round->loadMissing('track');
        $user = $request->user();
        $guess = $validated['guess'];
        $playerState = GameRoundPlayerState::firstOrCreate(
            ['game_round_id' => $round->id, 'user_id' => $user->id],
            ['lives_remaining' => $room->lives_per_round],
        );

        if ($playerState->disqualified || $playerState->lives_remaining < 1) {
            return response()->json([
                'status' => $playerState->disqualified ? 'disqualified' : 'no_lives',
                'lives_remaining' => $playerState->lives_remaining,
                'disqualified' => $playerState->disqualified,
            ], 423);
        }

        $foundArtist = false;
        $foundTitle = false;
        $titleAnswer = $round->track->answer_mode === 'title_only'
            ? ($round->track->custom_answer ?: $round->track->title)
            : $round->track->title;
        $matchesArtist = $round->track->answer_mode === 'artist_title'
            && GuessChecker::matches($guess, $round->track->artist);
        $matchesTitle = GuessChecker::matches($guess, $titleAnswer);

        if ($round->artist_found_by === null && $matchesArtist) {
            $round->update(['artist_found_by' => $user->id, 'artist_found_at' => now()]);
            $room->players()->where('user_id', $user->id)->increment('score');
            SafeBroadcast::send(new GuessRevealed($round, 'artist', $round->track->artist, $user));
            $foundArtist = true;
        }

        if ($round->title_found_by === null && $matchesTitle) {
            $round->update(['title_found_by' => $user->id, 'title_found_at' => now()]);
            $room->players()->where('user_id', $user->id)->increment('score');
            SafeBroadcast::send(new GuessRevealed($round, 'title', $titleAnswer, $user));
            $foundTitle = true;
        }

        if (($round->track->answer_mode === 'title_only' && $round->title_found_by !== null)
            || ($round->track->answer_mode === 'artist_title' && $round->isFullyFound())) {
            RoundManager::endRound($round);
        }

        $wrong = ! $matchesArtist && ! $matchesTitle;
        if ($wrong) {
            $playerState->update(['lives_remaining' => max(0, $playerState->lives_remaining - 1)]);
        }

        return response()->json([
            'found_artist' => $foundArtist,
            'found_title' => $foundTitle,
            'artist' => $foundArtist ? $round->track->artist : null,
            'title' => $foundTitle ? $titleAnswer : null,
            'wrong' => $wrong,
            'lives_remaining' => $playerState->lives_remaining,
            'disqualified' => $playerState->disqualified,
        ]);
    }

    public function disqualify(Request $request, GameRoom $room): JsonResponse
    {
        abort_unless($room->players()->where('user_id', $request->user()->id)->exists(), 403);

        if (! $room->anti_cheat || $room->status !== 'playing' || ! ($round = $room->currentRound())) {
            return response()->json(['disqualified' => false]);
        }

        $state = GameRoundPlayerState::firstOrCreate(
            ['game_round_id' => $round->id, 'user_id' => $request->user()->id],
            ['lives_remaining' => $room->lives_per_round],
        );
        $state->update(['disqualified' => true]);

        return response()->json([
            'disqualified' => true,
            'lives_remaining' => $state->lives_remaining,
        ]);
    }
}
