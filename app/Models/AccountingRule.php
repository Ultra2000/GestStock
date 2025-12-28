<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingRule extends Model
{
    //
    protected $fillable = [
        'company_id',
        'name',
        'condition_type',
        'condition_value',
        'accounting_category_id',
        'priority',
        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(AccountingCategory::class, 'accounting_category_id');
    }
}
