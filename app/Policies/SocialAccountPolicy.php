<?php

namespace App\Policies;

use App\Models\SocialAccount;
use App\Models\User;

/**
 * Políticas de autorización para el modelo SocialAccount.
 */
class SocialAccountPolicy
{
    /**
     * Determina si el usuario puede ver cualquier cuenta social.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determina si el usuario puede ver una cuenta social específica.
     *
     * @param User $user
     * @param SocialAccount $account
     * @return bool
     */
    public function view(User $user, SocialAccount $account): bool
    {
        return $account->user_id === $user->id;
    }

    /**
     * Determina si el usuario puede crear cuentas sociales.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determina si el usuario puede actualizar una cuenta social específica.
     *
     * @param User $user
     * @param SocialAccount $account
     * @return bool
     */
    public function update(User $user, SocialAccount $account): bool
    {
        return $account->user_id === $user->id;
    }

    /**
     * Determina si el usuario puede eliminar una cuenta social específica.
     *
     * @param User $user
     * @param SocialAccount $account
     * @return bool
     */
    public function delete(User $user, SocialAccount $account): bool
    {
        return $account->user_id === $user->id;
    }
}