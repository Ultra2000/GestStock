<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    protected $fillable = [
        'inventory_id',
        'product_id',
        'location_id',
        'quantity_expected',
        'quantity_counted',
        'quantity_difference',
        'unit_cost',
        'value_difference',
        'is_counted',
        'counted_by',
        'counted_at',
        'notes',
    ];

    protected $casts = [
        'quantity_expected' => 'decimal:4',
        'quantity_counted' => 'decimal:4',
        'quantity_difference' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'value_difference' => 'decimal:2',
        'is_counted' => 'boolean',
        'counted_at' => 'datetime',
    ];

    // Relations
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function countedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    // Methods
    public function count(float $quantity, ?int $userId = null, ?string $notes = null): void
    {
        $this->quantity_counted = $quantity;
        $this->quantity_difference = $quantity - $this->quantity_expected;
        $this->value_difference = $this->quantity_difference * ($this->unit_cost ?? 0);
        $this->is_counted = true;
        $this->counted_by = $userId ?? auth()->id();
        $this->counted_at = now();
        
        if ($notes) {
            $this->notes = $notes;
        }
        
        $this->save();

        // Update inventory totals
        $this->inventory->calculateTotals();
    }

    public function reset(): void
    {
        $this->quantity_counted = null;
        $this->quantity_difference = null;
        $this->value_difference = null;
        $this->is_counted = false;
        $this->counted_by = null;
        $this->counted_at = null;
        $this->save();

        $this->inventory->calculateTotals();
    }

    // Accessors
    public function getStatusAttribute(): string
    {
        if (!$this->is_counted) {
            return 'pending';
        }

        if ($this->quantity_difference == 0) {
            return 'ok';
        }

        return $this->quantity_difference > 0 ? 'surplus' : 'shortage';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'À compter',
            'ok' => 'Conforme',
            'surplus' => 'Excédent',
            'shortage' => 'Manquant',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'ok' => 'success',
            'surplus' => 'info',
            'shortage' => 'danger',
            default => 'gray',
        };
    }
}
