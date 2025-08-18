<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user !== null;
    }

    public function update(User $user, Post $post): bool
    {
        // Solo dueño y solo si sigue editable (queued/scheduled, no published/cancelado)
        return $post->user_id === $user->id && $post->isEditable();
    }

    public function delete(User $user, Post $post): bool
    {
        // Solo dueño y solo si sigue deletable (mismas reglas)
        return $post->user_id === $user->id && $post->isDeletable();
    }
}