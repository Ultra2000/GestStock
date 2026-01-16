<?php

namespace App\Filament\Resources\AccountingEntryResource\Widgets;

use App\Models\AccountingEntry;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AccountingBalanceWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = auth()->user()->company_id ?? null;
        
        $totals = AccountingEntry::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $totalDebit = $totals->total_debit ?? 0;
        $totalCredit = $totals->total_credit ?? 0;
        $balance = $totalDebit - $totalCredit;

        $unletteredCount = AccountingEntry::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNull('lettering')
            ->count();

        $thisMonthCount = AccountingEntry::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereMonth('entry_date', now()->month)
            ->whereYear('entry_date', now()->year)
            ->count();

        return [
            Stat::make('Total Débits', number_format($totalDebit, 2, ',', ' ') . ' €')
                ->description('Grand livre')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Total Crédits', number_format($totalCredit, 2, ',', ' ') . ' €')
                ->description('Grand livre')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('success')
                ->chart([3, 5, 4, 3, 6, 5, 3, 7]),

            Stat::make('Balance', number_format(abs($balance), 2, ',', ' ') . ' €')
                ->description($balance == 0 ? 'Équilibrée ✓' : ($balance > 0 ? 'Débit excédentaire' : 'Crédit excédentaire'))
                ->descriptionIcon($balance == 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($balance == 0 ? 'success' : 'warning'),

            Stat::make('Non lettrées', $unletteredCount)
                ->description('Écritures à lettrer')
                ->descriptionIcon('heroicon-m-link-slash')
                ->color($unletteredCount > 0 ? 'warning' : 'success'),
        ];
    }
}
