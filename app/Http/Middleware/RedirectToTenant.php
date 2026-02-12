<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ne rediriger que si on est sur /admin EXACT (sans slug de tenant)
        // et qu'aucun tenant n'est déjà résolu par Filament
        if ($request->is('admin') && !$request->is('admin/*') && auth()->check() && !Filament::getTenant()) {
            $user = auth()->user();

            // Vérifier si l'utilisateur a un dernier tenant en session
            $lastTenantSlug = session('last_tenant_slug');
            if ($lastTenantSlug) {
                $tenant = $user->companies()->where('slug', $lastTenantSlug)->first();
                if ($tenant) {
                    return redirect("/admin/{$tenant->slug}");
                }
            }

            // Sinon, rediriger vers le premier tenant disponible
            $tenant = $user->companies()->first();
            if ($tenant) {
                return redirect("/admin/{$tenant->slug}");
            }
        }

        // Mémoriser le tenant courant en session pour le prochain accès
        if (Filament::getTenant()) {
            session(['last_tenant_slug' => Filament::getTenant()->slug]);
        }

        return $next($request);
    }
}
