<?php

namespace App\Events\BlindTest;

use App\Models\BlindTest\GameRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameRoom $room)
    {
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
        return 'game.started';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'status' => $this->room->status,
        ];
    }
}
