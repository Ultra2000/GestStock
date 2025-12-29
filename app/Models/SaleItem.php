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
        'vat_rate',
        'unit_price_ht',
        'vat_amount',
        'total_price_ht',
        'total_price',
        'vat_category',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'unit_price_ht' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_price_ht' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Calculer les montants TVA
            $item->calculateVat();
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

    /**
     * Calcule les montants HT, TVA et TTC
     */
    public function calculateVat(): void
    {
        // Le prix unitaire est considéré comme HT (c'est le prix de vente HT du produit)
        $this->unit_price_ht = $this->unit_price;
        $this->total_price_ht = $this->quantity * $this->unit_price_ht;
        
        // Calculer la TVA
        $vatRate = $this->vat_rate ?? 20;
        $this->vat_amount = round($this->total_price_ht * ($vatRate / 100), 2);
        
        // Total TTC
        $this->total_price = $this->total_price_ht + $this->vat_amount;
    }

    /**
     * Retourne le montant TTC unitaire
     */
    public function getUnitPriceTtcAttribute(): float
    {
        $vatRate = $this->vat_rate ?? 20;
        return round($this->unit_price_ht * (1 + $vatRate / 100), 2);
    }
}
