<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

use Filament\Facades\Filament;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Ventes des 7 derniers jours';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Sale::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $currency = Filament::getTenant()->currency ?? 'FCFA';

        return [
            'datasets' => [
                [
                    'label' => "Ventes ($currency)",
                    'data' => $data->pluck('total')->toArray(),
                    'borderColor' => '#10B981',
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
} 