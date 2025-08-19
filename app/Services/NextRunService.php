<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PublicationSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio para calcular el próximo horario de ejecución disponible para publicaciones.
 */
class NextRunService
{
    /** @var string Zona horaria utilizada para los cálculos */
    protected string $tz;

    /**
     * Crea una nueva instancia del servicio.
     *
     * @param string|null $timezone Zona horaria opcional; por defecto usa la configuración de la aplicación.
     */
    public function __construct(?string $timezone = null)
    {
        $this->tz = $timezone ?: config('app.timezone', 'UTC');
    }

    /**
     * Calcula el próximo horario disponible mayor que el actual para un usuario, evitando colisiones.
     *
     * @param int $userId ID del usuario.
     * @return Carbon|null El próximo horario disponible o null si no hay horarios configurados.
     */
    public function nextForUser(int $userId): ?Carbon
    {
        $now = Carbon::now($this->tz);

        $slots = PublicationSchedule::query()
            ->where('user_id', $userId)
            ->orderBy('day_of_week')
            ->orderBy('time')
            ->get();

        if ($slots->isEmpty()) {
            return null;
        }

        $best = null;

        for ($addDays = 0; $addDays < 28; $addDays++) {
            $day = $now->copy()->addDays($addDays);
            $dow = (int) $day->dayOfWeek;

            foreach ($slots as $slot) {
                $slotDow = (int) $slot->day_of_week;
                if ($slotDow === 7) {
                    $slotDow = 0;
                }
                if ($slotDow !== $dow) {
                    continue;
                }

                [$h, $m, $s] = array_pad(explode(':', (string) $slot->time), 3, 0);
                $candidate = $day->copy()->setTime((int) $h, (int) $m, (int) $s);

                if (!$candidate->greaterThan($now)) {
                    continue;
                }

                if ($this->isOccupied($userId, $candidate)) {
                    continue;
                }

                if ($best === null || $candidate->lessThan($best)) {
                    $best = $candidate;
                }
            }

            if ($best) {
                break;
            }
        }

        return $best ?? $this->firstFreeSlotOfNextWeek($userId, $slots, $now);
    }

    /**
     * Calcula la próxima ocurrencia de un horario específico a partir de una fecha base.
     *
     * @param int $dow Día de la semana (0=Domingo, 1=Lunes, ..., 6=Sábado).
     * @param string $time Hora en formato 'HH:MM'.
     * @param Carbon|null $base Fecha base para el cálculo; por defecto usa el momento actual.
     * @return Carbon El próximo horario calculado.
     */
    public function nextOccurrenceFromDowAndTime(int $dow, string $time, ?Carbon $base = null): Carbon
    {
        $base ??= Carbon::now($this->tz);

        if ($dow === 7) {
            $dow = 0;
        }

        $dow = max(0, min(6, $dow));

        [$h, $m, $s] = array_pad(explode(':', $time), 3, 0);

        $candidate = $base->copy()->next($dow)->setTime((int) $h, (int) $m, (int) $s);

        if ($candidate->lessThanOrEqualTo($base)) {
            $candidate->addWeek();
        }

        return $candidate;
    }

    /**
     * Verifica si un horario específico está ocupado por otro post del usuario.
     *
     * @param int $userId ID del usuario.
     * @param Carbon $candidate Horario a verificar.
     * @return bool True si está ocupado, false en caso contrario.
     */
    protected function isOccupied(int $userId, Carbon $candidate): bool
    {
        $start = $candidate->copy()->startOfMinute();
        $end = $candidate->copy()->endOfMinute();

        return Post::query()
            ->where('user_id', $userId)
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereIn('status', ['scheduled', 'queued'])
            ->exists();
    }

    /**
     * Encuentra el primer horario libre en la próxima semana completa.
     *
     * @param int $userId ID del usuario.
     * @param Collection $slots Colección de horarios configurados.
     * @param Carbon $now Fecha actual.
     * @return Carbon|null El primer horario libre o null si no se encuentra.
     */
    protected function firstFreeSlotOfNextWeek(int $userId, Collection $slots, Carbon $now): ?Carbon
    {
        if ($slots->isEmpty()) {
            return null;
        }

        $baseNextWeek = $now->copy()->addWeek()->startOfWeek(Carbon::SUNDAY);

        $first = null;

        foreach ($slots as $slot) {
            $slotDow = (int) $slot->day_of_week;
            if ($slotDow === 7) {
                $slotDow = 0;
            }
            $slotDow = max(0, min(6, $slotDow));

            $day = $baseNextWeek->copy()->addDays($slotDow);
            [$h, $m, $s] = array_pad(explode(':', (string) $slot->time), 3, 0);
            $candidate = $day->setTime((int) $h, (int) $m, (int) $s);

            if ($this->isOccupied($userId, $candidate)) {
                continue;
            }

            if ($first === null || $candidate->lessThan($first)) {
                $first = $candidate;
            }
        }

        return $first;
    }

    /**
     * Calcula el próximo horario disponible a partir de un horario específico, evitando colisiones.
     *
     * @param PublicationSchedule $slot Horario base.
     * @param Carbon|null $base Fecha base para el cálculo.
     * @return Carbon|null El próximo horario disponible o null si no se encuentra en 8 semanas.
     */
    public function nextFromSchedule(PublicationSchedule $slot, ?Carbon $base = null): ?Carbon
    {
        $base ??= Carbon::now($this->tz);
        $userId = (int) $slot->user_id;

        for ($i = 0; $i < 8; $i++) {
            $probeBase = $base->copy()->addWeeks($i);
            $when = $this->nextOccurrenceFromDowAndTime(
                (int) $slot->day_of_week,
                (string) $slot->time,
                $probeBase
            );

            if (!$this->isOccupied($userId, $when)) {
                return $when;
            }
        }

        return null;
    }
}