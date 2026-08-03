<?php

namespace App\Events\BlindTest;

use App\Models\BlindTest\GameRoomPlayer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerJoinedRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameRoomPlayer $player)
    {
        $this->player->loadMissing('user:id,name,profile_photo_path');
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('blindtest.room.'.$this->player->game_room_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'player.joined';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'player' => [
                'id' => $this->player->id,
                'score' => $this->player->score,
                'user' => [
                    'id' => $this->player->user->id,
                    'name' => $this->player->user->name,
                    'profile_photo_url' => $this->player->user->profile_photo_url,
                ],
            ],
        ];
    }
}
