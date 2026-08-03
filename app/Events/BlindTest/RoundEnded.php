<?php

namespace App\Events\BlindTest;

use App\Models\BlindTest\GameRound;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoundEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameRound $round)
    {
        $this->round->loadMissing(['track', 'artistFoundBy:id,name', 'titleFoundBy:id,name']);
        $this->round->room->load('players.user:id,name,profile_photo_path');
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
        return 'round.ended';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'round_number' => $this->round->round_number,
            'track' => [
                'title' => $this->round->track->title,
                'artist' => $this->round->track->artist,
                'cover_url' => $this->round->track->cover_url,
            ],
            'artist_found_by' => $this->round->artistFoundBy?->only('id', 'name'),
            'title_found_by' => $this->round->titleFoundBy?->only('id', 'name'),
            'players' => $this->round->room->players->map(fn ($player) => [
                'id' => $player->id,
                'score' => $player->score,
                'user' => $player->user->only('id', 'name', 'profile_photo_url'),
            ]),
        ];
    }
}
