<?php

namespace App\Policies;

use App\Models\RecurringOrder;

class RecurringOrderPolicy extends BasePolicy
{
    protected string $module = 'sales';
}
