<?php
namespace App\Models\CultureQuiz;
use Illuminate\Database\Eloquent\Model;
class Answer extends Model {
    public $timestamps = false;
    protected $table = 'culture_answers';
    protected $fillable = ['culture_round_id','user_id','answer','grading','awarded_points','is_correct','poll_open'];
    protected $casts = ['answer'=>'array','grading'=>'array','awarded_points'=>'decimal:1','is_correct'=>'boolean','poll_open'=>'boolean'];
    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function round() { return $this->belongsTo(Round::class, 'culture_round_id'); }
    public function votes() { return $this->hasMany(Vote::class, 'culture_answer_id'); }
}
