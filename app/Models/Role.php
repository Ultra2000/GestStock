<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Role $role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });

        // Si un rôle devient le rôle par défaut, enlever le flag des autres
        static::saving(function (Role $role) {
            if ($role->is_default && $role->isDirty('is_default')) {
                static::where('company_id', $role->company_id)
                    ->where('id', '!=', $role->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Les permissions de ce rôle
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    /**
     * Les utilisateurs avec ce rôle
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'model_has_roles')
            ->withPivot('company_id');
    }

    /**
     * Vérifie si le rôle a une permission donnée
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('slug', $permission)->exists();
    }

    /**
     * Vérifie si le rôle a toutes les permissions données
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return $this->permissions()->whereIn('slug', $permissions)->count() === count($permissions);
    }

    /**
     * Vérifie si le rôle a au moins une des permissions données
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('slug', $permissions)->exists();
    }

    /**
     * Synchronise les permissions du rôle
     */
    public function syncPermissions(array $permissionIds): self
    {
        $this->permissions()->sync($permissionIds);
        return $this;
    }

    /**
     * Donne une permission au rôle
     */
    public function givePermissionTo(Permission|string $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->firstOrFail();
        }

        $this->permissions()->syncWithoutDetaching([$permission->id]);
        return $this;
    }

    /**
     * Retire une permission du rôle
     */
    public function revokePermissionTo(Permission|string $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
        return $this;
    }

    /**
     * Vérifie si c'est le rôle Admin
     */
    public function isAdmin(): bool
    {
        return $this->slug === 'admin';
    }
}
