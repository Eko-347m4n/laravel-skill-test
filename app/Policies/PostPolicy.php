<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine whether the user can update the model.
     * The user must be the author of the post.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * The user must be the author of the post.
     */
    public function destroy(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
