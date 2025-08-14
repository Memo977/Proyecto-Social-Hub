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
        // Solo el dueño puede actualizar y no si ya fue publicado
        return $post->user_id === $user->id && $post->status !== 'published';
    }

    public function delete(User $user, Post $post): bool
    {
        // Solo el dueño puede borrar y no si ya fue publicado
        return $post->user_id === $user->id && $post->status !== 'published';
    }
}