<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

/**
 * Modelo para representar una cuenta social asociada a un usuario.
 */
class SocialAccount extends Model
{
    /**
     * Atributos que pueden ser asignados masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'username',
        'instance_domain',
        'access_token',
        'refresh_token',
        'expires_at',
        'meta',
    ];

    /**
     * Definición de los tipos de datos para los atributos.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'meta' => AsArrayObject::class,
    ];

    /**
     * Obtiene el usuario asociado a la cuenta social.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}