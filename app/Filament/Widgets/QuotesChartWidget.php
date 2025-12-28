<?php

namespace App\Filament\Widgets;

use App\Models\Quote;
use Filament\Widgets\ChartWidget;
use Filament\Facades\Filament;
use Carbon\Carbon;

class QuotesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Évolution des devis (6 mois)';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $companyId = Filament::getTenant()?->id;

        $sentData = [];
        $acceptedData = [];
        $rejectedData = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');

            $monthQuotes = Quote::where('company_id', $companyId)
                ->whereYear('quote_date', $month->year)
                ->whereMonth('quote_date', $month->month);

            $sentData[] = (clone $monthQuotes)->whereIn('status', ['sent', 'accepted', 'rejected', 'converted', 'expired'])->count();
            $acceptedData[] = (clone $monthQuotes)->whereIn('status', ['accepted', 'converted'])->count();
            $rejectedData[] = (clone $monthQuotes)->where('status', 'rejected')->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Envoyés',
                    'data' => $sentData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Acceptés',
                    'data' => $acceptedData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Refusés',
                    'data' => $rejectedData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
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
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_quote') ?? true;
    }
}
