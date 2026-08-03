<?php

namespace App\Events\BlindTest;

use App\Models\BlindTest\GameRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerRemovedFromRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameRoom $room, public int $userId)
    {
    }

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('blindtest.room.'.$this->room->id)];
    }

    public function broadcastAs(): string
    {
        return 'player.removed';
    }

    /** @return array<string, int> */
    public function broadcastWith(): array
    {
        return ['user_id' => $this->userId];
    }
}
