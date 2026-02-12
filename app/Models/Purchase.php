<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Purchase extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    protected $fillable = [
        'company_id',
        'invoice_number',
        'supplier_id',
        'warehouse_id',
        'bank_account_id',
        'status',
        'payment_method',
        'total',
        'total_ht',
        'total_vat',
        'notes',
        'discount_percent',
        'tax_percent',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'total_ht' => 'decimal:2',
        'total_vat' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_number', 'supplier_id', 'total', 'status', 'payment_method'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('purchases')
            ->setDescriptionForEvent(fn(string $eventName) => "Achat {$eventName}")
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Écritures comptables liées à cet achat
     */
    public function accountingEntries()
    {
        return $this->morphMany(AccountingEntry::class, 'source');
    }

    protected static function booted()
    {
        static::creating(function ($purchase) {
            if (empty($purchase->invoice_number)) {
                // Numérotation séquentielle par entreprise
                $lastNumber = self::where('company_id', $purchase->company_id)
                    ->selectRaw("MAX(CAST(SUBSTRING(invoice_number, 5) AS UNSIGNED)) as max_num")
                    ->value('max_num') ?? 0;
                $purchase->invoice_number = 'ACH-' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
            }
            
            // Assigner l'entrepôt par défaut si non spécifié
            if (empty($purchase->warehouse_id)) {
                $defaultWarehouse = Warehouse::getDefault($purchase->company_id);
                $purchase->warehouse_id = $defaultWarehouse?->id;
            }
        });

        // Gérer le cas d'un achat créé directement avec status = 'completed'
        static::created(function ($purchase) {
            // Si l'achat est créé avec le statut "completed" ET que le total est défini
            if ($purchase->status === 'completed' && $purchase->total > 0) {
                // Créer la transaction bancaire si compte bancaire lié
                if ($purchase->bank_account_id) {
                    $exists = BankTransaction::where('reference', $purchase->invoice_number)->exists();
                    
                    if (!$exists) {
                        BankTransaction::create([
                            'bank_account_id' => $purchase->bank_account_id,
                            'date' => now(),
                            'amount' => $purchase->total,
                            'type' => 'debit',
                            'label' => "Achat " . $purchase->invoice_number,
                            'reference' => $purchase->invoice_number,
                            'status' => 'pending',
                            'metadata' => ['purchase_id' => $purchase->id],
                        ]);
                    }
                }

                // Générer les écritures comptables
                try {
                    $accountingService = app(\App\Services\AccountingEntryService::class);
                    $accountingService->createEntriesForPurchase($purchase);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error(
                        "Erreur génération écritures comptables achat (création) {$purchase->invoice_number}: " . $e->getMessage()
                    );
                }

                // Réceptionner le stock si créé directement en completed
                try {
                    $purchase->processStockReception();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error(
                        "Erreur réception stock achat (création) {$purchase->invoice_number}: " . $e->getMessage()
                    );
                }
            }
        });

        static::updated(function ($purchase) {
            // Si l'achat passe à "completed" et qu'un compte bancaire est lié
            if ($purchase->wasChanged('status') && $purchase->status === 'completed' && $purchase->bank_account_id) {
                // Vérifier si une transaction existe déjà pour éviter les doublons
                $exists = BankTransaction::where('reference', $purchase->invoice_number)->exists();
                
                if (!$exists) {
                    BankTransaction::create([
                        'bank_account_id' => $purchase->bank_account_id,
                        'date' => now(),
                        'amount' => $purchase->total,
                        'type' => 'debit',
                        'label' => "Achat " . $purchase->invoice_number,
                        'reference' => $purchase->invoice_number,
                        'status' => 'pending',
                        'metadata' => ['purchase_id' => $purchase->id],
                    ]);
                }
            }

            // Générer les écritures comptables quand l'achat passe à "completed"
            if ($purchase->wasChanged('status') && $purchase->status === 'completed') {
                try {
                    $accountingService = app(\App\Services\AccountingEntryService::class);
                    $accountingService->createEntriesForPurchase($purchase);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error(
                        "Erreur génération écritures comptables achat {$purchase->invoice_number}: " . $e->getMessage()
                    );
                }
            }

            if ($purchase->wasChanged('status')) {
                $oldStatus = $purchase->getOriginal('status');
                $newStatus = $purchase->status;

                if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                    // Annuler la réception de stock
                    $purchase->reverseStockReception();
                }
                elseif ($oldStatus !== 'completed' && $newStatus === 'completed') {
                    // Ajouter au stock
                    $purchase->processStockReception();
                }
            }
        });

        static::deleting(function ($purchase) {
            if ($purchase->status === 'completed') {
                $purchase->reverseStockReception();
            }
        });
    }

    /**
     * Ajouter le stock à l'entrepôt lors de la réception d'achat
     */
    public function processStockReception(): void
    {
        $warehouse = $this->warehouse ?? Warehouse::getDefault($this->company_id);
        
        if (!$warehouse) {
            throw new \Exception("Aucun entrepôt disponible pour traiter l'achat.");
        }

        foreach ($this->items as $item) {
            $warehouse->adjustStock(
                $item->product_id,
                $item->quantity,
                'purchase',
                "Achat {$this->invoice_number}",
                null
            );
        }
    }

    /**
     * Annuler la réception de stock
     */
    public function reverseStockReception(): void
    {
        $warehouse = $this->warehouse ?? Warehouse::getDefault($this->company_id);
        
        if (!$warehouse) {
            return;
        }

        foreach ($this->items as $item) {
            $warehouse->adjustStock(
                $item->product_id,
                -$item->quantity,
                'return_out',
                "Annulation achat {$this->invoice_number}",
                null
            );
        }
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function recalculateTotals(): void
    {
        // Calculer les totaux à partir des lignes (TVA gérée par ligne)
        $totalHt = $this->items()->sum('total_price_ht');
        $totalVat = $this->items()->sum('vat_amount');
        $subtotal = $totalHt + $totalVat; // Total TTC avant remise
        
        // Appliquer la remise globale (sur TTC)
        $discount = $subtotal * ($this->discount_percent / 100);
        $afterDiscount = $subtotal - $discount;
        
        $this->total_ht = round($totalHt * (1 - $this->discount_percent / 100), 2);
        $this->total_vat = round($totalVat * (1 - $this->discount_percent / 100), 2);
        $this->total = round($afterDiscount, 2);
        $this->save();

        // Créer la transaction bancaire si achat completed avec compte bancaire
        // (exécuté ici car le total est maintenant calculé)
        if ($this->status === 'completed' && $this->bank_account_id && $this->total > 0) {
            $exists = BankTransaction::where('reference', $this->invoice_number)->exists();
            
            if (!$exists) {
                BankTransaction::create([
                    'bank_account_id' => $this->bank_account_id,
                    'date' => now(),
                    'amount' => $this->total,
                    'type' => 'debit',
                    'label' => "Achat " . $this->invoice_number,
                    'reference' => $this->invoice_number,
                    'status' => 'pending',
                    'metadata' => ['purchase_id' => $this->id],
                ]);
            }
        }
    }

    /**
     * Retourne la ventilation TVA par taux (pour comptabilité)
     * TVA Déductible sur les achats
     * @return array [['rate' => 20, 'base' => 100, 'amount' => 20], ...]
     */
    public function getVatBreakdown(): array
    {
        $breakdown = [];
        
        foreach ($this->items as $item) {
            $rate = (string) $item->vat_rate;
            
            if (!isset($breakdown[$rate])) {
                $breakdown[$rate] = [
                    'rate' => (float) $rate,
                    'base' => 0,
                    'amount' => 0,
                ];
            }
            
            // Appliquer la remise proportionnellement
            $discountFactor = 1 - ($this->discount_percent / 100);
            $breakdown[$rate]['base'] += round($item->total_price_ht * $discountFactor, 2);
            $breakdown[$rate]['amount'] += round($item->vat_amount * $discountFactor, 2);
        }
        
        return array_values($breakdown);
    }
} 