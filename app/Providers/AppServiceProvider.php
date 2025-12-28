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
use Illuminate\Support\Facades\Gate;
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
        // Enregistrer les observers
        Company::observe(CompanyObserver::class);
        
        // Audit Trail Observers
        Sale::observe(AuditObserver::class);
        Purchase::observe(AuditObserver::class);
        Product::observe(AuditObserver::class);
        StockMovement::observe(AuditObserver::class);
        Quote::observe(AuditObserver::class);
        Inventory::observe(AuditObserver::class);

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
