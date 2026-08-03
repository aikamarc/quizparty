<?php

namespace App\Jobs\BlindTest;

use App\Models\BlindTest\GameRound;
use App\Services\BlindTest\RoundManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EndRoundIfNotRevealed implements ShouldQueue
{
    use Queueable;

    public function __construct(public GameRound $round)
    {
    }

    public function handle(): void
    {
        RoundManager::endRound($this->round);
    }
}
