<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SalesChart;
use App\Filament\Widgets\StockAlert;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Tableau de bord';
    protected static ?string $title = 'Tableau de bord';
    protected static ?int $navigationSort = -2;
    protected static ?string $slug = '';
    protected static bool $shouldRegisterNavigation = true;

    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            SalesChart::class,
            StockAlert::class,
        ];
    }

    public static function getNavigationUrl(): string
    {
        return '/admin';
    }
} 