<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SalesChart;
use App\Filament\Widgets\StockAlert;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Tableau de bord';
    protected static ?string $title = 'Tableau de bord';
    protected static ?int $navigationSort = -2;
    protected static bool $shouldRegisterNavigation = true;

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            \App\Filament\Widgets\UrssafOverviewWidget::class,
            SalesChart::class,
            StockAlert::class,
        ];
    }
} 