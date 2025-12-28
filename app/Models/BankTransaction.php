<?php

namespace App\Models;

use App\Services\AccountingService;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class BankTransaction extends Model
{
    use BelongsToCompany;

    protected static function booted(): void
    {
        static::created(function (BankTransaction $transaction) {
            // Tenter d'appliquer les règles automatiques à la création
            app(AccountingService::class)->applyRules($transaction);
        });
    }

    //
    protected $fillable = [
        'company_id',
        'bank_account_id',
        'date',
        'amount',
        'type',
        'label',
        'reference',
        'accounting_category_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function category()
    {
        return $this->belongsTo(AccountingCategory::class, 'accounting_category_id');
    }
}
