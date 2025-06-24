<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can update the model.
     * The user can update their own user model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     * The user can delete their own user model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }
}
