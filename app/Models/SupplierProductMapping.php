<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierProductMapping extends Model
{
    protected $fillable = [
        'company_id',
        'supplier_id',
        'product_id',
        'raw_description',
        'normalized_description',
        'use_count',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
