<?php

namespace App\Policies;

use App\Models\Customer;

class CustomerPolicy extends BasePolicy
{
    protected string $module = 'customers';
}
