<?php

namespace App\Events\BlindTest;

use App\Models\BlindTest\GameRound;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuessRevealed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GameRound $round,
        public string $field,
        public string $value,
        public User $foundBy,
    ) {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('blindtest.room.'.$this->round->game_room_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'guess.revealed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'round_number' => $this->round->round_number,
            'field' => $this->field,
            'value' => $this->value,
            'found_by' => $this->foundBy->only('id', 'name'),
        ];
    }
}
