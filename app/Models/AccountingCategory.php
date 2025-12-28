<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingCategory extends Model
{
    //
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'color',
        'is_system',
        'parent_id',
        'description',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parent()
    {
        return $this->belongsTo(AccountingCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AccountingCategory::class, 'parent_id');
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function rules()
    {
        return $this->hasMany(AccountingRule::class);
    }
}
