<?php

namespace App\Policies;

use App\Models\BankTransaction;

class BankTransactionPolicy extends BasePolicy
{
    protected string $module = 'banking';
}
