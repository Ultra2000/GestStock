<?php

namespace App\Filament\Superadmin\Widgets;

use App\Models\InvoiceConversion;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class InvoiceConverterStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $todayConversions  = InvoiceConversion::withoutGlobalScopes()->whereDate('created_at', today())->count();
        $todayUniqueUsers  = $this->countUniqueToday();

        $monthConversions  = InvoiceConversion::withoutGlobalScopes()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $failureRate       = $this->failureRateToday();

        // Trend 7 jours pour le sparkline
        $trend = $this->dailyTrend(7);

        return [
            Stat::make("Conversions aujourd'hui", $todayConversions)
                ->description($todayUniqueUsers . ' utilisateurs/IPs uniques')
                ->descriptionIcon('heroicon-m-document-arrow-up')
                ->color('info')
                ->chart($trend),

            Stat::make('Conversions ce mois', $monthConversions)
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make("Taux d'échec (24h)", $failureRate . '%')
                ->description($failureRate > 10 ? 'Taux élevé — vérifier les logs' : 'Normal')
                ->descriptionIcon($failureRate > 10 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($failureRate > 10 ? 'danger' : 'success'),
        ];
    }

    private function countUniqueToday(): int
    {
        $byUser = InvoiceConversion::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $byIp = InvoiceConversion::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->whereNull('user_id')
            ->whereNotNull('ip_address')
            ->distinct('ip_address')
            ->count('ip_address');

        return $byUser + $byIp;
    }

    private function failureRateToday(): int
    {
        $total  = InvoiceConversion::withoutGlobalScopes()->where('created_at', '>=', now()->subDay())->count();
        $failed = InvoiceConversion::withoutGlobalScopes()->where('created_at', '>=', now()->subDay())->where('status', 'failed')->count();

        return $total > 0 ? (int) round(($failed / $total) * 100) : 0;
    }

    private function dailyTrend(int $days): array
    {
        $counts = InvoiceConversion::withoutGlobalScopes()
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('cnt', 'day');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $key      = now()->subDays($i)->toDateString();
            $result[] = (int) ($counts[$key] ?? 0);
        }
        return $result;
    }
}
