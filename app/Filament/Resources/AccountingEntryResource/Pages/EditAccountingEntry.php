<?php

namespace App\Filament\Resources\AccountingEntryResource\Pages;

use App\Filament\Resources\AccountingEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAccountingEntry extends EditRecord
{
    protected static string $resource = AccountingEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Seul le lettrage peut être modifié
        // On ne garde que les champs autorisés
        return [
            'lettering' => $data['lettering'] ?? null,
            'lettering_date' => isset($data['lettering']) ? now() : null,
        ];
    }

    protected function beforeSave(): void
    {
        // Vérifier que l'écriture n'est pas verrouillée pour modification
        // (sauf lettrage qui reste possible)
        if ($this->record->is_locked) {
            // Vérifier qu'on ne modifie que le lettrage
            $changes = $this->record->getDirty();
            $allowedChanges = ['lettering', 'lettering_date'];
            
            foreach (array_keys($changes) as $key) {
                if (!in_array($key, $allowedChanges)) {
                    Notification::make()
                        ->danger()
                        ->title('Modification interdite')
                        ->body('Cette écriture est verrouillée. Seul le lettrage peut être modifié.')
                        ->send();
                    
                    $this->halt();
                }
            }
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Lettrage mis à jour')
            ->body('Le code de lettrage a été enregistré.');
    }
}
