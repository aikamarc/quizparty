<?php
namespace App\Models\CultureQuiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
class Room extends Model {
    protected $table = 'culture_rooms';
    protected $fillable = ['public_id','code','host_id','status','questions_total','seconds_per_question','current_question','question_ends_at','last_activity_at'];
    protected $casts = ['question_ends_at' => 'datetime','last_activity_at'=>'datetime'];
    public function getRouteKeyName(): string { return 'public_id'; }
    protected static function booted(): void { static::creating(function ($room) { $room->public_id ??= (string) Str::uuid(); $room->last_activity_at ??= now(); do { $room->code = strtoupper(Str::random(6)); } while (self::where('code',$room->code)->exists()); }); }
    public function host(): BelongsTo { return $this->belongsTo(User::class, 'host_id'); }
    public function players(): HasMany { return $this->hasMany(RoomPlayer::class, 'culture_room_id'); }
    public function categories(): BelongsToMany { return $this->belongsToMany(Category::class, 'culture_category_room', 'culture_room_id', 'culture_category_id'); }
    public function rounds(): HasMany { return $this->hasMany(Round::class, 'culture_room_id'); }
}
