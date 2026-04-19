<?php

namespace App\Filament\Superadmin\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Stripe\Stripe;
use Stripe\Invoice;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading   = 'Revenus mensuels (12 derniers mois)';
    protected static ?int    $sort      = 3;
    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        $data = Cache::remember('superadmin_revenue_chart', 3600, function () {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));

                $invoices = Invoice::all([
                    'status' => 'paid',
                    'limit'  => 100,
                    'created' => ['gte' => now()->subMonths(11)->startOfMonth()->timestamp],
                ]);

                $byMonth = collect($invoices->data)
                    ->groupBy(fn ($inv) => date('Y-m', $inv->created))
                    ->map(fn ($group) => $group->sum(fn ($i) => $i->amount_paid) / 100);

                return $byMonth->toArray();
            } catch (\Exception) {
                return [];
            }
        });

        // Construire les 12 derniers mois même si revenus = 0
        $labels  = [];
        $amounts = [];
        $annual  = 0;

        for ($i = 11; $i >= 0; $i--) {
            $month     = now()->subMonths($i);
            $key       = $month->format('Y-m');
            $label     = ucfirst($month->translatedFormat('M Y'));
            $amount    = $data[$key] ?? 0;
            $labels[]  = $label;
            $amounts[] = $amount;
            $annual   += $amount;
        }

        return [
            'datasets' => [[
                'label'           => 'Revenus (€)',
                'data'            => $amounts,
                'borderColor'     => '#6366f1',
                'backgroundColor' => 'rgba(99,102,241,0.15)',
                'fill'            => true,
                'tension'         => 0.4,
                'pointRadius'     => 4,
            ]],
            'labels' => $labels,
            'annual' => $annual,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(ctx) { return ctx.parsed.y.toFixed(2) + ' €'; }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(v) { return v + ' €'; }",
                    ],
                ],
            ],
        ];
    }
}
