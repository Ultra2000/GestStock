<?php

namespace App\Filament\Superadmin\Widgets;

use App\Models\Company;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Stripe\Stripe;
use Stripe\Invoice;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $active   = Company::where('subscription_status', 'active')->count();
        $trial    = Company::where('subscription_status', 'trial')->count();
        $expired  = Company::whereIn('subscription_status', ['expired', 'past_due'])->count();
        $total    = Company::count();

        $mrr = Cache::remember('superadmin_mrr', 3600, function () {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $invoices = Invoice::all(['status' => 'paid', 'limit' => 100,
                    'created' => ['gte' => now()->startOfMonth()->timestamp]]);
                return collect($invoices->data)->sum(fn ($i) => $i->amount_paid) / 100;
            } catch (\Exception) {
                return 0;
            }
        });

        return [
            Stat::make('Entreprises', $total)
                ->description("{$active} actives · {$trial} en éval · {$expired} expirées")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('Utilisateurs', User::count())
                ->description('Total comptes utilisateurs')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Revenus ce mois', number_format($mrr, 2, ',', ' ') . ' €')
                ->description('Factures Stripe payées en ' . now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),
        ];
    }
}
