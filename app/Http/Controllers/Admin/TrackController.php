<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search'));
        $perPage = min(100, max(10, $request->integer('per_page', 25)));

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
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Tracks/Index', [
            'tracks' => $tracks,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
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
        ]);

        $track->categories()->sync($validated['category_ids'] ?? []);

        return redirect()->route('admin.tracks.index');
    }

    public function destroy(Track $track): RedirectResponse
    {
        $track->delete();

        return back();
    }
}
