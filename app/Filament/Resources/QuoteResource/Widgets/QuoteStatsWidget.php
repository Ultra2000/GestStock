<?php

namespace App\Filament\Resources\QuoteResource\Widgets;

use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class QuoteStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Filament::getTenant()?->id;

        $totalQuotes = Quote::where('company_id', $companyId)->count();
        $pendingAmount = Quote::where('company_id', $companyId)
            ->whereIn('status', ['draft', 'sent'])
            ->sum('total');
        $acceptedThisMonth = Quote::where('company_id', $companyId)
            ->where('status', 'accepted')
            ->whereMonth('accepted_at', now()->month)
            ->sum('total');
        $conversionRate = $totalQuotes > 0
            ? round(Quote::where('company_id', $companyId)->where('status', 'converted')->count() / $totalQuotes * 100, 1)
            : 0;

        return [
            Stat::make('Devis en attente', number_format($pendingAmount, 2, ',', ' ') . ' €')
                ->description('Montant total en cours')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Acceptés ce mois', number_format($acceptedThisMonth, 2, ',', ' ') . ' €')
                ->description('Devis acceptés')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Taux de conversion', $conversionRate . '%')
                ->description('Devis → Ventes')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),
        ];
    }
}
