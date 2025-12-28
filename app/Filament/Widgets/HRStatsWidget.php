<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Carbon\Carbon;

class HRStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $companyId = Filament::getTenant()?->id;
        $today = Carbon::today();

        // Active employees
        $activeEmployees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->count();

        // Present today
        $presentToday = Attendance::where('company_id', $companyId)
            ->whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', 'present');
            })
            ->count();

        // Still clocked in
        $stillClockedIn = Attendance::where('company_id', $companyId)
            ->whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->count();

        // Pending leave requests
        $pendingLeaves = LeaveRequest::where('company_id', $companyId)
            ->where('status', 'pending')
            ->count();

        // On leave today
        $onLeaveToday = LeaveRequest::where('company_id', $companyId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->count();

        // Absent today (scheduled but not present)
        $absentToday = Attendance::where('company_id', $companyId)
            ->whereDate('date', $today)
            ->where('status', 'absent')
            ->count();

        return [
            Stat::make('Employés actifs', $activeEmployees)
                ->description('Total employés')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Présents aujourd\'hui', $presentToday . ' / ' . $activeEmployees)
                ->description($stillClockedIn . ' encore présent(s)')
                ->descriptionIcon('heroicon-m-clock')
                ->color('success'),

            Stat::make('En congé', $onLeaveToday)
                ->description($pendingLeaves . ' demande(s) en attente')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($pendingLeaves > 0 ? 'warning' : 'gray'),

            Stat::make('Absents', $absentToday)
                ->description('Absence non justifiée')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($absentToday > 0 ? 'danger' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_employee') ?? false;
    }
}
