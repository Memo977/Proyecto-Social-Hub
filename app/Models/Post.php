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
        'status',        // expected: 'queued'|'scheduled'|'published'|'failed'|...
        'mode',          // expected: 'now'|'queue'|'schedule'
        'scheduled_at',
        'published_at',
        'meta',          // JSON (reddit extras, etc.)
    ];

    protected $casts = [
        'meta'         => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'canceled_at'  => 'datetime',
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

    // Scopes
    public function scopePending($q)
    {
        return $q->whereIn('status', ['queued', 'scheduled'])->whereNull('canceled_at');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->whereNull('canceled_at');
    }

    public function scopeHistoryOfUser($q, $userId)
    {
        return $q->where('user_id', $userId)->whereNotNull('published_at');
    }

    public function scopeActive($q)
    {
        return $q->whereNull('canceled_at');
    }

    // Reglas de edición/eliminación
    public function isEditable(): bool
    {
        return in_array($this->status, ['queued','scheduled'])
            && is_null($this->published_at)
            && is_null($this->canceled_at);
    }

    public function isDeletable(): bool
    {
        return $this->isEditable();
    }
}