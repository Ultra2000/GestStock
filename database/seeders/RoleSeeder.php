<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Rôles par défaut avec leurs permissions
     */
    protected array $defaultRoles = [
        'admin' => [
            'name' => 'Administrateur',
            'description' => 'Accès complet à toutes les fonctionnalités',
            'permissions' => '*', // Toutes les permissions
        ],
        'manager' => [
            'name' => 'Manager',
            'description' => 'Gestion complète sauf administration',
            'permissions' => [
                'products.*',
                'customers.*',
                'suppliers.*',
                'sales.*',
                'purchases.*',
                'reports.*',
                'users.view',
            ],
        ],
        'vendeur' => [
            'name' => 'Vendeur',
            'description' => 'Gestion des ventes et clients',
            'permissions' => [
                'products.view',
                'customers.view',
                'customers.create',
                'customers.update',
                'sales.view',
                'sales.create',
                'sales.update',
            ],
        ],
        'comptable' => [
            'name' => 'Comptable',
            'description' => 'Accès aux rapports et finances',
            'permissions' => [
                'products.view',
                'customers.view',
                'suppliers.view',
                'sales.view',
                'purchases.view',
                'reports.*',
            ],
        ],
        'magasinier' => [
            'name' => 'Magasinier',
            'description' => 'Gestion du stock et des achats',
            'permissions' => [
                'products.view',
                'products.create',
                'products.update',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'purchases.view',
                'purchases.create',
                'purchases.update',
            ],
        ],
    ];

    public function run(): void
    {
        // Créer les rôles pour chaque entreprise existante
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $this->command->warn('Aucune entreprise trouvée. Les rôles seront créés lors de la création d\'une entreprise.');
            return;
        }

        foreach ($companies as $company) {
            $this->createRolesForCompany($company);
        }

        $this->command->info('Rôles créés avec succès pour toutes les entreprises !');
    }

    /**
     * Crée les rôles par défaut pour une entreprise
     */
    public function createRolesForCompany(Company $company): void
    {
        $allPermissions = Permission::all();

        foreach ($this->defaultRoles as $slug => $roleData) {
            // Créer le rôle
            $role = Role::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_default' => $slug === 'vendeur', // Vendeur par défaut
                ]
            );

            // Assigner les permissions
            if ($roleData['permissions'] === '*') {
                // Admin a toutes les permissions
                $role->permissions()->sync($allPermissions->pluck('id'));
            } else {
                $permissionIds = [];
                foreach ($roleData['permissions'] as $permPattern) {
                    if (str_ends_with($permPattern, '.*')) {
                        // Pattern module.* -> toutes les permissions du module
                        $module = str_replace('.*', '', $permPattern);
                        $modulePermissions = $allPermissions->where('module', $module);
                        $permissionIds = array_merge($permissionIds, $modulePermissions->pluck('id')->toArray());
                    } else {
                        // Permission spécifique
                        $permission = $allPermissions->where('slug', $permPattern)->first();
                        if ($permission) {
                            $permissionIds[] = $permission->id;
                        }
                    }
                }
                $role->permissions()->sync(array_unique($permissionIds));
            }
        }
    }
}
