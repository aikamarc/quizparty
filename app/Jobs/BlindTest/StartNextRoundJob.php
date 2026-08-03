<?php

namespace App\Jobs\BlindTest;

use App\Models\BlindTest\GameRoom;
use App\Services\BlindTest\RoundManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StartNextRoundJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public GameRoom $room)
    {
    }

    public function handle(): void
    {
        $this->room->refresh();

        if ($this->room->status === 'playing') {
            RoundManager::startNextRound($this->room);
        }
    }
}
