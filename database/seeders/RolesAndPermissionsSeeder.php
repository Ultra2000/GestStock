<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Les permissions par défaut de l'application
     */
    protected array $permissions = [
        // Produits
        ['name' => 'Voir les produits', 'slug' => 'products.view', 'description' => 'Peut consulter la liste des produits', 'module' => 'products', 'action' => 'view'],
        ['name' => 'Créer des produits', 'slug' => 'products.create', 'description' => 'Peut ajouter de nouveaux produits', 'module' => 'products', 'action' => 'create'],
        ['name' => 'Modifier des produits', 'slug' => 'products.edit', 'description' => 'Peut modifier les produits existants', 'module' => 'products', 'action' => 'update'],
        ['name' => 'Supprimer des produits', 'slug' => 'products.delete', 'description' => 'Peut supprimer des produits', 'module' => 'products', 'action' => 'delete'],
        ['name' => 'Gérer le stock', 'slug' => 'products.stock', 'description' => 'Peut ajuster les quantités en stock', 'module' => 'products', 'action' => 'manage'],
        
        // Ventes
        ['name' => 'Voir les ventes', 'slug' => 'sales.view', 'description' => 'Peut consulter les ventes', 'module' => 'sales', 'action' => 'view'],
        ['name' => 'Créer des ventes', 'slug' => 'sales.create', 'description' => 'Peut enregistrer des ventes', 'module' => 'sales', 'action' => 'create'],
        ['name' => 'Modifier des ventes', 'slug' => 'sales.edit', 'description' => 'Peut modifier les ventes', 'module' => 'sales', 'action' => 'update'],
        ['name' => 'Supprimer des ventes', 'slug' => 'sales.delete', 'description' => 'Peut annuler/supprimer des ventes', 'module' => 'sales', 'action' => 'delete'],
        
        // Achats
        ['name' => 'Voir les achats', 'slug' => 'purchases.view', 'description' => 'Peut consulter les achats', 'module' => 'purchases', 'action' => 'view'],
        ['name' => 'Créer des achats', 'slug' => 'purchases.create', 'description' => 'Peut enregistrer des achats', 'module' => 'purchases', 'action' => 'create'],
        ['name' => 'Modifier des achats', 'slug' => 'purchases.edit', 'description' => 'Peut modifier les achats', 'module' => 'purchases', 'action' => 'update'],
        ['name' => 'Supprimer des achats', 'slug' => 'purchases.delete', 'description' => 'Peut supprimer des achats', 'module' => 'purchases', 'action' => 'delete'],
        
        // Clients
        ['name' => 'Voir les clients', 'slug' => 'customers.view', 'description' => 'Peut consulter les clients', 'module' => 'customers', 'action' => 'view'],
        ['name' => 'Créer des clients', 'slug' => 'customers.create', 'description' => 'Peut ajouter des clients', 'module' => 'customers', 'action' => 'create'],
        ['name' => 'Modifier des clients', 'slug' => 'customers.edit', 'description' => 'Peut modifier les clients', 'module' => 'customers', 'action' => 'update'],
        ['name' => 'Supprimer des clients', 'slug' => 'customers.delete', 'description' => 'Peut supprimer des clients', 'module' => 'customers', 'action' => 'delete'],
        
        // Fournisseurs
        ['name' => 'Voir les fournisseurs', 'slug' => 'suppliers.view', 'description' => 'Peut consulter les fournisseurs', 'module' => 'suppliers', 'action' => 'view'],
        ['name' => 'Créer des fournisseurs', 'slug' => 'suppliers.create', 'description' => 'Peut ajouter des fournisseurs', 'module' => 'suppliers', 'action' => 'create'],
        ['name' => 'Modifier des fournisseurs', 'slug' => 'suppliers.edit', 'description' => 'Peut modifier les fournisseurs', 'module' => 'suppliers', 'action' => 'update'],
        ['name' => 'Supprimer des fournisseurs', 'slug' => 'suppliers.delete', 'description' => 'Peut supprimer des fournisseurs', 'module' => 'suppliers', 'action' => 'delete'],
        
        // Devis
        ['name' => 'Voir les devis', 'slug' => 'quotes.view', 'description' => 'Peut consulter les devis', 'module' => 'quotes', 'action' => 'view'],
        ['name' => 'Créer des devis', 'slug' => 'quotes.create', 'description' => 'Peut créer des devis', 'module' => 'quotes', 'action' => 'create'],
        ['name' => 'Modifier des devis', 'slug' => 'quotes.edit', 'description' => 'Peut modifier les devis', 'module' => 'quotes', 'action' => 'update'],
        ['name' => 'Supprimer des devis', 'slug' => 'quotes.delete', 'description' => 'Peut supprimer des devis', 'module' => 'quotes', 'action' => 'delete'],
        
        // Bons de livraison
        ['name' => 'Voir les livraisons', 'slug' => 'deliveries.view', 'description' => 'Peut consulter les bons de livraison', 'module' => 'deliveries', 'action' => 'view'],
        ['name' => 'Créer des livraisons', 'slug' => 'deliveries.create', 'description' => 'Peut créer des bons de livraison', 'module' => 'deliveries', 'action' => 'create'],
        ['name' => 'Modifier des livraisons', 'slug' => 'deliveries.edit', 'description' => 'Peut modifier les bons de livraison', 'module' => 'deliveries', 'action' => 'update'],
        
        // Caisse (POS)
        ['name' => 'Accéder à la caisse', 'slug' => 'pos.access', 'description' => 'Peut utiliser le point de vente', 'module' => 'pos', 'action' => 'view'],
        ['name' => 'Ouvrir/fermer la caisse', 'slug' => 'pos.session', 'description' => 'Peut ouvrir et fermer des sessions de caisse', 'module' => 'pos', 'action' => 'manage'],
        ['name' => 'Voir les rapports caisse', 'slug' => 'pos.reports', 'description' => 'Peut consulter les rapports de caisse', 'module' => 'pos', 'action' => 'view'],
        
        // Entrepôts
        ['name' => 'Voir les entrepôts', 'slug' => 'warehouses.view', 'description' => 'Peut consulter les entrepôts', 'module' => 'warehouses', 'action' => 'view'],
        ['name' => 'Gérer les entrepôts', 'slug' => 'warehouses.manage', 'description' => 'Peut créer/modifier les entrepôts', 'module' => 'warehouses', 'action' => 'manage'],
        
        // Transferts
        ['name' => 'Voir les transferts', 'slug' => 'transfers.view', 'description' => 'Peut consulter les transferts', 'module' => 'transfers', 'action' => 'view'],
        ['name' => 'Créer des transferts', 'slug' => 'transfers.create', 'description' => 'Peut créer des transferts inter-entrepôts', 'module' => 'transfers', 'action' => 'create'],
        ['name' => 'Approuver des transferts', 'slug' => 'transfers.approve', 'description' => 'Peut approuver les transferts', 'module' => 'transfers', 'action' => 'update'],
        
        // Inventaires
        ['name' => 'Voir les inventaires', 'slug' => 'inventory.view', 'description' => 'Peut consulter les inventaires', 'module' => 'inventory', 'action' => 'view'],
        ['name' => 'Gérer les inventaires', 'slug' => 'inventory.manage', 'description' => 'Peut créer et valider des inventaires', 'module' => 'inventory', 'action' => 'manage'],
        
        // RH
        ['name' => 'Voir les employés', 'slug' => 'employees.view', 'description' => 'Peut consulter les fiches employés', 'module' => 'employees', 'action' => 'view'],
        ['name' => 'Créer des employés', 'slug' => 'employees.create', 'description' => 'Peut créer des employés', 'module' => 'employees', 'action' => 'create'],
        ['name' => 'Modifier des employés', 'slug' => 'employees.edit', 'description' => 'Peut modifier les employés', 'module' => 'employees', 'action' => 'update'],
        ['name' => 'Supprimer des employés', 'slug' => 'employees.delete', 'description' => 'Peut supprimer des employés', 'module' => 'employees', 'action' => 'delete'],
        
        ['name' => 'Gérer le planning', 'slug' => 'schedule.manage', 'description' => 'Peut gérer le planning', 'module' => 'hr', 'action' => 'manage'],
        ['name' => 'Gérer les congés', 'slug' => 'leaves.manage', 'description' => 'Peut approuver/refuser les congés', 'module' => 'hr', 'action' => 'manage'],
        ['name' => 'Voir le pointage', 'slug' => 'attendance.view', 'description' => 'Peut consulter les pointages', 'module' => 'hr', 'action' => 'view'],
        ['name' => 'Gérer le pointage', 'slug' => 'attendance.manage', 'description' => 'Peut modifier les pointages', 'module' => 'hr', 'action' => 'manage'],
        
        // Comptabilité
        ['name' => 'Voir la comptabilité', 'slug' => 'accounting.view', 'description' => 'Peut consulter les règles et catégories', 'module' => 'accounting', 'action' => 'view'],
        ['name' => 'Gérer la comptabilité', 'slug' => 'accounting.manage', 'description' => 'Peut gérer les règles et catégories', 'module' => 'accounting', 'action' => 'manage'],
        
        // Banque
        ['name' => 'Voir les comptes bancaires', 'slug' => 'banking.view', 'description' => 'Peut consulter les comptes et transactions', 'module' => 'banking', 'action' => 'view'],
        ['name' => 'Gérer les comptes bancaires', 'slug' => 'banking.manage', 'description' => 'Peut gérer les comptes et transactions', 'module' => 'banking', 'action' => 'manage'],
        
        // Administration
        ['name' => 'Gérer les utilisateurs', 'slug' => 'users.manage', 'description' => 'Peut gérer les utilisateurs', 'module' => 'admin', 'action' => 'manage'],
        ['name' => 'Gérer les rôles', 'slug' => 'roles.manage', 'description' => 'Peut gérer les rôles et permissions', 'module' => 'admin', 'action' => 'manage'],
        ['name' => 'Voir les rapports', 'slug' => 'reports.view', 'description' => 'Peut consulter tous les rapports', 'module' => 'admin', 'action' => 'view'],
        ['name' => 'Paramètres entreprise', 'slug' => 'settings.manage', 'description' => 'Peut modifier les paramètres de l\'entreprise', 'module' => 'admin', 'action' => 'manage'],
    ];

    /**
     * Les rôles par défaut avec leurs permissions
     */
    protected array $defaultRoles = [
        'admin' => [
            'name' => 'Administrateur',
            'description' => 'Accès complet à toutes les fonctionnalités',
            'permissions' => '*', // Toutes les permissions
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
                'warehouses.view', 'transfers.view', 'transfers.create', 'transfers.approve',
                'inventory.view', 'inventory.manage',
                'employees.view', 'schedule.manage', 'leaves.manage', 'attendance.view',
                'accounting.view', 'banking.view',
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
                'warehouses.view', 'warehouses.manage',
                'transfers.view', 'transfers.create', 'transfers.approve',
                'inventory.view', 'inventory.manage',
            ],
        ],
        'comptable' => [
            'name' => 'Comptable',
            'description' => 'Accès aux données financières',
            'permissions' => [
                'products.view',
                'sales.view',
                'purchases.view',
                'customers.view',
                'suppliers.view',
                'accounting.view', 'accounting.manage',
                'banking.view', 'banking.manage',
                'reports.view',
            ],
        ],
        'hr' => [
            'name' => 'Responsable RH',
            'description' => 'Gestion des ressources humaines',
            'permissions' => [
                'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
                'schedule.manage',
                'leaves.manage',
                'attendance.view', 'attendance.manage',
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
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Création des permissions...');
        
        // Créer les permissions globales (sans company_id pour les partager)
        foreach ($this->permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'module' => $permission['module'],
                    'action' => $permission['action'],
                ]
            );
        }

        $this->command->info(count($this->permissions) . ' permissions créées.');

        // Créer les rôles pour chaque entreprise existante
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $this->command->warn('Aucune entreprise trouvée. Les rôles seront créés lors de la création d\'entreprise.');
            return;
        }

        foreach ($companies as $company) {
            $this->createRolesForCompany($company);
        }

        $this->command->info('Rôles créés pour ' . $companies->count() . ' entreprise(s).');
    }

    /**
     * Crée les rôles par défaut pour une entreprise
     */
    public function createRolesForCompany(Company $company): void
    {
        $this->command->info("Création des rôles pour {$company->name}...");

        foreach ($this->defaultRoles as $slug => $roleData) {
            $role = Role::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_default' => $roleData['is_default'] ?? false,
                ]
            );

            // Assigner les permissions
            if ($roleData['permissions'] === '*') {
                // Toutes les permissions
                $role->permissions()->sync(Permission::pluck('id'));
            } else {
                $permissionIds = Permission::whereIn('slug', $roleData['permissions'])->pluck('id');
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
