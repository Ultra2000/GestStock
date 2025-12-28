<?php

namespace App\Filament\Resources\StockTransferResource\Pages;

use App\Filament\Resources\StockTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewStockTransfer extends ViewRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => in_array($this->record->status, ['draft', 'pending'])),
            
            Actions\Action::make('submit')
                ->label('Soumettre')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'pending']);
                    Notification::make()
                        ->title('Transfert soumis pour approbation')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'draft'),

            Actions\Action::make('approve')
                ->label('Approuver')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $this->record->approve();
                        Notification::make()
                            ->title('Transfert approuvé')
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
                ->visible(fn () => $this->record->canBeApproved()),

            Actions\Action::make('ship')
                ->label('Expédier')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Cette action va déduire le stock de l\'entrepôt source.')
                ->action(function () {
                    try {
                        $this->record->ship();
                        Notification::make()
                            ->title('Transfert expédié')
                            ->body('Le stock a été déduit de l\'entrepôt source.')
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
                ->visible(fn () => $this->record->canBeShipped()),

            Actions\Action::make('receive')
                ->label('Réceptionner')
                ->icon('heroicon-o-inbox-arrow-down')
                ->color('success')
                ->url(fn () => $this->getResource()::getUrl('receive', ['record' => $this->record]))
                ->visible(fn () => $this->record->canBeReceived()),

            Actions\Action::make('cancel')
                ->label('Annuler')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Motif d\'annulation')
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->cancel($data['reason']);
                        Notification::make()
                            ->title('Transfert annulé')
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
                ->visible(fn () => $this->record->canBeCancelled()),

            Actions\Action::make('print')
                ->label('Imprimer')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('stock-transfers.print', ['transfer' => $this->record]))
                ->openUrlInNewTab(),
        ];
    }
}
