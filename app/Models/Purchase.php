<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'status',
        'total',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($purchase) {
            if (empty($purchase->invoice_number)) {
                $purchase->invoice_number = 'ACH-' . strtoupper(Str::random(8));
            }
        });

        static::updated(function ($purchase) {
            if ($purchase->isDirty('status')) {
                $oldStatus = $purchase->getOriginal('status');
                $newStatus = $purchase->status;

                if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                    foreach ($purchase->items as $item) {
                        $product = $item->product;
                        $product->stock -= $item->quantity;
                        $product->save();
                    }
                }
                elseif ($oldStatus !== 'completed' && $newStatus === 'completed') {
                    foreach ($purchase->items as $item) {
                        $product = $item->product;
                        $product->stock += $item->quantity;
                        $product->save();
                    }
                }
            }
        });

        static::deleting(function ($purchase) {
            if ($purchase->status === 'completed') {
                foreach ($purchase->items as $item) {
                    $product = $item->product;
                    $product->stock -= $item->quantity;
                    $product->save();
                }
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
} 