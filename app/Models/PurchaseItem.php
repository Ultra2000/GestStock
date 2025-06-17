<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($item) {
            if (empty($item->total_price)) {
                $item->total_price = $item->quantity * $item->unit_price;
            }
        });

        static::updating(function ($item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });

        // Mise à jour du stock uniquement si l'achat est terminé
        static::created(function ($item) {
            if ($item->purchase->status === 'completed') {
                $product = $item->product;
                $product->stock += $item->quantity;
                $product->save();
            }
        });

        static::updated(function ($item) {
            if ($item->purchase->status === 'completed') {
                $product = $item->product;
                $oldQuantity = $item->getOriginal('quantity');
                $newQuantity = $item->quantity;
                $product->stock = $product->stock - $oldQuantity + $newQuantity;
                $product->save();
            }
        });

        static::deleted(function ($item) {
            if ($item->purchase->status === 'completed') {
                $product = $item->product;
                $product->stock -= $item->quantity;
                $product->save();
            }
        });
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
} 