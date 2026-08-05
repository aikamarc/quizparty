<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CultureQuiz\Category;
use App\Models\CultureQuiz\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CultureQuizController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/CultureQuiz/Index', [
            'categories' => Category::withCount('questions')->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        Category::create($request->validate(['name' => ['required','string','max:255']]));
        return back();
    }

    public function show(Category $category): Response
    {
        return Inertia::render('Admin/CultureQuiz/Show', [
            'category' => $category,
            'questions' => $category->questions()->with('translations')->latest()->paginate(30),
        ]);
    }

    public function storeQuestion(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'question_fr'=>['required','string'], 'answer_fr'=>['required','string'],
            'question_en'=>['required','string'], 'answer_en'=>['required','string'],
            'image'=>['nullable','image','max:4096'],
        ]);
        DB::transaction(function () use ($request, $category, $validated) {
            $question = $category->questions()->create([
                'image_path' => $request->file('image')?->store('culture-quiz', 'public'),
            ]);
            foreach (['fr','en'] as $locale) $question->translations()->create([
                'locale'=>$locale, 'question'=>$validated['question_'.$locale], 'answer'=>$validated['answer_'.$locale],
            ]);
        });
        return back();
    }

    public function import(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate(['list'=>['required','string']]);
        $errors = [];
        $imported = 0;
        foreach (preg_split('/\R/u', $validated['list']) as $index => $line) {
            if (!trim($line)) continue;
            $parts = preg_split('/\s*\|\|\s*/u', trim($line), 3);
            $locale = strtoupper(trim($parts[2] ?? ''));
            if (count($parts) !== 3 || !in_array($locale, ['FR','EN'], true)) { $errors[] = $index + 1; continue; }
            $question = $category->questions()->create();
            $question->translations()->create(['locale'=>strtolower($locale),'question'=>trim($parts[0]),'answer'=>trim($parts[1])]);
            $imported++;
        }
        return back()->with('flash', ['culture_imported'=>$imported,'culture_import_errors'=>$errors]);
    }

    public function destroyQuestion(Question $question): RedirectResponse
    {
        $question->delete();
        return back();
    }
}
