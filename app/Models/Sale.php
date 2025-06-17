<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'total',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($sale) {
            if ($sale->isDirty('status') && $sale->status === 'completed') {
                foreach ($sale->items as $item) {
                    $product = $item->product;
                    $product->stock -= $item->quantity;
                    $product->save();
                }
            }
        });

        static::deleting(function ($sale) {
            if ($sale->status === 'completed') {
                foreach ($sale->items as $item) {
                    $product = $item->product;
                    $product->stock += $item->quantity;
                    $product->save();
                }
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function calculateTotal(): void
    {
        $this->total = $this->items->sum('total_price');
        $this->save();
    }
}
