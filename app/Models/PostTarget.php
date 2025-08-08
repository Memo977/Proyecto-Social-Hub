<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PostTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id', 'social_account_id', 'status',
        'provider_post_id', 'published_at', 'error',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }
}