<?php

namespace App\Http\Controllers;

use App\Models\Songless\Attempt;
use App\Models\Songless\DailySong;
use App\Models\Track;
use App\Services\Songless\DailyChallenge;
use App\Services\Songless\GuessJudge;
use App\Services\DeezerClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SonglessController extends Controller
{
    public function index(Request $request, DeezerClient $deezer): Response
    {
        $songs = DailyChallenge::forToday();
        $available = $songs->count() === 3;
        $attempts = Attempt::where('user_id', $request->user()->id)
            ->whereIn('daily_song_id', $songs->pluck('id'))
            ->with('guesses')
            ->get()
            ->keyBy('daily_song_id');

        $activePosition = $songs->first(function (DailySong $song) use ($attempts) {
            $attempt = $attempts->get($song->id);

            return ! $attempt || ! $attempt->isComplete();
        })?->position;

        $activeTrack = $songs->firstWhere('position', $activePosition)?->track;
        if ($activeTrack?->source === 'deezer' && filled($activeTrack->source_id)) {
            $freshPreviewUrl = $deezer->fetchPreviewUrl($activeTrack->source_id);

            if ($freshPreviewUrl) {
                $activeTrack->update(['preview_url' => $freshPreviewUrl]);
            }
        }

        return Inertia::render('Songless/Index', [
            'date' => today()->toDateString(),
            'available' => $available,
            'stages' => Attempt::STAGES,
            'completed' => $available && $activePosition === null,
            'songs' => $songs->map(function (DailySong $song) use ($attempts, $activePosition) {
                $attempt = $attempts->get($song->id);
                $isComplete = $attempt?->isComplete() ?? false;
                $isActive = $song->position === $activePosition;

                return [
                    'id' => $song->id,
                    'position' => $song->position,
                    'categories' => $song->track->categories->pluck('name')->values(),
                    'state' => $isComplete ? 'complete' : ($isActive ? 'active' : 'locked'),
                    'preview_url' => $isActive ? $song->track->preview_url : null,
                    'stage' => $attempt?->current_stage ?? 0,
                    'status' => $attempt?->status,
                    'guesses' => $attempt?->guesses->map->only('id', 'stage', 'guess', 'result')->values() ?? [],
                    'result' => $isComplete ? [
                        ...$song->track->only('title', 'artist', 'cover_url'),
                        'percentile' => $this->percentile($attempt),
                        'stage' => $attempt->current_stage,
                        'won' => $attempt->status === 'won',
                    ] : null,
                ];
            })->values(),
        ]);
    }

    public function guess(Request $request, DailySong $song): RedirectResponse
    {
        $validated = $request->validate(
            ['track_id' => ['required', 'integer', 'exists:tracks,id']],
            ['track_id.required' => __('Select one of the suggested tracks.')],
        );

        DB::transaction(function () use ($request, $song, $validated) {
            $attempt = $this->playableAttempt($request, $song);
            $selected = Track::findOrFail($validated['track_id']);
            $result = GuessJudge::selectedTrackResult($selected, $song->track);

            $attempt->guesses()->create([
                'stage' => $attempt->current_stage,
                'guess' => $selected->artist.' — '.$selected->title,
                'result' => $result,
            ]);

            if ($result === 'correct') {
                $attempt->update(['status' => 'won', 'completed_at' => now()]);
            } else {
                $this->advance($attempt);
            }
        });

        return back();
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q'));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $tokens = collect(preg_split('/\s+/', $query))->filter()->take(6);
        $tracks = Track::query()
            ->where(function ($builder) use ($tokens) {
                foreach ($tokens as $token) {
                    $builder->where(function ($part) use ($token) {
                        $part->where('title', 'like', '%'.$token.'%')
                            ->orWhere('artist', 'like', '%'.$token.'%');
                    });
                }
            })
            ->orderBy('artist')
            ->orderBy('title')
            ->limit(20)
            ->get(['id', 'title', 'artist', 'cover_url']);

        return response()->json($tracks);
    }

    public function skip(Request $request, DailySong $song): RedirectResponse
    {
        DB::transaction(function () use ($request, $song) {
            $this->advance($this->playableAttempt($request, $song));
        });

        return back();
    }

    private function playableAttempt(Request $request, DailySong $song): Attempt
    {
        abort_unless($song->play_date->isToday(), 404);

        $hasUnfinishedPreviousSong = DailySong::whereDate('play_date', $song->play_date)
            ->where('position', '<', $song->position)
            ->whereDoesntHave('attempts', fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['won', 'lost']))
            ->exists();

        if ($hasUnfinishedPreviousSong) {
            throw ValidationException::withMessages(['song' => __('Complete the previous song first.')]);
        }

        $attempt = Attempt::firstOrCreate(
            ['daily_song_id' => $song->id, 'user_id' => $request->user()->id],
            ['current_stage' => 0, 'status' => 'in_progress'],
        );

        $attempt = Attempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

        if ($attempt->isComplete()) {
            throw ValidationException::withMessages(['song' => __('This song has already been completed.')]);
        }

        $song->loadMissing('track');

        return $attempt;
    }

    private function advance(Attempt $attempt): void
    {
        if ($attempt->current_stage >= count(Attempt::STAGES) - 1) {
            $attempt->update(['status' => 'lost', 'completed_at' => now()]);

            return;
        }

        $attempt->increment('current_stage');
    }

    private function percentile(Attempt $attempt): int
    {
        if ($attempt->status === 'lost') {
            return 0;
        }

        $completed = Attempt::where('daily_song_id', $attempt->daily_song_id)
            ->whereIn('status', ['won', 'lost']);
        $total = (clone $completed)->count();
        $worse = (clone $completed)->where(function ($query) use ($attempt) {
            $query->where('status', 'lost')
                ->orWhere(fn ($won) => $won->where('status', 'won')->where('current_stage', '>', $attempt->current_stage));
        })->count();

        return (int) round((($worse + 1) / max(1, $total)) * 100);
    }
}
