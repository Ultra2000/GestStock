<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Grand Livre - Écritures Comptables Immutables
 * 
 * Table conforme FEC (Fichier des Écritures Comptables) pour le contrôle fiscal.
 * Les écritures sont verrouillées dès leur création et ne peuvent être modifiées.
 * Seul le lettrage peut être ajouté/modifié.
 */
class AccountingEntry extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'source_type',
        'source_id',
        'fec_sequence',
        'entry_date',
        'piece_number',
        'journal_code',
        'account_number',
        'account_auxiliary',
        'label',
        'debit',
        'credit',
        'vat_rate',
        'vat_base',
        'currency',
        'lettering',
        'lettering_date',
        'is_locked',
        'reversal_of_id',
        'payment_for_id',
        'entry_type',
        'created_by',
        'creation_source',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'lettering_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_base' => 'decimal:2',
        'is_locked' => 'boolean',
    ];

    protected $attributes = [
        'currency' => 'EUR',
        'is_locked' => true,
        'creation_source' => 'auto',
        'debit' => 0,
        'credit' => 0,
    ];

    /**
     * PROTECTION IMMUTABILITÉ
     * Une écriture verrouillée ne peut pas être modifiée
     */
    protected static function boot()
    {
        parent::boot();

        // Attribution automatique du numéro FEC séquentiel
        static::creating(function ($entry) {
            if (!$entry->fec_sequence) {
                $entry->fec_sequence = static::where('company_id', $entry->company_id)
                    ->max('fec_sequence') + 1 ?? 1;
            }
        });

        static::updating(function ($entry) {
            // Seuls le lettrage et quelques champs peuvent être modifiés sur une écriture verrouillée
            $allowedChanges = ['lettering', 'lettering_date'];
            
            if ($entry->getOriginal('is_locked')) {
                $dirty = array_keys($entry->getDirty());
                $forbiddenChanges = array_diff($dirty, $allowedChanges);
                
                if (!empty($forbiddenChanges)) {
                    throw new \Exception(
                        "Modification interdite : Cette écriture comptable est verrouillée. " .
                        "Champs modifiés: " . implode(', ', $forbiddenChanges)
                    );
                }
            }
        });

        static::deleting(function ($entry) {
            if ($entry->is_locked) {
                throw new \Exception(
                    "Suppression interdite : Cette écriture comptable est verrouillée. " .
                    "Utilisez une contre-passation pour annuler."
                );
            }
        });
    }

    /**
     * Document source (Sale, Purchase, etc.)
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Écriture de contre-passation originale (si c'est un avoir)
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'reversal_of_id');
    }

    /**
     * Écritures de contre-passation de cette écriture
     */
    public function reversals()
    {
        return $this->hasMany(AccountingEntry::class, 'reversal_of_id');
    }

    /**
     * Utilisateur qui a créé l'écriture
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Solde de la ligne (débit - crédit)
     */
    public function getBalanceAttribute(): float
    {
        return $this->debit - $this->credit;
    }

    /**
     * Scope : Écritures d'un journal
     */
    public function scopeJournal($query, string $code)
    {
        return $query->where('journal_code', $code);
    }

    /**
     * Scope : Écritures d'un compte
     */
    public function scopeAccount($query, string $number)
    {
        return $query->where('account_number', $number);
    }

    /**
     * Scope : Écritures sur une période
     */
    public function scopePeriod($query, $start, $end)
    {
        return $query->whereBetween('entry_date', [$start, $end]);
    }

    /**
     * Scope : Écritures non lettrées
     */
    public function scopeUnlettered($query)
    {
        return $query->whereNull('lettering');
    }

    /**
     * Récupérer le solde d'un compte pour une entreprise
     */
    public static function getAccountBalance(int $companyId, string $accountNumber, ?string $endDate = null): float
    {
        $query = static::where('company_id', $companyId)
            ->where('account_number', $accountNumber);
        
        if ($endDate) {
            $query->where('entry_date', '<=', $endDate);
        }
        
        return $query->sum('debit') - $query->sum('credit');
    }

    /**
     * Récupérer le solde d'un compte auxiliaire
     */
    public static function getAuxiliaryBalance(int $companyId, string $auxiliary, ?string $endDate = null): float
    {
        $query = static::where('company_id', $companyId)
            ->where('account_auxiliary', $auxiliary);
        
        if ($endDate) {
            $query->where('entry_date', '<=', $endDate);
        }
        
        return $query->sum('debit') - $query->sum('credit');
    }

    /**
     * Vérifier l'équilibre d'une pièce (somme débits = somme crédits)
     */
    public static function isPieceBalanced(int $companyId, string $pieceNumber): bool
    {
        $totals = static::where('company_id', $companyId)
            ->where('piece_number', $pieceNumber)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();
        
        return abs($totals->total_debit - $totals->total_credit) < 0.01;
    }

    /**
     * Formater pour export FEC
     * Format conforme DGFiP: séparateur décimal = virgule, séparateur champs = pipe
     */
    public function toFecArray(): array
    {
        return [
            'JournalCode' => $this->journal_code,
            'JournalLib' => $this->getJournalLabel(),
            'EcritureNum' => $this->fec_sequence ?? $this->id, // Numéro séquentiel global sans trou
            'EcritureDate' => $this->entry_date->format('Ymd'),
            'CompteNum' => $this->account_number,
            'CompteLib' => $this->getAccountLabel(),
            'CompAuxNum' => $this->account_auxiliary ?? '',
            'CompAuxLib' => $this->getAuxiliaryLabel(),
            'PieceRef' => $this->piece_number,
            'PieceDate' => $this->entry_date->format('Ymd'),
            'EcritureLib' => $this->label,
            'Debit' => number_format($this->debit, 2, ',', ''),
            'Credit' => number_format($this->credit, 2, ',', ''),
            'EcritureLet' => $this->lettering ?? '',
            'DateLet' => $this->lettering_date?->format('Ymd') ?? '',
            'ValidDate' => $this->created_at->format('Ymd'),
            'Montantdevise' => '',
            'Idevise' => $this->currency,
        ];
    }

    /**
     * Libellé du compte auxiliaire
     */
    protected function getAuxiliaryLabel(): string
    {
        if (!$this->account_auxiliary) {
            return '';
        }

        // CLI-00045 -> Chercher le client
        if (str_starts_with($this->account_auxiliary, 'CLI-')) {
            $customerId = (int) str_replace('CLI-', '', $this->account_auxiliary);
            $customer = Customer::find($customerId);
            return $customer?->name ?? 'Client ' . $this->account_auxiliary;
        }

        // FRN-00012 -> Chercher le fournisseur
        if (str_starts_with($this->account_auxiliary, 'FRN-')) {
            $supplierId = (int) str_replace('FRN-', '', $this->account_auxiliary);
            $supplier = Supplier::find($supplierId);
            return $supplier?->name ?? 'Fournisseur ' . $this->account_auxiliary;
        }

        return $this->account_auxiliary;
    }

    /**
     * Libellé du journal
     */
    protected function getJournalLabel(): string
    {
        $labels = [
            'VTE' => 'Journal des Ventes',
            'ACH' => 'Journal des Achats',
            'BQ' => 'Journal de Banque',
            'CAI' => 'Journal de Caisse',
            'OD' => 'Opérations Diverses',
        ];
        
        return $labels[$this->journal_code] ?? $this->journal_code;
    }

    /**
     * Libellé du compte (simplifié)
     */
    protected function getAccountLabel(): string
    {
        $labels = [
            '411' => 'Clients',
            '401' => 'Fournisseurs',
            '512' => 'Banque',
            '530' => 'Caisse',
            '607' => 'Achats de marchandises',
            '701' => 'Ventes de produits finis',
            '706' => 'Prestations de services',
            '707' => 'Ventes de marchandises',
            '445' => 'TVA',
        ];
        
        $prefix = substr($this->account_number, 0, 3);
        return $labels[$prefix] ?? 'Compte ' . $this->account_number;
    }
}
