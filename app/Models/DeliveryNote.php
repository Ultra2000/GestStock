<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'sale_id',
        'customer_id',
        'user_id',
        'delivery_number',
        'delivery_date',
        'status',
        'carrier',
        'tracking_number',
        'delivery_address',
        'notes',
        'total_weight',
        'total_packages',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'total_weight' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($note) {
            if (!$note->delivery_number) {
                $note->delivery_number = static::generateDeliveryNumber($note->company_id);
            }
        });
    }

    public static function generateDeliveryNumber($companyId): string
    {
        $prefix = 'BL-' . date('Y');
        $lastNote = static::where('company_id', $companyId)
            ->where('delivery_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        if ($lastNote) {
            $lastNumber = intval(substr($lastNote->delivery_number, -5));
            return $prefix . '-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        }

        return $prefix . '-00001';
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public function markAsShipped(?string $carrier = null, ?string $trackingNumber = null): void
    {
        $this->update([
            'status' => 'shipped',
            'carrier' => $carrier ?? $this->carrier,
            'tracking_number' => $trackingNumber ?? $this->tracking_number,
            'shipped_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        // Mettre à jour la vente si liée
        if ($this->sale) {
            $this->sale->update(['status' => 'completed']);
        }
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'preparing' => 'info',
            'ready' => 'warning',
            'shipped' => 'primary',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'preparing' => 'En préparation',
            'ready' => 'Prêt',
            'shipped' => 'Expédié',
            'delivered' => 'Livré',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    public static function createFromSale(Sale $sale): self
    {
        $note = static::create([
            'company_id' => $sale->company_id,
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'user_id' => auth()->id(),
            'delivery_date' => now(),
            'status' => 'pending',
            'delivery_address' => $sale->customer?->address,
        ]);

        foreach ($sale->items as $saleItem) {
            $note->items()->create([
                'product_id' => $saleItem->product_id,
                'sale_item_id' => $saleItem->id,
                'description' => $saleItem->product?->name,
                'quantity_ordered' => $saleItem->quantity,
                'quantity_delivered' => $saleItem->quantity,
            ]);
        }

        return $note;
    }
}
