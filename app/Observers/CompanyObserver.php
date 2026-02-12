<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Warehouse;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     * 
     * Note : La création des rôles est gérée exclusivement par 
     * RolesAndPermissionsSeeder::createRolesForCompany() appelé 
     * depuis RegisterCompany::handleRegistration().
     * Ne PAS dupliquer la logique ici.
     */
    public function created(Company $company): void
    {
        $this->createDefaultWarehouse($company);
    }

    /**
     * Crée l'entrepôt par défaut pour une nouvelle entreprise
     */
    protected function createDefaultWarehouse(Company $company): void
    {
        Warehouse::create([
            'company_id' => $company->id,
            'code' => 'MAIN',
            'name' => 'Entrepôt Principal',
            'type' => 'warehouse',
            'is_default' => true,
            'is_active' => true,
            'allow_negative_stock' => false,
            'is_pos_location' => true,
            'address' => $company->address,
            'city' => $company->city,
            'country' => $company->country ?? 'SN',
        ]);
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        // Les rôles seront supprimés en cascade grâce aux foreign keys
    }
}
