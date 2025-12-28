<?php

namespace App\Filament\Resources\WarehouseResource\Widgets;

use App\Models\Warehouse;
use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WarehouseStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = filament()->getTenant()->id;

        $totalWarehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $totalStockValue = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->sum(fn ($w) => $w->getTotalStockValue());

        $lowStockItems = \DB::table('product_warehouse')
            ->join('warehouses', 'warehouses.id', '=', 'product_warehouse.warehouse_id')
            ->where('warehouses.company_id', $companyId)
            ->whereNotNull('product_warehouse.min_quantity')
            ->whereRaw('product_warehouse.quantity <= product_warehouse.min_quantity')
            ->count();

        $pendingTransfers = \DB::table('stock_transfers')
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'approved', 'in_transit'])
            ->count();

        return [
            Stat::make('Entrepôts actifs', $totalWarehouses)
                ->description('Entrepôts et magasins')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary'),

            Stat::make('Valeur du stock', number_format($totalStockValue, 0, ',', ' ') . ' ' . filament()->getTenant()->currency)
                ->description('Tous entrepôts confondus')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Alertes stock', $lowStockItems)
                ->description('Produits en stock bas')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockItems > 0 ? 'danger' : 'success'),

            Stat::make('Transferts en cours', $pendingTransfers)
                ->description('À traiter')
                ->descriptionIcon('heroicon-m-truck')
                ->color($pendingTransfers > 0 ? 'warning' : 'gray'),
        ];
    }
}
