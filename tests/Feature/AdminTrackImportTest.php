<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Track;
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
            'list' => "Toy Story || You’ve Got a Friend in Me || Randy Newman\nToy Story || You’ve Got a Friend in Me",
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

    public function test_category_page_lists_its_elements_alphabetically(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'Movies', 'answer_mode' => 'title_only']);
        $zulu = Track::create($this->trackAttributes('2', 'Zulu song', 'Zulu'));
        $alpha = Track::create($this->trackAttributes('1', 'Alpha song', 'Alpha'));
        $category->tracks()->attach([$zulu->id, $alpha->id]);

        $this->actingAs($admin)
            ->get(route('admin.categories.show', $category))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Categories/Show')
                ->where('category.answer_mode', 'title_only')
                ->where('tracks.data.0.custom_answer', 'Alpha')
                ->where('tracks.data.1.custom_answer', 'Zulu'));
    }

    public function test_import_from_a_category_uses_its_type_and_attaches_the_track(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'Movies', 'answer_mode' => 'title_only']);

        $response = $this->actingAs($admin)->post(route('admin.categories.import.store', $category), [
            'tracks' => [[
                ...$this->trackAttributes('42', 'Main Theme', 'The Composer'),
                'answer_mode' => 'artist_title',
                'custom_answer' => 'Interstellar',
            ]],
        ]);

        $response->assertRedirect(route('admin.categories.show', $category));
        $track = Track::where('source_id', '42')->firstOrFail();
        $this->assertSame('title_only', $track->answer_mode);
        $this->assertSame('Interstellar', $track->custom_answer);
        $this->assertTrue($track->categories()->whereKey($category->id)->exists());
    }

    private function trackAttributes(string $sourceId, string $title, string $customAnswer): array
    {
        return [
            'source' => 'deezer',
            'source_id' => $sourceId,
            'title' => $title,
            'artist' => 'Artist',
            'answer_mode' => 'title_only',
            'custom_answer' => $customAnswer,
            'preview_url' => 'https://example.test/'.$sourceId.'.mp3',
            'duration_seconds' => 30,
            'added_by' => 1,
        ];
    }
}
