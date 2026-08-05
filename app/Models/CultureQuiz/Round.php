<?php
namespace App\Models\CultureQuiz;
use Illuminate\Database\Eloquent\Model;
class Round extends Model {
    public $timestamps = false;
    protected $table = 'culture_rounds';
    protected $fillable = ['culture_question_id','position','special_type','special_letter'];
    public function question() { return $this->belongsTo(Question::class, 'culture_question_id'); }
    public function answers() { return $this->hasMany(Answer::class, 'culture_round_id'); }
}
