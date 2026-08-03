<?php

namespace App\Http\Controllers\BlindTest;

use App\Events\BlindTest\AddedToRoom;
use App\Events\BlindTest\GameStarted;
use App\Events\BlindTest\PlayerJoinedRoom;
use App\Events\BlindTest\PlayerRemovedFromRoom;
use App\Events\BlindTest\RoomSettingsUpdated;
use App\Http\Controllers\Controller;
use App\Models\BlindTest\GameRoom;
use App\Models\BlindTest\GameRoomPlayer;
use App\Models\Category;
use App\Models\Track;
use App\Models\User;
use App\Services\BlindTest\RoundManager;
use App\Support\SafeBroadcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $rooms = GameRoom::where('status', '!=', 'finished')
            ->select(['id', 'public_id', 'host_id', 'status', 'created_at'])
            ->where(function ($query) use ($user) {
                $query->where('host_id', $user->id)
                    ->orWhereHas('players', fn ($q) => $q->where('user_id', $user->id));
            })
            ->with('host:id,name')
            ->withCount('players')
            ->latest()
            ->get();

        $publicRooms = GameRoom::where('status', 'lobby')
            ->select(['id', 'public_id', 'host_id', 'status', 'created_at'])
            ->where('is_public', true)
            ->whereDoesntHave('players', fn ($q) => $q->where('user_id', $user->id))
            ->with('host:id,name')
            ->withCount('players')
            ->latest()
            ->get();

        return Inertia::render('BlindTest/Index', [
            'rooms' => $rooms,
            'publicRooms' => $publicRooms,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_public' => ['boolean'],
        ]);

        $room = GameRoom::create([
            'code' => GameRoom::generateCode(),
            'host_id' => $request->user()->id,
            'is_public' => $validated['is_public'] ?? false,
            'host_last_seen_at' => now(),
        ]);

        $room->players()->create(['user_id' => $request->user()->id]);

        return redirect()->route('blindtest.rooms.show', $room);
    }

    public function show(Request $request, GameRoom $room): Response
    {
        $user = $request->user();

        $isPlayer = $user && $room->players()->where('user_id', $user->id)->exists();

        // Nudge the game forward if it's due for a transition before building the
        // response, so a fresh page load never shows stale round/game state.
        RoundManager::tick($room);
        $room->refresh();

        $categories = Category::whereNull('parent_id')
            ->with(['children' => fn ($query) => $query->withCount('tracks')->orderBy('name')])
            ->withCount('tracks')
            ->orderBy('name')
            ->get();

        $categories->each(function (Category $category) {
            $categoryIds = collect([$category->id])->merge($category->children->pluck('id'));
            $category->setAttribute('total_tracks_count', Track::whereHas(
                'categories',
                fn ($query) => $query->whereIn('categories.id', $categoryIds),
            )->count());
        });

        $data = [
            'room' => [
                'id' => $room->id,
                'public_id' => $room->public_id,
                'status' => $room->status,
                'is_public' => $room->is_public,
                'host_id' => $room->host_id,
                'rounds_total' => $room->rounds_total,
                'seconds_per_round' => $room->seconds_per_round,
                'anti_cheat' => $room->anti_cheat,
                'lives_per_round' => $room->lives_per_round,
            ],
            'viewerIsPlayer' => $isPlayer,
            'players' => $room->players()->with('user:id,name,profile_photo_path')->get()->map(fn (GameRoomPlayer $player) => [
                'id' => $player->id,
                'score' => $player->score,
                'user' => $player->user->only('id', 'name', 'profile_photo_url'),
            ]),
            'categories' => $categories,
            'selectedCategoryIds' => $room->categories()->pluck('categories.id'),
        ];

        if ($isPlayer) {
            $playerUserIds = $room->players()->pluck('user_id');

            $data['invitableFriends'] = $user->friends()
                ->reject(fn (User $friend) => $playerUserIds->contains($friend->id))
                ->values()
                ->map->only('id', 'name', 'profile_photo_url');
        }

        if ($room->status === 'playing' && ($round = $room->currentRound())) {
            $round->loadMissing('track', 'category:id,name', 'artistFoundBy:id,name', 'titleFoundBy:id,name');
            $playerState = $isPlayer ? $round->playerStates()->firstOrCreate(
                ['user_id' => $user->id],
                ['lives_remaining' => $room->lives_per_round],
            ) : null;

            $data['currentRound'] = [
                'round_number' => $round->round_number,
                'started_at' => $round->started_at->toIso8601String(),
                'preview_url' => $round->track->preview_url,
                'category' => $round->category?->name,
                'lives_remaining' => $playerState?->lives_remaining,
                'disqualified' => $playerState?->disqualified ?? false,
                'revealed' => $round->revealed_at !== null,
                'track' => $round->revealed_at !== null ? [
                    ...$round->track->only('title', 'artist', 'cover_url'),
                    'answer_mode' => $round->track->answer_mode,
                    'answer' => $round->track->custom_answer ?: $round->track->title,
                ] : null,
                'artist_found' => $round->artist_found_by ? ['value' => $round->track->artist, 'found_by' => $round->artistFoundBy->only('id', 'name')] : null,
                'title_found' => $round->title_found_by ? ['value' => $round->track->title, 'found_by' => $round->titleFoundBy->only('id', 'name')] : null,
            ];
        }

        return Inertia::render('BlindTest/Room', $data);
    }

    public function resolveCode(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'size:6']]);
        $room = GameRoom::where('code', strtoupper($validated['code']))->first();

        if (! $room) {
            throw ValidationException::withMessages([
                'code' => __('No room matches this code.'),
            ]);
        }

        return redirect()->route('blindtest.rooms.show', $room);
    }

    public function join(Request $request, GameRoom $room): RedirectResponse
    {
        if ($room->status !== 'lobby') {
            throw ValidationException::withMessages([
                'join' => __('This game can no longer be joined.'),
            ]);
        }

        $this->addPlayer($room, $request->user());

        return back();
    }

    public function guestJoin(Request $request, GameRoom $room): RedirectResponse
    {
        if ($room->status !== 'lobby') {
            throw ValidationException::withMessages([
                'join' => __('This game can no longer be joined.'),
            ]);
        }

        $user = $request->user();

        if (! $user) {
            $locale = app()->getLocale();

            $user = User::forceCreate([
                'name' => ($locale === 'fr' ? 'Invité' : 'Guest').' '.random_int(1000, 9999),
                'email' => 'guest-'.Str::uuid().'@guests.quizparty.internal',
                'password' => Hash::make(Str::random(40)),
                'is_guest' => true,
                'email_verified_at' => now(),
            ]);

            Auth::login($user, remember: true);
        }

        $this->addPlayer($room, $user);

        return redirect()->route('blindtest.rooms.show', $room);
    }

    public function heartbeat(Request $request, GameRoom $room): JsonResponse
    {
        if ($room->host_id === $request->user()->id) {
            $room->update(['host_last_seen_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    public function secretCode(Request $request, GameRoom $room): JsonResponse
    {
        abort_unless($room->host_id === $request->user()->id, 403);

        return response()->json(['code' => $room->code]);
    }

    // Polled by the frontend every couple seconds as a resilient fallback to the
    // WebSocket broadcasts — real-time push is a nice-to-have, this endpoint is
    // what actually guarantees the game stays in sync regardless of whether
    // Reverb is reachable in a given environment.
    public function state(Request $request, GameRoom $room): JsonResponse
    {
        abort_unless($room->players()->where('user_id', $request->user()->id)->exists(), 403);

        // Nudge the game forward if it's due for a transition — this is what makes
        // round timeouts, reveals, and round-advancing work reliably even when the
        // queue worker isn't around to process the delayed jobs.
        RoundManager::tick($room);
        $room->refresh();

        $data = [
            'status' => $room->status,
            'players' => $room->players()->with('user:id,name,profile_photo_path')->get()->map(fn (GameRoomPlayer $player) => [
                'id' => $player->id,
                'score' => $player->score,
                'user' => $player->user->only('id', 'name', 'profile_photo_url'),
            ]),
        ];

        if ($room->status === 'playing' && ($round = $room->currentRound())) {
            $round->loadMissing('track', 'category:id,name', 'artistFoundBy:id,name', 'titleFoundBy:id,name');
            $playerState = $round->playerStates()->firstOrCreate(
                ['user_id' => $request->user()->id],
                ['lives_remaining' => $room->lives_per_round],
            );

            $data['round'] = [
                'round_number' => $round->round_number,
                'started_at' => $round->started_at->toIso8601String(),
                'preview_url' => $round->track->preview_url,
                'category' => $round->category?->name,
                'lives_remaining' => $playerState->lives_remaining,
                'disqualified' => $playerState->disqualified,
                'revealed' => $round->revealed_at !== null,
                'track' => $round->revealed_at !== null ? [
                    ...$round->track->only('title', 'artist', 'cover_url'),
                    'answer_mode' => $round->track->answer_mode,
                    'answer' => $round->track->custom_answer ?: $round->track->title,
                ] : null,
                // Exposed as soon as found (not only on full reveal) so everyone sees
                // a correct artist/title guess live, before the round timer ends.
                'artist_found' => $round->artist_found_by ? ['value' => $round->track->artist, 'found_by' => $round->artistFoundBy->only('id', 'name')] : null,
                'title_found' => $round->title_found_by ? ['value' => $round->track->title, 'found_by' => $round->titleFoundBy->only('id', 'name')] : null,
            ];
        }

        return response()->json($data);
    }

    private function addPlayer(GameRoom $room, User $user): void
    {
        if (! $room->players()->where('user_id', $user->id)->exists()) {
            $player = $room->players()->create(['user_id' => $user->id]);

            SafeBroadcast::send(new PlayerJoinedRoom($player), toOthers: true);
        }
    }

    public function update(Request $request, GameRoom $room): RedirectResponse|JsonResponse
    {
        abort_unless($room->host_id === $request->user()->id, 403);

        $validated = $request->validate([
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'rounds_total' => ['required', 'integer', 'min:1', 'max:50'],
            'seconds_per_round' => ['required', 'integer', 'min:5', 'max:30'],
            'is_public' => ['boolean'],
            'anti_cheat' => ['boolean'],
            'lives_per_round' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $room->update([
            'rounds_total' => $validated['rounds_total'],
            'seconds_per_round' => $validated['seconds_per_round'],
            'is_public' => $validated['is_public'] ?? $room->is_public,
            'anti_cheat' => $validated['anti_cheat'] ?? $room->anti_cheat,
            'lives_per_round' => $validated['lives_per_round'],
        ]);

        $room->categories()->sync($validated['category_ids'] ?? []);

        SafeBroadcast::send(new RoomSettingsUpdated($room), toOthers: true);

        if ($request->expectsJson()) {
            return response()->json(['saved' => true]);
        }

        return back();
    }

    public function invite(Request $request, GameRoom $room): RedirectResponse
    {
        abort_unless($room->players()->where('user_id', $request->user()->id)->exists(), 403);

        $validated = $request->validate([
            'friend_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $friend = User::findOrFail($validated['friend_id']);

        if (! $request->user()->isFriendsWith($friend)) {
            throw ValidationException::withMessages([
                'friend_id' => __('This player is not in your friends list.'),
            ]);
        }

        if ($room->players()->where('user_id', $friend->id)->exists()) {
            return back();
        }

        $player = $room->players()->create(['user_id' => $friend->id]);

        SafeBroadcast::send(new PlayerJoinedRoom($player), toOthers: true);
        SafeBroadcast::send(new AddedToRoom($room, $friend));

        return back();
    }

    public function kick(Request $request, GameRoom $room, User $player): RedirectResponse
    {
        abort_unless($room->host_id === $request->user()->id, 403);
        abort_if($player->id === $room->host_id, 422, 'The host cannot be removed from the room.');

        $removed = $room->players()->where('user_id', $player->id)->delete();
        abort_unless($removed > 0, 404);

        SafeBroadcast::send(new PlayerRemovedFromRoom($room, $player->id));

        return back();
    }

    public function start(Request $request, GameRoom $room): RedirectResponse
    {
        abort_unless($room->host_id === $request->user()->id, 403);

        if ($room->categories()->count() === 0 || $room->players()->count() < 1) {
            throw ValidationException::withMessages([
                'start' => __('Select at least one category to start the game.'),
            ]);
        }

        $categoryIds = $room->categories()->pluck('categories.id');
        $availableTracks = Track::whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds))->count();

        if ($availableTracks === 0) {
            throw ValidationException::withMessages([
                'start' => __('No tracks are associated with the selected categories.'),
            ]);
        }

        $room->update(['status' => 'playing', 'started_at' => now()]);

        SafeBroadcast::send(new GameStarted($room), toOthers: true);

        RoundManager::startNextRound($room->fresh());

        return back();
    }
}
