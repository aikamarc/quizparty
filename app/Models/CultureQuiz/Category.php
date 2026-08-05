<?php
namespace App\Models\CultureQuiz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Category extends Model {
    protected $table = 'culture_categories';
    protected $fillable = ['name'];
    public function questions(): HasMany { return $this->hasMany(Question::class, 'culture_category_id'); }
}
