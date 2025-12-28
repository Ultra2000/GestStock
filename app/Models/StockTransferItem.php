<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'source_location_id',
        'destination_location_id',
        'quantity_requested',
        'quantity_shipped',
        'quantity_received',
        'unit_cost',
        'batch_number',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:4',
        'quantity_shipped' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'expiry_date' => 'date',
    ];

    // Relations
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'destination_location_id');
    }

    // Accessors
    public function getTotalValueAttribute(): float
    {
        $cost = $this->unit_cost ?? $this->product?->cost_price ?? 0;
        return $this->quantity_requested * $cost;
    }

    public function getPendingQuantityAttribute(): float
    {
        return $this->quantity_shipped - $this->quantity_received;
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->quantity_received >= $this->quantity_shipped;
    }

    public function getStatusAttribute(): string
    {
        if ($this->quantity_received >= $this->quantity_requested) {
            return 'complete';
        }
        if ($this->quantity_received > 0) {
            return 'partial';
        }
        if ($this->quantity_shipped > 0) {
            return 'shipped';
        }
        return 'pending';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'complete' => 'Complet',
            'partial' => 'Partiel',
            'shipped' => 'Expédié',
            'pending' => 'En attente',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'complete' => 'success',
            'partial' => 'warning',
            'shipped' => 'info',
            'pending' => 'gray',
            default => 'gray',
        };
    }
}
