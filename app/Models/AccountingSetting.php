<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AccountingSetting extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'account_customers',
        'account_suppliers',
        'account_sales',
        'account_purchases',
        'account_vat_collected',
        'account_vat_deductible',
        'account_bank',
        'account_cash',
        'account_discounts_granted',
        'account_discounts_received',
        'journal_sales',
        'journal_purchases',
        'journal_bank',
        'journal_cash',
        'journal_misc',
        'fec_siren',
        'fec_company_name',
        'accounting_software',
        'accounting_software_version',
        'is_vat_franchise',
        'vat_regime',
    ];

    protected $casts = [
        'is_vat_franchise' => 'boolean',
    ];

    /**
     * Régimes TVA disponibles
     */
    public const VAT_REGIMES = [
        'debits' => 'TVA sur les débits (facturation)',
        'encaissements' => 'TVA sur les encaissements (paiement)',
    ];

    /**
     * Vérifie si l'entreprise est en franchise de TVA
     */
    public static function isVatFranchise(?int $companyId = null): bool
    {
        $companyId = $companyId ?? filament()->getTenant()?->id;
        if (!$companyId) {
            return false;
        }
        
        return static::where('company_id', $companyId)->value('is_vat_franchise') ?? false;
    }

    /**
     * Récupérer ou créer les paramètres comptables pour une entreprise
     */
    public static function getForCompany(int $companyId): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId],
            [
                'account_customers' => '411000',
                'account_suppliers' => '401000',
                'account_sales' => '707000', // Ventes de marchandises (défaut TPE/PME)
                'account_purchases' => '607000',
                'account_vat_collected' => '445710',
                'account_vat_deductible' => '445660',
                'account_bank' => '512000',
                'account_cash' => '530000',
                'account_discounts_granted' => '709000',
                'account_discounts_received' => '609000',
                'journal_sales' => 'VTE',
                'journal_purchases' => 'ACH',
                'journal_bank' => 'BQ',
                'journal_cash' => 'CAI',
                'journal_misc' => 'OD',
                'accounting_software' => 'FRECORP ERP',
                'accounting_software_version' => '1.0',
                'vat_regime' => 'debits', // Par défaut : TVA sur les débits
            ]
        );
    }

    /**
     * Vérifie si le régime TVA est sur les encaissements
     */
    public static function isVatOnReceipts(?int $companyId = null): bool
    {
        $companyId = $companyId ?? filament()->getTenant()?->id;
        if (!$companyId) {
            return false;
        }
        
        return static::where('company_id', $companyId)->value('vat_regime') === 'encaissements';
    }
}
