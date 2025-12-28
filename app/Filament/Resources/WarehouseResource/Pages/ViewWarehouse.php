<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWarehouse extends ViewRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('newTransfer')
                ->label('Nouveau transfert')
                ->icon('heroicon-o-truck')
                ->url(fn () => route('filament.admin.resources.stock-transfers.create', [
                    'tenant' => filament()->getTenant(),
                    'source_warehouse_id' => $this->record->id,
                ])),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            WarehouseResource\Widgets\WarehouseStockChart::class,
        ];
    }
}
