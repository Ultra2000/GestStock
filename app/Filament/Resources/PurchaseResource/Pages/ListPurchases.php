<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListPurchases extends ListRecords
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_pdf')
                ->label('Importer une facture PDF')
                ->icon('heroicon-o-document-arrow-up')
                ->color('info')
                ->url(static::getResource()::getUrl('import-pdf')),

            Actions\CreateAction::make()
                ->label('Nouvel achat'),
        ];
    }
} 