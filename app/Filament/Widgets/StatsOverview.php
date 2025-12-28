<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

use Filament\Facades\Filament;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalSales = Sale::where('status', 'completed')->sum('total');
        $totalProducts = Product::count();
        $lowStockProducts = Product::where('stock', '<', 10)->count();
        $totalCustomers = Customer::count();
        $currency = Filament::getTenant()->currency ?? 'FCFA';

        return [
            Stat::make('Chiffre d\'affaires', number_format($totalSales, 0, ',', ' ') . ' ' . $currency)
                ->description('Total des ventes terminées')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Produits en stock', $totalProducts)
                ->description('Nombre total de produits')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
            Stat::make('Produits en alerte', $lowStockProducts)
                ->description('Stock inférieur à 10 unités')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
            Stat::make('Clients', $totalCustomers)
                ->description('Nombre total de clients')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
} 