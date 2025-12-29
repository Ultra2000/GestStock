<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'unit_price_ht',
        'discount_percent',
        'vat_rate',
        'vat_amount',
        'total_price_ht',
        'total_price',
        'vat_category',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'unit_price_ht' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_price_ht' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->calculateVat();
        });

        static::saved(function ($item) {
            $item->quote->calculateTotals();
        });

        static::deleted(function ($item) {
            $item->quote->calculateTotals();
        });
    }

    /**
     * Calcule les montants HT, TVA et TTC
     */
    public function calculateVat(): void
    {
        // Prix unitaire HT
        $this->unit_price_ht = $this->unit_price;
        
        // Sous-total HT avant remise
        $subtotalHt = $this->quantity * $this->unit_price_ht;
        
        // Appliquer la remise ligne
        $discountAmount = $subtotalHt * (($this->discount_percent ?? 0) / 100);
        $this->total_price_ht = round($subtotalHt - $discountAmount, 2);
        
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

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
