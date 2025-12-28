<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    protected string $module = 'users';

    /**
     * Empêcher un utilisateur de se supprimer lui-même
     */
    public function delete(User $user, $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }
        return parent::delete($user, $model);
    }
}
