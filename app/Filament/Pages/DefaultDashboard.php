<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceChartWidget;
use App\Filament\Widgets\HRStatsWidget;
use App\Filament\Widgets\OrdersStatsWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\QuotesChartWidget;
use App\Filament\Widgets\SalesChart;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\StockAlert;
use App\Filament\Widgets\UrssafOverviewWidget;
use App\Filament\Widgets\VatSummaryWidget;
use App\Filament\Widgets\WarehouseOverview;
use App\Filament\Widgets\WarehouseStockSummary;
use Filament\Pages\Dashboard as BaseDashboard;

class DefaultDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Tableau de bord';
    protected static ?string $title = 'Tableau de bord';
    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            // Statistiques principales
            StatsOverview::class,
            QuickActionsWidget::class,
            
            // Ventes & Devis
            SalesChart::class,
            OrdersStatsWidget::class,
            QuotesChartWidget::class,
            
            // Stock
            StockAlert::class,
            WarehouseOverview::class,
            WarehouseStockSummary::class,
            
            // RH
            HRStatsWidget::class,
            AttendanceChartWidget::class,
            
            // Comptabilité
            UrssafOverviewWidget::class,
            VatSummaryWidget::class,
        ];
    }
} 