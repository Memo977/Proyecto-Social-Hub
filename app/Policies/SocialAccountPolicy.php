<?php

namespace App\Policies;

use App\Models\SocialAccount;
use App\Models\User;

class SocialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, SocialAccount $account): bool
    {
        return $account->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user !== null;
    }

    public function update(User $user, SocialAccount $account): bool
    {
        return $account->user_id === $user->id;
    }

    public function delete(User $user, SocialAccount $account): bool
    {
        return $account->user_id === $user->id;
    }
}