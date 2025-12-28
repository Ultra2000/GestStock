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
        // Si on est sur /admin sans tenant et que l'utilisateur est connecté
        if ($request->is('admin') && auth()->check()) {
            $user = auth()->user();
            $tenant = $user->companies()->first();

            if ($tenant) {
                // Rediriger vers le dashboard du tenant
                return redirect("/admin/{$tenant->slug}");
            }
        }

        return $next($request);
    }
}
