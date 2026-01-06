<?php

namespace App\Filament\Widgets;

use App\Services\Integration\UrssafService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class UrssafOverviewWidget extends BaseWidget
{
    // Masquer le widget temporairement
    public static function canView(): bool
    {
        return false;
    }

    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $company = Filament::getTenant();
        
        if (!$company) {
            return [];
        }

        $integration = $company->integrations()->where('service_name', 'urssaf')->first();

        if (!$integration || !$integration->is_active) {
            return [
                Stat::make('URSSAF', 'Non connecté')
                    ->description('Configurez l\'intégration URSSAF')
                    ->color('gray'),
            ];
        }

        /** @var UrssafService $service */
        $service = app(UrssafService::class);
        $data = $service->getAccountSituation($integration);

        if (isset($data['error'])) {
             return [
                Stat::make('URSSAF', 'Erreur')
                    ->description('Erreur de connexion')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Solde URSSAF', number_format($data['balance'], 2, ',', ' ') . ' €')
                ->description('Dette actuelle')
                ->color($data['balance'] > 0 ? 'danger' : 'success'),
            
            Stat::make('Prochaine Échéance', number_format($data['next_due_amount'], 2, ',', ' ') . ' €')
                ->description('Le ' . \Carbon\Carbon::parse($data['next_due_date'])->format('d/m/Y'))
                ->color('warning'),

            Stat::make('Conformité', $data['status'] === 'compliant' ? 'À jour' : 'Irrégulier')
                ->description('Attestation de vigilance')
                ->color($data['status'] === 'compliant' ? 'success' : 'danger')
                ->icon($data['status'] === 'compliant' ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle'),
        ];
    }
}