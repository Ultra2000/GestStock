<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Warehouse;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            $item->sale->calculateTotal();
        });

        static::created(function ($item) {
            if ($item->sale->status === 'completed') {
                $warehouse = $item->sale->warehouse ?? Warehouse::getDefault($item->sale->company_id);
                if ($warehouse) {
                    // Si c'est un avoir, on réintègre le stock (quantité positive dans l'avoir = entrée en stock)
                    // Si c'est une vente, on déduit le stock (quantité positive dans la vente = sortie de stock)
                    $multiplier = $item->sale->type === 'credit_note' ? 1 : -1;
                    
                    $warehouse->adjustStock(
                        $item->product_id,
                        $item->quantity * $multiplier,
                        $item->sale->type === 'credit_note' ? 'credit_note' : 'sale',
                        ($item->sale->type === 'credit_note' ? "Avoir " : "Vente ") . $item->sale->invoice_number,
                        null
                    );
                }
            }
        });

        static::updated(function ($item) {
            if ($item->sale->status === 'completed') {
                $warehouse = $item->sale->warehouse ?? Warehouse::getDefault($item->sale->company_id);
                if ($warehouse) {
                    $oldQuantity = $item->getOriginal('quantity');
                    $diff = $item->quantity - $oldQuantity;
                    
                    if ($diff != 0) {
                        $multiplier = $item->sale->type === 'credit_note' ? 1 : -1;

                        $warehouse->adjustStock(
                            $item->product_id,
                            $diff * $multiplier,
                            'sale_adjustment',
                            "Modification quantité " . ($item->sale->type === 'credit_note' ? "avoir " : "vente ") . $item->sale->invoice_number,
                            null
                        );
                    }
                }
            }
        });

        static::deleted(function ($item) {
            $item->sale->calculateTotal();

            if ($item->sale->status === 'completed') {
                $warehouse = $item->sale->warehouse ?? Warehouse::getDefault($item->sale->company_id);
                if ($warehouse) {
                    // Annulation de l'opération : on fait l'inverse de la création
                    $multiplier = $item->sale->type === 'credit_note' ? -1 : 1;

                    $warehouse->adjustStock(
                        $item->product_id,
                        $item->quantity * $multiplier,
                        'sale_return',
                        "Suppression article " . ($item->sale->type === 'credit_note' ? "avoir " : "vente ") . $item->sale->invoice_number,
                        null
                    );
                }
            }
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
