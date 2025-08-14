<?php

namespace App\Policies;

use App\Models\PublicationSchedule;
use App\Models\User;

class PublicationSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, PublicationSchedule $schedule): bool
    {
        return $schedule->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user !== null;
    }

    public function update(User $user, PublicationSchedule $schedule): bool
    {
        return $schedule->user_id === $user->id;
    }

    public function delete(User $user, PublicationSchedule $schedule): bool
    {
        return $schedule->user_id === $user->id;
    }
}