<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class UrssafContribution extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'periode',
        'date_exigibilite',
        'type_cotisation',
        'base',
        'taux',
        'montant',
        'montant_paye',
        'statut',
        'dsn_reference',
        'effectif',
        'masse_salariale',
        'raw_data',
    ];

    protected $casts = [
        'periode' => 'date',
        'date_exigibilite' => 'date',
        'base' => 'decimal:2',
        'taux' => 'decimal:4',
        'montant' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'effectif' => 'integer',
        'masse_salariale' => 'decimal:2',
        'raw_data' => 'array',
    ];

    /**
     * Relations vers le compte URSSAF
     */
    public function urssafAccount()
    {
        return $this->hasOne(UrssafAccount::class, 'company_id', 'company_id');
    }

    /**
     * Reste à payer
     */
    public function getResteAPayerAttribute(): float
    {
        return max(0, $this->montant - $this->montant_paye);
    }

    /**
     * Est entièrement payé
     */
    public function isPaid(): bool
    {
        return $this->montant_paye >= $this->montant;
    }

    /**
     * Badge de statut
     */
    public function getStatutBadgeAttribute(): array
    {
        return match($this->statut) {
            'paye' => ['label' => 'Payé', 'color' => 'success'],
            'partiel' => ['label' => 'Partiel', 'color' => 'warning'],
            'impaye' => ['label' => 'Impayé', 'color' => 'danger'],
            'en_cours' => ['label' => 'En cours', 'color' => 'info'],
            default => ['label' => 'En attente', 'color' => 'gray'],
        };
    }

    /**
     * Formatte la période en mois/année
     */
    public function getPeriodeFormatteeAttribute(): string
    {
        return $this->periode ? $this->periode->translatedFormat('F Y') : '-';
    }

    /**
     * Scope pour les cotisations impayées
     */
    public function scopeImpayees($query)
    {
        return $query->whereIn('statut', ['impaye', 'partiel']);
    }

    /**
     * Scope pour une année donnée
     */
    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('periode', $year);
    }
}
