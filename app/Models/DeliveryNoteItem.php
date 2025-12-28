<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNoteItem extends Model
{
    protected $fillable = [
        'delivery_note_id',
        'product_id',
        'sale_item_id',
        'description',
        'quantity_ordered',
        'quantity_delivered',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_delivered' => 'decimal:2',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isFullyDelivered(): bool
    {
        return $this->quantity_delivered >= $this->quantity_ordered;
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->quantity_ordered - $this->quantity_delivered);
    }
}
