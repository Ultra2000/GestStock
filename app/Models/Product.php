<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'barcode_type',
        'description',
        'purchase_price',
        'price',
        'stock',
        'unit',
        'min_stock',
        'supplier_id',
    ];

    protected $appends = ['total_stock'];

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
        // Si multi-entrepôt activé
        $hasWarehouse = \DB::table('product_warehouse')
            ->where('product_id', $this->id)
            ->exists();

        if ($hasWarehouse) {
            return \DB::table('product_warehouse')
                ->where('product_id', $this->id)
                ->sum('quantity');
        }

        // Retourner le stock simple si aucun stock entrepôt n'existe
        return $this->stock ?? 0;
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
}
