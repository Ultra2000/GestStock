<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'customer_id',
        'user_id',
        'quote_number',
        'quote_date',
        'valid_until',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
        'terms',
        'converted_sale_id',
        'sent_at',
        'accepted_at',
        'rejected_at',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quote) {
            if (!$quote->quote_number) {
                $quote->quote_number = static::generateQuoteNumber($quote->company_id);
            }
        });
    }

    public static function generateQuoteNumber($companyId): string
    {
        $prefix = 'DEV-' . date('Y');
        $lastQuote = static::where('company_id', $companyId)
            ->where('quote_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        if ($lastQuote) {
            $lastNumber = intval(substr($lastQuote->quote_number, -5));
            return $prefix . '-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        }

        return $prefix . '-00001';
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
        return $this->hasMany(QuoteItem::class);
    }

    public function convertedSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'converted_sale_id');
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total_price');
        $this->tax_amount = ($this->subtotal - $this->discount_amount) * ($this->tax_rate / 100);
        $this->total = $this->subtotal - $this->discount_amount + $this->tax_amount;
        $this->save();
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function accept(): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $this->convertToSale();
    }

    public function reject(): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);
    }

    public function convertToSale(): ?Sale
    {
        if ($this->status !== 'accepted') {
            return null;
        }

        $sale = Sale::create([
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'user_id' => auth()->id() ?? $this->user_id,
            'total' => $this->total,
            'tax_percent' => $this->tax_rate,
            'discount_percent' => ($this->subtotal > 0) ? ($this->discount_amount / $this->subtotal * 100) : 0,
            'status' => 'pending',
            'payment_method' => 'cash',
            'notes' => "Converti depuis le devis {$this->quote_number}",
        ]);

        foreach ($this->items as $item) {
            // Calculer le prix unitaire effectif pour conserver le montant total (incluant la remise ligne)
            // car SaleItem ne gère pas explicitement les remises par ligne pour l'instant
            $effectiveUnitPrice = $item->quantity > 0 ? $item->total_price / $item->quantity : 0;

            $sale->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $effectiveUnitPrice,
                'total_price' => $item->total_price,
            ]);
        }

        $this->update([
            'status' => 'converted',
            'converted_sale_id' => $sale->id,
        ]);

        return $sale;
    }

    public function isExpired(): bool
    {
        return $this->valid_until->isPast() && $this->status === 'sent';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'sent' => 'info',
            'accepted' => 'success',
            'rejected' => 'danger',
            'expired' => 'warning',
            'converted' => 'primary',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyé',
            'accepted' => 'Accepté',
            'rejected' => 'Refusé',
            'expired' => 'Expiré',
            'converted' => 'Converti',
            default => $this->status,
        };
    }
}
