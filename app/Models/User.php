<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->companies;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->is_super_admin) {
            return true;
        }
        return $this->companies->contains($tenant);
    }

    /**
     * Vérifie si l'utilisateur est admin de l'entreprise courante
     * Utilise maintenant le système de rôles basé sur les tables
     */
    public function isAdmin(): bool
    {
        return $this->isAdminOf();
    }

    /**
     * Vérifie si l'utilisateur est manager
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager') || $this->hasRole('gestionnaire');
    }

    /**
     * Vérifie si l'utilisateur est un simple utilisateur
     */
    public function isUser(): bool
    {
        return $this->hasRole('user') || $this->hasRole('utilisateur');
    }

    /**
     * Vérifie si l'utilisateur est caissier
     */
    public function isCashier(): bool
    {
        return $this->hasRole('cashier') || $this->hasRole('caissier') || $this->hasRole('vendeur');
    }

    /**
     * Les invitations envoyées par cet utilisateur
     */
    public function sentInvitations()
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    /**
     * Filament: déterminer si l'utilisateur peut accéder à un panel.
     * Utilise maintenant le système de rôles basé sur les tables
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Bloquer l'accès si le compte est désactivé
        if (!$this->is_active) {
            return false;
        }

        if ($panel->getId() === 'superadmin') {
            return $this->is_super_admin;
        }

        if ($panel->getId() === 'admin') {
            // Tout utilisateur associé à au moins une entreprise peut accéder au panel admin
            // Les super admins ont toujours accès
            if ($this->is_super_admin) {
                return true;
            }
            return $this->companies()->exists();
        }
        
        if ($panel->getId() === 'caisse') {
            // Admin ou caissier peuvent accéder à la caisse
            if ($this->is_super_admin) {
                return true;
            }
            return $this->isAdmin() || $this->isCashier();
        }
        
        return false;
    }

    /**
     * Récupère le nom d'affichage du rôle principal pour l'entreprise courante
     */
    public function getRoleDisplayAttribute(): string
    {
        $role = $this->currentRole();
        return $role ? $role->name : 'Aucun rôle';
    }

    /**
     * Vérifie si l'utilisateur peut gérer un module spécifique
     */
    public function canManage(string $module): bool
    {
        return $this->hasPermission("{$module}.manage") || 
               $this->hasPermission("{$module}.edit") || 
               $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut voir un module spécifique
     */
    public function canView(string $module): bool
    {
        return $this->hasPermission("{$module}.view") || 
               $this->hasPermission("{$module}.manage") || 
               $this->isAdmin();
    }
}
