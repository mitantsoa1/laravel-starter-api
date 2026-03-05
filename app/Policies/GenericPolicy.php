<?php

namespace App\Policies;

use App\Models\User;

class GenericPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Example: Super admins can do anything.
        // if ($user->is_super_admin) {
        //     return true;
        // }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, mixed $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, mixed $model): bool
    {
        // Example logic:
        // return $user->id === $model->user_id;
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, mixed $model): bool
    {
        // Example logic:
        // return $user->id === $model->user_id;
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, mixed $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, mixed $model): bool
    {
        return true;
    }
}
