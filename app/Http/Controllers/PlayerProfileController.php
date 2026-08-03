<?php

namespace App\Http\Controllers;

use App\Models\BlindTest\GameRoomPlayer;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerProfileController extends Controller
{
    public function show(Request $request, User $user): Response
    {
        abort_if($user->is_guest, 404);

        return Inertia::render('Profile/Player', $this->profileData($request, $user));
    }

    public function own(Request $request): Response
    {
        return Inertia::render('Profile/Player', $this->profileData($request, $request->user()));
    }

    /** @return array<string, mixed> */
    private function profileData(Request $request, User $user): array
    {
        $performances = GameRoomPlayer::query()->where('user_id', $user->id);
        $viewer = $request->user();

        return [
            'player' => [
                ...$user->only('id', 'name', 'profile_photo_url'),
                'joined_at' => $user->created_at?->toDateString(),
                'friends_count' => $user->friends()->count(),
                'games_count' => (clone $performances)->count(),
                'total_score' => (int) (clone $performances)->sum('score'),
                'best_score' => (int) (clone $performances)->max('score'),
            ],
            'isOwnProfile' => $viewer?->is($user) ?? false,
            'isFriend' => $viewer ? $viewer->isFriendsWith($user) : false,
        ];
    }
}
