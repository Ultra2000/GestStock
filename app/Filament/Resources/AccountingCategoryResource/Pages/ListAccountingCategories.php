<?php

namespace App\Filament\Resources\AccountingCategoryResource\Pages;

use App\Filament\Resources\AccountingCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingCategories extends ListRecords
{
    protected static string $resource = AccountingCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
