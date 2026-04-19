<?php

namespace App\Filament\Superadmin\Widgets;

use App\Models\Company;
use Filament\Widgets\ChartWidget;

class SubscriptionBreakdownChart extends ChartWidget
{
    protected static ?string $heading   = 'Répartition des abonnements';
    protected static ?int    $sort      = 2;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $active   = Company::where('subscription_status', 'active')->count();
        $trial    = Company::where('subscription_status', 'trial')->count();
        $expired  = Company::where('subscription_status', 'expired')->count();
        $pastDue  = Company::where('subscription_status', 'past_due')->count();
        $none     = Company::whereNull('subscription_status')->count();

        return [
            'datasets' => [[
                'data'            => [$active, $trial, $expired, $pastDue, $none],
                'backgroundColor' => ['#10b981', '#6366f1', '#ef4444', '#f59e0b', '#9ca3af'],
                'hoverOffset'     => 6,
            ]],
            'labels' => ['Actif', 'Évaluation', 'Expiré', 'Paiement échoué', 'Aucun'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
