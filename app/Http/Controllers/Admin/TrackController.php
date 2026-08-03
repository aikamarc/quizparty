<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RemoveBrokenTracks;
use App\Models\Category;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrackController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search'));
        $perPage = min(100, max(10, $request->integer('per_page', 25)));
        $category = Category::with('children:id,parent_id')->find($request->integer('category_id'));
        $categoryIds = $category
            ? collect([$category->id])->merge($category->children->pluck('id'))
            : collect();

        $tracks = Track::query()
            ->select(['id', 'title', 'artist', 'album', 'cover_url', 'preview_url', 'duration_seconds'])
            ->with('categories:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('artist', 'like', '%'.$search.'%')
                        ->orWhere('album', 'like', '%'.$search.'%');
                });
            })
            ->when($categoryIds->isNotEmpty(), function ($query) use ($categoryIds) {
                $query->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds));
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Tracks/Index', [
            'tracks' => $tracks,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'category_id' => $category?->id,
            ],
            'categories' => Category::whereNull('parent_id')->with('children:id,name,parent_id')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Track $track): Response
    {
        return Inertia::render('Admin/Tracks/Edit', [
            'track' => $track->load('categories'),
            'categories' => Category::whereNull('parent_id')->with('children')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Track $track): RedirectResponse
    {
        $validated = $request->validate([
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'answer_mode' => ['required', 'in:artist_title,title_only'],
            'custom_answer' => ['nullable', 'required_if:answer_mode,title_only', 'string', 'max:255'],
        ]);

        $track->update([
            'answer_mode' => $validated['answer_mode'],
            'custom_answer' => $validated['answer_mode'] === 'title_only' ? $validated['custom_answer'] : null,
        ]);
        $track->categories()->sync($validated['category_ids'] ?? []);

        return redirect()->route('admin.tracks.index');
    }

    public function destroy(Track $track): RedirectResponse
    {
        $track->delete();

        return back();
    }

    public function destroyBroken(): RedirectResponse
    {
        $queued = 0;

        Track::where('source', 'deezer')
            ->whereNotNull('source_id')
            ->select('id')
            ->chunkById(25, function ($tracks) use (&$queued) {
                RemoveBrokenTracks::dispatch($tracks->pluck('id')->all());
                $queued += $tracks->count();
            });

        return back()->with('flash', ['broken_tracks_scan_queued' => $queued]);
    }

    public function destroyFrenchCategoryTracks(): RedirectResponse
    {
        $categoryIds = Category::where('name', 'FR')->pluck('id');

        $deleted = $categoryIds->isEmpty()
            ? 0
            : Track::whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds))->delete();

        return back()->with('flash', ['fr_tracks_deleted' => $deleted]);
    }

    public function tempmarc()
    {
        $tracks = Track::where('id', '>', 186)->get();
        $cat = Category::where('name', 'FR')->first()->id;

        foreach($tracks as $track)
        {
            DB::table('category_track')->insert([
                'category_id' => $cat,
                'track_id' => $track->id,
            ]);
        }

        dd('DONE');
    }
}
