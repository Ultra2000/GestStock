<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringOrderItem extends Model
{
    protected $fillable = [
        'recurring_order_id',
        'product_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($item) {
            $item->recurringOrder->calculateTotal();
        });

        static::deleted(function ($item) {
            $item->recurringOrder->calculateTotal();
        });
    }

    public function recurringOrder(): BelongsTo
    {
        return $this->belongsTo(RecurringOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
