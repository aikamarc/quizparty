<?php

namespace App\Console\Commands\BlindTest;

use App\Events\BlindTest\RoomDeleted;
use App\Models\BlindTest\GameRoom;
use App\Models\BlindTest\GameRoomPlayer;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('blindtest:cleanup-stale-rooms')]
#[Description('Delete lobby rooms that were never started and whose host has gone offline')]
class CleanupStaleRooms extends Command
{
    private const LOBBY_TIMEOUT_MINUTES = 5;

    private const HOST_OFFLINE_SECONDS = 90;

    public function handle(): int
    {
        $staleRooms = GameRoom::where('status', 'lobby')
            ->where('created_at', '<=', now()->subMinutes(self::LOBBY_TIMEOUT_MINUTES))
            ->where(function ($query) {
                $query->whereNull('host_last_seen_at')
                    ->orWhere('host_last_seen_at', '<=', now()->subSeconds(self::HOST_OFFLINE_SECONDS));
            })
            ->get();

        foreach ($staleRooms as $room) {
            $guestPlayerUserIds = $room->players()
                ->whereHas('user', fn ($query) => $query->where('is_guest', true))
                ->pluck('user_id');

            SafeBroadcast::send(new RoomDeleted($room));

            $room->delete();

            $orphanedGuestIds = $guestPlayerUserIds->filter(
                fn ($id) => ! GameRoomPlayer::where('user_id', $id)->exists()
            );

            User::whereIn('id', $orphanedGuestIds)->delete();
        }

        $this->info(sprintf('Cleaned up %d stale room(s).', $staleRooms->count()));

        return self::SUCCESS;
    }
}
