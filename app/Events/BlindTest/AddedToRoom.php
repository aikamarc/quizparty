<?php

namespace App\Events\BlindTest;

use App\Models\BlindTest\GameRoom;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AddedToRoom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameRoom $room, public User $friend)
    {
        $this->room->loadMissing('host:id,name');
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->friend->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'blindtest.invited';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'room' => [
                'public_id' => $this->room->public_id,
                'host_name' => $this->room->host->name,
            ],
        ];
    }
}
