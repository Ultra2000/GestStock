<?php

namespace App\Models\Traits;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRoles
{
    /**
     * Tous les rôles de l'utilisateur (toutes entreprises confondues)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'model_has_roles')
            ->withPivot('company_id');
    }

    /**
     * Les rôles de l'utilisateur pour une entreprise spécifique
     */
    public function rolesForCompany(?Company $company = null): Collection
    {
        $company = $company ?? Filament::getTenant();
        
        if (!$company) {
            return collect();
        }

        return $this->roles()
            ->wherePivot('company_id', $company->id)
            ->get();
    }

    /**
     * Le rôle principal de l'utilisateur pour l'entreprise courante
     */
    public function currentRole(): ?Role
    {
        return $this->rolesForCompany()->first();
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique dans l'entreprise courante
     */
    public function hasRole(string|Role $role, ?Company $company = null): bool
    {
        $company = $company ?? Filament::getTenant();
        
        if (!$company) {
            return false;
        }

        $roleSlug = $role instanceof Role ? $role->slug : $role;

        return $this->roles()
            ->wherePivot('company_id', $company->id)
            ->where('roles.slug', $roleSlug)
            ->exists();
    }

    /**
     * Vérifie si l'utilisateur a un des rôles donnés
     */
    public function hasAnyRole(array $roles, ?Company $company = null): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role, $company)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur est admin de l'entreprise courante
     */
    public function isAdminOf(?Company $company = null): bool
    {
        return $this->hasRole('admin', $company);
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permission, ?Company $company = null): bool
    {
        // Les admins ont toutes les permissions
        if ($this->isAdminOf($company)) {
            return true;
        }

        $roles = $this->rolesForCompany($company);
        
        foreach ($roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur a toutes les permissions données
     */
    public function hasAllPermissions(array $permissions, ?Company $company = null): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission, $company)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Vérifie si l'utilisateur a au moins une des permissions données
     */
    public function hasAnyPermission(array $permissions, ?Company $company = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission, $company)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Récupère toutes les permissions de l'utilisateur
     */
    public function getAllPermissions(?Company $company = null): Collection
    {
        // Les admins ont toutes les permissions
        if ($this->isAdminOf($company)) {
            return Permission::all();
        }

        $roles = $this->rolesForCompany($company);
        
        return $roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id');
    }

    /**
     * Assigne un rôle à l'utilisateur pour une entreprise
     */
    public function assignRole(Role|string $role, ?Company $company = null): self
    {
        $company = $company ?? Filament::getTenant();
        
        if (!$company) {
            throw new \Exception('Aucune entreprise sélectionnée');
        }

        if (is_string($role)) {
            $role = Role::where('company_id', $company->id)
                ->where('slug', $role)
                ->firstOrFail();
        }

        // Vérifier que le rôle appartient à cette entreprise
        if ($role->company_id !== $company->id) {
            throw new \Exception('Le rôle n\'appartient pas à cette entreprise');
        }

        $this->roles()->attach($role->id, ['company_id' => $company->id]);
        
        return $this;
    }

    /**
     * Retire un rôle à l'utilisateur pour une entreprise
     */
    public function removeRole(Role|string $role, ?Company $company = null): self
    {
        $company = $company ?? Filament::getTenant();
        
        if (!$company) {
            return $this;
        }

        if (is_string($role)) {
            $role = Role::where('company_id', $company->id)
                ->where('slug', $role)
                ->first();
        }

        if ($role) {
            $this->roles()
                ->wherePivot('company_id', $company->id)
                ->detach($role->id);
        }

        return $this;
    }

    /**
     * Synchronise les rôles de l'utilisateur pour une entreprise
     */
    public function syncRoles(array $roleIds, ?Company $company = null): self
    {
        $company = $company ?? Filament::getTenant();
        
        if (!$company) {
            return $this;
        }

        // Retirer tous les rôles actuels pour cette entreprise
        $this->roles()->wherePivot('company_id', $company->id)->detach();

        // Assigner les nouveaux rôles
        foreach ($roleIds as $roleId) {
            $this->roles()->attach($roleId, ['company_id' => $company->id]);
        }

        return $this;
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action sur un module
     */
    public function can($abilities, $arguments = []): bool
    {
        // Si c'est une vérification de permission style "module.action"
        if (is_string($abilities) && str_contains($abilities, '.')) {
            return $this->hasPermission($abilities);
        }

        return parent::can($abilities, $arguments);
    }
}
