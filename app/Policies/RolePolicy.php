<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy extends BasePolicy
{
    protected string $module = 'roles';

    /**
     * Empêcher la suppression du rôle admin
     */
    public function delete(User $user, $model): bool
    {
        if ($model->slug === 'admin') {
            return false;
        }
        return parent::delete($user, $model);
    }
}
