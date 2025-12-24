<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'username',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'username';
    }

    /**
     * Get the comments for the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(\App\Models\Comment::class);
    }

    /**
     * Get the items created by the user.
     */
    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\Item::class);
    }

    /**
     * Get the users that this user is following (friends).
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'friendships', 'user_id', 'friend_id')
                    ->withTimestamps();
    }

    /**
     * Get the users that are following this user (followers).
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'friendships', 'friend_id', 'user_id')
                    ->withTimestamps();
    }
}
