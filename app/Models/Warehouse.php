<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'address',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'gps_radius',
        'requires_gps_check',
        'requires_qr_check',
        'phone',
        'email',
        'manager_name',
        'is_default',
        'is_active',
        'allow_negative_stock',
        'is_pos_location',
        'settings',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'is_pos_location' => 'boolean',
        'requires_gps_check' => 'boolean',
        'requires_qr_check' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'gps_radius' => 'integer',
        'settings' => 'array',
    ];

    // Relations
    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_warehouse')
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

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'source_warehouse_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'destination_warehouse_id');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->postal_code,
            $this->city,
        ]);
        
        return implode(', ', $parts);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'warehouse' => 'Entrepôt',
            'store' => 'Magasin',
            'supplier' => 'Fournisseur',
            'customer' => 'Client',
            default => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'warehouse' => 'primary',
            'store' => 'success',
            'supplier' => 'warning',
            'customer' => 'info',
            default => 'gray',
        };
    }

    /**
     * Calculer la distance entre le warehouse et une position GPS (formule Haversine)
     * @return float Distance en mètres
     */
    public function calculateDistanceFrom(float $latitude, float $longitude): float
    {
        if (!$this->latitude || !$this->longitude) {
            return PHP_FLOAT_MAX; // Pas de coordonnées configurées
        }

        $earthRadius = 6371000; // Rayon de la Terre en mètres

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
             cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Vérifier si une position GPS est dans le rayon autorisé
     */
    public function isPositionInRange(float $latitude, float $longitude): bool
    {
        $distance = $this->calculateDistanceFrom($latitude, $longitude);
        return $distance <= $this->gps_radius;
    }

    /**
     * Valider la position GPS pour le pointage
     */
    public function validateGpsPosition(float $latitude, float $longitude): array
    {
        if (!$this->requires_gps_check) {
            return ['valid' => true, 'distance' => null, 'required' => false];
        }

        if (!$this->latitude || !$this->longitude) {
            return ['valid' => false, 'reason' => 'gps_not_configured', 'distance' => null];
        }

        $distance = $this->calculateDistanceFrom($latitude, $longitude);
        $isValid = $distance <= $this->gps_radius;

        return [
            'valid' => $isValid,
            'distance' => round($distance, 2),
            'max_distance' => $this->gps_radius,
            'reason' => $isValid ? null : 'gps_out_of_range',
        ];
    }

    // Methods
    public function getProductStock(int $productId, ?int $locationId = null): float
    {
        $query = $this->products()->where('product_id', $productId);
        
        if ($locationId) {
            $query->wherePivot('location_id', $locationId);
        }
        
        return $query->sum('product_warehouse.quantity') ?? 0;
    }

    public function getAvailableStock(int $productId, ?int $locationId = null): float
    {
        $query = $this->products()->where('product_id', $productId);
        
        if ($locationId) {
            $query->wherePivot('location_id', $locationId);
        }
        
        $stock = $query->first();
        
        if (!$stock) {
            return 0;
        }
        
        return $stock->pivot->quantity - $stock->pivot->reserved_quantity;
    }

    public function adjustStock(int $productId, float $quantity, string $type, ?string $reason = null, ?int $locationId = null): StockMovement
    {
        $currentStock = $this->getProductStock($productId, $locationId);
        $newStock = $currentStock + $quantity;

        // Check for negative stock
        if ($newStock < 0 && !$this->allow_negative_stock) {
            throw new \Exception("Stock insuffisant dans l'entrepôt {$this->name}");
        }

        // Update or create product_warehouse record
        $pivotData = [
            'company_id' => $this->company_id,
            'quantity' => $newStock,
        ];

        if ($locationId) {
            $pivotData['location_id'] = $locationId;
        }

        \DB::table('product_warehouse')->updateOrInsert(
            [
                'product_id' => $productId,
                'warehouse_id' => $this->id,
                'location_id' => $locationId,
            ],
            $pivotData
        );

        // Create stock movement
        return StockMovement::create([
            'company_id' => $this->company_id,
            'product_id' => $productId,
            'warehouse_id' => $this->id,
            'location_id' => $locationId,
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $currentStock,
            'quantity_after' => $newStock,
            'reason' => $reason,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Déstockage intelligent FIFO depuis les emplacements
     * Ordre de priorité :
     * 1. Emplacements de picking (is_picking_location = true)
     * 2. Stock non affecté (location_id = NULL)
     * 3. Autres emplacements actifs
     * 
     * @param int $productId
     * @param float $quantityToDeduct Quantité à déduire (positive)
     * @param string $type Type de mouvement
     * @param string|null $reason Raison du mouvement
     * @return array Liste des mouvements de stock créés
     */
    public function deductStockFIFO(int $productId, float $quantityToDeduct, string $type, ?string $reason = null): array
    {
        $remainingQty = abs($quantityToDeduct);
        $movements = [];

        // Vérifier le stock total disponible
        $totalStock = $this->getProductStock($productId);
        if ($totalStock < $remainingQty && !$this->allow_negative_stock) {
            throw new \Exception("Stock insuffisant dans l'entrepôt {$this->name}. Disponible: {$totalStock}, Demandé: {$remainingQty}");
        }

        // 1. D'abord les emplacements de picking (FIFO par date de création)
        $pickingLocations = \DB::table('product_warehouse')
            ->join('warehouse_locations', 'warehouse_locations.id', '=', 'product_warehouse.location_id')
            ->where('product_warehouse.warehouse_id', $this->id)
            ->where('product_warehouse.product_id', $productId)
            ->where('product_warehouse.quantity', '>', 0)
            ->where('warehouse_locations.is_picking_location', true)
            ->where('warehouse_locations.is_active', true)
            ->orderBy('product_warehouse.created_at', 'asc')
            ->select('product_warehouse.*', 'warehouse_locations.code as location_code')
            ->get();

        foreach ($pickingLocations as $stock) {
            if ($remainingQty <= 0) break;
            
            $deductFromThis = min($stock->quantity, $remainingQty);
            $movements[] = $this->deductFromLocation($productId, $stock->location_id, $deductFromThis, $type, $reason, $stock->location_code);
            $remainingQty -= $deductFromThis;
        }

        // 2. Ensuite le stock non affecté (location_id = NULL)
        if ($remainingQty > 0) {
            $unassignedStock = \DB::table('product_warehouse')
                ->where('warehouse_id', $this->id)
                ->where('product_id', $productId)
                ->whereNull('location_id')
                ->where('quantity', '>', 0)
                ->first();

            if ($unassignedStock && $unassignedStock->quantity > 0) {
                $deductFromThis = min($unassignedStock->quantity, $remainingQty);
                $movements[] = $this->deductFromLocation($productId, null, $deductFromThis, $type, $reason, 'Stock général');
                $remainingQty -= $deductFromThis;
            }
        }

        // 3. Autres emplacements actifs (non-picking)
        if ($remainingQty > 0) {
            $otherLocations = \DB::table('product_warehouse')
                ->join('warehouse_locations', 'warehouse_locations.id', '=', 'product_warehouse.location_id')
                ->where('product_warehouse.warehouse_id', $this->id)
                ->where('product_warehouse.product_id', $productId)
                ->where('product_warehouse.quantity', '>', 0)
                ->where('warehouse_locations.is_picking_location', false)
                ->where('warehouse_locations.is_active', true)
                ->orderBy('product_warehouse.created_at', 'asc')
                ->select('product_warehouse.*', 'warehouse_locations.code as location_code')
                ->get();

            foreach ($otherLocations as $stock) {
                if ($remainingQty <= 0) break;
                
                $deductFromThis = min($stock->quantity, $remainingQty);
                $movements[] = $this->deductFromLocation($productId, $stock->location_id, $deductFromThis, $type, $reason, $stock->location_code);
                $remainingQty -= $deductFromThis;
            }
        }

        // 4. Si toujours du reste et stock négatif autorisé, créer un stock négatif général
        if ($remainingQty > 0 && $this->allow_negative_stock) {
            $movements[] = $this->deductFromLocation($productId, null, $remainingQty, $type, $reason, 'Stock général (négatif)');
        }

        return $movements;
    }

    /**
     * Déduire du stock d'un emplacement spécifique
     */
    protected function deductFromLocation(int $productId, ?int $locationId, float $quantity, string $type, ?string $reason, string $locationLabel): StockMovement
    {
        $currentStock = \DB::table('product_warehouse')
            ->where('warehouse_id', $this->id)
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->value('quantity') ?? 0;

        $newStock = $currentStock - $quantity;

        // Mettre à jour le stock
        \DB::table('product_warehouse')
            ->where('warehouse_id', $this->id)
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->update([
                'quantity' => $newStock,
                'updated_at' => now(),
            ]);

        // Créer le mouvement de stock
        return StockMovement::create([
            'company_id' => $this->company_id,
            'product_id' => $productId,
            'warehouse_id' => $this->id,
            'location_id' => $locationId,
            'type' => $type,
            'quantity' => -$quantity,
            'quantity_before' => $currentStock,
            'quantity_after' => $newStock,
            'reason' => $reason . ($locationId ? " (depuis {$locationLabel})" : ''),
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Réintégrer du stock (pour avoirs, annulations, retours)
     * Ajoute au stock non affecté par défaut
     */
    public function addStockBack(int $productId, float $quantity, string $type, ?string $reason = null, ?int $locationId = null): StockMovement
    {
        return $this->adjustStock($productId, abs($quantity), $type, $reason, $locationId);
    }

    public function getTotalStockValue(): float
    {
        return \DB::table('product_warehouse')
            ->join('products', 'products.id', '=', 'product_warehouse.product_id')
            ->where('product_warehouse.warehouse_id', $this->id)
            ->selectRaw('SUM(product_warehouse.quantity * COALESCE(products.purchase_price, 0)) as total')
            ->value('total') ?? 0;
    }

    public function getLowStockProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->products()
            ->wherePivotNotNull('min_quantity')
            ->whereRaw('product_warehouse.quantity <= product_warehouse.min_quantity')
            ->get();
    }

    public static function getDefault(int $companyId): ?self
    {
        return static::where('company_id', $companyId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public function setAsDefault(): void
    {
        // Remove default from other warehouses
        static::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
