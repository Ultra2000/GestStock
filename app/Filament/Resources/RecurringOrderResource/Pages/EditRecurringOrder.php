<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecurringOrder extends EditRecord
{
    protected static string $resource = RecurringOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Recalculate totals
        $items = $data['items'] ?? [];
        $subtotal = collect($items)->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));
        $taxAmount = $subtotal * (($data['tax_rate'] ?? 20) / 100);
        
        $data['subtotal'] = $subtotal;
        $data['tax_amount'] = $taxAmount;
        $data['total_amount'] = $subtotal + $taxAmount;
        
        return $data;
    }
}
