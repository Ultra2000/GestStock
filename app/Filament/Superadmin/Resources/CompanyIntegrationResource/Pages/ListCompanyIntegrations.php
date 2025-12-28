<?php

namespace App\Filament\Superadmin\Resources\CompanyIntegrationResource\Pages;

use App\Filament\Superadmin\Resources\CompanyIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanyIntegrations extends ListRecords
{
    protected static string $resource = CompanyIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
