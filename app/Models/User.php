<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'google_id',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_guest' => 'boolean',
        ];
    }

    public function sentFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function receivedFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    public function pendingReceivedRequests(): HasMany
    {
        return $this->receivedFriendships()->where('status', 'pending')->with('requester');
    }

    public function pendingSentRequests(): HasMany
    {
        return $this->sentFriendships()->where('status', 'pending')->with('recipient');
    }

    /**
     * Accepted friends, regardless of who sent the original request.
     *
     * @return Collection<int, User>
     */
    public function friends(): Collection
    {
        $sent = $this->sentFriendships()->where('status', 'accepted')->with('recipient')->get()->pluck('recipient');
        $received = $this->receivedFriendships()->where('status', 'accepted')->with('requester')->get()->pluck('requester');

        return $sent->concat($received)->unique('id')->values();
    }

    public function isFriendsWith(User $user): bool
    {
        return $this->sentFriendships()->where('friend_id', $user->id)->where('status', 'accepted')->exists()
            || $this->receivedFriendships()->where('user_id', $user->id)->where('status', 'accepted')->exists();
    }
}
