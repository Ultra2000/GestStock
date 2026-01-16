<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingCategory extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'color',
        'is_system',
        'parent_id',
        'description',
        'account_number',
        'account_vat',
        'default_vat_rate',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'default_vat_rate' => 'decimal:2',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccountingCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AccountingCategory::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'accounting_category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(AccountingRule::class);
    }

    /**
     * Récupère le compte de vente (avec héritage du parent si non défini)
     */
    public function getSalesAccount(): ?string
    {
        if ($this->account_number && $this->type === 'income') {
            return $this->account_number;
        }
        
        if ($this->parent) {
            return $this->parent->getSalesAccount();
        }
        
        return null;
    }

    /**
     * Récupère le compte d'achat (avec héritage du parent si non défini)
     */
    public function getPurchasesAccount(): ?string
    {
        if ($this->account_number && $this->type === 'expense') {
            return $this->account_number;
        }
        
        if ($this->parent) {
            return $this->parent->getPurchasesAccount();
        }
        
        return null;
    }

    /**
     * Récupère le compte TVA (avec héritage du parent si non défini)
     */
    public function getVatAccount(): ?string
    {
        if ($this->account_vat) {
            return $this->account_vat;
        }
        
        if ($this->parent) {
            return $this->parent->getVatAccount();
        }
        
        return null;
    }

    /**
     * Créer les catégories système par défaut
     */
    public static function createDefaults(int $companyId): void
    {
        $defaults = [
            // Revenus
            [
                'name' => 'Ventes de marchandises',
                'type' => 'income',
                'color' => '#10B981',
                'is_system' => true,
                'account_number' => '707000',
                'account_vat' => '445710',
                'default_vat_rate' => 20.00,
            ],
            [
                'name' => 'Prestations de services',
                'type' => 'income',
                'color' => '#3B82F6',
                'is_system' => true,
                'account_number' => '706000',
                'account_vat' => '445710',
                'default_vat_rate' => 20.00,
            ],
            [
                'name' => 'Produits alimentaires (5.5%)',
                'type' => 'income',
                'color' => '#F59E0B',
                'is_system' => true,
                'account_number' => '707100',
                'account_vat' => '445711',
                'default_vat_rate' => 5.50,
            ],
            [
                'name' => 'Restauration sur place (10%)',
                'type' => 'income',
                'color' => '#EF4444',
                'is_system' => true,
                'account_number' => '707200',
                'account_vat' => '445712',
                'default_vat_rate' => 10.00,
            ],
            // Dépenses
            [
                'name' => 'Achats de marchandises',
                'type' => 'expense',
                'color' => '#8B5CF6',
                'is_system' => true,
                'account_number' => '607000',
                'account_vat' => '445660',
                'default_vat_rate' => 20.00,
            ],
            [
                'name' => 'Services extérieurs',
                'type' => 'expense',
                'color' => '#EC4899',
                'is_system' => true,
                'account_number' => '626000',
                'account_vat' => '445660',
                'default_vat_rate' => 20.00,
            ],
        ];

        foreach ($defaults as $category) {
            static::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $category['name'],
                ],
                array_merge($category, ['company_id' => $companyId])
            );
        }
    }
}
