<?php

namespace App\Console\Commands\BlindTest;

use App\Services\StaleGameRoomCleaner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('games:cleanup-stale-rooms')]
#[Description('Delete inactive lobby and finished rooms after one minute')]
class CleanupStaleRooms extends Command
{
    public function handle(StaleGameRoomCleaner $cleaner): int
    {
        $count=$cleaner->cleanup();
        $this->info(sprintf('Cleaned up %d stale room(s).',$count));

        return self::SUCCESS;
    }
}
