<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockAlert extends BaseWidget
{
    protected function getHeading(): string
    {
        return 'Produits en alerte de stock';
    }

    protected function getStats(): array
    {
        $lowStockProducts = Product::where('stock', '<', 10)
            ->orderBy('stock')
            ->take(5)
            ->get();

        return $lowStockProducts->map(function ($product) {
            return Stat::make($product->name, $product->stock . ' unités')
                ->description('Stock restant')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');
        })->toArray();
    }
} 