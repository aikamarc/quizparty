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
            'categories' => Category::whereNull('parent_id')
                ->with('children')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
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
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($category->image_path);
            $validated['image_path'] = $request->file('image')->store('category-images', 'public');
        }

        unset($validated['image']);
        $category->update($validated);

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        Storage::disk('public')->delete($category->image_path);
        $category->delete();

        return back();
    }
}
