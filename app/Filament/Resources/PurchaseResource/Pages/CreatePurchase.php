<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    public function getTitle(): string
    {
        return 'Nouvel achat';
    }

    protected function afterCreate(): void
    {
        // Recalcule totaux HT/TVA/TTC depuis les lignes sauvegardées par le repeater
        $this->record->recalculateTotals();
    }
}
