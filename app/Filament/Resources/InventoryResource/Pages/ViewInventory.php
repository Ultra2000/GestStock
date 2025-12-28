<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewInventory extends ViewRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'draft'),

            Actions\Action::make('start')
                ->label('Démarrer')
                ->icon('heroicon-o-play')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $this->record->start();
                        Notification::make()
                            ->title('Inventaire démarré')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status === 'draft'),

            Actions\Action::make('count')
                ->label('Comptage')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->url(fn () => $this->getResource()::getUrl('count', ['record' => $this->record]))
                ->visible(fn () => $this->record->status === 'in_progress'),

            Actions\Action::make('submit')
                ->label('Soumettre pour validation')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $this->record->submitForValidation();
                        Notification::make()
                            ->title('Inventaire soumis pour validation')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status === 'in_progress'),

            Actions\Action::make('validate')
                ->label('Valider')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Cette action va appliquer les ajustements de stock. Cette opération est irréversible.')
                ->action(function () {
                    try {
                        $this->record->validate();
                        Notification::make()
                            ->title('Inventaire validé')
                            ->body('Les ajustements de stock ont été appliqués.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status === 'pending_validation'),

            Actions\Action::make('print')
                ->label('Imprimer')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('inventories.print', ['inventory' => $this->record]))
                ->openUrlInNewTab(),

            Actions\Action::make('cancel')
                ->label('Annuler')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $this->record->cancel();
                        Notification::make()
                            ->title('Inventaire annulé')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => !in_array($this->record->status, ['validated', 'cancelled'])),
        ];
    }
}
