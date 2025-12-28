<?php

namespace App\Policies;

use App\Models\Employee;

class EmployeePolicy extends BasePolicy
{
    protected string $module = 'employees';
}
