<?php
namespace App\Models\CultureQuiz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
class Question extends Model {
    protected $table = 'culture_questions';
    protected $fillable = ['culture_category_id', 'type', 'image_path'];
    protected $appends = ['image_url'];
    public function category(): BelongsTo { return $this->belongsTo(Category::class, 'culture_category_id'); }
    public function translations(): HasMany { return $this->hasMany(QuestionTranslation::class, 'culture_question_id'); }
    public function getImageUrlAttribute(): ?string { return $this->image_path ? Storage::url($this->image_path) : null; }
    public function translation(string $locale): ?QuestionTranslation { return $this->translations->firstWhere('locale', $locale) ?? $this->translations->first(); }
}
