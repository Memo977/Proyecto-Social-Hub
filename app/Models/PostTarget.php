<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo para representar un destino de publicación (target) asociado a un post y una cuenta social.
 */
class PostTarget extends Model
{
    use HasFactory;

    /**
     * Atributos que pueden ser asignados masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'post_id',
        'social_account_id',
        'status',
        'provider_post_id',
        'published_at',
        'error',
    ];

    /**
     * Definición de los tipos de datos para los atributos.
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Obtiene el post asociado al destino.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Obtiene la cuenta social asociada al destino.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }
}