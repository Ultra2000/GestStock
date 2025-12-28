<?php

namespace App\Filament\Superadmin\Widgets;

use App\Models\Company;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Entreprises', Company::count())
                ->description('Entreprises inscrites')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),
            
            Stat::make('Total Utilisateurs', User::count())
                ->description('Utilisateurs globaux')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Revenus Globaux', '0.00 €')
                ->description('Plan Gratuit')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('gray'),
        ];
    }
}
