<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateRecurringOrder extends CreateRecord
{
    protected static string $resource = RecurringOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()->id;
        $data['created_by'] = auth()->id();
        
        // Calculate total
        $items = $data['items'] ?? [];
        $subtotal = collect($items)->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));
        $taxAmount = $subtotal * (($data['tax_rate'] ?? 20) / 100);
        
        $data['subtotal'] = $subtotal;
        $data['tax_amount'] = $taxAmount;
        $data['total_amount'] = $subtotal + $taxAmount;
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
