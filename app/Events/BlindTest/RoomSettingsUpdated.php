<?php

namespace App\Events\BlindTest;

use App\Models\BlindTest\GameRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomSettingsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameRoom $room)
    {
        $this->room->loadMissing('categories:id,name,parent_id');
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
        return 'settings.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'rounds_total' => $this->room->rounds_total,
            'seconds_per_round' => $this->room->seconds_per_round,
            'anti_cheat' => $this->room->anti_cheat,
            'lives_per_round' => $this->room->lives_per_round,
            'categories' => $this->room->categories,
        ];
    }
}
