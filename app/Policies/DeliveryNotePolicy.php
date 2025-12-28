<?php

namespace App\Policies;

use App\Models\DeliveryNote;

class DeliveryNotePolicy extends BasePolicy
{
    protected string $module = 'deliveries';
}
