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
                    if ($item->sale->type === 'credit_note') {
                        // Avoir : on réintègre le stock (au stock général)
                        $warehouse->addStockBack(
                            $item->product_id,
                            $item->quantity,
                            'credit_note',
                            "Avoir " . $item->sale->invoice_number
                        );
                    } else {
                        // Vente : déstockage intelligent FIFO depuis les emplacements
                        $warehouse->deductStockFIFO(
                            $item->product_id,
                            $item->quantity,
                            'sale',
                            "Vente " . $item->sale->invoice_number
                        );
                    }
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
                        if ($item->sale->type === 'credit_note') {
                            // Avoir modifié : ajuster le stock réintégré
                            $warehouse->adjustStock(
                                $item->product_id,
                                $diff,
                                'credit_note_adjustment',
                                "Modification avoir " . $item->sale->invoice_number
                            );
                        } else {
                            // Vente modifiée
                            if ($diff > 0) {
                                // Quantité augmentée : déduire plus (FIFO)
                                $warehouse->deductStockFIFO(
                                    $item->product_id,
                                    $diff,
                                    'sale_adjustment',
                                    "Modification vente " . $item->sale->invoice_number
                                );
                            } else {
                                // Quantité diminuée : réintégrer la différence
                                $warehouse->addStockBack(
                                    $item->product_id,
                                    abs($diff),
                                    'sale_adjustment',
                                    "Modification vente " . $item->sale->invoice_number
                                );
                            }
                        }
                    }
                }
            }
        });

        static::deleted(function ($item) {
            $item->sale->calculateTotal();

            if ($item->sale->status === 'completed') {
                $warehouse = $item->sale->warehouse ?? Warehouse::getDefault($item->sale->company_id);
                if ($warehouse) {
                    if ($item->sale->type === 'credit_note') {
                        // Suppression d'un article d'avoir : on retire le stock réintégré
                        $warehouse->deductStockFIFO(
                            $item->product_id,
                            $item->quantity,
                            'credit_note_cancel',
                            "Suppression article avoir " . $item->sale->invoice_number
                        );
                    } else {
                        // Suppression d'un article de vente : on réintègre le stock
                        $warehouse->addStockBack(
                            $item->product_id,
                            $item->quantity,
                            'sale_return',
                            "Suppression article vente " . $item->sale->invoice_number
                        );
                    }
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
