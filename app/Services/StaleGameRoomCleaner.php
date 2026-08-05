<?php

namespace App\Services;

use App\Events\BlindTest\RoomDeleted;
use App\Models\BlindTest\GameRoom;
use App\Models\BlindTest\GameRoomPlayer;
use App\Models\CultureQuiz\Room as CultureQuizRoom;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\Cache;

class StaleGameRoomCleaner
{
    private const INACTIVITY_SECONDS = 60;

    public function cleanupIfDue(): int
    {
        if (! Cache::add('games:stale-room-cleanup', true, now()->addSeconds(30))) return 0;

        return $this->cleanup();
    }

    public function cleanup(): int
    {
        $cutoff=now()->subSeconds(self::INACTIVITY_SECONDS);
        $staleBlindRooms=$this->staleQuery(GameRoom::query(),$cutoff)->get();

        foreach ($staleBlindRooms as $room) {
            $guestPlayerUserIds=$room->players()->whereHas('user',fn($query)=>$query->where('is_guest',true))->pluck('user_id');
            SafeBroadcast::send(new RoomDeleted($room));
            $room->delete();
            $orphanedGuestIds=$guestPlayerUserIds->filter(fn($id)=>!GameRoomPlayer::where('user_id',$id)->exists());
            User::whereIn('id',$orphanedGuestIds)->delete();
        }

        $staleCultureRooms=$this->staleQuery(CultureQuizRoom::query(),$cutoff)->get();
        CultureQuizRoom::whereKey($staleCultureRooms->modelKeys())->delete();

        return $staleBlindRooms->count()+$staleCultureRooms->count();
    }

    private function staleQuery($query,$cutoff)
    {
        return $query->whereIn('status',['lobby','finished'])->where(function($query) use($cutoff) {
            $query->where('last_activity_at','<=',$cutoff)
                ->orWhere(fn($fallback)=>$fallback->whereNull('last_activity_at')->where('updated_at','<=',$cutoff));
        });
    }
}
