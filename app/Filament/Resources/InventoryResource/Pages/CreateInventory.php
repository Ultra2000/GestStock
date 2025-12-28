<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = filament()->getTenant()->id;
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        // Initialize items based on type
        if ($this->record->type === 'full') {
            $this->record->initializeItems();
        }

        Notification::make()
            ->title('Inventaire créé')
            ->body($this->record->type === 'full' 
                ? 'Tous les produits de l\'entrepôt ont été ajoutés.' 
                : 'Ajoutez manuellement les produits à inventorier.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
