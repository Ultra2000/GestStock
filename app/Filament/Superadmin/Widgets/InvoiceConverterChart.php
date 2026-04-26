<?php

namespace App\Filament\Superadmin\Widgets;

use App\Models\InvoiceConversion;
use Filament\Widgets\ChartWidget;

class InvoiceConverterChart extends ChartWidget
{
    protected static ?string $heading  = 'Conversions de factures — 30 derniers jours';
    protected static ?int    $sort     = 5;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels   = [];
        $totals   = [];
        $failures = [];

        for ($i = 29; $i >= 0; $i--) {
            $day      = now()->subDays($i);
            $key      = $day->toDateString();
            $labels[] = $day->format('d/m');

            $rows = InvoiceConversion::withoutGlobalScopes()
                ->whereDate('created_at', $key)
                ->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status');

            $totals[]   = $rows->sum();
            $failures[] = (int) ($rows['failed'] ?? 0);
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Total',
                    'data'            => $totals,
                    'borderColor'     => '#6366f1',
                    'backgroundColor' => 'rgba(99,102,241,0.15)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Échecs',
                    'data'            => $failures,
                    'borderColor'     => '#ef4444',
                    'backgroundColor' => 'rgba(239,68,68,0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => true]],
            'scales'  => ['y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]]],
        ];
    }
}
