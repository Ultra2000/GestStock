<?php

namespace App\Policies;

use App\Models\LeaveRequest;

class LeaveRequestPolicy extends BasePolicy
{
    protected string $module = 'hr';
}
