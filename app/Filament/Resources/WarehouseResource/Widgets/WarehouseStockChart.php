<?php

namespace App\Filament\Resources\WarehouseResource\Widgets;

use App\Models\Warehouse;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class WarehouseStockChart extends ChartWidget
{
    protected static ?string $heading = 'Distribution du stock par produit';
    
    public ?Warehouse $record = null;

    protected function getData(): array
    {
        if (!$this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $stocks = DB::table('product_warehouse')
            ->join('products', 'products.id', '=', 'product_warehouse.product_id')
            ->where('product_warehouse.warehouse_id', $this->record->id)
            ->where('product_warehouse.quantity', '>', 0)
            ->orderByDesc('product_warehouse.quantity')
            ->limit(10)
            ->select([
                'products.name',
                'product_warehouse.quantity',
                DB::raw('product_warehouse.quantity * COALESCE(products.purchase_price, 0) as value'),
            ])
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Quantité',
                    'data' => $stocks->pluck('quantity')->toArray(),
                    'backgroundColor' => [
                        '#3B82F6',
                        '#10B981',
                        '#F59E0B',
                        '#EF4444',
                        '#8B5CF6',
                        '#EC4899',
                        '#06B6D4',
                        '#84CC16',
                        '#F97316',
                        '#6366F1',
                    ],
                ],
            ],
            'labels' => $stocks->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
        ];
    }
}
