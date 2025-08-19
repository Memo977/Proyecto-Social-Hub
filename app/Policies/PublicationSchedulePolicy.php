<?php

namespace App\Policies;

use App\Models\PublicationSchedule;
use App\Models\User;

/**
 * Políticas de autorización para el modelo PublicationSchedule.
 */
class PublicationSchedulePolicy
{
    /**
     * Determina si el usuario puede ver cualquier horario de publicación.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determina si el usuario puede ver un horario de publicación específico.
     *
     * @param User $user
     * @param PublicationSchedule $schedule
     * @return bool
     */
    public function view(User $user, PublicationSchedule $schedule): bool
    {
        return $schedule->user_id === $user->id;
    }

    /**
     * Determina si el usuario puede crear horarios de publicación.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determina si el usuario puede actualizar un horario de publicación específico.
     *
     * @param User $user
     * @param PublicationSchedule $schedule
     * @return bool
     */
    public function update(User $user, PublicationSchedule $schedule): bool
    {
        return $schedule->user_id === $user->id;
    }

    /**
     * Determina si el usuario puede eliminar un horario de publicación específico.
     *
     * @param User $user
     * @param PublicationSchedule $schedule
     * @return bool
     */
    public function delete(User $user, PublicationSchedule $schedule): bool
    {
        return $schedule->user_id === $user->id;
    }
}