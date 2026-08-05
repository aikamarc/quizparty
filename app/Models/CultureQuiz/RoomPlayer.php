<?php
namespace App\Models\CultureQuiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
class RoomPlayer extends Model {
    public $timestamps = false;
    protected $table = 'culture_room_players';
    protected $fillable = ['culture_room_id','user_id','score'];
    protected $casts = ['score'=>'decimal:1'];
    public function user() { return $this->belongsTo(User::class); }
}
