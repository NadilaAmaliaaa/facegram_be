<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';
    protected $fillable = [
        'full_name', 'username', 'password', 'bio', 'is_private', 'created_at'
    ];

    protected $hidden = ['password'];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function followers()
    {
        return $this->hasMany(Follow::class, 'following_id');
    }

    public function followings()
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }
}
