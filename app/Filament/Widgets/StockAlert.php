<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class StockAlert extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getHeading(): string
    {
        return 'Produits en alerte de stock';
    }

    protected function getStats(): array
    {
        $companyId = Filament::getTenant()?->id;

        $lowStockProducts = Product::where('company_id', $companyId)
            ->where('stock', '<', 10)
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