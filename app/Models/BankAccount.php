<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    //
    protected $fillable = [
        'company_id',
        'name',
        'bank_name',
        'account_number',
        'currency',
        'initial_balance',
        'current_balance',
        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }
}
