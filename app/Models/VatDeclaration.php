<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VatDeclaration extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'created_by',
        'period_start',
        'period_end',
        'period_label',
        'base_20', 'vat_20',
        'base_10', 'vat_10',
        'base_55', 'vat_55',
        'base_21', 'vat_21',
        'base_other', 'vat_other',
        'total_vat_collected',
        'vat_deductible_goods',
        'vat_deductible_assets',
        'total_vat_deductible',
        'vat_due',
        'vat_credit',
        'status',
        'notes',
        'validated_at',
    ];

    protected $casts = [
        'period_start'         => 'date',
        'period_end'           => 'date',
        'base_20'              => 'float',
        'vat_20'               => 'float',
        'base_10'              => 'float',
        'vat_10'               => 'float',
        'base_55'              => 'float',
        'vat_55'               => 'float',
        'base_21'              => 'float',
        'vat_21'               => 'float',
        'base_other'           => 'float',
        'vat_other'            => 'float',
        'total_vat_collected'  => 'float',
        'vat_deductible_goods' => 'float',
        'vat_deductible_assets'=> 'float',
        'total_vat_deductible' => 'float',
        'vat_due'              => 'float',
        'vat_credit'           => 'float',
        'validated_at'         => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    public function validate(): void
    {
        $this->update([
            'status'       => 'validated',
            'validated_at' => now(),
        ]);
    }

    /**
     * TVA nette = collectée - déductible
     */
    public function getNetVat(): float
    {
        return round($this->total_vat_collected - $this->total_vat_deductible, 2);
    }
}
