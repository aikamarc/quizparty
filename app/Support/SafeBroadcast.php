<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class SafeBroadcast
{
    // Real-time updates (Reverb) are a nice-to-have, not a requirement for the
    // underlying action (starting a game, updating settings, ...) to succeed.
    // If the broadcast server is unreachable (e.g. not running in local dev),
    // log it and move on instead of failing the whole request.
    public static function send(object $event, bool $toOthers = false): void
    {
        try {
            $pending = broadcast($event);

            if ($toOthers) {
                $pending->toOthers();
            }

            unset($pending);
        } catch (Throwable $e) {
            Log::warning('Broadcast failed: '.$e->getMessage(), ['event' => $event::class]);
        }
    }
}
