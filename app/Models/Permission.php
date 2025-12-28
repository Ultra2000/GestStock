<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'module',
        'action',
        'description',
    ];

    /**
     * Les rôles qui ont cette permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions');
    }

    /**
     * Modules disponibles dans l'application
     */
    public static function modules(): array
    {
        return [
            'products' => 'Produits',
            'customers' => 'Clients',
            'suppliers' => 'Fournisseurs',
            'sales' => 'Ventes',
            'purchases' => 'Achats',
            'quotes' => 'Devis',
            'deliveries' => 'Bons de livraison',
            'pos' => 'Caisse (POS)',
            'warehouses' => 'Entrepôts',
            'transfers' => 'Transferts',
            'inventory' => 'Inventaires',
            'hr' => 'Ressources Humaines',
            'employees' => 'Employés',
            'accounting' => 'Comptabilité',
            'banking' => 'Banque',
            'users' => 'Utilisateurs',
            'roles' => 'Rôles & Permissions',
            'reports' => 'Rapports',
            'settings' => 'Paramètres',
        ];
    }

    /**
     * Actions disponibles par module
     */
    public static function actions(): array
    {
        return [
            'view' => 'Voir',
            'create' => 'Créer',
            'update' => 'Modifier',
            'delete' => 'Supprimer',
        ];
    }

    /**
     * Génère toutes les permissions pour un module
     */
    public static function generateForModule(string $module, string $moduleName): array
    {
        $permissions = [];
        foreach (self::actions() as $action => $actionName) {
            $permissions[] = [
                'name' => "{$actionName} {$moduleName}",
                'slug' => "{$module}.{$action}",
                'module' => $module,
                'action' => $action,
                'description' => "Permet de {$actionName} les {$moduleName}",
            ];
        }
        return $permissions;
    }
}
