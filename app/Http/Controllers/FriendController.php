<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FriendController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $friendsFromSent = $user->sentFriendships()->where('status', 'accepted')->with('recipient')->get()
            ->map(fn (Friendship $friendship) => [
                'friendship_id' => $friendship->id,
                ...$friendship->recipient->only('id', 'name', 'email', 'profile_photo_url'),
            ]);

        $friendsFromReceived = $user->receivedFriendships()->where('status', 'accepted')->with('requester')->get()
            ->map(fn (Friendship $friendship) => [
                'friendship_id' => $friendship->id,
                ...$friendship->requester->only('id', 'name', 'email', 'profile_photo_url'),
            ]);

        return Inertia::render('Friends/Index', [
            'friends' => $friendsFromSent->concat($friendsFromReceived)->unique('id')->values(),
            'receivedRequests' => $user->pendingReceivedRequests()->get()->map(fn (Friendship $friendship) => [
                'id' => $friendship->id,
                'user' => $friendship->requester->only('id', 'name', 'email', 'profile_photo_url'),
            ]),
            'sentRequests' => $user->pendingSentRequests()->get()->map(fn (Friendship $friendship) => [
                'id' => $friendship->id,
                'user' => $friendship->recipient->only('id', 'name', 'email', 'profile_photo_url'),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = $request->user();

        $target = User::where('email', $request->input('email'))->first();

        if (! $target) {
            throw ValidationException::withMessages([
                'email' => __('No user was found with this email address.'),
            ]);
        }

        if ($target->is($user)) {
            throw ValidationException::withMessages([
                'email' => __('You cannot add yourself as a friend.'),
            ]);
        }

        $alreadyLinked = Friendship::where(function ($query) use ($user, $target) {
            $query->where('user_id', $user->id)->where('friend_id', $target->id);
        })->orWhere(function ($query) use ($user, $target) {
            $query->where('user_id', $target->id)->where('friend_id', $user->id);
        })->exists();

        if ($alreadyLinked) {
            throw ValidationException::withMessages([
                'email' => __('You are already friends with this user, or a request is already pending.'),
            ]);
        }

        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $target->id,
            'status' => 'pending',
        ]);

        return back();
    }

    public function update(Request $request, Friendship $friendship): RedirectResponse
    {
        abort_unless($friendship->friend_id === $request->user()->id, 403);

        $request->validate([
            'action' => ['required', 'in:accept,decline'],
        ]);

        if ($request->input('action') === 'accept') {
            $friendship->update(['status' => 'accepted']);
        } else {
            $friendship->delete();
        }

        return back();
    }

    public function destroy(Request $request, Friendship $friendship): RedirectResponse
    {
        $userId = $request->user()->id;

        abort_unless(in_array($userId, [$friendship->user_id, $friendship->friend_id], true), 403);

        $friendship->delete();

        return back();
    }
}
