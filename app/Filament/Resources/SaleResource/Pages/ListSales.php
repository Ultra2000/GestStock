<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Services\Integration\PpfService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle vente'),
            Actions\Action::make('sync_all_ppf')
                ->label('Synchroniser statuts PPF')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Synchroniser les statuts Chorus Pro')
                ->modalDescription('Actualiser les statuts de toutes les factures en attente auprès de Chorus Pro ?')
                ->modalSubmitActionLabel('Synchroniser')
                ->action(function (PpfService $ppfService) {
                    $companyId = Filament::getTenant()?->id;

                    try {
                        $synced = $ppfService->syncAllPendingInvoices($companyId);

                        Notification::make()
                            ->title('Synchronisation terminée')
                            ->body("{$synced} facture(s) mise(s) à jour depuis Chorus Pro.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur de synchronisation')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
