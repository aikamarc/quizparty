<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Track;
use App\Services\DeezerClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TrackImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Admin/Tracks/Import');
    }

    public function preview(Request $request, DeezerClient $deezer): Response
    {
        $request->validate([
            'list' => ['required', 'string'],
            'answer_mode' => ['required', 'in:artist_title,title_only'],
        ]);

        $answerMode = $request->string('answer_mode')->toString();

        $lines = collect(explode("\n", $request->string('list')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        $results = $lines->map(function (string $line) use ($answerMode, $deezer) {
            $parts = $answerMode === 'title_only'
                ? preg_split('/\s+(?:-|–|—)\s+/u', $line, 3)
                : [];
            $parts = collect($parts)->map(fn ($part) => trim($part, " \t\n\r\0\x0B*_"))->all();
            $answer = $parts[0] ?? null;
            $title = $parts[1] ?? null;
            $artist = $parts[2] ?? null;

            // Exclude the expected answer from the Deezer query. Otherwise a
            // franchise name such as "Toy Story" can skew the search results.
            $search = filled($title)
                ? 'track:"'.addcslashes($title, '"').'"'.(filled($artist) ? ' artist:"'.addcslashes($artist, '"').'"' : '')
                : $line;

            return [
                'input' => $line,
                'answer_mode' => $answerMode,
                'custom_answer' => $answerMode === 'title_only' ? trim((string) $answer) : null,
                'match' => $deezer->search(trim((string) $search)),
            ];
        });

        $categories = Category::whereNull('parent_id')
            ->with(['children' => fn ($query) => $query->withCount('tracks')->orderBy('name')])
            ->withCount('tracks')
            ->orderBy('name')
            ->get();

        $categories->each(function (Category $category) {
            $categoryIds = collect([$category->id])->merge($category->children->pluck('id'));
            $category->setAttribute('total_tracks_count', Track::whereHas(
                'categories',
                fn ($query) => $query->whereIn('categories.id', $categoryIds),
            )->count());
        });

        return Inertia::render('Admin/Tracks/ImportPreview', [
            'results' => $results,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tracks' => ['required', 'array'],
            'tracks.*.source_id' => ['required', 'string'],
            'tracks.*.title' => ['required', 'string'],
            'tracks.*.artist' => ['required', 'string'],
            'tracks.*.answer_mode' => ['required', 'in:artist_title,title_only'],
            'tracks.*.custom_answer' => ['nullable', 'required_if:tracks.*.answer_mode,title_only', 'string', 'max:255'],
            'tracks.*.album' => ['nullable', 'string'],
            'tracks.*.cover_url' => ['nullable', 'string'],
            'tracks.*.preview_url' => ['required', 'string'],
            'tracks.*.duration_seconds' => ['nullable', 'integer'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $categoryIds = $validated['category_ids'] ?? [];
        $imported = 0;

        foreach ($validated['tracks'] as $track) {
            $created = Track::firstOrCreate(
                ['source' => 'deezer', 'source_id' => $track['source_id']],
                [
                    'title' => $track['title'],
                    'artist' => $track['artist'],
                    'answer_mode' => $track['answer_mode'],
                    'custom_answer' => $track['custom_answer'] ?? null,
                    'album' => $track['album'] ?? null,
                    'cover_url' => $track['cover_url'] ?? null,
                    'preview_url' => $track['preview_url'],
                    'duration_seconds' => $track['duration_seconds'] ?? null,
                    'added_by' => $request->user()->id,
                ]
            );

            if (! $created->wasRecentlyCreated) {
                $created->update([
                    'answer_mode' => $track['answer_mode'],
                    'custom_answer' => $track['custom_answer'] ?? null,
                ]);
            }

            if ($categoryIds) {
                $created->categories()->syncWithoutDetaching($categoryIds);
            }

            if ($created->wasRecentlyCreated) {
                $imported++;
            }
        }

        return redirect()->route('admin.tracks.index')->with('flash', [
            'imported' => $imported,
            'total' => count($validated['tracks']),
        ]);
    }
}
