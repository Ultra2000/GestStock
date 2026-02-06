<?php

/**
 * Script de déploiement pour la production
 * Exécuter avec: php seed-production.php
 * 
 * Ce script crée automatiquement :
 * - Toutes les permissions de l'application
 * - Les rôles par défaut pour chaque entreprise existante
 * - Associe les utilisateurs à leurs entreprises
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         FRECORP ERP - Script de Déploiement               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// ÉTAPE 1 : CRÉATION DES PERMISSIONS (GLOBALES)
// ============================================================================
echo "📋 ÉTAPE 1: Création des permissions...\n";

$permissions = [
    // Produits
    ['name' => 'Voir les produits', 'slug' => 'products.view', 'module' => 'products', 'action' => 'view'],
    ['name' => 'Créer des produits', 'slug' => 'products.create', 'module' => 'products', 'action' => 'create'],
    ['name' => 'Modifier des produits', 'slug' => 'products.edit', 'module' => 'products', 'action' => 'update'],
    ['name' => 'Supprimer des produits', 'slug' => 'products.delete', 'module' => 'products', 'action' => 'delete'],
    ['name' => 'Gérer le stock', 'slug' => 'products.stock', 'module' => 'products', 'action' => 'manage'],
    
    // Ventes
    ['name' => 'Voir les ventes', 'slug' => 'sales.view', 'module' => 'sales', 'action' => 'view'],
    ['name' => 'Créer des ventes', 'slug' => 'sales.create', 'module' => 'sales', 'action' => 'create'],
    ['name' => 'Modifier des ventes', 'slug' => 'sales.edit', 'module' => 'sales', 'action' => 'update'],
    ['name' => 'Supprimer des ventes', 'slug' => 'sales.delete', 'module' => 'sales', 'action' => 'delete'],
    
    // Achats
    ['name' => 'Voir les achats', 'slug' => 'purchases.view', 'module' => 'purchases', 'action' => 'view'],
    ['name' => 'Créer des achats', 'slug' => 'purchases.create', 'module' => 'purchases', 'action' => 'create'],
    ['name' => 'Modifier des achats', 'slug' => 'purchases.edit', 'module' => 'purchases', 'action' => 'update'],
    ['name' => 'Supprimer des achats', 'slug' => 'purchases.delete', 'module' => 'purchases', 'action' => 'delete'],
    
    // Clients
    ['name' => 'Voir les clients', 'slug' => 'customers.view', 'module' => 'customers', 'action' => 'view'],
    ['name' => 'Créer des clients', 'slug' => 'customers.create', 'module' => 'customers', 'action' => 'create'],
    ['name' => 'Modifier des clients', 'slug' => 'customers.edit', 'module' => 'customers', 'action' => 'update'],
    ['name' => 'Supprimer des clients', 'slug' => 'customers.delete', 'module' => 'customers', 'action' => 'delete'],
    
    // Fournisseurs
    ['name' => 'Voir les fournisseurs', 'slug' => 'suppliers.view', 'module' => 'suppliers', 'action' => 'view'],
    ['name' => 'Créer des fournisseurs', 'slug' => 'suppliers.create', 'module' => 'suppliers', 'action' => 'create'],
    ['name' => 'Modifier des fournisseurs', 'slug' => 'suppliers.edit', 'module' => 'suppliers', 'action' => 'update'],
    ['name' => 'Supprimer des fournisseurs', 'slug' => 'suppliers.delete', 'module' => 'suppliers', 'action' => 'delete'],
    
    // Devis
    ['name' => 'Voir les devis', 'slug' => 'quotes.view', 'module' => 'quotes', 'action' => 'view'],
    ['name' => 'Créer des devis', 'slug' => 'quotes.create', 'module' => 'quotes', 'action' => 'create'],
    ['name' => 'Modifier des devis', 'slug' => 'quotes.edit', 'module' => 'quotes', 'action' => 'update'],
    ['name' => 'Supprimer des devis', 'slug' => 'quotes.delete', 'module' => 'quotes', 'action' => 'delete'],
    
    // Bons de livraison
    ['name' => 'Voir les livraisons', 'slug' => 'deliveries.view', 'module' => 'deliveries', 'action' => 'view'],
    ['name' => 'Créer des livraisons', 'slug' => 'deliveries.create', 'module' => 'deliveries', 'action' => 'create'],
    ['name' => 'Modifier des livraisons', 'slug' => 'deliveries.edit', 'module' => 'deliveries', 'action' => 'update'],
    
    // Caisse (POS)
    ['name' => 'Accéder à la caisse', 'slug' => 'pos.access', 'module' => 'pos', 'action' => 'view'],
    ['name' => 'Ouvrir/fermer la caisse', 'slug' => 'pos.session', 'module' => 'pos', 'action' => 'manage'],
    ['name' => 'Voir les rapports caisse', 'slug' => 'pos.reports', 'module' => 'pos', 'action' => 'view'],
    
    // Entrepôts
    ['name' => 'Voir les entrepôts', 'slug' => 'warehouses.view', 'module' => 'warehouses', 'action' => 'view'],
    ['name' => 'Gérer les entrepôts', 'slug' => 'warehouses.manage', 'module' => 'warehouses', 'action' => 'manage'],
    
    // Transferts
    ['name' => 'Voir les transferts', 'slug' => 'transfers.view', 'module' => 'transfers', 'action' => 'view'],
    ['name' => 'Créer des transferts', 'slug' => 'transfers.create', 'module' => 'transfers', 'action' => 'create'],
    ['name' => 'Approuver des transferts', 'slug' => 'transfers.approve', 'module' => 'transfers', 'action' => 'update'],
    
    // Inventaires
    ['name' => 'Voir les inventaires', 'slug' => 'inventory.view', 'module' => 'inventory', 'action' => 'view'],
    ['name' => 'Gérer les inventaires', 'slug' => 'inventory.manage', 'module' => 'inventory', 'action' => 'manage'],
    
    // RH - Employés
    ['name' => 'Voir les employés', 'slug' => 'employees.view', 'module' => 'employees', 'action' => 'view'],
    ['name' => 'Créer des employés', 'slug' => 'employees.create', 'module' => 'employees', 'action' => 'create'],
    ['name' => 'Modifier des employés', 'slug' => 'employees.edit', 'module' => 'employees', 'action' => 'update'],
    ['name' => 'Supprimer des employés', 'slug' => 'employees.delete', 'module' => 'employees', 'action' => 'delete'],
    
    // RH - Planning et congés
    ['name' => 'Gérer le planning', 'slug' => 'schedule.manage', 'module' => 'hr', 'action' => 'manage'],
    ['name' => 'Gérer les congés', 'slug' => 'leaves.manage', 'module' => 'hr', 'action' => 'manage'],
    ['name' => 'Voir le pointage', 'slug' => 'attendance.view', 'module' => 'hr', 'action' => 'view'],
    ['name' => 'Gérer le pointage', 'slug' => 'attendance.manage', 'module' => 'hr', 'action' => 'manage'],
    
    // Comptabilité
    ['name' => 'Voir la comptabilité', 'slug' => 'accounting.view', 'module' => 'accounting', 'action' => 'view'],
    ['name' => 'Gérer la comptabilité', 'slug' => 'accounting.manage', 'module' => 'accounting', 'action' => 'manage'],
    
    // Banque
    ['name' => 'Voir les comptes bancaires', 'slug' => 'banking.view', 'module' => 'banking', 'action' => 'view'],
    ['name' => 'Gérer les comptes bancaires', 'slug' => 'banking.manage', 'module' => 'banking', 'action' => 'manage'],
    
    // Administration
    ['name' => 'Gérer les utilisateurs', 'slug' => 'users.manage', 'module' => 'admin', 'action' => 'manage'],
    ['name' => 'Gérer les rôles', 'slug' => 'roles.manage', 'module' => 'admin', 'action' => 'manage'],
    ['name' => 'Voir les rapports', 'slug' => 'reports.view', 'module' => 'admin', 'action' => 'view'],
    ['name' => 'Paramètres entreprise', 'slug' => 'settings.manage', 'module' => 'admin', 'action' => 'manage'],
];

$permCount = 0;
foreach ($permissions as $p) {
    Permission::firstOrCreate(['slug' => $p['slug']], $p);
    $permCount++;
}
echo "   ✅ $permCount permissions créées/vérifiées\n\n";

// ============================================================================
// ÉTAPE 2 : CRÉATION DES RÔLES POUR CHAQUE ENTREPRISE
// ============================================================================
echo "👥 ÉTAPE 2: Création des rôles par entreprise...\n";

$roles = [
    [
        'slug' => 'admin',
        'name' => 'Administrateur',
        'description' => 'Accès complet à toutes les fonctionnalités',
        'permissions' => 'all',
        'is_default' => false,
    ],
    [
        'slug' => 'manager',
        'name' => 'Manager',
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
            'warehouses.view',
            'transfers.view', 'transfers.create', 'transfers.approve',
            'inventory.view', 'inventory.manage',
            'employees.view',
            'schedule.manage', 'leaves.manage', 'attendance.view',
            'accounting.view', 'banking.view',
            'reports.view',
        ],
        'is_default' => false,
    ],
    [
        'slug' => 'cashier',
        'name' => 'Caissier',
        'description' => 'Accès à la caisse uniquement',
        'permissions' => [
            'products.view',
            'sales.view', 'sales.create',
            'customers.view', 'customers.create',
            'pos.access', 'pos.session',
        ],
        'is_default' => true,
    ],
    [
        'slug' => 'accountant',
        'name' => 'Comptable',
        'description' => 'Accès aux fonctionnalités comptables',
        'permissions' => [
            'sales.view',
            'purchases.view',
            'accounting.view', 'accounting.manage',
            'banking.view', 'banking.manage',
            'reports.view',
        ],
        'is_default' => false,
    ],
    [
        'slug' => 'warehouse',
        'name' => 'Magasinier',
        'description' => 'Gestion des stocks et entrepôts',
        'permissions' => [
            'products.view', 'products.stock',
            'warehouses.view', 'warehouses.manage',
            'transfers.view', 'transfers.create', 'transfers.approve',
            'inventory.view', 'inventory.manage',
        ],
        'is_default' => false,
    ],
    [
        'slug' => 'hr',
        'name' => 'Responsable RH',
        'description' => 'Gestion des ressources humaines',
        'permissions' => [
            'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
            'schedule.manage',
            'leaves.manage',
            'attendance.view', 'attendance.manage',
        ],
        'is_default' => false,
    ],
];

$companies = Company::all();

if ($companies->isEmpty()) {
    echo "   ⚠️  Aucune entreprise trouvée. Les rôles seront créés lors de la création d'une entreprise.\n\n";
} else {
    foreach ($companies as $company) {
        echo "   📁 Entreprise: {$company->name}\n";
        
        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug'], 'company_id' => $company->id],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_default' => $roleData['is_default'],
                ]
            );
            
            // Attribuer les permissions
            if ($roleData['permissions'] === 'all') {
                $permissionIds = Permission::pluck('id');
            } else {
                $permissionIds = Permission::whereIn('slug', $roleData['permissions'])->pluck('id');
            }
            $role->permissions()->sync($permissionIds);
            
            echo "      ✅ Rôle '{$role->name}' avec " . count($permissionIds) . " permissions\n";
        }
    }
    echo "\n";
}

// ============================================================================
// ÉTAPE 3 : ASSOCIATION DES UTILISATEURS
// ============================================================================
echo "🔗 ÉTAPE 3: Association des utilisateurs aux entreprises...\n";

$users = User::all();
$companies = Company::all();

if ($companies->isEmpty()) {
    echo "   ⚠️  Aucune entreprise. Cette étape sera ignorée.\n\n";
} else {
    foreach ($users as $user) {
        foreach ($companies as $company) {
            // Vérifier si l'utilisateur est déjà associé
            if (!$user->companies()->where('company_id', $company->id)->exists()) {
                $user->companies()->attach($company->id);
                echo "   ✅ {$user->email} → {$company->name}\n";
            }
            
            // Vérifier si l'utilisateur a un rôle dans cette company
            $hasRole = DB::table('model_has_roles')
                ->where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->exists();
            
            if (!$hasRole) {
                $adminRole = Role::where('slug', 'admin')->where('company_id', $company->id)->first();
                if ($adminRole) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $adminRole->id,
                        'user_id' => $user->id,
                        'company_id' => $company->id,
                    ]);
                    echo "      → Rôle Admin attribué\n";
                }
            }
        }
    }
    echo "\n";
}

// ============================================================================
// RÉSUMÉ
// ============================================================================
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                      RÉSUMÉ                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "📊 Permissions : " . Permission::count() . "\n";
echo "👥 Entreprises : " . Company::count() . "\n";
echo "🎭 Rôles total : " . Role::count() . "\n";
echo "👤 Utilisateurs: " . User::count() . "\n";

if ($companies->isNotEmpty()) {
    echo "\n📌 URLs d'accès :\n";
    foreach ($companies as $company) {
        echo "   → https://test-erp.frecorp.fr/admin/{$company->slug}\n";
    }
}

echo "\n✅ Déploiement terminé avec succès!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

