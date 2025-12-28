<?php

namespace App\Filament\Resources\EmployeeResource\Widgets;

use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class EmployeeStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Filament::getTenant()?->id;

        $totalEmployees = Employee::where('company_id', $companyId)->where('status', 'active')->count();
        $onLeave = Employee::where('company_id', $companyId)->where('status', 'on_leave')->count();
        $cdiCount = Employee::where('company_id', $companyId)->where('status', 'active')->where('contract_type', 'cdi')->count();
        
        // Employés embauchés ce mois
        $newThisMonth = Employee::where('company_id', $companyId)
            ->whereMonth('hire_date', now()->month)
            ->whereYear('hire_date', now()->year)
            ->count();

        return [
            Stat::make('Employés actifs', $totalEmployees)
                ->description("Dont {$cdiCount} en CDI")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
            Stat::make('En congé', $onLeave)
                ->description('Absents actuellement')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
            Stat::make('Nouveaux ce mois', $newThisMonth)
                ->description('Embauches récentes')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),
        ];
    }
}
