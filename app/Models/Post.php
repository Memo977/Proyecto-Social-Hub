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
        'media_url',
        'status',
        'mode',
        'scheduled_at',
        'published_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targets()
    {
        return $this->hasMany(PostTarget::class);
    }

    /* Scopes útiles para cola/histórico */
    public function scopeOfUser($q, $userId)
    {
        return $q->where('user_id', $userId);
    }
    public function scopeQueued($q)
    {
        return $q->where('status', 'queued');
    }
    public function scopeScheduled($q)
    {
        return $q->where('status', 'scheduled');
    }
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