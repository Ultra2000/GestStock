<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Warehouse;

class CompanyObserver
{
    /**
     * Les rôles par défaut à créer pour chaque nouvelle entreprise
     */
    protected array $defaultRoles = [
        'admin' => [
            'name' => 'Administrateur',
            'description' => 'Accès complet à toutes les fonctionnalités',
            'permissions' => '*',
        ],
        'manager' => [
            'name' => 'Gestionnaire',
            'description' => 'Gestion des opérations quotidiennes',
            'permissions' => [
                'products.view', 'products.create', 'products.edit', 'products.stock',
                'sales.view', 'sales.create', 'sales.edit',
                'purchases.view', 'purchases.create', 'purchases.edit',
                'customers.view', 'customers.create', 'customers.edit',
                'suppliers.view', 'suppliers.create', 'suppliers.edit',
                'quotes.view', 'quotes.create', 'quotes.edit',
                'deliveries.view', 'deliveries.create', 'deliveries.edit',
                'pos.access', 'pos.session', 'pos.reports',
                'warehouses.view', 'transfers.create', 'transfers.approve', 'inventory.manage',
                'employees.view', 'schedule.manage', 'leaves.manage', 'attendance.view',
                'reports.view',
            ],
        ],
        'cashier' => [
            'name' => 'Caissier',
            'description' => 'Opérations de vente et caisse',
            'permissions' => [
                'products.view',
                'sales.view', 'sales.create',
                'customers.view', 'customers.create',
                'pos.access', 'pos.session',
            ],
        ],
        'vendeur' => [
            'name' => 'Vendeur',
            'description' => 'Ventes et relation client',
            'permissions' => [
                'products.view',
                'sales.view', 'sales.create',
                'customers.view', 'customers.create', 'customers.edit',
                'quotes.view', 'quotes.create',
                'deliveries.view',
                'pos.access',
            ],
        ],
        'magasinier' => [
            'name' => 'Magasinier',
            'description' => 'Gestion du stock et des entrepôts',
            'permissions' => [
                'products.view', 'products.stock',
                'purchases.view',
                'warehouses.view', 'transfers.create', 'inventory.manage',
            ],
        ],
        'user' => [
            'name' => 'Utilisateur',
            'description' => 'Accès limité en lecture',
            'permissions' => [
                'products.view',
                'sales.view',
                'customers.view',
            ],
            'is_default' => true,
        ],
    ];

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        $this->createDefaultRoles($company);
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
     * Crée les rôles par défaut pour une entreprise
     */
    protected function createDefaultRoles(Company $company): void
    {
        foreach ($this->defaultRoles as $slug => $roleData) {
            $role = Role::create([
                'company_id' => $company->id,
                'slug' => $slug,
                'name' => $roleData['name'],
                'description' => $roleData['description'],
                'is_default' => $roleData['is_default'] ?? false,
            ]);

            // Assigner les permissions
            if ($roleData['permissions'] === '*') {
                $role->permissions()->sync(Permission::pluck('id'));
            } else {
                $permissionIds = Permission::whereIn('slug', $roleData['permissions'])->pluck('id');
                $role->permissions()->sync($permissionIds);
            }
        }
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        // Les rôles seront supprimés en cascade grâce aux foreign keys
    }
}
