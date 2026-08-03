<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DeezerClient
{
    /**
     * Search Deezer for a track and return the best match, normalized.
     *
     * @return array{source: string, source_id: string, title: string, artist: string, album: ?string, cover_url: ?string, preview_url: string, duration_seconds: ?int}|null
     */
    public function search(string $query): ?array
    {
        $response = Http::get('https://api.deezer.com/search', ['q' => $query]);

        if (! $response->ok()) {
            return null;
        }

        $track = collect($response->json('data'))
            ->first(fn (array $track) => filled($track['preview'] ?? null));

        if (! $track) {
            return null;
        }

        return [
            'source' => 'deezer',
            'source_id' => (string) $track['id'],
            'title' => $track['title'],
            'artist' => $track['artist']['name'] ?? '',
            'album' => $track['album']['title'] ?? null,
            'cover_url' => $track['album']['cover_medium'] ?? null,
            'preview_url' => $track['preview'],
            'duration_seconds' => $track['duration'] ?? null,
        ];
    }

    // Deezer's `preview` URLs are signed and expire (~24h), so a URL stored at import
    // time goes dead by the next day. Callers should re-fetch a live one right before
    // it's actually needed (e.g. when a round starts) rather than trusting a stored value.
    public function fetchPreviewUrl(string $sourceId): ?string
    {
        $response = Http::get("https://api.deezer.com/track/{$sourceId}");

        if (! $response->ok()) {
            return null;
        }

        return $response->json('preview') ?: null;
    }
}
