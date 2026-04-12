<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy extends BasePolicy
{
    protected string $module = 'transfers';

    /**
     * Le seeder utilise transfers.approve (pas transfers.update)
     */
    public function update(User $user, $model): bool
    {
        return $user->hasPermission('transfers.approve') || $user->hasPermission('transfers.manage');
    }
}
