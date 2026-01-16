<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Modèle Payment - Suivi des règlements
 * 
 * Permet de tracer chaque paiement reçu ou émis et de générer
 * les écritures comptables correspondantes (512/530 vs 411/401).
 */
class Payment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'payable_type',
        'payable_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference',
        'account_number',
        'cash_session_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Modes de paiement disponibles
     */
    public const METHODS = [
        'cash' => 'Espèces',
        'card' => 'Carte bancaire',
        'transfer' => 'Virement',
        'check' => 'Chèque',
        'other' => 'Autre',
    ];

    /**
     * Comptes par mode de paiement
     */
    public const ACCOUNTS = [
        'cash' => '530000', // Caisse
        'card' => '512000', // Banque (CB = virement direct)
        'transfer' => '512000', // Banque
        'check' => '511200', // Chèques à encaisser (puis 512 après dépôt)
        'other' => '512000', // Banque par défaut
    ];

    /**
     * Document payé (Sale ou Purchase)
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Session de caisse
     */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /**
     * Utilisateur qui a enregistré le paiement
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Écritures comptables liées à ce paiement
     */
    public function accountingEntries()
    {
        return $this->morphMany(AccountingEntry::class, 'source');
    }

    /**
     * Boot : générer les écritures comptables à la création
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function (Payment $payment) {
            try {
                $accountingService = app(\App\Services\AccountingEntryService::class);
                $accountingService->createEntriesForPayment($payment);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error(
                    "Erreur génération écritures paiement : " . $e->getMessage()
                );
            }
        });
    }

    /**
     * Récupérer le compte comptable selon le mode de paiement
     */
    public function getAccountForMethod(): string
    {
        if ($this->account_number) {
            return $this->account_number;
        }

        $settings = AccountingSetting::getForCompany($this->company_id);

        return match ($this->payment_method) {
            'cash' => $settings->account_cash ?? '530000',
            'check' => '511200', // Chèques à encaisser
            default => $settings->account_bank ?? '512000',
        };
    }

    /**
     * Détermine le journal comptable
     */
    public function getJournalCode(): string
    {
        $settings = AccountingSetting::getForCompany($this->company_id);

        return match ($this->payment_method) {
            'cash' => $settings->journal_cash ?? 'CAI',
            default => $settings->journal_bank ?? 'BQ',
        };
    }

    /**
     * Vérifie si c'est un paiement client (vente) ou fournisseur (achat)
     */
    public function isCustomerPayment(): bool
    {
        return $this->payable_type === Sale::class;
    }

    /**
     * Vérifie si c'est un paiement fournisseur
     */
    public function isSupplierPayment(): bool
    {
        return $this->payable_type === Purchase::class;
    }
}
