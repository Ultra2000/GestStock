<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Sale extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    protected $fillable = [
        'company_id',
        'cash_session_id',
        'invoice_number',
        'type',
        'parent_id',
        'customer_id',
        'warehouse_id',
        'bank_account_id',
        'total',
        'total_ht',
        'total_vat',
        'status',
        'payment_status',
        'amount_paid',
        'paid_at',
        'payment_method',
        'payment_details',
        'discount_percent',
        'tax_percent',
        'security_hash',
        'previous_hash',
        'notes',
        'ppf_status',
        'ppf_id',
        'ppf_chorus_id',
        'ppf_synced_at',
    ];

    protected $casts = [
        'status' => 'string',
        'payment_status' => 'string',
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
        'payment_details' => 'array',
        'ppf_synced_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'parent_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_number', 'customer_id', 'total', 'status', 'payment_method'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('sales')
            ->setDescriptionForEvent(fn(string $eventName) => "Vente {$eventName}")
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(Sale::class, 'parent_id')->where('type', 'credit_note');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Paiements reçus pour cette vente
     */
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Écritures comptables liées à cette vente
     */
    public function accountingEntries()
    {
        return $this->morphMany(AccountingEntry::class, 'source');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->invoice_number)) {
                // Numérotation séquentielle par entreprise avec Année (Format FAC-YYYY-XXXXX)
                $year = date('Y');
                $prefix = ($sale->type === 'credit_note' ? 'AVR-' : 'FAC-') . $year . '-';
                
                $lastNumber = self::where('company_id', $sale->company_id)
                    ->where('invoice_number', 'like', $prefix . '%')
                    ->selectRaw("MAX(CAST(SUBSTRING(invoice_number, 10) AS UNSIGNED)) as max_num")
                    ->value('max_num') ?? 0;
                
                $sale->invoice_number = $prefix . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
            }
            
            // Assigner l'entrepôt par défaut si non spécifié
            if (empty($sale->warehouse_id)) {
                $defaultWarehouse = Warehouse::getDefault($sale->company_id);
                $sale->warehouse_id = $defaultWarehouse?->id;
            }

            // Génération du hash de sécurité (Chaînage NF525)
            $sale->generateSecurityHash();
        });

        // Gérer le cas d'une vente créée directement avec status = 'completed'
        static::created(function ($sale) {
            // Si la vente est créée avec le statut "completed"
            if ($sale->status === 'completed') {
                // Créer la transaction bancaire si compte bancaire lié ET si le total est défini
                // Note: Le total peut être NULL si les items n'ont pas encore été ajoutés
                if ($sale->bank_account_id && $sale->total > 0) {
                    $exists = BankTransaction::where('reference', $sale->invoice_number)->exists();
                    
                    if (!$exists) {
                        BankTransaction::create([
                            'bank_account_id' => $sale->bank_account_id,
                            'date' => now(),
                            'amount' => $sale->total,
                            'type' => $sale->type === 'credit_note' ? 'debit' : 'credit',
                            'label' => ($sale->type === 'credit_note' ? "Avoir " : "Vente ") . $sale->invoice_number,
                            'reference' => $sale->invoice_number,
                            'status' => 'pending',
                            'metadata' => ['sale_id' => $sale->id],
                        ]);
                    }
                }

                // Générer les écritures comptables
                try {
                    $accountingService = app(\App\Services\AccountingEntryService::class);
                    
                    // Si c'est un avoir avec une facture parente, contre-passer les écritures
                    if ($sale->type === 'credit_note' && $sale->parent_id) {
                        $originalSale = Sale::find($sale->parent_id);
                        if ($originalSale) {
                            $accountingService->reverseEntries($originalSale, $sale);
                        }
                    } else {
                        // Vente normale : créer les écritures standard
                        $accountingService->createEntriesForSale($sale);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error(
                        "Erreur génération écritures comptables {$sale->type} (création) {$sale->invoice_number}: " . $e->getMessage()
                    );
                }

                // Enregistrer le paiement POS si payé immédiatement
                if ($sale->payment_method && $sale->cash_session_id) {
                    try {
                        $accountingService = app(\App\Services\AccountingEntryService::class);
                        $accountingService->registerPosPayment($sale);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error(
                            "Erreur enregistrement paiement POS {$sale->invoice_number}: " . $e->getMessage()
                        );
                    }
                }
            }
        });

        static::updated(function ($sale) {
            // Si la vente passe à "completed" et qu'un compte bancaire est lié
            if ($sale->wasChanged('status') && $sale->status === 'completed' && $sale->bank_account_id) {
                // Vérifier si une transaction existe déjà pour éviter les doublons
                $exists = BankTransaction::where('reference', $sale->invoice_number)->exists();
                
                if (!$exists) {
                    BankTransaction::create([
                        'bank_account_id' => $sale->bank_account_id,
                        'date' => now(),
                        'amount' => $sale->total,
                        'type' => 'credit',
                        'label' => "Vente " . $sale->invoice_number,
                        'reference' => $sale->invoice_number,
                        'status' => 'pending',
                        'metadata' => ['sale_id' => $sale->id],
                    ]);
                }
            }

            // Générer les écritures comptables quand la vente passe à "completed"
            if ($sale->wasChanged('status') && $sale->status === 'completed') {
                try {
                    $accountingService = app(\App\Services\AccountingEntryService::class);
                    
                    // Avoir avec facture parente : contre-passer les écritures originales
                    if ($sale->type === 'credit_note' && $sale->parent_id) {
                        $originalSale = Sale::find($sale->parent_id);
                        if ($originalSale) {
                            $accountingService->reverseEntries($originalSale, $sale);
                        }
                    } else {
                        // Vente normale : créer les écritures standard
                        $accountingService->createEntriesForSale($sale);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error(
                        "Erreur génération écritures comptables {$sale->type} {$sale->invoice_number}: " . $e->getMessage()
                    );
                }
            }
        });

        static::updating(function ($sale) {
            // Protection NF525 : Vérifier l'intégrité de la chaîne
            if ($sale->security_hash) {
                // Vérifier s'il existe une facture postérieure scellée
                $nextSaleExists = self::where('company_id', $sale->company_id)
                    ->where('id', '>', $sale->id)
                    ->whereNotNull('security_hash')
                    ->exists();

                if ($nextSaleExists) {
                    // Si on essaie de modifier des données critiques d'une facture déjà chaînée
                    if ($sale->isDirty(['total', 'invoice_number', 'created_at', 'customer_id'])) {
                        throw new \Exception("Opération illégale (NF525) : Impossible de modifier une facture déjà scellée et chaînée.");
                    }
                } else {
                    // C'est la dernière facture, on peut mettre à jour le hash
                    // uniquement si les données critiques ont changé
                    if ($sale->isDirty(['total', 'invoice_number', 'created_at', 'customer_id'])) {
                        $sale->generateSecurityHash();
                    }
                }
            } else {
                // Si pas de hash (ex: brouillon ou ancienne facture), on le génère
                $sale->generateSecurityHash();
            }
        });

        static::saving(function ($sale) {
            if ($sale->isDirty('status') && $sale->status === 'completed') {
                // Avoir : reconstituer le stock au lieu de le déduire
                if ($sale->type === 'credit_note') {
                    $sale->reverseStockDeduction();
                } else {
                    $sale->processStockDeduction();
                }
            }
        });

        static::deleting(function ($sale) {
            if ($sale->status === 'completed') {
                $sale->reverseStockDeduction();
            }
        });
    }

    /**
     * Déduire le stock de l'entrepôt lors de la vente
     */
    public function processStockDeduction(): void
    {
        $warehouse = $this->warehouse ?? Warehouse::getDefault($this->company_id);
        
        if (!$warehouse) {
            throw new \Exception("Aucun entrepôt disponible pour traiter la vente.");
        }

        foreach ($this->items as $item) {
            // Créer mouvement de stock via le système multi-entrepôt
            $warehouse->adjustStock(
                $item->product_id,
                -$item->quantity,
                'sale',
                "Vente {$this->invoice_number}",
                null // location_id
            );
        }
    }

    /**
     * Annuler la déduction de stock (suppression ou annulation)
     */
    public function reverseStockDeduction(): void
    {
        $warehouse = $this->warehouse ?? Warehouse::getDefault($this->company_id);
        
        if (!$warehouse) {
            return;
        }

        foreach ($this->items as $item) {
            $warehouse->adjustStock(
                $item->product_id,
                $item->quantity,
                'return_in',
                "Annulation vente {$this->invoice_number}",
                null
            );
        }
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function calculateTotal(): void
    {
        // Calculer les totaux à partir des lignes (TVA gérée par ligne)
        $totalHt = $this->items()->sum('total_price_ht');
        $totalVat = $this->items()->sum('vat_amount');
        $subtotal = $totalHt + $totalVat; // Total TTC avant remise
        
        // Appliquer la remise globale (sur TTC)
        $discount = $subtotal * ($this->discount_percent / 100);
        $afterDiscount = $subtotal - $discount;
        
        // Note: tax_percent est maintenant obsolète car la TVA est gérée par ligne
        // On le garde pour compatibilité mais il ne devrait plus être utilisé
        
        $this->total_ht = round($totalHt * (1 - $this->discount_percent / 100), 2);
        $this->total_vat = round($totalVat * (1 - $this->discount_percent / 100), 2);
        $this->total = round($afterDiscount, 2);
        $this->save();

        // Créer la transaction bancaire si vente completed avec compte bancaire
        // (exécuté ici car le total est maintenant calculé)
        if ($this->status === 'completed' && $this->bank_account_id && $this->total > 0) {
            $exists = BankTransaction::where('reference', $this->invoice_number)->exists();
            
            if (!$exists) {
                BankTransaction::create([
                    'bank_account_id' => $this->bank_account_id,
                    'date' => now(),
                    'amount' => $this->total,
                    'type' => $this->type === 'credit_note' ? 'debit' : 'credit',
                    'label' => ($this->type === 'credit_note' ? "Avoir " : "Vente ") . $this->invoice_number,
                    'reference' => $this->invoice_number,
                    'status' => 'pending',
                    'metadata' => ['sale_id' => $this->id],
                ]);
            }
        }
    }

    /**
     * Retourne la ventilation TVA par taux (pour Chorus Pro)
     * @return array [['rate' => 20, 'base' => 100, 'amount' => 20, 'category' => 'S'], ...]
     */
    public function getVatBreakdown(): array
    {
        $breakdown = [];
        
        foreach ($this->items as $item) {
            $rate = (string) $item->vat_rate;
            $category = $item->vat_category ?? 'S';
            $key = $rate . '_' . $category;
            
            if (!isset($breakdown[$key])) {
                $breakdown[$key] = [
                    'rate' => (float) $rate,
                    'category' => $category,
                    'base' => 0,
                    'amount' => 0,
                ];
            }
            
            // Appliquer la remise proportionnellement
            $discountFactor = 1 - ($this->discount_percent / 100);
            $breakdown[$key]['base'] += round($item->total_price_ht * $discountFactor, 2);
            $breakdown[$key]['amount'] += round($item->vat_amount * $discountFactor, 2);
        }
        
        return array_values($breakdown);
    }

    /**
     * Génère le hash de sécurité pour la facture (Conformité NF525)
     * Le hash inclut les données critiques de la facture actuelle + le hash de la facture précédente.
     */
    public function generateSecurityHash(): void
    {
        // 1. Récupérer la dernière facture de l'entreprise (N-1)
        // On cherche la facture précédente (ID inférieur) ayant un hash
        $query = self::where('company_id', $this->company_id)
            ->whereNotNull('security_hash');
            
        if ($this->id) {
            $query->where('id', '<', $this->id);
        }
            
        $previousSale = $query->orderBy('id', 'desc')->first();

        $previousHash = $previousSale ? $previousSale->security_hash : '';

        // 2. Construire la chaîne de données à signer
        // Format: [DateYYYYMMDDHHMMSS][Total][InvoiceNumber][PreviousHash]
        // On utilise created_at si dispo, sinon now()
        $dateStr = ($this->created_at ?? now())->format('YmdHis');
        
        $dataToSign = implode('|', [
            $dateStr,
            $this->invoice_number ?? '',
            number_format($this->total ?? 0, 2, '.', ''),
            $this->customer_id ?? '0',
            $previousHash
        ]);

        // 3. Générer le hash SHA-256
        $this->previous_hash = $previousHash;
        $this->security_hash = hash('sha256', $dataToSign);
    }
}
