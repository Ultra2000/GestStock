<?php

namespace App\Filament\Widgets;

use App\Models\Quote;
use App\Models\DeliveryNote;
use App\Models\RecurringOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Carbon\Carbon;

class OrdersStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $companyId = Filament::getTenant()?->id;
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Quotes stats
        $draftQuotes = Quote::where('company_id', $companyId)
            ->where('status', 'draft')
            ->count();

        $sentQuotes = Quote::where('company_id', $companyId)
            ->where('status', 'sent')
            ->count();

        $quotesThisMonth = Quote::where('company_id', $companyId)
            ->whereBetween('quote_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $acceptedThisMonth = Quote::where('company_id', $companyId)
            ->where('status', 'accepted')
            ->whereBetween('quote_date', [$startOfMonth, $endOfMonth])
            ->count();

        $sentThisMonth = Quote::where('company_id', $companyId)
            ->whereIn('status', ['sent', 'accepted', 'rejected', 'converted'])
            ->whereBetween('quote_date', [$startOfMonth, $endOfMonth])
            ->count();

        $conversionRate = $sentThisMonth > 0 
            ? round(($acceptedThisMonth / $sentThisMonth) * 100, 1) 
            : 0;

        // Delivery notes stats
        $pendingDeliveries = DeliveryNote::where('company_id', $companyId)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->count();

        $shippedToday = DeliveryNote::where('company_id', $companyId)
            ->whereDate('shipped_at', Carbon::today())
            ->count();

        // Recurring orders
        $activeRecurring = RecurringOrder::where('company_id', $companyId)
            ->where('status', 'active')
            ->count();

        $dueToday = RecurringOrder::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereDate('next_execution', Carbon::today())
            ->count();

        return [
            Stat::make('Devis en cours', $draftQuotes + $sentQuotes)
                ->description($draftQuotes . ' brouillon(s), ' . $sentQuotes . ' envoyé(s)')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('CA Devis (mois)', number_format($quotesThisMonth, 0, ',', ' ') . ' €')
                ->description('Taux conversion: ' . $conversionRate . '%')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color($conversionRate > 30 ? 'success' : 'warning'),

            Stat::make('Livraisons en attente', $pendingDeliveries)
                ->description($shippedToday . ' expédié(s) aujourd\'hui')
                ->descriptionIcon('heroicon-m-truck')
                ->color($pendingDeliveries > 5 ? 'warning' : 'success'),

            Stat::make('Abonnements actifs', $activeRecurring)
                ->description($dueToday > 0 ? $dueToday . ' à traiter aujourd\'hui' : 'Tout est à jour')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($dueToday > 0 ? 'warning' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_quote') ?? true;
    }
}
