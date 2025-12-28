<?php

namespace App\Filament\Resources\AccountingRuleResource\Pages;

use App\Filament\Resources\AccountingRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingRules extends ListRecords
{
    protected static string $resource = AccountingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
