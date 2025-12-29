<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\Purchase;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Carbon\Carbon;

class VatSummaryWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 5;
    
    // Rendre le widget collapsible
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $companyId = Filament::getTenant()?->id;
        $currency = Filament::getTenant()->currency ?? 'EUR';
        
        // Période du mois en cours
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // TVA Collectée (ventes du mois - status completed)
        $vatCollectedThisMonth = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_vat') ?? 0;
        
        // TVA Déductible (achats du mois - status completed)
        $vatDeductibleThisMonth = Purchase::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_vat') ?? 0;
        
        // TVA à payer ou crédit de TVA
        $vatBalance = $vatCollectedThisMonth - $vatDeductibleThisMonth;
        
        // Tendance vs mois précédent
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
        
        $vatCollectedLastMonth = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total_vat') ?? 0;
        
        $vatDeductibleLastMonth = Purchase::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total_vat') ?? 0;
        
        // Calculer les variations
        $collectedChange = $vatCollectedLastMonth > 0 
            ? (($vatCollectedThisMonth - $vatCollectedLastMonth) / $vatCollectedLastMonth) * 100 
            : 0;
        $deductibleChange = $vatDeductibleLastMonth > 0 
            ? (($vatDeductibleThisMonth - $vatDeductibleLastMonth) / $vatDeductibleLastMonth) * 100 
            : 0;

        return [
            Stat::make('TVA Collectée (ventes)', number_format($vatCollectedThisMonth, 2, ',', ' ') . ' ' . $currency)
                ->description($this->formatChange($collectedChange) . ' vs mois dernier')
                ->descriptionIcon($collectedChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($collectedChange >= 0 ? 'success' : 'danger')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => '$dispatch("openVatDetail", {"type": "collected"})',
                ]),
            
            Stat::make('TVA Déductible (achats)', number_format($vatDeductibleThisMonth, 2, ',', ' ') . ' ' . $currency)
                ->description($this->formatChange($deductibleChange) . ' vs mois dernier')
                ->descriptionIcon($deductibleChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('info')
                ->chart([3, 2, 5, 4, 6, 2, 3, 5]),
            
            Stat::make(
                $vatBalance >= 0 ? 'TVA à reverser' : 'Crédit de TVA', 
                number_format(abs($vatBalance), 2, ',', ' ') . ' ' . $currency
            )
                ->description('Solde du mois de ' . Carbon::now()->translatedFormat('F'))
                ->descriptionIcon($vatBalance >= 0 ? 'heroicon-m-banknotes' : 'heroicon-m-receipt-refund')
                ->color($vatBalance >= 0 ? 'warning' : 'success'),
        ];
    }

    protected function formatChange(float $change): string
    {
        $sign = $change >= 0 ? '+' : '';
        return $sign . number_format($change, 1, ',', ' ') . '%';
    }

    public static function canView(): bool
    {
        return Filament::getTenant() !== null;
    }

    public function getHeading(): ?string
    {
        return '📊 Récapitulatif TVA - ' . Carbon::now()->translatedFormat('F Y');
    }
}
