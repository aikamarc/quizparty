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
        ]);

        $lines = collect(explode("\n", $request->string('list')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        $results = $lines->map(fn (string $line) => [
            'input' => $line,
            'match' => $deezer->search($line),
        ]);

        return Inertia::render('Admin/Tracks/ImportPreview', [
            'results' => $results,
            'categories' => Category::whereNull('parent_id')->with('children')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tracks' => ['required', 'array'],
            'tracks.*.source_id' => ['required', 'string'],
            'tracks.*.title' => ['required', 'string'],
            'tracks.*.artist' => ['required', 'string'],
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
                    'album' => $track['album'] ?? null,
                    'cover_url' => $track['cover_url'] ?? null,
                    'preview_url' => $track['preview_url'],
                    'duration_seconds' => $track['duration_seconds'] ?? null,
                    'added_by' => $request->user()->id,
                ]
            );

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
