<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Nouvelle vente';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Laisser le modèle gérer la génération du numéro de facture
        // $data['invoice_number'] = 'FACT-' . strtoupper(Str::random(8));
        return $data;
    }

    protected function afterCreate(): void
    {
        $sale = $this->record;
        $items = $this->data['items'] ?? [];

        foreach ($items as $item) {
            $sale->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'],
            ]);
        }

        $sale->calculateTotal();
    }
}
