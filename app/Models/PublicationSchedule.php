<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PublicationSchedule extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'day_of_week', 'time'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* Helper opcional para mostrar el nombre del día */
    public function getDayNameAttribute(): string
    {
        $map = [0=>'Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        return $map[$this->day_of_week] ?? (string) $this->day_of_week;
    }
}