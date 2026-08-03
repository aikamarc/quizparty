<?php

namespace Tests\Feature;

use App\Jobs\RemoveBrokenTracks;
use App\Models\Category;
use App\Models\Track;
use App\Models\User;
use App\Services\DeezerClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BrokenTracksCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_queue_the_cleanup_in_small_batches(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (range(1, 26) as $id) {
            Track::create([
                'title' => "Track {$id}",
                'artist' => 'Artist',
                'preview_url' => 'https://old.test/preview.mp3',
                'source' => 'deezer',
                'source_id' => (string) $id,
            ]);
        }

        $this->actingAs($admin)
            ->delete(route('admin.tracks.destroy-broken'))
            ->assertRedirect()
            ->assertSessionHas('flash.broken_tracks_scan_queued', 26);

        Queue::assertPushed(RemoveBrokenTracks::class, 2);
    }

    public function test_cleanup_deletes_only_tracks_deezer_confirms_are_broken(): void
    {
        $broken = $this->track('broken');
        $unreachable = $this->track('unreachable');
        $playable = $this->track('playable');

        Http::fake(function (Request $request) {
            return match (basename($request->url())) {
                'broken' => Http::response(['id' => 'broken', 'preview' => '']),
                'playable' => Http::response(['id' => 'playable', 'preview' => 'https://fresh.test/preview.mp3']),
                default => Http::response([], 503),
            };
        });

        (new RemoveBrokenTracks([$broken->id, $unreachable->id, $playable->id]))
            ->handle(new DeezerClient);

        $this->assertModelMissing($broken);
        $this->assertModelExists($unreachable);
        $this->assertSame('https://fresh.test/preview.mp3', $playable->fresh()->preview_url);
    }

    public function test_admin_can_delete_all_tracks_assigned_to_the_fr_category(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fr = Category::create(['name' => 'FR']);
        $otherCategory = Category::create(['name' => 'US']);
        $frenchTrack = $this->track('french');
        $otherTrack = $this->track('other');
        $frenchTrack->categories()->attach([$fr->id, $otherCategory->id]);
        $otherTrack->categories()->attach($otherCategory);

        $this->actingAs($admin)
            ->delete(route('admin.tracks.destroy-fr-category'))
            ->assertRedirect()
            ->assertSessionHas('flash.fr_tracks_deleted', 1);

        $this->assertModelMissing($frenchTrack);
        $this->assertModelExists($otherTrack);
        $this->assertModelExists($fr);
    }

    private function track(string $sourceId): Track
    {
        return Track::create([
            'title' => 'Track',
            'artist' => 'Artist',
            'preview_url' => 'https://old.test/preview.mp3',
            'source' => 'deezer',
            'source_id' => $sourceId,
        ]);
    }
}
