<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseLocation extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'parent_id',
        'code',
        'name',
        'type',
        'barcode',
        'capacity',
        'max_weight',
        'is_picking_location',
        'is_receiving_location',
        'is_shipping_location',
        'is_active',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'max_weight' => 'decimal:2',
        'is_picking_location' => 'boolean',
        'is_receiving_location' => 'boolean',
        'is_shipping_location' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relations
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class, 'parent_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'location_id');
    }

    // Accessors
    public function getFullCodeAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_code . '-' . $this->code;
        }
        return $this->warehouse->code . '-' . $this->code;
    }

    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_name . ' > ' . $this->name;
        }
        return $this->warehouse->name . ' > ' . $this->name;
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'zone' => 'Zone',
            'aisle' => 'Allée',
            'rack' => 'Rack',
            'shelf' => 'Étagère',
            'bin' => 'Emplacement',
            default => $this->type,
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'zone' => 'heroicon-o-map',
            'aisle' => 'heroicon-o-arrows-right-left',
            'rack' => 'heroicon-o-squares-2x2',
            'shelf' => 'heroicon-o-inbox-stack',
            'bin' => 'heroicon-o-archive-box',
            default => 'heroicon-o-cube',
        };
    }

    // Methods
    public function getStock(): float
    {
        return \DB::table('product_warehouse')
            ->where('location_id', $this->id)
            ->sum('quantity') ?? 0;
    }

    public function getProductCount(): int
    {
        return \DB::table('product_warehouse')
            ->where('location_id', $this->id)
            ->where('quantity', '>', 0)
            ->count();
    }

    public function getUsagePercent(): float
    {
        if (!$this->capacity) {
            return 0;
        }

        return round(($this->getStock() / $this->capacity) * 100, 1);
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->capacity && $this->getStock() >= $this->capacity) {
            return false;
        }

        return true;
    }

    public static function generateBarcode(int $warehouseId): string
    {
        $prefix = 'LOC';
        $number = static::where('warehouse_id', $warehouseId)->count() + 1;
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
