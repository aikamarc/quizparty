<?php
namespace App\Models\CultureQuiz;
use Illuminate\Database\Eloquent\Model;
class Vote extends Model {
    public $timestamps = false;
    protected $table = 'culture_votes';
    protected $fillable = ['user_id','accepted'];
    protected $casts = ['accepted'=>'boolean'];
}
