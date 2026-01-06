<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UrssafAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'siret',
        'numero_compte',
        'urssaf_region',
        'solde_debiteur',
        'solde_crediteur',
        'derniere_echeance_payee',
        'prochaine_echeance',
        'montant_prochaine_echeance',
        'statut_conformite',
        'attestation_vigilance_validite',
        'attestation_vigilance_url',
        'last_synced_at',
        'raw_data',
    ];

    protected $casts = [
        'solde_debiteur' => 'decimal:2',
        'solde_crediteur' => 'decimal:2',
        'montant_prochaine_echeance' => 'decimal:2',
        'derniere_echeance_payee' => 'date',
        'prochaine_echeance' => 'date',
        'attestation_vigilance_validite' => 'date',
        'last_synced_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public function contributions(): HasMany
    {
        return $this->hasMany(UrssafContribution::class, 'company_id', 'company_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(UrssafPayment::class, 'company_id', 'company_id');
    }

    /**
     * Vérifie si l'attestation de vigilance est valide
     */
    public function hasValidVigilanceCertificate(): bool
    {
        return $this->attestation_vigilance_validite 
            && $this->attestation_vigilance_validite->isFuture();
    }

    /**
     * Retourne le solde net (dette - crédit)
     */
    public function getSoldeNetAttribute(): float
    {
        return $this->solde_debiteur - $this->solde_crediteur;
    }

    /**
     * Vérifie si l'entreprise est en conformité
     */
    public function isCompliant(): bool
    {
        return $this->statut_conformite === 'conforme';
    }

    /**
     * Badge de statut pour affichage
     */
    public function getStatutBadgeAttribute(): array
    {
        return match($this->statut_conformite) {
            'conforme' => ['label' => 'Conforme', 'color' => 'success'],
            'dette' => ['label' => 'Dette en cours', 'color' => 'warning'],
            'contentieux' => ['label' => 'Contentieux', 'color' => 'danger'],
            default => ['label' => 'Inconnu', 'color' => 'gray'],
        };
    }
}
