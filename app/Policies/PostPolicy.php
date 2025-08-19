<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * Políticas de autorización para el modelo Post.
 */
class PostPolicy
{
    /**
     * Determina si el usuario puede ver cualquier post.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determina si el usuario puede ver un post específico.
     *
     * @param User $user
     * @param Post $post
     * @return bool
     */
    public function view(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }

    /**
     * Determina si el usuario puede crear posts.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determina si el usuario puede actualizar un post específico.
     *
     * @param User $user
     * @param Post $post
     * @return bool
     */
    public function update(User $user, Post $post): bool
    {
        return $post->user_id === $user->id && $post->isEditable();
    }

    /**
     * Determina si el usuario puede eliminar un post específico.
     *
     * @param User $user
     * @param Post $post
     * @return bool
     */
    public function delete(User $user, Post $post): bool
    {
        return $post->user_id === $user->id && $post->isDeletable();
    }
}