<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PublicationSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NextRunService
{
    protected string $tz;

    public function __construct(?string $timezone = null)
    {
        $this->tz = $timezone ?: config('app.timezone', 'UTC');
    }

    /**
     * Calcula el próximo slot DISPONIBLE (> now) para el usuario.
     * Evita colisionar con otros posts del mismo usuario en ese mismo minuto.
     */
    public function nextForUser(int $userId): ?Carbon
    {
        $now = Carbon::now($this->tz);

        /** @var Collection<int, PublicationSchedule> $slots */
        $slots = PublicationSchedule::query()
            ->where('user_id', $userId)
            ->orderBy('day_of_week')
            ->orderBy('time')
            ->get();

        if ($slots->isEmpty()) {
            return null;
        }

        $best = null;

        // Ventana de 4 semanas para encontrar un slot libre
        for ($addDays = 0; $addDays < 28; $addDays++) {
            $day = $now->copy()->addDays($addDays);
            $dow = (int) $day->dayOfWeek; // 0=Dom..6=Sáb

            foreach ($slots as $slot) {
                $slotDow = (int) $slot->day_of_week;
                // Normaliza 7 => 0 (Domingo) por si acaso
                if ($slotDow === 7) { $slotDow = 0; }
                if ($slotDow !== $dow) continue;

                [$h, $m, $s] = array_pad(explode(':', (string) $slot->time), 3, 0);
                $candidate = $day->copy()->setTime((int) $h, (int) $m, (int) $s);

                // Solo estrictamente futuro
                if (!$candidate->greaterThan($now)) continue;

                // Evitar colisión en el MISMO minuto con otro post del mismo usuario
                if ($this->isOccupied($userId, $candidate)) continue;

                if ($best === null || $candidate->lessThan($best)) {
                    $best = $candidate;
                }
            }

            if ($best) break;
        }

        // Como último recurso, toma el primer slot libre de la semana siguiente
        if ($best === null) {
            $best = $this->firstFreeSlotOfNextWeek($userId, $slots, $now);
        }

        return $best;
    }

    /**
     * Conversión utilitaria: desde (día_semana, 'HH:MM[:SS]') a la próxima ocurrencia.
     * $dayOfWeek: 0=Dom..6=Sáb (si reciben 7 => Dom).
     */
    public function nextOccurrenceFromDowAndTime(int $dayOfWeek, string $time, ?Carbon $base = null): Carbon
    {
        $base ??= Carbon::now($this->tz);

        $dow = (int) $dayOfWeek;
        if ($dow === 7) { $dow = 0; }           // normaliza
        $dow = max(0, min(6, $dow));            // clamp defensivo 0..6

        $daysToAdd = ($dow - (int) $base->dayOfWeek + 7) % 7;
        $candidateDay = $base->copy()->addDays($daysToAdd);

        [$h, $m, $s] = array_pad(explode(':', $time), 3, 0);
        $candidate = $candidateDay->setTime((int) $h, (int) $m, (int) $s);

        // Si cayó en el pasado (mismo día pero hora pasada), empuja una semana
        if ($candidate->lessThanOrEqualTo($base)) {
            $candidate = $candidate->addWeek();
        }

        return $candidate;
    }

    /**
     * Devuelve true si ya existe otra publicación del mismo usuario asignada a ese minuto.
     */
    protected function isOccupied(int $userId, Carbon $candidate): bool
    {
        // Igualamos al MINUTO (ignora segundos)
        $start = $candidate->copy()->startOfMinute();
        $end   = $candidate->copy()->endOfMinute();

        return Post::query()
            ->where('user_id', $userId)
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereIn('status', ['pending', 'scheduled', 'queued']) // incluye cola
            ->exists();
        // Nota: ajusta los estados si usas otros nombres
    }

    protected function firstFreeSlotOfNextWeek(int $userId, Collection $slots, Carbon $now): ?Carbon
    {
        if ($slots->isEmpty()) return null;

        $baseNextWeek = $now->copy()->addWeek()->startOfWeek(Carbon::SUNDAY); // 0=Dom
        $first = null;

        foreach ($slots as $slot) {
            $slotDow = (int) $slot->day_of_week;
            if ($slotDow === 7) { $slotDow = 0; }
            $slotDow = max(0, min(6, $slotDow));

            $day = $baseNextWeek->copy()->addDays($slotDow);
            [$h, $m, $s] = array_pad(explode(':', (string) $slot->time), 3, 0);
            $candidate = $day->setTime((int) $h, (int) $m, (int) $s);

            if ($this->isOccupied($userId, $candidate)) continue;

            if ($first === null || $candidate->lessThan($first)) {
                $first = $candidate;
            }
        }

        return $first;
    }
}