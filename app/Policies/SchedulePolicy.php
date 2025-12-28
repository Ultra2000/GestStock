<?php

namespace App\Policies;

use App\Models\Schedule;

class SchedulePolicy extends BasePolicy
{
    protected string $module = 'hr';
}
