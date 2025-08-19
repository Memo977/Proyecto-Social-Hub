<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo para representar un post creado por un usuario.
 */
class Post extends Model
{
    use HasFactory;

    /**
     * Atributos que pueden ser asignados masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'content',
        'title',
        'media_url',
        'link',
        'status',
        'mode',
        'scheduled_at',
        'published_at',
        'meta',
    ];

    /**
     * Definición de los tipos de datos para los atributos.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    /**
     * Obtiene el usuario asociado al post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene los destinos (targets) asociados al post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function targets()
    {
        return $this->hasMany(PostTarget::class);
    }

    /**
     * Scope para obtener posts pendientes (en cola o programados).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['queued', 'scheduled'])->whereNull('canceled_at');
    }

    /**
     * Scope para obtener posts publicados.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNull('canceled_at');
    }

    /**
     * Scope para obtener el historial de posts de un usuario.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHistoryOfUser($query, $userId)
    {
        return $query->where('user_id', $userId)->whereNotNull('published_at');
    }

    /**
     * Scope para obtener posts activos (no cancelados).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('canceled_at');
    }

    /**
     * Verifica si el post puede ser editado.
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['queued', 'scheduled'])
            && is_null($this->published_at)
            && is_null($this->canceled_at);
    }

    /**
     * Verifica si el post puede ser eliminado.
     *
     * @return bool
     */
    public function isDeletable(): bool
    {
        return $this->isEditable();
    }
}