<?php

namespace App\Http\Responses;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $panel = Filament::getCurrentPanel();
        $user = Filament::auth()->user();
        
        // Récupérer l'URL intended
        $intended = session()->pull('url.intended', '');
        $panelPath = '/' . ltrim($panel->getPath(), '/');
        
        // Si l'URL intended est juste /admin (la racine du panel sans tenant),
        // on force la redirection vers le tenant par défaut de l'utilisateur
        $intendedPath = $intended ? rtrim(parse_url($intended, PHP_URL_PATH), '/') : '';
        $needsTenantRedirect = empty($intended) || $intendedPath === rtrim($panelPath, '/');
        
        if ($needsTenantRedirect && $panel->hasTenancy() && $user instanceof User) {
            // Obtenir le tenant par défaut de l'utilisateur
            $defaultTenant = $user->getDefaultTenant($panel);
            
            if ($defaultTenant) {
                // Construire l'URL du tenant manuellement
                $tenantSlug = $defaultTenant->getAttribute($panel->getTenantSlugAttribute() ?? 'id');
                $tenantUrl = $panelPath . '/' . $tenantSlug;
                
                return redirect($tenantUrl);
            }
        }

        // Si l'URL intended contenait un vrai path (pas juste /admin), l'utiliser
        if ($intended && $intendedPath !== rtrim($panelPath, '/')) {
            return redirect($intended);
        }

        // Fallback : utiliser Filament::getUrl() ou le path du panel
        return redirect(Filament::getUrl() ?? $panelPath);
    }
}
