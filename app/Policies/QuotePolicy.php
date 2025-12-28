<?php

namespace App\Policies;

use App\Models\Quote;

class QuotePolicy extends BasePolicy
{
    protected string $module = 'quotes';
}
