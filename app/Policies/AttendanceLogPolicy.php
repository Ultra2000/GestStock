<?php

namespace App\Policies;

use App\Models\AttendanceLog;

class AttendanceLogPolicy extends BasePolicy
{
    protected string $module = 'hr';
}
