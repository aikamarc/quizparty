<?php

namespace App\Jobs;

use App\Models\Track;
use App\Services\DeezerClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveBrokenTracks implements ShouldQueue
{
    use Queueable;

    /** @param array<int, int> $trackIds */
    public function __construct(public array $trackIds)
    {
    }

    public function handle(DeezerClient $deezer): void
    {
        Track::whereIn('id', $this->trackIds)
            ->where('source', 'deezer')
            ->get()
            ->each(function (Track $track) use ($deezer) {
                if (! filled($track->source_id)) {
                    return;
                }

                $preview = $deezer->inspectPreview($track->source_id);

                if ($preview === false) {
                    $track->delete();
                } elseif (is_string($preview) && $preview !== $track->preview_url) {
                    $track->update(['preview_url' => $preview]);
                }
            });
    }
}
