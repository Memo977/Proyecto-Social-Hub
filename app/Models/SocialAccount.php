<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'provider', 'provider_user_id',
        'access_token', 'refresh_token', 'expires_at',
        'scopes', 'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'scopes'     => 'array',
        'meta'       => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targets()
    {
        return $this->hasMany(PostTarget::class);
    }
}