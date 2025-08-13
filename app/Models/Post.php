<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'title',
        'media_url',
        'link',
        'meta',
        'mode',          // now | scheduled | queue (queue se usará en el commit 21)
        'status',        // draft|pending|scheduled|queued|published|failed
        'scheduled_at',
        'published_at',
    ];

    protected $casts = [
        'meta'         => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targets()
    {
        return $this->hasMany(PostTarget::class);
    }

    // Alcances útiles
    public function scopePending($q)
    {
        return $q->whereIn('status', ['queued', 'scheduled']);
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function scopeHistoryOfUser($q, $userId)
    {
        return $q->where('user_id', $userId)->whereNotNull('published_at');
    }
}