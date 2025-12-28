<?php

namespace App\Policies;

use App\Models\Inventory;

class InventoryPolicy extends BasePolicy
{
    protected string $module = 'inventory';
}
