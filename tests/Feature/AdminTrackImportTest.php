<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTrackImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_import_uses_optional_artist_to_refine_the_track_search(): void
    {
        $queries = [];

        Http::fake(function (Request $request) use (&$queries) {
            $queries[] = $request->data()['q'];

            return Http::response(['data' => [[
                'id' => 1,
                'title' => 'You’ve Got a Friend in Me',
                'artist' => ['name' => 'Randy Newman'],
                'album' => ['title' => 'Toy Story', 'cover_medium' => null],
                'preview' => 'https://example.test/preview.mp3',
                'duration' => 124,
            ]]]);
        });

        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'Movies']);
        Category::create(['name' => 'Animation', 'parent_id' => $category->id]);

        $this->actingAs($admin)->post(route('admin.tracks.import.preview'), [
            'answer_mode' => 'title_only',
            'list' => "Toy Story - You’ve Got a Friend in Me - Randy Newman\nToy Story - You’ve Got a Friend in Me",
        ])->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Tracks/ImportPreview')
            ->where('results.0.custom_answer', 'Toy Story')
            ->where('results.1.custom_answer', 'Toy Story')
            ->where('categories.0.name', 'Movies')
            ->where('categories.0.total_tracks_count', 0)
            ->where('categories.0.children.0.name', 'Animation')
            ->where('categories.0.children.0.tracks_count', 0));

        $this->assertSame([
            'track:"You’ve Got a Friend in Me" artist:"Randy Newman"',
            'track:"You’ve Got a Friend in Me"',
        ], $queries);
    }
}
