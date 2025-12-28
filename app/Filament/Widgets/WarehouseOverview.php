<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse;
use App\Models\StockTransfer;
use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class WarehouseOverview extends BaseWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $companyId = filament()->getTenant()?->id;

        if (!$companyId) {
            return [];
        }

        // Total stock value across all warehouses
        $totalStockValue = DB::table('product_warehouse')
            ->join('warehouses', 'warehouses.id', '=', 'product_warehouse.warehouse_id')
            ->join('products', 'products.id', '=', 'product_warehouse.product_id')
            ->where('warehouses.company_id', $companyId)
            ->selectRaw('SUM(product_warehouse.quantity * COALESCE(products.purchase_price, 0)) as total')
            ->value('total') ?? 0;

        // Low stock alerts
        $lowStockCount = DB::table('product_warehouse')
            ->join('warehouses', 'warehouses.id', '=', 'product_warehouse.warehouse_id')
            ->where('warehouses.company_id', $companyId)
            ->whereNotNull('product_warehouse.min_quantity')
            ->whereRaw('product_warehouse.quantity <= product_warehouse.min_quantity')
            ->count();

        // Pending transfers
        $pendingTransfers = StockTransfer::where('company_id', $companyId)
            ->whereIn('status', ['pending', 'approved', 'in_transit'])
            ->count();

        // Today's movements
        $todayMovements = StockMovement::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->count();

        return [
            Stat::make('Valeur totale du stock', number_format($totalStockValue, 0, ',', ' ') . ' ' . \Filament\Facades\Filament::getTenant()->currency)
                ->description('Tous entrepôts')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getStockValueChart($companyId)),

            Stat::make('Alertes stock', $lowStockCount)
                ->description('Produits en rupture')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Transferts en cours', $pendingTransfers)
                ->description('À traiter')
                ->descriptionIcon('heroicon-m-truck')
                ->color($pendingTransfers > 0 ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.stock-transfers.index', ['tenant' => filament()->getTenant()])),

            Stat::make('Mouvements aujourd\'hui', $todayMovements)
                ->description('Entrées & sorties')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }

    protected function getStockValueChart(int $companyId): array
    {
        // Get stock value by day for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            // Simplified - in production you'd calculate historical values
            $data[] = rand(80, 120);
        }
        return $data;
    }
}
