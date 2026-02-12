<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'city',
        'zip_code',
        'website',
        'logo_path',
        'tax_number',
        'registration_number',
        'siret',
        'footer_text',
        'settings',
        'currency',
        'country_code',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            // Toujours normaliser le slug (minuscules, tirets, pas d'espaces)
            $baseSlug = Str::slug($company->slug ?: $company->name);
            $slug = $baseSlug;
            $counter = 1;

            while (self::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . ++$counter;
            }

            $company->slug = $slug;
            // Si aucune devise n'est configurée, détecter par IP
            if (empty($company->currency)) {
                $geoService = new \App\Services\GeoLocationService();
                $location = $geoService->getLocationByIp();
                $company->currency = $location['currency'] ?? 'EUR';
                $company->country_code = $location['country_code'];
            }
        });

        // Invalider le cache lors de la mise à jour
        static::updated(function ($company) {
            $company->clearCache();
        });

        static::deleted(function ($company) {
            $company->clearCache();
        });
    }

    /**
     * Récupère les settings avec mise en cache
     */
    public function getCachedSettings(): array
    {
        return Cache::remember(
            "company.{$this->id}.settings",
            now()->addHours(24),
            fn() => $this->settings ?? []
        );
    }

    /**
     * Invalide tous les caches de cette entreprise
     */
    public function clearCache(): void
    {
        Cache::forget("company.{$this->id}.settings");
        Cache::forget("company.{$this->id}.modules");
        Cache::forget("company.{$this->id}.stats");
    }

    /**
     * Récupère les statistiques de base avec mise en cache
     */
    public function getCachedStats(): array
    {
        return Cache::remember(
            "company.{$this->id}.stats",
            now()->addMinutes(15),
            fn() => [
                'products_count' => $this->products()->count(),
                'customers_count' => $this->customers()->count(),
                'suppliers_count' => $this->suppliers()->count(),
                'sales_count' => $this->sales()->where('status', 'completed')->count(),
                'purchases_count' => $this->purchases()->whereIn('status', ['completed', 'received'])->count(),
            ]
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Vérifie si un module est activé pour l'entreprise
     */
    public function isModuleEnabled(string $module): bool
    {
        // Les modules d'administration et de base sont toujours actifs
        // Cela garantit que les utilisateurs, rôles et paramètres restent accessibles
        if (in_array($module, ['users', 'roles', 'settings', 'admin'])) {
            return true;
        }

        // Par défaut, tout est activé si rien n'est configuré
        if (empty($this->settings) || !isset($this->settings['modules'])) {
            return true;
        }

        // Si le module n'est pas présent dans la config, il est actif par défaut
        // (pour éviter de masquer des modules non listés dans le formulaire comme products, sales, etc.)
        return $this->settings['modules'][$module] ?? true;
    }

    /**
     * Récupère toutes les permissions disponibles (globales)
     * Note: Les permissions ne sont pas liées aux companies
     */
    public function getAvailablePermissions(): \Illuminate\Database\Eloquent\Collection
    {
        return Permission::all();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    // Core Business Relations
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    // HR Relations
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function customRoles(): HasMany
    {
        return $this->hasMany(CustomRole::class);
    }

    // Sales/Orders Relations
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function recurringOrders(): HasMany
    {
        return $this->hasMany(RecurringOrder::class);
    }

    // Warehouse Relations
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    // Accounting Relations
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function accountingCategories(): HasMany
    {
        return $this->hasMany(AccountingCategory::class);
    }

    public function accountingRules(): HasMany
    {
        return $this->hasMany(AccountingRule::class);
    }

    public function accountingSettings(): HasMany
    {
        return $this->hasMany(AccountingSetting::class);
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function accountingEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(CompanyIntegration::class);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
