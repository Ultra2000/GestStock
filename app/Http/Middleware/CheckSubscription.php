<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = Filament::getTenant();

        if (!$company) {
            return $next($request);
        }

        // Si l'accès est actif (trial en cours ou abonnement payant), on laisse passer
        if ($company->hasActiveAccess()) {
            // Avertissement paiement en échec (past_due) — accès maintenu mais bannière
            if ($company->isPaymentFailing()) {
                session()->flash('payment_warning', true);
            }
            return $next($request);
        }

        // Éviter la boucle de redirection sur la page d'expiration elle-même
        if ($request->routeIs('filament.admin.pages.subscription-expired')
            || str_contains($request->path(), 'subscription-expired')
            || str_contains($request->path(), 'logout')
        ) {
            return $next($request);
        }

        return redirect()->route('filament.admin.pages.subscription-expired', [
            'tenant' => $company->slug,
        ]);
    }
}
