<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Warehouse;

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
                $warehouse = $item->purchase->warehouse ?? Warehouse::getDefault($item->purchase->company_id);
                if ($warehouse) {
                    $warehouse->adjustStock(
                        $item->product_id,
                        $item->quantity,
                        'purchase',
                        "Ajout article achat {$item->purchase->invoice_number}",
                        null
                    );
                }
            }
        });

        static::updated(function ($item) {
            if ($item->purchase->status === 'completed') {
                $warehouse = $item->purchase->warehouse ?? Warehouse::getDefault($item->purchase->company_id);
                if ($warehouse) {
                    $oldQuantity = $item->getOriginal('quantity');
                    $diff = $item->quantity - $oldQuantity;
                    
                    if ($diff != 0) {
                        $warehouse->adjustStock(
                            $item->product_id,
                            $diff,
                            'purchase_adjustment',
                            "Modification quantité achat {$item->purchase->invoice_number}",
                            null
                        );
                    }
                }
            }
        });

        static::deleted(function ($item) {
            if ($item->purchase->status === 'completed') {
                $warehouse = $item->purchase->warehouse ?? Warehouse::getDefault($item->purchase->company_id);
                if ($warehouse) {
                    $warehouse->adjustStock(
                        $item->product_id,
                        -$item->quantity,
                        'purchase_cancellation',
                        "Suppression article achat {$item->purchase->invoice_number}",
                        null
                    );
                }
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