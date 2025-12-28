<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Filament\Facades\Filament;
use Carbon\Carbon;

class AttendanceChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Présences de la semaine';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $companyId = Filament::getTenant()?->id;
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $presentData = [];
        $absentData = [];
        $lateData = [];
        $labels = [];

        for ($date = $startOfWeek->copy(); $date <= $endOfWeek; $date->addDay()) {
            $labels[] = $date->translatedFormat('D');

            $dayAttendances = Attendance::where('company_id', $companyId)
                ->whereDate('date', $date)
                ->get();

            $presentData[] = $dayAttendances->filter(fn ($a) => 
                $a->status === 'present' || ($a->clock_in && !$a->status)
            )->count();

            $absentData[] = $dayAttendances->where('status', 'absent')->count();

            $lateData[] = $dayAttendances->where('status', 'late')->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Présents',
                    'data' => $presentData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Retards',
                    'data' => $lateData,
                    'backgroundColor' => 'rgba(234, 179, 8, 0.8)',
                    'borderColor' => 'rgb(234, 179, 8)',
                ],
                [
                    'label' => 'Absents',
                    'data' => $absentData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_employee') ?? false;
    }
}
