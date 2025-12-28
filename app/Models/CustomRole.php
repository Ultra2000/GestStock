<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @deprecated Ce modèle est obsolète. Utilisez App\Models\Role à la place.
 * 
 * Ce modèle faisait partie d'un ancien système de rôles personnalisés avec
 * permissions stockées en JSON. Le nouveau système utilise:
 * - Table `roles` pour les rôles
 * - Table `permissions` pour les permissions
 * - Table pivot `role_has_permissions` pour les associations
 * - Table pivot `model_has_roles` pour assigner les rôles aux utilisateurs
 * 
 * @see \App\Models\Role Le modèle de rôle à utiliser
 * @see \App\Models\Permission Le modèle de permission
 */
class CustomRole extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'permissions',
        'is_default',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_default' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($role) {
            if (!$role->slug) {
                $role->slug = \Illuminate\Support\Str::slug($role->name);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_custom_role');
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function givePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }

    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_filter($permissions, fn($p) => $p !== $permission);
        $this->update(['permissions' => array_values($permissions)]);
    }

    public static function getAvailablePermissions(): array
    {
        return [
            // Produits
            'products.view' => 'Voir les produits',
            'products.create' => 'Créer des produits',
            'products.edit' => 'Modifier les produits',
            'products.delete' => 'Supprimer les produits',
            
            // Ventes
            'sales.view' => 'Voir les ventes',
            'sales.create' => 'Créer des ventes',
            'sales.edit' => 'Modifier les ventes',
            'sales.delete' => 'Supprimer les ventes',
            'sales.discount' => 'Appliquer des remises',
            
            // Achats
            'purchases.view' => 'Voir les achats',
            'purchases.create' => 'Créer des achats',
            'purchases.edit' => 'Modifier les achats',
            'purchases.delete' => 'Supprimer les achats',
            
            // Caisse
            'pos.access' => 'Accéder à la caisse',
            'pos.open_session' => 'Ouvrir une session',
            'pos.close_session' => 'Fermer une session',
            'pos.reports' => 'Voir les rapports de caisse',
            
            // Clients
            'customers.view' => 'Voir les clients',
            'customers.create' => 'Créer des clients',
            'customers.edit' => 'Modifier les clients',
            'customers.delete' => 'Supprimer les clients',
            
            // Fournisseurs
            'suppliers.view' => 'Voir les fournisseurs',
            'suppliers.create' => 'Créer des fournisseurs',
            'suppliers.edit' => 'Modifier les fournisseurs',
            'suppliers.delete' => 'Supprimer les fournisseurs',
            
            // Devis
            'quotes.view' => 'Voir les devis',
            'quotes.create' => 'Créer des devis',
            'quotes.edit' => 'Modifier les devis',
            'quotes.delete' => 'Supprimer les devis',
            'quotes.convert' => 'Convertir en vente',
            
            // Bons de livraison
            'delivery.view' => 'Voir les bons de livraison',
            'delivery.create' => 'Créer des bons de livraison',
            'delivery.edit' => 'Modifier les bons de livraison',
            'delivery.ship' => 'Marquer comme expédié',
            
            // RH
            'employees.view' => 'Voir les employés',
            'employees.create' => 'Créer des employés',
            'employees.edit' => 'Modifier les employés',
            'employees.delete' => 'Supprimer les employés',
            'attendance.view' => 'Voir le pointage',
            'attendance.manage' => 'Gérer le pointage',
            'schedule.view' => 'Voir les plannings',
            'schedule.manage' => 'Gérer les plannings',
            'commissions.view' => 'Voir les commissions',
            'commissions.manage' => 'Gérer les commissions',
            'leave.view' => 'Voir les congés',
            'leave.approve' => 'Approuver les congés',
            
            // Rapports
            'reports.view' => 'Voir les rapports',
            'reports.export' => 'Exporter les rapports',
            
            // Paramètres
            'settings.view' => 'Voir les paramètres',
            'settings.edit' => 'Modifier les paramètres',
            'users.manage' => 'Gérer les utilisateurs',
            'roles.manage' => 'Gérer les rôles',
        ];
    }

    public static function createDefaultRoles(int $companyId): void
    {
        // Admin
        static::create([
            'company_id' => $companyId,
            'name' => 'Administrateur',
            'slug' => 'admin',
            'description' => 'Accès complet à toutes les fonctionnalités',
            'permissions' => array_keys(static::getAvailablePermissions()),
            'is_default' => true,
        ]);

        // Manager
        static::create([
            'company_id' => $companyId,
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Gestion des ventes, achats et employés',
            'permissions' => [
                'products.view', 'products.create', 'products.edit',
                'sales.view', 'sales.create', 'sales.edit', 'sales.discount',
                'purchases.view', 'purchases.create', 'purchases.edit',
                'pos.access', 'pos.open_session', 'pos.close_session', 'pos.reports',
                'customers.view', 'customers.create', 'customers.edit',
                'suppliers.view', 'suppliers.create', 'suppliers.edit',
                'quotes.view', 'quotes.create', 'quotes.edit', 'quotes.convert',
                'delivery.view', 'delivery.create', 'delivery.edit', 'delivery.ship',
                'employees.view', 'attendance.view', 'schedule.view',
                'commissions.view', 'leave.view', 'leave.approve',
                'reports.view', 'reports.export',
            ],
        ]);

        // Vendeur
        static::create([
            'company_id' => $companyId,
            'name' => 'Vendeur',
            'slug' => 'seller',
            'description' => 'Accès à la caisse et aux ventes',
            'permissions' => [
                'products.view',
                'sales.view', 'sales.create',
                'pos.access', 'pos.open_session', 'pos.close_session',
                'customers.view', 'customers.create',
                'quotes.view', 'quotes.create',
                'delivery.view',
            ],
        ]);

        // Magasinier
        static::create([
            'company_id' => $companyId,
            'name' => 'Magasinier',
            'slug' => 'warehouse',
            'description' => 'Gestion des stocks et livraisons',
            'permissions' => [
                'products.view', 'products.edit',
                'purchases.view',
                'delivery.view', 'delivery.create', 'delivery.edit', 'delivery.ship',
                'suppliers.view',
            ],
        ]);
    }
}
