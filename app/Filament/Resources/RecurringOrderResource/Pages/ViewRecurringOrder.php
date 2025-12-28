<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewRecurringOrder extends ViewRecord
{
    protected static string $resource = RecurringOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('execute')
                ->label('Exécuter maintenant')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $sale = $this->record->generateSale();
                    if ($sale) {
                        \Filament\Notifications\Notification::make()
                            ->title('Vente générée')
                            ->body("Vente #{$sale->reference} créée")
                            ->success()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status === 'active'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informations générales')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Référence'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nom'),
                        Infolists\Components\TextEntry::make('customer.name')
                            ->label('Client'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'active' => 'success',
                                'paused' => 'warning',
                                'cancelled' => 'danger',
                                'completed' => 'gray',
                                default => 'gray',
                            }),
                    ])->columns(4),

                Infolists\Components\Section::make('Planification')
                    ->schema([
                        Infolists\Components\TextEntry::make('frequency')
                            ->label('Fréquence')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'daily' => 'Quotidien',
                                'weekly' => 'Hebdomadaire',
                                'biweekly' => 'Bimensuel',
                                'monthly' => 'Mensuel',
                                'quarterly' => 'Trimestriel',
                                'yearly' => 'Annuel',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('start_date')
                            ->label('Début')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('end_date')
                            ->label('Fin')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('next_execution')
                            ->label('Prochaine exécution')
                            ->date('d/m/Y')
                            ->placeholder('-')
                            ->color(fn ($record) => $record->next_execution?->isPast() ? 'danger' : 'success'),
                        Infolists\Components\TextEntry::make('executions_count')
                            ->label('Exécutions'),
                        Infolists\Components\TextEntry::make('last_execution')
                            ->label('Dernière exécution')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                    ])->columns(3),

                Infolists\Components\Section::make('Montants')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label('Sous-total')
                            ->money('EUR'),
                        Infolists\Components\TextEntry::make('tax_amount')
                            ->label('TVA')
                            ->money('EUR'),
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total TTC')
                            ->money('EUR')
                            ->weight('bold'),
                    ])->columns(3),
            ]);
    }
}
