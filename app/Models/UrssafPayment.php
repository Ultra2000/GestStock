<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class UrssafPayment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'reference',
        'montant',
        'date_paiement',
        'mode_paiement',
        'statut',
        'periode_debut',
        'periode_fin',
        'tipi_reference',
        'transaction_id',
        'error_message',
        'raw_data',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
        'periode_debut' => 'date',
        'periode_fin' => 'date',
        'raw_data' => 'array',
    ];

    /**
     * Génère une référence unique
     */
    public static function generateReference(): string
    {
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -5));
        
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Boot model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->reference)) {
                $payment->reference = self::generateReference();
            }
        });
    }

    /**
     * Badge de mode de paiement
     */
    public function getModePaiementBadgeAttribute(): array
    {
        return match($this->mode_paiement) {
            'prelevement' => ['label' => 'Prélèvement', 'icon' => 'heroicon-o-credit-card'],
            'virement' => ['label' => 'Virement', 'icon' => 'heroicon-o-banknotes'],
            'tipi' => ['label' => 'TiPi', 'icon' => 'heroicon-o-building-library'],
            'cheque' => ['label' => 'Chèque', 'icon' => 'heroicon-o-document-text'],
            default => ['label' => 'Autre', 'icon' => 'heroicon-o-question-mark-circle'],
        };
    }

    /**
     * Badge de statut
     */
    public function getStatutBadgeAttribute(): array
    {
        return match($this->statut) {
            'initie' => ['label' => 'Initié', 'color' => 'info'],
            'en_cours' => ['label' => 'En cours', 'color' => 'warning'],
            'valide' => ['label' => 'Validé', 'color' => 'success'],
            'refuse' => ['label' => 'Refusé', 'color' => 'danger'],
            'erreur' => ['label' => 'Erreur', 'color' => 'danger'],
            default => ['label' => 'En attente', 'color' => 'gray'],
        };
    }

    /**
     * Scope pour les paiements réussis
     */
    public function scopeSuccessful($query)
    {
        return $query->where('statut', 'valide');
    }

    /**
     * Scope pour les paiements en erreur
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('statut', ['refuse', 'erreur']);
    }

    /**
     * Total payé pour une période
     */
    public static function totalForPeriod(int $companyId, $start, $end): float
    {
        return static::where('company_id', $companyId)
            ->where('statut', 'valide')
            ->whereBetween('date_paiement', [$start, $end])
            ->sum('montant');
    }
}
