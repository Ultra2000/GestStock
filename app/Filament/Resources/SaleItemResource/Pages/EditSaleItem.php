<?php

namespace App\Filament\Resources\SaleItemResource\Pages;

use App\Filament\Resources\SaleItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSaleItem extends EditRecord
{
    protected static string $resource = SaleItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
