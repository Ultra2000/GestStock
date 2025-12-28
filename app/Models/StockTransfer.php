<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'reference',
        'source_warehouse_id',
        'destination_warehouse_id',
        'status',
        'transfer_date',
        'expected_date',
        'shipped_date',
        'received_date',
        'requested_by',
        'approved_by',
        'shipped_by',
        'received_by',
        'carrier',
        'tracking_number',
        'total_items',
        'total_quantity',
        'total_value',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'expected_date' => 'date',
        'shipped_date' => 'date',
        'received_date' => 'date',
        'total_quantity' => 'decimal:4',
        'total_value' => 'decimal:2',
    ];

    // Relations
    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'in_transit' => 'En transit',
            'partial' => 'Partiel',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'pending' => 'warning',
            'approved' => 'info',
            'in_transit' => 'primary',
            'partial' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->total_quantity == 0) {
            return 0;
        }

        $received = $this->items->sum('quantity_received');
        return round(($received / $this->total_quantity) * 100, 1);
    }

    // Methods
    public static function generateReference(int $companyId): string
    {
        $prefix = 'TR';
        $year = date('Y');
        $month = date('m');
        
        $lastTransfer = static::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $number = 1;
        if ($lastTransfer && preg_match('/(\d+)$/', $lastTransfer->reference, $matches)) {
            $number = (int) $matches[1] + 1;
        }

        return $prefix . $year . $month . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotals(): void
    {
        $this->total_items = $this->items()->count();
        $this->total_quantity = $this->items()->sum('quantity_requested');
        $this->total_value = $this->items()
            ->join('products', 'products.id', '=', 'stock_transfer_items.product_id')
            ->selectRaw('SUM(stock_transfer_items.quantity_requested * COALESCE(stock_transfer_items.unit_cost, products.purchase_price, 0)) as total')
            ->value('total') ?? 0;
        $this->save();
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
    }

    public function canBeShipped(): bool
    {
        return $this->status === 'approved';
    }

    public function canBeReceived(): bool
    {
        return in_array($this->status, ['in_transit', 'partial']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'pending', 'approved']);
    }

    public function approve(?int $userId = null): void
    {
        if (!$this->canBeApproved()) {
            throw new \Exception('Ce transfert ne peut pas être approuvé.');
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $userId ?? auth()->id(),
        ]);
    }

    public function ship(?int $userId = null): void
    {
        if (!$this->canBeShipped()) {
            throw new \Exception('Ce transfert ne peut pas être expédié.');
        }

        // Reserve stock in source warehouse
        foreach ($this->items as $item) {
            $item->update(['quantity_shipped' => $item->quantity_requested]);
            
            // Create stock movement for outgoing
            $this->sourceWarehouse->adjustStock(
                $item->product_id,
                -$item->quantity_requested,
                'transfer_out',
                "Transfert {$this->reference} vers {$this->destinationWarehouse->name}",
                $item->source_location_id
            );
        }

        $this->update([
            'status' => 'in_transit',
            'shipped_date' => now(),
            'shipped_by' => $userId ?? auth()->id(),
        ]);
    }

    public function receive(array $quantities, ?int $userId = null): void
    {
        if (!$this->canBeReceived()) {
            throw new \Exception('Ce transfert ne peut pas être réceptionné.');
        }

        $allReceived = true;
        $anyReceived = false;

        foreach ($this->items as $item) {
            $quantityReceived = $quantities[$item->id] ?? 0;
            
            if ($quantityReceived > 0) {
                $item->quantity_received += $quantityReceived;
                $item->save();

                // Create stock movement for incoming
                $this->destinationWarehouse->adjustStock(
                    $item->product_id,
                    $quantityReceived,
                    'transfer_in',
                    "Transfert {$this->reference} depuis {$this->sourceWarehouse->name}",
                    $item->destination_location_id
                );

                $anyReceived = true;
            }

            if ($item->quantity_received < $item->quantity_shipped) {
                $allReceived = false;
            }
        }

        $status = $allReceived ? 'completed' : ($anyReceived ? 'partial' : $this->status);

        $this->update([
            'status' => $status,
            'received_date' => $allReceived ? now() : $this->received_date,
            'received_by' => $userId ?? auth()->id(),
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        if (!$this->canBeCancelled()) {
            throw new \Exception('Ce transfert ne peut pas être annulé.');
        }

        $this->update([
            'status' => 'cancelled',
            'rejection_reason' => $reason,
        ]);
    }
}
