<?php

namespace App\Filament\Resources\AccountingCategoryResource\Pages;

use App\Filament\Resources\AccountingCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountingCategory extends EditRecord
{
    protected static string $resource = AccountingCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
