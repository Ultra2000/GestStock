<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Inventory;
use App\Models\Quote;
use App\Models\StockMovement;
use App\Observers\AuditObserver;
use App\Observers\CompanyObserver;
use App\Policies\CustomerPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\RolePolicy;
use App\Policies\SalePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ==========================================
        // OPTIMISATIONS DE PERFORMANCE
        // ==========================================
        
        // Désactiver le lazy loading en développement pour détecter les problèmes N+1
        // Model::preventLazyLoading(!app()->isProduction());
        
        // Monitoring des requêtes lentes (> 100ms)
        if (config('app.debug')) {
            DB::listen(function ($query) {
                if ($query->time > 100) { // Plus de 100ms
                    Log::channel('slow-queries')->warning('Slow Query Detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time_ms' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }

        // ==========================================
        // OBSERVERS
        // ==========================================
        
        // Enregistrer les observers
        Company::observe(CompanyObserver::class);
        
        // Audit Trail Observers
        Sale::observe(AuditObserver::class);
        Purchase::observe(AuditObserver::class);
        Product::observe(AuditObserver::class);
        StockMovement::observe(AuditObserver::class);
        Quote::observe(AuditObserver::class);
        Inventory::observe(AuditObserver::class);

        // Observer pour invalider le cache du stock produit
        StockMovement::created(function ($movement) {
            Product::clearStockCacheForProducts([$movement->product_id]);
        });

        // ==========================================
        // POLICIES
        // ==========================================
        
        // Enregistrer les policies
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Quote::class, \App\Policies\QuotePolicy::class);
        Gate::policy(Purchase::class, PurchasePolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
    }
}
