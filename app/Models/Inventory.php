<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'reference',
        'name',
        'type',
        'status',
        'inventory_date',
        'validated_at',
        'created_by',
        'validated_by',
        'total_items',
        'items_counted',
        'discrepancies_count',
        'total_value_expected',
        'total_value_counted',
        'value_difference',
        'notes',
    ];

    protected $casts = [
        'inventory_date' => 'date',
        'validated_at' => 'date',
        'total_value_expected' => 'decimal:2',
        'total_value_counted' => 'decimal:2',
        'value_difference' => 'decimal:2',
    ];

    // Relations
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'full' => 'Complet',
            'partial' => 'Partiel',
            'cycle' => 'Cyclique',
            default => $this->type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'in_progress' => 'En cours',
            'pending_validation' => 'En attente validation',
            'validated' => 'Validé',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'in_progress' => 'info',
            'pending_validation' => 'warning',
            'validated' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->total_items == 0) {
            return 0;
        }
        return round(($this->items_counted / $this->total_items) * 100, 1);
    }

    // Methods
    public static function generateReference(int $companyId): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        $lastInventory = static::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $number = 1;
        if ($lastInventory && preg_match('/(\d+)$/', $lastInventory->reference, $matches)) {
            $number = (int) $matches[1] + 1;
        }

        return $prefix . $year . $month . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function initializeItems(?array $productIds = null, ?int $locationId = null): void
    {
        $query = \DB::table('product_warehouse')
            ->where('warehouse_id', $this->warehouse_id);

        if ($productIds) {
            $query->whereIn('product_id', $productIds);
        }

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $stocks = $query->get();

        foreach ($stocks as $stock) {
            $product = Product::find($stock->product_id);
            
            $this->items()->create([
                'product_id' => $stock->product_id,
                'location_id' => $stock->location_id,
                'quantity_expected' => $stock->quantity,
                'unit_cost' => $product?->cost_price,
            ]);
        }

        $this->calculateTotals();
    }

    public function calculateTotals(): void
    {
        $this->total_items = $this->items()->count();
        $this->items_counted = $this->items()->where('is_counted', true)->count();
        $this->discrepancies_count = $this->items()
            ->where('is_counted', true)
            ->whereRaw('quantity_counted != quantity_expected')
            ->count();

        $this->total_value_expected = $this->items()
            ->selectRaw('SUM(quantity_expected * COALESCE(unit_cost, 0)) as total')
            ->value('total') ?? 0;

        $this->total_value_counted = $this->items()
            ->where('is_counted', true)
            ->selectRaw('SUM(COALESCE(quantity_counted, 0) * COALESCE(unit_cost, 0)) as total')
            ->value('total') ?? 0;

        $this->value_difference = $this->total_value_counted - $this->total_value_expected;
        
        $this->save();
    }

    public function start(): void
    {
        if ($this->status !== 'draft') {
            throw new \Exception('L\'inventaire doit être en brouillon pour être démarré.');
        }

        $this->update(['status' => 'in_progress']);
    }

    public function submitForValidation(): void
    {
        if ($this->status !== 'in_progress') {
            throw new \Exception('L\'inventaire doit être en cours pour être soumis.');
        }

        $this->calculateTotals();
        $this->update(['status' => 'pending_validation']);
    }

    public function validate(?int $userId = null): void
    {
        if ($this->status !== 'pending_validation') {
            throw new \Exception('L\'inventaire doit être en attente de validation.');
        }

        // Apply stock adjustments
        foreach ($this->items()->where('is_counted', true)->get() as $item) {
            if ($item->quantity_difference != 0) {
                $type = $item->quantity_difference > 0 ? 'adjustment_in' : 'adjustment_out';
                
                $this->warehouse->adjustStock(
                    $item->product_id,
                    $item->quantity_difference,
                    'inventory',
                    "Inventaire {$this->reference}",
                    $item->location_id
                );
            }
        }

        $this->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => $userId ?? auth()->id(),
        ]);
    }

    public function cancel(): void
    {
        if (in_array($this->status, ['validated', 'cancelled'])) {
            throw new \Exception('Cet inventaire ne peut pas être annulé.');
        }

        $this->update(['status' => 'cancelled']);
    }
}
