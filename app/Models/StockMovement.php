<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'location_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'reference',
        'moveable_type',
        'moveable_id',
        'user_id',
        'batch_number',
        'expiry_date',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'quantity_before' => 'decimal:4',
        'quantity_after' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    // Relations
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moveable(): MorphTo
    {
        return $this->morphTo();
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'purchase' => 'Achat',
            'sale' => 'Vente',
            'transfer_out' => 'Sortie transfert',
            'transfer_in' => 'Entrée transfert',
            'adjustment_in' => 'Ajustement +',
            'adjustment_out' => 'Ajustement -',
            'inventory' => 'Inventaire',
            'return_in' => 'Retour client',
            'return_out' => 'Retour fournisseur',
            'production_in' => 'Production entrée',
            'production_out' => 'Production sortie',
            'waste' => 'Perte/Casse',
            'initial' => 'Stock initial',
            default => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'purchase', 'transfer_in', 'adjustment_in', 'return_in', 'production_in', 'initial' => 'success',
            'sale', 'transfer_out', 'adjustment_out', 'return_out', 'production_out', 'waste' => 'danger',
            'inventory' => 'info',
            default => 'gray',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'purchase' => 'heroicon-o-shopping-cart',
            'sale' => 'heroicon-o-banknotes',
            'transfer_out' => 'heroicon-o-arrow-right-circle',
            'transfer_in' => 'heroicon-o-arrow-left-circle',
            'adjustment_in' => 'heroicon-o-plus-circle',
            'adjustment_out' => 'heroicon-o-minus-circle',
            'inventory' => 'heroicon-o-clipboard-document-check',
            'return_in' => 'heroicon-o-arrow-uturn-left',
            'return_out' => 'heroicon-o-arrow-uturn-right',
            'waste' => 'heroicon-o-trash',
            'initial' => 'heroicon-o-flag',
            default => 'heroicon-o-cube',
        };
    }

    public function getDirectionAttribute(): string
    {
        return $this->quantity >= 0 ? 'in' : 'out';
    }

    public function getAbsoluteQuantityAttribute(): float
    {
        return abs($this->quantity);
    }

    // Scopes
    public function scopeIncoming($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeOutgoing($query)
    {
        return $query->where('quantity', '<', 0);
    }

    public function scopeOfType($query, string|array $type)
    {
        if (is_array($type)) {
            return $query->whereIn('type', $type);
        }
        return $query->where('type', $type);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Static methods
    public static function getMovementTypes(): array
    {
        return [
            'purchase' => 'Achat',
            'sale' => 'Vente',
            'transfer_out' => 'Sortie transfert',
            'transfer_in' => 'Entrée transfert',
            'adjustment_in' => 'Ajustement +',
            'adjustment_out' => 'Ajustement -',
            'inventory' => 'Inventaire',
            'return_in' => 'Retour client',
            'return_out' => 'Retour fournisseur',
            'production_in' => 'Production entrée',
            'production_out' => 'Production sortie',
            'waste' => 'Perte/Casse',
            'initial' => 'Stock initial',
        ];
    }

    public static function getIncomingTypes(): array
    {
        return ['purchase', 'transfer_in', 'adjustment_in', 'return_in', 'production_in', 'initial'];
    }

    public static function getOutgoingTypes(): array
    {
        return ['sale', 'transfer_out', 'adjustment_out', 'return_out', 'production_out', 'waste'];
    }
}
