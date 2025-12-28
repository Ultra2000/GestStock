<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringOrder extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'customer_id',
        'user_id',
        'name',
        'frequency',
        'start_date',
        'end_date',
        'next_order_date',
        'status',
        'total',
        'orders_generated',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_order_date' => 'date',
        'total' => 'decimal:2',
    ];

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
        return $this->hasMany(RecurringOrderItem::class);
    }

    public function calculateTotal(): void
    {
        $this->total = $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
        $this->save();
    }

    public function generateSale(): ?Sale
    {
        if ($this->status !== 'active' || $this->next_order_date->isFuture()) {
            return null;
        }

        $sale = Sale::create([
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'total' => $this->total,
            'status' => 'pending',
            'payment_method' => 'card',
            'notes' => "Généré depuis l'abonnement: {$this->name}",
        ]);

        foreach ($this->items as $item) {
            $sale->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->quantity * $item->unit_price,
            ]);
        }

        // Mettre à jour la prochaine date
        $this->next_order_date = $this->calculateNextDate();
        $this->orders_generated++;
        
        // Vérifier si terminé
        if ($this->end_date && $this->next_order_date->isAfter($this->end_date)) {
            $this->status = 'completed';
        }
        
        $this->save();

        return $sale;
    }

    public function calculateNextDate(): \Carbon\Carbon
    {
        return match($this->frequency) {
            'daily' => $this->next_order_date->addDay(),
            'weekly' => $this->next_order_date->addWeek(),
            'biweekly' => $this->next_order_date->addWeeks(2),
            'monthly' => $this->next_order_date->addMonth(),
            'quarterly' => $this->next_order_date->addMonths(3),
            'yearly' => $this->next_order_date->addYear(),
            default => $this->next_order_date->addMonth(),
        };
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'active']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match($this->frequency) {
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'biweekly' => 'Bi-hebdomadaire',
            'monthly' => 'Mensuel',
            'quarterly' => 'Trimestriel',
            'yearly' => 'Annuel',
            default => $this->frequency,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'paused' => 'warning',
            'cancelled' => 'danger',
            'completed' => 'gray',
            default => 'gray',
        };
    }
}
