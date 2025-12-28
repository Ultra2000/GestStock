<?php

namespace App\Policies;

use App\Models\BankAccount;

class BankAccountPolicy extends BasePolicy
{
    protected string $module = 'banking';
}
