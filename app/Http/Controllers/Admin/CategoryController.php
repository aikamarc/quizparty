<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::query()
                ->with('parent:id,name')
                ->withCount('tracks')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Category $category): Response
    {
        $tracks = $category->tracks()
            ->select(['tracks.id', 'title', 'artist', 'custom_answer', 'answer_mode', 'album', 'cover_url', 'preview_url'])
            ->orderByRaw("LOWER(CASE WHEN tracks.answer_mode = 'title_only' THEN COALESCE(tracks.custom_answer, tracks.title) ELSE tracks.title END)")
            ->paginate(50);

        return Inertia::render('Admin/Categories/Show', [
            'category' => $category,
            'tracks' => $tracks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'answer_mode' => ['required', 'in:artist_title,title_only'],
            'image' => ['nullable', 'image', 'max:4096'],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($value && Category::whereKey($value)->whereNotNull('parent_id')->exists()) {
                        $fail(__("A subcategory cannot contain another subcategory."));
                    }
                },
            ],
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('category-images', 'public');
        }

        unset($validated['image']);
        Category::create($validated);

        return back();
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'answer_mode' => ['required', 'in:artist_title,title_only'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('category-images', 'public');
        }

        unset($validated['image']);
        $category->update($validated);

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }
        $category->delete();

        return back();
    }
}
