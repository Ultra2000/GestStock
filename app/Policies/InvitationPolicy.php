<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy extends BasePolicy
{
    protected string $module = 'users';

    /**
     * Seuls les utilisateurs avec la permission users.create peuvent inviter
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }
}
