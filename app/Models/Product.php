<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'price', 'cost_price', 'stock', 'min_stock'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('products')
            ->setDescriptionForEvent(fn(string $eventName) => "Produit {$eventName}")
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'barcode_type',
        'description',
        'purchase_price',
        'purchase_price_ht',
        'vat_rate_purchase',
        'price',
        'sale_price_ht',
        'vat_rate_sale',
        'vat_category',
        'prices_include_vat',
        'stock',
        'unit',
        'min_stock',
        'supplier_id',
    ];

    protected $appends = ['total_stock', 'margin', 'margin_percent'];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'purchase_price_ht' => 'decimal:2',
        'price' => 'decimal:2',
        'sale_price_ht' => 'decimal:2',
        'vat_rate_purchase' => 'decimal:2',
        'vat_rate_sale' => 'decimal:2',
        'prices_include_vat' => 'boolean',
    ];

    protected $attributes = [
        'barcode_type' => 'code128',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = self::generateInternalCode();
            }
            if (empty($model->barcode_type)) {
                $model->barcode_type = 'code128';
            }
        });

        // Assigner automatiquement le produit à l'entrepôt par défaut après création
        static::created(function ($model) {
            $model->assignToDefaultWarehouse();
        });

        static::updating(function ($model) {
            // Empêche modification manuelle du code (sécurité supplémentaire)
            if ($model->isDirty('code')) {
                $model->code = $model->getOriginal('code');
            }
        });
    }

    /**
     * Assigne le produit à l'entrepôt par défaut de l'entreprise
     * avec le stock initial défini dans le champ 'stock'
     */
    public function assignToDefaultWarehouse(): bool
    {
        // Vérifier si le produit a déjà un entrepôt assigné
        if ($this->warehouses()->exists()) {
            return false;
        }

        // Trouver l'entrepôt par défaut de l'entreprise
        $defaultWarehouse = Warehouse::getDefault($this->company_id);

        if (!$defaultWarehouse) {
            // Si pas d'entrepôt par défaut, chercher le premier entrepôt actif
            $defaultWarehouse = Warehouse::where('company_id', $this->company_id)
                ->where('is_active', true)
                ->first();
        }

        if (!$defaultWarehouse) {
            return false;
        }

        // Assigner le produit à l'entrepôt avec le stock initial
        \DB::table('product_warehouse')->insert([
            'company_id' => $this->company_id,
            'product_id' => $this->id,
            'warehouse_id' => $defaultWarehouse->id,
            'quantity' => $this->stock ?? 0,
            'reserved_quantity' => 0,
            'min_quantity' => $this->min_stock,
            'max_quantity' => null,
            'reorder_point' => $this->min_stock,
            'reorder_quantity' => null,
            'location_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Créer un mouvement de stock initial si le stock > 0
        if (($this->stock ?? 0) > 0) {
            StockMovement::create([
                'company_id' => $this->company_id,
                'warehouse_id' => $defaultWarehouse->id,
                'product_id' => $this->id,
                'type' => 'initial',
                'quantity' => $this->stock,
                'quantity_before' => 0,
                'quantity_after' => $this->stock,
                'reference' => 'INIT-' . $this->code,
                'reason' => 'Stock initial à la création du produit',
            ]);
        }

        return true;
    }

    public static function generateInternalCode(): string
    {
        try {
            if (\Schema::hasTable('sequences')) {
                return \App\Services\BarcodeGenerator::nextInternalCode();
            }
        } catch (\Throwable $e) {
            // Fallback silencieux
        }
        return \App\Services\BarcodeGenerator::naiveInternalCode();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // Multi-Warehouse Relations
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouse')
            ->withPivot([
                'quantity',
                'reserved_quantity',
                'location_id',
                'min_quantity',
                'max_quantity',
                'reorder_point',
                'reorder_quantity',
            ])
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    // Stock Methods
    public function getTotalStockAttribute(): float
    {
        // Utiliser le cache pour les calculs de stock fréquents
        return \Illuminate\Support\Facades\Cache::remember(
            "product.{$this->id}.total_stock",
            now()->addMinutes(5),
            function () {
                // Si multi-entrepôt activé
                $hasWarehouse = \DB::table('product_warehouse')
                    ->where('product_id', $this->id)
                    ->exists();

                if ($hasWarehouse) {
                    return (float) \DB::table('product_warehouse')
                        ->where('product_id', $this->id)
                        ->sum('quantity');
                }

                // Retourner le stock simple si aucun stock entrepôt n'existe
                return (float) ($this->stock ?? 0);
            }
        );
    }

    /**
     * Invalide le cache du stock pour ce produit
     */
    public function clearStockCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget("product.{$this->id}.total_stock");
    }

    /**
     * Invalide le cache du stock pour plusieurs produits
     */
    public static function clearStockCacheForProducts(array $productIds): void
    {
        foreach ($productIds as $id) {
            \Illuminate\Support\Facades\Cache::forget("product.{$id}.total_stock");
        }
    }

    public function getCostPriceAttribute(): ?float
    {
        return $this->purchase_price;
    }

    public function getStockInWarehouse(int $warehouseId, ?int $locationId = null): float
    {
        $query = \DB::table('product_warehouse')
            ->where('product_id', $this->id)
            ->where('warehouse_id', $warehouseId);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->sum('quantity') ?? 0;
    }

    public function getAvailableStockInWarehouse(int $warehouseId, ?int $locationId = null): float
    {
        $query = \DB::table('product_warehouse')
            ->where('product_id', $this->id)
            ->where('warehouse_id', $warehouseId);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $record = $query->first();

        if (!$record) {
            return 0;
        }

        return ($record->quantity ?? 0) - ($record->reserved_quantity ?? 0);
    }

    public function getStockByWarehouse(): array
    {
        return \DB::table('product_warehouse')
            ->join('warehouses', 'warehouses.id', '=', 'product_warehouse.warehouse_id')
            ->where('product_warehouse.product_id', $this->id)
            ->select([
                'warehouses.id',
                'warehouses.name',
                'warehouses.code',
                'product_warehouse.quantity',
                'product_warehouse.reserved_quantity',
                'product_warehouse.min_quantity',
            ])
            ->get()
            ->toArray();
    }

    public function isLowStockInWarehouse(int $warehouseId): bool
    {
        $stock = \DB::table('product_warehouse')
            ->where('product_id', $this->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$stock || !$stock->min_quantity) {
            return false;
        }

        return $stock->quantity <= $stock->min_quantity;
    }

    public function needsReorderInWarehouse(int $warehouseId): bool
    {
        $stock = \DB::table('product_warehouse')
            ->where('product_id', $this->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$stock || !$stock->reorder_point) {
            return false;
        }

        return $stock->quantity <= $stock->reorder_point;
    }

    // ========================================
    // GESTION TVA ET PRIX HT/TTC
    // ========================================

    /**
     * Retourne le prix d'achat HT
     */
    public function getPurchasePriceHtAttribute(): float
    {
        if ($this->attributes['purchase_price_ht'] ?? null) {
            return (float) $this->attributes['purchase_price_ht'];
        }
        
        // Si pas de prix HT stocké, calculer depuis le prix TTC
        $price = (float) ($this->attributes['purchase_price'] ?? 0);
        $vatRate = (float) ($this->attributes['vat_rate_purchase'] ?? 20);
        
        if ($this->prices_include_vat && $vatRate > 0) {
            return round($price / (1 + $vatRate / 100), 2);
        }
        
        return $price;
    }

    /**
     * Retourne le prix d'achat TTC
     */
    public function getPurchasePriceTtcAttribute(): float
    {
        $priceHt = $this->purchase_price_ht;
        $vatRate = (float) ($this->vat_rate_purchase ?? 20);
        
        return round($priceHt * (1 + $vatRate / 100), 2);
    }

    /**
     * Retourne le prix de vente HT
     */
    public function getSalePriceHtAttribute(): float
    {
        if ($this->attributes['sale_price_ht'] ?? null) {
            return (float) $this->attributes['sale_price_ht'];
        }
        
        // Si pas de prix HT stocké, calculer depuis le prix TTC
        $price = (float) ($this->attributes['price'] ?? 0);
        $vatRate = (float) ($this->attributes['vat_rate_sale'] ?? 20);
        
        if ($this->prices_include_vat && $vatRate > 0) {
            return round($price / (1 + $vatRate / 100), 2);
        }
        
        return $price;
    }

    /**
     * Retourne le prix de vente TTC
     */
    public function getSalePriceTtcAttribute(): float
    {
        $priceHt = $this->sale_price_ht;
        $vatRate = (float) ($this->vat_rate_sale ?? 20);
        
        return round($priceHt * (1 + $vatRate / 100), 2);
    }

    /**
     * Retourne la marge brute (en valeur)
     * Marge = Prix de vente HT - Prix d'achat HT
     */
    public function getMarginAttribute(): float
    {
        return round($this->sale_price_ht - $this->purchase_price_ht, 2);
    }

    /**
     * Retourne le taux de marge en pourcentage
     * Taux de marge = (Marge / Prix d'achat HT) × 100
     */
    public function getMarginPercentAttribute(): float
    {
        $purchaseHt = $this->purchase_price_ht;
        
        if ($purchaseHt <= 0) {
            return 0;
        }
        
        return round(($this->margin / $purchaseHt) * 100, 2);
    }

    /**
     * Retourne le taux de marque en pourcentage
     * Taux de marque = (Marge / Prix de vente HT) × 100
     */
    public function getMarkupPercentAttribute(): float
    {
        $saleHt = $this->sale_price_ht;
        
        if ($saleHt <= 0) {
            return 0;
        }
        
        return round(($this->margin / $saleHt) * 100, 2);
    }

    /**
     * Montant de TVA à l'achat (déductible)
     */
    public function getVatAmountPurchaseAttribute(): float
    {
        return round($this->purchase_price_ttc - $this->purchase_price_ht, 2);
    }

    /**
     * Montant de TVA à la vente (collectée)
     */
    public function getVatAmountSaleAttribute(): float
    {
        return round($this->sale_price_ttc - $this->sale_price_ht, 2);
    }

    /**
     * Catégories TVA pour Chorus Pro / Factur-X
     */
    public static function getVatCategories(): array
    {
        return [
            'S' => 'Standard (taux normal)',
            'AA' => 'Taux réduit',
            'Z' => 'Taux zéro',
            'E' => 'Exonéré de TVA',
            'AE' => 'Autoliquidation (reverse charge)',
            'K' => 'Intracommunautaire',
            'G' => 'Export hors UE',
            'O' => 'Non soumis à TVA',
        ];
    }

    /**
     * Taux de TVA courants en France
     */
    public static function getCommonVatRates(): array
    {
        return [
            20.00 => '20% - Taux normal',
            10.00 => '10% - Taux intermédiaire',
            5.50 => '5,5% - Taux réduit',
            2.10 => '2,1% - Taux super-réduit',
            0.00 => '0% - Exonéré',
        ];
    }
}
