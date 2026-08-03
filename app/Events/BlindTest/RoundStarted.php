<?php

namespace App\Events\BlindTest;

use App\Models\BlindTest\GameRound;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoundStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameRound $round)
    {
        $this->round->loadMissing('track:id,preview_url', 'category:id,name');
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
        return 'round.started';
    }

    /**
     * Deliberately omits the track's title/artist/cover — those must stay
     * hidden from clients until the round is revealed.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'round_number' => $this->round->round_number,
            'started_at' => $this->round->started_at->toIso8601String(),
            'preview_url' => $this->round->track->preview_url,
            'category' => $this->round->category?->name,
        ];
    }
}
