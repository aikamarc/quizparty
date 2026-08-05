<?php
namespace App\Models\CultureQuiz;
use Illuminate\Database\Eloquent\Model;
class QuestionTranslation extends Model {
    public $timestamps = false;
    protected $table = 'culture_question_translations';
    protected $fillable = ['locale', 'question', 'answer'];
}
