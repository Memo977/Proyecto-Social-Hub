<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo para representar un horario de publicación de un usuario.
 */
class PublicationSchedule extends Model
{
    use HasFactory;

    /**
     * Atributos que pueden ser asignados masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'day_of_week',
        'time',
    ];

    /**
     * Obtiene el usuario asociado al horario de publicación.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene el nombre del día de la semana a partir del atributo day_of_week.
     *
     * @return string
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
        ];
        return $days[$this->day_of_week] ?? (string) $this->day_of_week;
    }
}