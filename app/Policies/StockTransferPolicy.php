<?php

namespace App\Policies;

use App\Models\StockTransfer;

class StockTransferPolicy extends BasePolicy
{
    protected string $module = 'transfers';
}
