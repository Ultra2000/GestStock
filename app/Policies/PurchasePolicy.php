<?php

namespace App\Policies;

use App\Models\Purchase;

class PurchasePolicy extends BasePolicy
{
    protected string $module = 'purchases';
}
