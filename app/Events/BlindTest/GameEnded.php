<?php

namespace App\Events\BlindTest;

use App\Models\BlindTest\GameRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameRoom $room)
    {
        $this->room->load('players.user:id,name,profile_photo_path');
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('blindtest.room.'.$this->room->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.ended';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'players' => $this->room->players
                ->sortByDesc('score')
                ->values()
                ->map(fn ($player) => [
                    'id' => $player->id,
                    'score' => $player->score,
                    'user' => $player->user->only('id', 'name', 'profile_photo_url'),
                ]),
        ];
    }
}
