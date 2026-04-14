<?php

namespace App\Policies;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Le module géré par cette policy
     */
    protected string $module;

    /**
     * Map policy module to feature setting key
     */
    protected function getFeatureKey(): string
    {
        return match($this->module) {
            'warehouses', 'transfers', 'inventory' => 'stock',
            'employees', 'leaves', 'attendance', 'schedule', 'commissions', 'hr' => 'hr',
            'accounting', 'accounting_rules', 'accounting_categories' => 'accounting',
            'banking', 'bank_accounts', 'bank_transactions' => 'banking',
            'pos' => 'pos',
            default => $this->module,
        };
    }

    /**
     * Vérifie avant toutes les autres méthodes
     * Les super admins ont tous les droits (y compris sur modules désactivés)
     * Les admins d'entreprise sont bloqués si le module est désactivé
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        // Vérifier si le module est activé pour l'entreprise courante
        try {
            $tenant = Filament::getTenant();
            if ($tenant && method_exists($tenant, 'isModuleEnabled')) {
                // Si le module est désactivé, on refuse l'accès (même pour les admins d'entreprise)
                if (!$tenant->isModuleEnabled($this->getFeatureKey())) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            // Ignorer si hors contexte Filament
        }

        if ($user->isAdminOf()) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission("{$this->module}.view") || $user->hasPermission("{$this->module}.manage");
    }

    /**
     * Vérifie que le modèle appartient bien au tenant courant.
     * Protège contre les accès cross-tenant.
     */
    protected function belongsToCurrentTenant($model): bool
    {
        if (!property_exists($model, 'company_id') && !isset($model->company_id)) {
            return true; // Pas de notion de tenant sur ce modèle
        }
        try {
            $tenant = Filament::getTenant();
            return $tenant && (int) $model->company_id === (int) $tenant->id;
        } catch (\Exception $e) {
            return true; // Hors contexte Filament (console, etc.)
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $model): bool
    {
        if (!$this->belongsToCurrentTenant($model)) {
            return false;
        }
        return $user->hasPermission("{$this->module}.view") || $user->hasPermission("{$this->module}.manage");
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission("{$this->module}.create") || $user->hasPermission("{$this->module}.manage");
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $model): bool
    {
        if (!$this->belongsToCurrentTenant($model)) {
            return false;
        }
        return $user->hasPermission("{$this->module}.update")
            || $user->hasPermission("{$this->module}.edit")
            || $user->hasPermission("{$this->module}.manage");
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $model): bool
    {
        if (!$this->belongsToCurrentTenant($model)) {
            return false;
        }
        return $user->hasPermission("{$this->module}.delete") || $user->hasPermission("{$this->module}.manage");
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermission("{$this->module}.delete") || $user->hasPermission("{$this->module}.manage");
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, $model): bool
    {
        return $user->hasPermission("{$this->module}.update")
            || $user->hasPermission("{$this->module}.edit")
            || $user->hasPermission("{$this->module}.manage");
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, $model): bool
    {
        return $user->hasPermission("{$this->module}.delete") || $user->hasPermission("{$this->module}.manage");
    }
}
